<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Package;
use App\Models\PackageAddon;
use App\Models\Role;
use App\Models\Transaction;
use App\Models\User;
use App\Services\PrintLayoutService;
use App\Services\RawImageService;
use App\Services\TransactionService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class GaladyaWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_database_seeders_and_rbac_roles_exist(): void
    {
        $this->assertDatabaseHas('roles', ['slug' => 'super_admin']);
        $this->assertDatabaseHas('roles', ['slug' => 'store_manager']);
        $this->assertDatabaseHas('roles', ['slug' => 'photographer']);

        $superAdmin = Role::where('slug', 'super_admin')->first();
        $this->assertTrue($superAdmin->hasPermission('dashboard.view'));
        $this->assertTrue($superAdmin->hasPermission('workflow.start'));
        $this->assertTrue($superAdmin->hasPermission('workflow.print'));
    }

    public function test_customer_computed_attributes(): void
    {
        $customer = Customer::first();
        $this->assertNotNull($customer);
        $this->assertIsInt($customer->total_sessions_count);
        $this->assertIsFloat($customer->lifetime_spend);
        $this->assertStringContainsString('₱', $customer->formatted_lifetime_spend);
    }

    public function test_packages_and_addons_seed_data(): void
    {
        $this->assertDatabaseHas('packages', ['title' => 'Solo Self Shoot', 'base_price' => 299.00]);
        $this->assertDatabaseHas('packages', ['title' => 'Duo Self Shoot', 'base_price' => 399.00]);
        $this->assertDatabaseHas('packages', ['title' => 'Trio Self Shoot', 'base_price' => 499.00]);
        $this->assertDatabaseHas('packages', ['title' => 'Group Self Shoot', 'base_price' => 599.00]);

        $this->assertDatabaseHas('package_addons', ['title' => 'Photographer', 'price' => 300.00]);
        $this->assertDatabaseHas('package_addons', ['title' => 'Extra 15 Minutes', 'price' => 100.00]);
        $this->assertDatabaseHas('package_addons', ['title' => 'Extra Pax', 'price' => 100.00]);
        $this->assertDatabaseHas('package_addons', ['title' => 'Extra Pet', 'price' => 100.00]);
    }

    public function test_raw_image_service_processes_image(): void
    {
        $service = app(RawImageService::class);
        $source = storage_path('app/public/raw_uploads/sample_shot_1.jpg');
        $dest = storage_path('app/public/previews/test_preview_1.jpg');

        $result = $service->processImage($source, $dest);
        $this->assertTrue($result['success']);
        $this->assertFileExists($dest);

        [$w, $h] = getimagesize($dest);
        $this->assertGreaterThan(0, $w);
        $this->assertGreaterThan(0, $h);
    }

    public function test_print_layout_service_generates_4r_dual_photostrip_and_solo_favorite(): void
    {
        $layoutService = app(PrintLayoutService::class);

        // Gather 10 sample photos
        $samplePhotos = [];
        for ($i = 1; $i <= 10; $i++) {
            $samplePhotos[] = storage_path("app/public/raw_uploads/sample_shot_{$i}.jpg");
        }

        // Test Output A: Dual photostrip composite
        $stripOutput = storage_path('app/public/prints/test_dual_strip.jpg');
        $stripRes = $layoutService->generateDualPhotostrip($samplePhotos, $stripOutput, [
            'code' => 'GP-TEST-0001',
            'client' => 'Maria Clara',
            'date' => '03 Nov 2026',
        ]);

        $this->assertTrue($stripRes['success']);
        $this->assertFileExists($stripOutput);

        [$stripW, $stripH] = getimagesize($stripOutput);
        $this->assertEquals(1200, $stripW, 'Photostrip composite width must be exactly 1200 px at 300 DPI');
        $this->assertEquals(1800, $stripH, 'Photostrip composite height must be exactly 1800 px at 300 DPI');

        // Test Output B: Solo Favorite 4R Print
        $soloOutput = storage_path('app/public/prints/test_solo_favorite.jpg');
        $soloRes = $layoutService->generateSoloFavorite4R($samplePhotos[0], $soloOutput, [
            'code' => 'GP-TEST-0001',
            'client' => 'Maria Clara',
            'date' => '03 Nov 2026',
        ]);

        $this->assertTrue($soloRes['success']);
        $this->assertFileExists($soloOutput);

        [$soloW, $soloH] = getimagesize($soloOutput);
        $this->assertTrue(($soloW === 1200 && $soloH === 1800) || ($soloW === 1800 && $soloH === 1200));
    }

    public function test_transaction_service_finalizes_workflow(): void
    {
        $customer = Customer::first();
        $package = Package::first();
        $addon = PackageAddon::first();
        $admin = User::first();

        $transactionService = app(TransactionService::class);

        $photos = [];
        for ($i = 1; $i <= 10; $i++) {
            $photos[] = [
                'original_filename' => "sample_shot_{$i}.jpg",
                'storage_path' => "raw_uploads/sample_shot_{$i}.jpg",
                'preview_path' => "raw_uploads/sample_shot_{$i}.jpg",
                'is_starred' => true,
                'is_favorite' => ($i === 1),
            ];
        }

        $payload = [
            'customer_id' => $customer->id,
            'package_id' => $package->id,
            'addons' => [
                ['id' => $addon->id, 'qty' => 2],
            ],
            'discount_amount' => 50.00,
            'payment_method' => 'gcash',
            'payment_status' => 'completed',
            'notes' => 'Test workflow session booking.',
            'photos' => $photos,
        ];

        $result = $transactionService->finalizeWorkflow($payload, $admin->id);

        $this->assertTrue($result['success']);
        $this->assertNotNull($result['transaction']);
        $this->assertStringStartsWith('GP-', $result['transaction']->transaction_code);
        $this->assertNotNull($result['print_photostrip_url']);
        $this->assertNotNull($result['print_solo_url']);
        $this->assertEquals('GCASH', $result['receipt_data']['payment_method']);

        // Verify grand total formula: package base + (addon price * 2) - discount
        $expectedGrandTotal = ($package->base_price + ($addon->price * 2)) - 50.00;
        $this->assertEquals($expectedGrandTotal, $result['transaction']->grand_total);
    }

    public function test_authenticated_user_can_access_dashboard_and_workflow(): void
    {
        $user = User::first();

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertStatus(200)
            ->assertSee('Galadya Studio Dashboard');

        $this->actingAs($user)
            ->get('/workflow/start')
            ->assertStatus(200)
            ->assertSee('Interactive Photo Selection Workflow');
    }

    public function test_operator_can_delete_single_ingested_photo(): void
    {
        $user = User::first();
        Storage::disk('public')->put('raw_uploads/temp_test.jpg', 'fake-image-content');
        Storage::disk('public')->put('previews/temp_test_preview.jpg', 'fake-preview-content');

        $this->actingAs($user)
            ->postJson(route('workflow.delete.photo'), [
                'storage_path' => 'raw_uploads/temp_test.jpg',
                'preview_path' => 'previews/temp_test_preview.jpg',
            ])
            ->assertStatus(200)
            ->assertJson(['success' => true]);

        Storage::disk('public')->assertMissing('raw_uploads/temp_test.jpg');
        Storage::disk('public')->assertMissing('previews/temp_test_preview.jpg');
    }

    public function test_operator_can_clear_all_ingested_photos(): void
    {
        $user = User::first();
        Storage::disk('public')->put('raw_uploads/temp_1.jpg', 'img-1');
        Storage::disk('public')->put('raw_uploads/temp_2.jpg', 'img-2');

        $this->actingAs($user)
            ->postJson(route('workflow.clear.photos'), [
                'photos' => [
                    ['storage_path' => 'raw_uploads/temp_1.jpg', 'preview_path' => null],
                    ['storage_path' => 'raw_uploads/temp_2.jpg', 'preview_path' => null],
                ],
            ])
            ->assertStatus(200)
            ->assertJson(['success' => true]);

        Storage::disk('public')->assertMissing('raw_uploads/temp_1.jpg');
        Storage::disk('public')->assertMissing('raw_uploads/temp_2.jpg');
    }

    public function test_workflow_view_contains_dual_photostrip_split_layout(): void
    {
        $user = User::first();

        $this->actingAs($user)
            ->get(route('workflow.start'))
            ->assertStatus(200)
            ->assertSee('Dual Photostrip Real-Time Layout')
            ->assertSee('Left Part: Strip 1 (Photos 1 – 5)')
            ->assertSee('Right Part: Strip 2 (Photos 6 – 10)')
            ->assertSee('Clear All Photos');
    }

    public function test_print_layout_partitions_ten_photos_evenly(): void
    {
        $layoutService = app(PrintLayoutService::class);
        $reflection = new \ReflectionClass($layoutService);
        $method = $reflection->getMethod('prepareTenPhotos');
        $method->setAccessible(true);

        $photos = ['p1.jpg', 'p2.jpg', 'p3.jpg', 'p4.jpg', 'p5.jpg', 'p6.jpg', 'p7.jpg', 'p8.jpg', 'p9.jpg', 'p10.jpg'];
        $prepared = $method->invoke($layoutService, $photos);

        $col1 = array_slice($prepared, 0, 5);
        $col2 = array_slice($prepared, 5, 5);

        $this->assertCount(5, $col1);
        $this->assertCount(5, $col2);
        $this->assertEquals(['p1.jpg', 'p2.jpg', 'p3.jpg', 'p4.jpg', 'p5.jpg'], $col1);
        $this->assertEquals(['p6.jpg', 'p7.jpg', 'p8.jpg', 'p9.jpg', 'p10.jpg'], $col2);
    }

    public function test_brand_logo_file_exists_and_is_valid_image(): void
    {
        $logoPath = public_path('images/logo 1.png');
        $this->assertFileExists($logoPath);

        $info = getimagesize($logoPath);
        $this->assertNotFalse($info);
        $this->assertEquals(IMAGETYPE_PNG, $info[2]);
        $this->assertGreaterThan(0, $info[0]);
        $this->assertGreaterThan(0, $info[1]);
    }

    public function test_all_studio_screens_render_successfully_with_studio_branding(): void
    {
        $user = User::first();

        // Guest screen
        $this->get('/login')
            ->assertStatus(200)
            ->assertSee('images/logo 1.png', false);

        // Authenticated screens
        $this->actingAs($user)->get('/dashboard')
            ->assertStatus(200)
            ->assertSee('images/logo 1.png', false);

        $this->actingAs($user)->get('/clients')
            ->assertStatus(200)
            ->assertSee('Client Database & Studio History', false);

        $this->actingAs($user)->get('/packages')
            ->assertStatus(200)
            ->assertSee('Active Self Shoot Packages', false);

        $this->actingAs($user)->get('/transactions')
            ->assertStatus(200)
            ->assertSee('Transaction History & Print Archives', false);

        $this->actingAs($user)->get('/settings')
            ->assertStatus(200)
            ->assertSee('images/logo 1.png', false);

        $this->actingAs($user)->get('/admin/users')
            ->assertStatus(200)
            ->assertSee('Staff Users & Role Permissions', false);
    }

    public function test_workflow_inline_client_creation_api(): void
    {
        $user = User::first();

        $response = $this->actingAs($user)->postJson(route('workflow.api.clients.store'), [
            'first_name' => 'Clara',
            'last_name' => 'delos Santos',
            'contact_number' => '09181234567',
            'email' => 'clara@example.com',
            'notes' => 'Graduation solo shoot',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'first_name' => 'Clara',
                    'last_name' => 'delos Santos',
                    'full_name' => 'Clara delos Santos',
                    'contact_number' => '09181234567',
                ]
            ]);

        $this->assertDatabaseHas('customers', [
            'first_name' => 'Clara',
            'last_name' => 'delos Santos',
            'contact_number' => '09181234567',
        ]);
    }

    public function test_workflow_client_search_finds_database_clients(): void
    {
        $user = User::first();

        // Create a unique client in the database (as if added via Client Database)
        $client = Customer::create([
            'first_name' => 'Zendaya',
            'last_name' => 'Coleman',
            'contact_number' => '09998887777',
            'email' => 'zendaya@example.com',
        ]);

        $response = $this->actingAs($user)->getJson(route('workflow.api.clients', ['q' => 'Zendaya']));

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $data = $response->json('data');
        $this->assertNotEmpty($data);
        $this->assertEquals($client->id, $data[0]['id']);
        $this->assertEquals('Zendaya Coleman', $data[0]['full_name']);

        // Test searching full name with space (e.g. 'Zendaya Coleman')
        $fullNameRes = $this->actingAs($user)->getJson(route('workflow.api.clients', ['q' => 'Zendaya Coleman']));
        $fullNameRes->assertStatus(200);
        $fullNameData = $fullNameRes->json('data');
        $this->assertNotEmpty($fullNameData);
        $this->assertEquals($client->id, $fullNameData[0]['id']);

        // Test searching in Client Database index with full name
        $clientIndexRes = $this->actingAs($user)->get(route('clients.index', ['search' => 'Zendaya Coleman']));
        $clientIndexRes->assertStatus(200);
        $clientIndexRes->assertSee('Zendaya Coleman');
    }

    public function test_workflow_view_has_clean_component_scope_and_interactive_controls(): void
    {
        $user = User::first();

        $response = $this->actingAs($user)->get(route('workflow.start'));
        $response->assertStatus(200);

        $html = $response->getContent();

        // 1. Ensure no nested x-data creates broken child scope on search dropdown
        $this->assertStringNotContainsString('x-data="{ openDropdown: false }"', $html);

        // 2. Ensure modal trigger has stop propagation and openAddClientModal
        $this->assertStringContainsString('openAddClientModal()', $html);

        // 3. Ensure packages use selectPackage and isPackageSelected
        $this->assertStringContainsString('selectPackage(pkg.id)', $html);
        $this->assertStringContainsString('isPackageSelected(pkg.id)', $html);

        // 4. Ensure add-ons use toggleAddon and immutable reactivity
        $this->assertStringContainsString('toggleAddon(addon.id)', $html);
        $this->assertStringContainsString('isAddonSelected(addon.id)', $html);

        // 5. Ensure RFID Discount & Loyalty Card Station exists in the template
        $this->assertStringContainsString('RFID Discount & Loyalty Card', $html);
        $this->assertStringContainsString('scanRfidCard()', $html);
    }

    public function test_client_has_rfid_and_points_attributes(): void
    {
        $customer = Customer::create([
            'first_name' => 'Giselle',
            'last_name' => 'Aenlle',
            'rfid' => 'GP-RFID-554433',
            'points' => 10,
            'contact_number' => '09171112233',
        ]);

        $this->assertEquals('GP-RFID-554433', $customer->rfid);
        $this->assertEquals(10, $customer->points);

        $customer->awardPoints(1);
        $this->assertEquals(11, $customer->points);
    }

    public function test_rfid_scan_endpoint_verifies_card_and_returns_client_data(): void
    {
        $user = User::first();
        $customer = Customer::create([
            'first_name' => 'Catriona',
            'last_name' => 'Gray',
            'rfid' => 'GP-RFID-998877',
            'points' => 4,
            'contact_number' => '09179998888',
        ]);

        // 1. Scan registered RFID
        $response = $this->actingAs($user)->postJson(route('workflow.api.rfid.scan'), [
            'rfid' => 'GP-RFID-998877',
            'customer_id' => $customer->id,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'status' => 'found',
                'data' => [
                    'id' => $customer->id,
                    'full_name' => 'Catriona Gray',
                    'rfid' => 'GP-RFID-998877',
                    'points' => 4,
                    'matches_current_customer' => true,
                ]
            ]);

        // 2. Scan unassigned RFID with an active customer in session
        $unassignedRes = $this->actingAs($user)->postJson(route('workflow.api.rfid.scan'), [
            'rfid' => 'GP-RFID-NEWCARD',
            'customer_id' => $customer->id,
        ]);

        $unassignedRes->assertStatus(200)
            ->assertJson([
                'success' => true,
                'status' => 'unassigned_can_link',
                'data' => [
                    'id' => $customer->id,
                    'rfid' => 'GP-RFID-NEWCARD',
                    'is_new_registration' => true,
                ]
            ]);
    }

    public function test_checkout_with_rfid_scan_accumulates_one_point_and_records_transaction(): void
    {
        $user = User::first();
        $customer = Customer::create([
            'first_name' => 'Pia',
            'last_name' => 'Wurtzbach',
            'rfid' => 'GP-RFID-PIA01',
            'points' => 7,
            'contact_number' => '09177776655',
        ]);

        $package = Package::first();

        $previewRelPath = 'previews/test_rfid_checkout.jpg';
        $fullPath = storage_path("app/public/{$previewRelPath}");
        if (!file_exists(dirname($fullPath))) {
            mkdir(dirname($fullPath), 0755, true);
        }
        $img = imagecreatetruecolor(800, 1200);
        imagejpeg($img, $fullPath);
        imagedestroy($img);

        $payload = [
            'customer_id' => $customer->id,
            'package_id' => $package->id,
            'payment_method' => 'cash',
            'payment_status' => 'completed',
            'discount_amount' => 50.00, // Loyalty discount applied
            'rfid' => 'GP-RFID-PIA01',
            'rfid_scanned' => true,
            'photos' => [
                [
                    'original_filename' => 'test.jpg',
                    'preview_path' => $previewRelPath,
                    'storage_path' => $previewRelPath,
                    'is_starred' => true,
                    'is_favorite' => true,
                ]
            ]
        ];

        $response = $this->actingAs($user)->postJson(route('workflow.finalize'), $payload);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'rfid_scanned' => true,
                    'points_earned' => 1,
                    'total_points' => 8,
                ]
            ]);

        // Verify customer accumulated +1 point
        $customer->refresh();
        $this->assertEquals(8, $customer->points, 'Customer points must increase by exactly 1 on RFID scan');

        // Verify transaction record contains RFID and points
        $this->assertDatabaseHas('transactions', [
            'customer_id' => $customer->id,
            'rfid_code' => 'GP-RFID-PIA01',
            'rfid_scanned' => 1,
            'points_earned' => 1,
        ]);
    }
}

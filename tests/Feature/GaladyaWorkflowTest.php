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
}

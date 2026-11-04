<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Package;
use App\Models\PackageAddon;
use App\Models\Transaction;
use App\Models\TransactionAddon;
use App\Models\User;
use App\Services\BlockchainAuditService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class CustomerAndTransactionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::first();
        $packages = Package::all();
        $addons = PackageAddon::all();

        if ($packages->isEmpty()) {
            return;
        }

        $demoCustomers = [
            [
                'first_name' => 'Bea',
                'last_name' => 'Alonzo',
                'rfid' => 'GP-RFID-849201',
                'points' => 5,
                'email' => 'bea.alonzo@gmail.com',
                'contact_number' => '09178881234',
                'address' => 'Quezon City, Metro Manila',
                'notes' => 'Frequent studio client. Prefers warm studio lighting.',
            ],
            [
                'first_name' => 'Joshua',
                'last_name' => 'Garcia',
                'rfid' => 'GP-RFID-103948',
                'points' => 3,
                'email' => 'joshua.g@yahoo.com',
                'contact_number' => '09187774321',
                'address' => 'Makati City, Metro Manila',
                'notes' => 'Celebrated graduation self-shoot.',
            ],
            [
                'first_name' => 'Kathryn',
                'last_name' => 'Bernardo',
                'rfid' => 'GP-RFID-772910',
                'points' => 8,
                'email' => 'kathryn.b@gmail.com',
                'contact_number' => '09205556789',
                'address' => 'Taguig City, BGC',
                'notes' => 'Brought 1 pet dog (Golden Retriever).',
            ],
            [
                'first_name' => 'Daniel',
                'last_name' => 'Padilla',
                'rfid' => 'GP-RFID-482019',
                'points' => 2,
                'email' => 'daniel.p@outlook.com',
                'contact_number' => '09153332211',
                'address' => 'Pasig City, Metro Manila',
                'notes' => 'Group shoot with 4 college friends.',
            ],
            [
                'first_name' => 'Liza',
                'last_name' => 'Soberano',
                'rfid' => 'GP-RFID-990123',
                'points' => 4,
                'email' => 'liza.s@gmail.com',
                'contact_number' => '09192228899',
                'address' => 'Mandaluyong City',
                'notes' => 'Requested 2 extra 15-minute extensions.',
            ],
        ];

        $paymentMethods = ['cash', 'gcash', 'maya', 'card'];

        foreach ($demoCustomers as $index => $custData) {
            $customer = Customer::create($custData);

            // Generate 1 to 3 past transactions per customer
            $numTransactions = ($index === 0) ? 3 : (($index < 3) ? 2 : 1);

            for ($t = 0; $t < $numTransactions; $t++) {
                $pkg = $packages->random();
                $date = Carbon::now('Asia/Manila')->subDays(($index * 4) + ($t * 7));

                // Decide on some add-ons
                $selectedAddons = $addons->random(rand(0, 2));
                $addonsTotal = 0;
                $addonRecords = [];

                foreach ($selectedAddons as $ad) {
                    $qty = rand(1, 2);
                    $subtotal = $ad->price * $qty;
                    $addonsTotal += $subtotal;
                    $addonRecords[] = [
                        'addon' => $ad,
                        'qty' => $qty,
                        'subtotal' => $subtotal,
                    ];
                }

                $discount = ($t === 0 && $index === 0) ? 50.00 : 0.00;
                $grandTotal = ($pkg->base_price + $addonsTotal) - $discount;

                $code = 'GP-' . $date->format('Ymd') . '-' . str_pad((string) rand(100, 999), 4, '0', STR_PAD_LEFT);

                $transaction = Transaction::create([
                    'transaction_code' => $code,
                    'customer_id' => $customer->id,
                    'package_id' => $pkg->id,
                    'package_price' => $pkg->base_price,
                    'addons_total' => $addonsTotal,
                    'discount_amount' => $discount,
                    'grand_total' => $grandTotal,
                    'payment_method' => $paymentMethods[array_rand($paymentMethods)],
                    'payment_status' => 'completed',
                    'created_by' => $admin?->id,
                    'notes' => 'Seeded sample studio shoot record.',
                    'created_at' => $date,
                    'updated_at' => $date,
                ]);

                foreach ($addonRecords as $item) {
                    TransactionAddon::create([
                        'transaction_id' => $transaction->id,
                        'package_addon_id' => $item['addon']->id,
                        'qty' => $item['qty'],
                        'unit_price' => $item['addon']->price,
                        'subtotal' => $item['subtotal'],
                        'created_at' => $date,
                        'updated_at' => $date,
                    ]);
                }
            }
        }

        // Anchor all seeded transactions onto the immutable cryptographic blockchain ledger
        app(BlockchainAuditService::class)->syncHistoricalTransactions();
    }
}


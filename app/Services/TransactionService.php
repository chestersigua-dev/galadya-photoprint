<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Package;
use App\Models\PackageAddon;
use App\Models\Setting;
use App\Models\Transaction;
use App\Models\TransactionAddon;
use App\Models\TransactionPhoto;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TransactionService
{
    public function __construct(
        protected PrintLayoutService $printLayoutService
    ) {}

    /**
     * Generate unique transaction code: GP-YYYYMMDD-XXXX
     */
    public function generateTransactionCode(): string
    {
        $today = Carbon::now('Asia/Manila')->format('Ymd');
        $prefix = "GP-{$today}-";

        // Find last transaction for today
        $last = Transaction::where('transaction_code', 'LIKE', "{$prefix}%")
            ->orderBy('id', 'desc')
            ->first();

        if ($last) {
            $lastSeq = (int) substr($last->transaction_code, -4);
            $newSeq = str_pad((string) ($lastSeq + 1), 4, '0', STR_PAD_LEFT);
        } else {
            $newSeq = '0001';
        }

        return $prefix . $newSeq;
    }

    /**
     * Process workflow completion and create full transaction with prints.
     */
    public function finalizeWorkflow(array $data, ?int $userId = null): array
    {
        return DB::transaction(function () use ($data, $userId) {
            $customer = Customer::findOrFail($data['customer_id']);
            $package = Package::findOrFail($data['package_id']);

            $packagePrice = (float) $package->base_price;
            $addonsData = $data['addons'] ?? [];
            $discountAmount = (float) ($data['discount_amount'] ?? 0.00);

            // Compute addons subtotal
            $addonsTotal = 0.00;
            $addonItemsToSave = [];

            if (!empty($addonsData)) {
                $addonIds = collect($addonsData)->pluck('id');
                $addonModels = PackageAddon::whereIn('id', $addonIds)->get()->keyBy('id');

                foreach ($addonsData as $item) {
                    $addonId = $item['id'];
                    $qty = max(1, (int) ($item['qty'] ?? 1));

                    if ($addon = $addonModels->get($addonId)) {
                        $unitPrice = (float) $addon->price;
                        $subtotal = $unitPrice * $qty;
                        $addonsTotal += $subtotal;

                        $addonItemsToSave[] = [
                            'package_addon_id' => $addon->id,
                            'qty' => $qty,
                            'unit_price' => $unitPrice,
                            'subtotal' => $subtotal,
                        ];
                    }
                }
            }

            $grandTotal = max(0, ($packagePrice + $addonsTotal) - $discountAmount);
            $code = $this->generateTransactionCode();

            // 1. Create Transaction
            $transaction = Transaction::create([
                'transaction_code' => $code,
                'customer_id' => $customer->id,
                'package_id' => $package->id,
                'package_price' => $packagePrice,
                'addons_total' => $addonsTotal,
                'discount_amount' => $discountAmount,
                'grand_total' => $grandTotal,
                'payment_method' => $data['payment_method'] ?? 'cash',
                'payment_status' => $data['payment_status'] ?? 'completed',
                'created_by' => $userId,
                'notes' => $data['notes'] ?? null,
            ]);

            // 2. Save Addon rows
            foreach ($addonItemsToSave as $row) {
                TransactionAddon::create([
                    'transaction_id' => $transaction->id,
                    'package_addon_id' => $row['package_addon_id'],
                    'qty' => $row['qty'],
                    'unit_price' => $row['unit_price'],
                    'subtotal' => $row['subtotal'],
                ]);
            }

            // 3. Process Photos & Generate 4R Prints
            $photos = $data['photos'] ?? [];
            $starredPhotoPaths = [];
            $favoritePhotoPath = null;
            $savedPhotoModels = [];

            foreach ($photos as $p) {
                $isStarred = !empty($p['is_starred']);
                $isFavorite = !empty($p['is_favorite']);

                $previewRelPath = $p['preview_path'] ?? null;
                $originalRelPath = $p['storage_path'] ?? $previewRelPath;

                $photoModel = TransactionPhoto::create([
                    'transaction_id' => $transaction->id,
                    'original_filename' => $p['original_filename'] ?? basename($originalRelPath),
                    'storage_path' => $originalRelPath,
                    'preview_path' => $previewRelPath,
                    'is_starred' => $isStarred,
                    'is_favorite' => $isFavorite,
                ]);

                $savedPhotoModels[] = $photoModel;

                // Full path to preview/original image
                $absPath = Storage::disk('public')->path($previewRelPath ?? $originalRelPath);

                if ($isStarred && file_exists($absPath)) {
                    $starredPhotoPaths[] = $absPath;
                }
                if ($isFavorite && file_exists($absPath)) {
                    $favoritePhotoPath = $absPath;
                }
            }

            // If no photo was marked favorite, default to first starred or first uploaded
            if (!$favoritePhotoPath && !empty($starredPhotoPaths)) {
                $favoritePhotoPath = $starredPhotoPaths[0];
            }

            // 4. Generate Output A: 4R Dual Photostrip
            $timestamp = time();
            $stripFilename = "prints/{$code}_dual_strip_{$timestamp}.jpg";
            $stripAbsPath = Storage::disk('public')->path($stripFilename);

            $this->printLayoutService->generateDualPhotostrip(
                $starredPhotoPaths,
                $stripAbsPath,
                [
                    'code' => $code,
                    'date' => Carbon::now('Asia/Manila')->format('d M Y'),
                    'client' => $customer->full_name,
                ]
            );

            // 5. Generate Output B: 4R Solo Favorite Print
            $soloFilename = null;
            if ($favoritePhotoPath) {
                $soloFilename = "prints/{$code}_solo_favorite_{$timestamp}.jpg";
                $soloAbsPath = Storage::disk('public')->path($soloFilename);

                $this->printLayoutService->generateSoloFavorite4R(
                    $favoritePhotoPath,
                    $soloAbsPath,
                    [
                        'code' => $code,
                        'date' => Carbon::now('Asia/Manila')->format('d M Y'),
                        'client' => $customer->full_name,
                    ]
                );

                // Update favorite photo record with print ready path
                $favModel = collect($savedPhotoModels)->firstWhere('is_favorite', true);
                if ($favModel) {
                    $favModel->update(['print_ready_path' => $soloFilename]);
                }
            }

            return [
                'success' => true,
                'transaction' => $transaction->load(['customer', 'package', 'addons.packageAddon', 'photos']),
                'print_photostrip_url' => Storage::disk('public')->url($stripFilename),
                'print_photostrip_path' => $stripFilename,
                'print_solo_url' => $soloFilename ? Storage::disk('public')->url($soloFilename) : null,
                'print_solo_path' => $soloFilename,
                'receipt_data' => $this->buildReceiptPayload($transaction),
            ];
        });
    }

    /**
     * Build standard thermal/POS receipt printable payload.
     */
    public function buildReceiptPayload(Transaction $transaction): array
    {
        return [
            'studio_name' => Setting::get('studio_name', 'Galadya Photoprint'),
            'studio_tagline' => Setting::get('studio_tagline', 'Self-Shoot Studio & High-Definition Printing'),
            'studio_contact' => Setting::get('studio_contact', '+63 917 123 4567'),
            'studio_address' => Setting::get('studio_address', 'Quezon City, Philippines'),
            'transaction_code' => $transaction->transaction_code,
            'date_time' => $transaction->formatted_date,
            'customer_name' => $transaction->customer->full_name,
            'contact_number' => $transaction->customer->contact_number,
            'package_title' => $transaction->package->title,
            'package_price' => $transaction->formatted_package_price,
            'addons' => $transaction->addons->map(fn($item) => [
                'title' => $item->packageAddon->title,
                'qty' => $item->qty,
                'unit_price' => '₱' . number_format($item->unit_price, 2),
                'subtotal' => $item->formatted_subtotal,
            ])->toArray(),
            'addons_total' => $transaction->formatted_addons_total,
            'discount_amount' => $transaction->formatted_discount_amount,
            'grand_total' => $transaction->formatted_grand_total,
            'payment_method' => strtoupper($transaction->payment_method),
            'payment_status' => strtoupper($transaction->payment_status),
            'operator' => $transaction->creator?->name ?? 'Staff',
            'footer_text' => Setting::get('receipt_footer_text', 'Thank you for choosing Galadya Studio!'),
        ];
    }
}


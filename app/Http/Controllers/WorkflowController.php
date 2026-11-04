<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCustomerRequest;
use App\Http\Requests\StoreTransactionWorkflowRequest;
use App\Models\Customer;
use App\Models\Package;
use App\Models\PackageAddon;
use App\Models\Setting;
use App\Services\RawImageService;
use App\Services\TransactionService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class WorkflowController extends Controller
{
    public function __construct(
        protected RawImageService $rawImageService,
        protected TransactionService $transactionService
    ) {}

    /**
     * Show the step-by-step Import & Interactive Photo Selection Workflow wizard.
     */
    public function index(): View
    {
        $customers = Customer::orderBy('created_at', 'desc')->take(50)->get();
        $packages = Package::where('is_active', true)->get();
        $addons = PackageAddon::where('is_active', true)->get();

        $maxSelectionLimit = (int) Setting::get('max_selection_limit', 10);

        return view('workflow.index', compact(
            'customers',
            'packages',
            'addons',
            'maxSelectionLimit'
        ));
    }

    /**
     * Ingest and parse an uploaded photo (Camera RAW or standard raster).
     */
    public function upload(Request $request): JsonResponse
    {
        $request->validate([
            'photo' => [
                'required',
                'file',
                'max:102400', // Allow up to 100MB per RAW or high-res file
            ],
        ]);

        try {
            $file = $request->file('photo');
            $originalName = $file->getClientOriginalName();
            $extension = strtolower($file->getClientOriginalExtension());

            $uuid = Str::uuid()->toString();
            $rawFilename = "{$uuid}.{$extension}";
            $previewFilename = "{$uuid}_preview.jpg";

            // Store original file in public disk raw_uploads
            $rawRelPath = "raw_uploads/{$rawFilename}";
            $file->storeAs('raw_uploads', $rawFilename, 'public');

            $sourceFullPath = Storage::disk('public')->path($rawRelPath);
            $previewRelPath = "previews/{$previewFilename}";
            $destFullPath = Storage::disk('public')->path($previewRelPath);

            // Execute zero-lag preview extraction pipeline
            $extraction = $this->rawImageService->processImage(
                $sourceFullPath,
                $destFullPath,
                1600 // Web display dimension
            );

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $uuid,
                    'original_filename' => $originalName,
                    'extension' => $extension,
                    'is_raw' => $this->rawImageService->isRaw($extension),
                    'storage_path' => $rawRelPath,
                    'preview_path' => $previewRelPath,
                    'preview_url' => Storage::disk('public')->url($previewRelPath),
                    'width' => $extraction['width'] ?? 1200,
                    'height' => $extraction['height'] ?? 1800,
                    'method' => $extraction['method'] ?? 'default',
                    'is_starred' => false,
                    'is_favorite' => false,
                ],
            ]);
        } catch (Exception $e) {
            Log::error("Workflow upload failed: {$e->getMessage()}", ['trace' => $e->getTraceAsString()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to process image: ' . $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Finalize the transaction and generate dual-strip + solo 4R print outputs.
     */
    public function finalize(StoreTransactionWorkflowRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();
            $userId = Auth::id();

            $result = $this->transactionService->finalizeWorkflow($validated, $userId);

            return response()->json([
                'success' => true,
                'message' => 'Transaction finalized and 4R print files successfully generated.',
                'data' => $result,
            ]);
        } catch (Exception $e) {
            Log::error("Workflow finalization failed: {$e->getMessage()}", ['trace' => $e->getTraceAsString()]);
            return response()->json([
                'success' => false,
                'message' => 'Finalization error: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Quick search for existing clients (AJAX auto-complete).
     */
    public function apiClients(Request $request): JsonResponse
    {
        $query = trim($request->get('q', ''));

        $clients = Customer::when($query, function ($q) use ($query) {
            $q->where(function ($sub) use ($query) {
                $sub->where('first_name', 'LIKE', "%{$query}%")
                    ->orWhere('last_name', 'LIKE', "%{$query}%")
                    ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$query}%"])
                    ->orWhereRaw("(first_name || ' ' || last_name) LIKE ?", ["%{$query}%"])
                    ->orWhere('contact_number', 'LIKE', "%{$query}%")
                    ->orWhere('email', 'LIKE', "%{$query}%")
                    ->orWhere('rfid', 'LIKE', "%{$query}%");

                $words = array_filter(explode(' ', $query));
                if (count($words) > 1) {
                    $sub->orWhere(function ($multi) use ($words) {
                        foreach ($words as $word) {
                            $multi->where(function ($w) use ($word) {
                                $w->where('first_name', 'LIKE', "%{$word}%")
                                  ->orWhere('last_name', 'LIKE', "%{$word}%");
                            });
                        }
                    });
                }
            });
        })
        ->latest()
        ->take(25)
        ->get();

        return response()->json([
            'success' => true,
            'data' => $clients,
        ]);
    }

    /**
     * Inline "Add New Client" popup form.
     */
    public function apiStoreClient(StoreCustomerRequest $request): JsonResponse
    {
        try {
            $customer = Customer::create($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Customer registered successfully.',
                'data' => $customer,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to register customer: ' . $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Process an RFID scan during checkout or client lookup.
     */
    public function apiRfidScan(Request $request): JsonResponse
    {
        $rfid = trim((string) $request->input('rfid', ''));
        $currentCustomerId = $request->input('customer_id');

        if (empty($rfid)) {
            return response()->json([
                'success' => false,
                'message' => 'Please scan or enter a valid RFID card UID.',
            ], 422);
        }

        // Look up customer by RFID
        $customer = Customer::where('rfid', $rfid)->first();

        if ($customer) {
            $isSameCustomer = $currentCustomerId && ((int) $customer->id === (int) $currentCustomerId);

            return response()->json([
                'success' => true,
                'status' => 'found',
                'message' => "Loyalty Card verified for {$customer->full_name}",
                'data' => [
                    'id' => $customer->id,
                    'full_name' => $customer->full_name,
                    'first_name' => $customer->first_name,
                    'last_name' => $customer->last_name,
                    'contact_number' => $customer->contact_number,
                    'email' => $customer->email,
                    'rfid' => $customer->rfid,
                    'points' => (int) ($customer->points ?? 0),
                    'matches_current_customer' => $isSameCustomer,
                ],
            ]);
        }

        // Card is not yet assigned to any customer
        // If an active client is selected in the workflow, allow pairing this card to them
        if ($currentCustomerId && ($activeCustomer = Customer::find($currentCustomerId))) {
            return response()->json([
                'success' => true,
                'status' => 'unassigned_can_link',
                'message' => "Unassigned RFID Card ({$rfid}). Will be paired to {$activeCustomer->full_name} upon checkout.",
                'data' => [
                    'id' => $activeCustomer->id,
                    'full_name' => $activeCustomer->full_name,
                    'first_name' => $activeCustomer->first_name,
                    'last_name' => $activeCustomer->last_name,
                    'contact_number' => $activeCustomer->contact_number,
                    'email' => $activeCustomer->email,
                    'rfid' => $rfid,
                    'points' => (int) ($activeCustomer->points ?? 0),
                    'matches_current_customer' => true,
                    'is_new_registration' => true,
                ],
            ]);
        }

        return response()->json([
            'success' => false,
            'status' => 'not_found',
            'message' => "Unregistered RFID card ({$rfid}). Select a client or register a new client to link this card.",
            'rfid' => $rfid,
        ], 404);
    }

    /**
     * Remove a single ingested photo from storage.
     */
    public function deletePhoto(Request $request): JsonResponse
    {
        $storagePath = $request->input('storage_path');
        $previewPath = $request->input('preview_path');

        if ($storagePath && Storage::disk('public')->exists($storagePath)) {
            Storage::disk('public')->delete($storagePath);
        }
        if ($previewPath && Storage::disk('public')->exists($previewPath)) {
            Storage::disk('public')->delete($previewPath);
        }

        return response()->json(['success' => true]);
    }

    /**
     * Clear all ingested photos from storage.
     */
    public function clearPhotos(Request $request): JsonResponse
    {
        $photos = $request->input('photos', []);
        foreach ($photos as $p) {
            if (!empty($p['storage_path']) && Storage::disk('public')->exists($p['storage_path'])) {
                Storage::disk('public')->delete($p['storage_path']);
            }
            if (!empty($p['preview_path']) && Storage::disk('public')->exists($p['preview_path'])) {
                Storage::disk('public')->delete($p['preview_path']);
            }
        }

        return response()->json(['success' => true]);
    }
}


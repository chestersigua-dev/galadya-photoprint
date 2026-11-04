<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Services\BlockchainAuditService;
use App\Services\TransactionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class TransactionController extends Controller
{
    public function __construct(
        protected TransactionService $transactionService,
        protected BlockchainAuditService $blockchainAuditService
    ) {}

    /**
     * Display all transactions.
     */
    public function index(Request $request): View
    {
        $search = trim($request->get('search', ''));
        $status = $request->get('status', '');
        $dateFrom = $request->get('date_from', '');
        $dateTo = $request->get('date_to', '');

        $transactions = Transaction::with(['customer', 'package', 'addons.packageAddon', 'creator'])
            ->when($search, function ($q) use ($search) {
                $q->where('transaction_code', 'LIKE', "%{$search}%")
                  ->orWhere('rfid_code', 'LIKE', "%{$search}%")
                  ->orWhereHas('customer', function ($cq) use ($search) {
                      $cq->where('first_name', 'LIKE', "%{$search}%")
                         ->orWhere('last_name', 'LIKE', "%{$search}%")
                         ->orWhere('contact_number', 'LIKE', "%{$search}%")
                         ->orWhere('rfid', 'LIKE', "%{$search}%");
                  });
            })
            ->when($status, fn($q) => $q->where('payment_status', $status))
            ->when($dateFrom, fn($q) => $q->whereDate('created_at', '>=', $dateFrom))
            ->when($dateTo, fn($q) => $q->whereDate('created_at', '<=', $dateTo))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('transactions.index', compact('transactions', 'search', 'status', 'dateFrom', 'dateTo'));
    }

    /**
     * Show single transaction detail or thermal receipt payload.
     */
    public function show(Transaction $transaction): JsonResponse
    {
        $transaction->load(['customer', 'package', 'addons.packageAddon', 'photos', 'creator', 'blockchainLogs']);

        // Record immutable access log
        $this->blockchainAuditService->recordAccessBlock(
            $transaction,
            'sale.accessed',
            'Staff viewed transaction receipt and details'
        );

        return response()->json([
            'success' => true,
            'transaction' => $transaction,
            'receipt_data' => $this->transactionService->buildReceiptPayload($transaction),
        ]);
    }

    /**
     * Print thermal receipt view directly.
     */
    public function receipt(Transaction $transaction): View
    {
        $transaction->load(['customer', 'package', 'addons.packageAddon', 'creator']);

        // Record immutable receipt printing event
        $this->blockchainAuditService->recordAccessBlock(
            $transaction,
            'sale.receipt_printed',
            'Printed or reprinted POS thermal receipt'
        );

        $receipt = $this->transactionService->buildReceiptPayload($transaction);

        return view('transactions.receipt', compact('transaction', 'receipt'));
    }

    /**
     * Download generated 4R print file.
     */
    public function downloadPrint(string $filename): BinaryFileResponse
    {
        $path = Storage::disk('public')->path("prints/{$filename}");
        if (!file_exists($path)) {
            abort(404, 'Print file not found.');
        }

        return response()->download($path, $filename, [
            'Content-Type' => 'image/jpeg',
        ]);
    }
}


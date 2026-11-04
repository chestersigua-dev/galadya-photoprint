<?php

namespace App\Http\Controllers;

use App\Models\SalesBlockchainLog;
use App\Services\BlockchainAuditService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function __construct(
        protected BlockchainAuditService $blockchainAuditService
    ) {}

    /**
     * Display the Sales Blockchain Explorer & Access Audit Trail.
     */
    public function index(Request $request): View
    {
        $search = trim($request->get('search', ''));
        $eventType = $request->get('event_type', '');
        $dateFrom = $request->get('date_from', '');
        $dateTo = $request->get('date_to', '');

        // Ensure genesis block is initialized
        $this->blockchainAuditService->ensureGenesisBlock();

        $blocks = SalesBlockchainLog::with(['transaction.customer', 'operator'])
            ->when($search, function ($q) use ($search) {
                $q->where('transaction_code', 'LIKE', "%{$search}%")
                  ->orWhere('hash', 'LIKE', "%{$search}%")
                  ->orWhere('previous_hash', 'LIKE', "%{$search}%")
                  ->orWhere('operator_name', 'LIKE', "%{$search}%")
                  ->orWhere('ip_address', 'LIKE', "%{$search}%")
                  ->orWhereHas('transaction.customer', function ($cq) use ($search) {
                      $cq->where('first_name', 'LIKE', "%{$search}%")
                         ->orWhere('last_name', 'LIKE', "%{$search}%");
                  });
            })
            ->when($eventType, fn($q) => $q->where('event_type', $eventType))
            ->when($dateFrom, fn($q) => $q->whereDate('created_at', '>=', $dateFrom))
            ->when($dateTo, fn($q) => $q->whereDate('created_at', '<=', $dateTo))
            ->orderBy('block_index', 'desc')
            ->paginate(20)
            ->withQueryString();

        $stats = $this->blockchainAuditService->getLedgerStats();

        return view('admin.audit.index', compact('blocks', 'stats', 'search', 'eventType', 'dateFrom', 'dateTo'));
    }

    /**
     * Run cryptographic chain integrity verification.
     */
    public function verify(Request $request): JsonResponse|RedirectResponse
    {
        $result = $this->blockchainAuditService->verifyChainIntegrity();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => $result['is_valid'],
                'result' => $result,
            ]);
        }

        if ($result['is_valid']) {
            return redirect()->route('admin.audit.index')
                ->with('success', "Blockchain Ledger Verified! {$result['total_blocks']} blocks are 100% intact and tamper-free.");
        }

        return redirect()->route('admin.audit.index')
            ->with('error', "SECURITY ALERT: {$result['message']}");
    }

    /**
     * Synchronize historical transactions.
     */
    public function sync(Request $request): JsonResponse|RedirectResponse
    {
        $synced = $this->blockchainAuditService->syncHistoricalTransactions();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'synced_count' => $synced,
                'message' => "Synchronized {$synced} transactions into blockchain ledger.",
            ]);
        }

        return redirect()->route('admin.audit.index')
            ->with('success', "Successfully anchored {$synced} historical sales blocks into the ledger.");
    }

    /**
     * Return detailed cryptographic inspection data for a single block.
     */
    public function show(SalesBlockchainLog $block): JsonResponse
    {
        $block->load(['transaction.customer', 'transaction.package', 'operator']);

        $canonicalPayload = BlockchainAuditService::canonicalJson($block->payload ?? []);
        $recomputedHash = $block->calculateMathematicalHash();
        $isSelfValid = hash_equals($block->hash, $recomputedHash);

        return response()->json([
            'success' => true,
            'block' => $block,
            'canonical_payload' => $canonicalPayload,
            'recomputed_hash' => $recomputedHash,
            'is_valid' => $isSelfValid,
            'formatted_date' => $block->formatted_date,
        ]);
    }
}


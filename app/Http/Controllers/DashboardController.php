<?php

namespace App\Http\Controllers;

use App\Models\Package;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Display the main studio dashboard.
     */
    public function index(Request $request): View
    {
        $nowPST = Carbon::now('Asia/Manila');
        $todayStart = $nowPST->copy()->startOfDay();
        $todayEnd = $nowPST->copy()->endOfDay();
        $monthStart = $nowPST->copy()->startOfMonth();

        // Daily revenue and completed sessions
        $todayGrossRevenue = (float) Transaction::where('payment_status', 'completed')
            ->whereBetween('created_at', [$todayStart, $todayEnd])
            ->sum('grand_total');

        $sessionsCompletedToday = Transaction::where('payment_status', 'completed')
            ->whereBetween('created_at', [$todayStart, $todayEnd])
            ->count();

        // Month-to-date active unique clients
        $activeClientsMTD = Transaction::whereBetween('created_at', [$monthStart, $todayEnd])
            ->distinct('customer_id')
            ->count('customer_id');

        // Most popular package calculation
        $popularPackageId = Transaction::select('package_id', DB::raw('count(*) as total'))
            ->groupBy('package_id')
            ->orderByDesc('total')
            ->value('package_id');

        $popularPackage = $popularPackageId ? Package::find($popularPackageId) : Package::first();
        $popularPackageCount = $popularPackageId ? Transaction::where('package_id', $popularPackageId)->count() : 0;

        // Recent transaction history
        $recentTransactions = Transaction::with(['customer', 'package', 'creator'])
            ->latest()
            ->take(10)
            ->get();

        // Weekly revenue trend (last 7 days)
        $weeklyTrend = [];
        for ($i = 6; $i >= 0; $i--) {
            $day = $nowPST->copy()->subDays($i);
            $dayRevenue = Transaction::where('payment_status', 'completed')
                ->whereDate('created_at', $day->format('Y-m-d'))
                ->sum('grand_total');

            $weeklyTrend[] = [
                'day' => $day->format('D'),
                'date' => $day->format('M d'),
                'revenue' => (float) $dayRevenue,
            ];
        }

        return view('dashboard.index', compact(
            'todayGrossRevenue',
            'sessionsCompletedToday',
            'activeClientsMTD',
            'popularPackage',
            'popularPackageCount',
            'recentTransactions',
            'weeklyTrend',
            'nowPST'
        ));
    }
}

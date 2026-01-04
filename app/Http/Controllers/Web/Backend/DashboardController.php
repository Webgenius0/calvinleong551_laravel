<?php

namespace App\Http\Controllers\Web\Backend;

use App\Models\User;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;

use App\Http\Controllers\Controller;
use function App\Helpers\parseTemplate;

class DashboardController extends Controller
{
    public function index()
    {
        // Total Users
        $totalUsers = User::count();

        // Active Users (assuming you have a 'status' field or 'last_login_at')
        $activeUsers = User::where('status', 'active')->count();
        $activeUsersPercentage = $totalUsers > 0 ? round(($activeUsers / $totalUsers) * 100, 2) : 0;

        // This Month Users
        $thisMonthUsers = User::whereMonth('created_at', date('m'))
            ->whereYear('created_at', date('Y'))
            ->count();

        // Last Month Users for growth calculation
        $lastMonthUsers = User::whereMonth('created_at', date('m', strtotime('-1 month')))
            ->whereYear('created_at', date('Y', strtotime('-1 month')))
            ->count();

        $monthlyGrowth = $lastMonthUsers > 0
            ? round((($thisMonthUsers - $lastMonthUsers) / $lastMonthUsers) * 100, 2)
            : ($thisMonthUsers > 0 ? 100 : 0);

        // This Week Users
        $startOfWeek = now()->startOfWeek();
        $endOfWeek = now()->endOfWeek();
        $thisWeekUsers = User::whereBetween('created_at', [$startOfWeek, $endOfWeek])->count();

        // Last Week Users
        $lastWeekStart = now()->subWeek()->startOfWeek();
        $lastWeekEnd = now()->subWeek()->endOfWeek();
        $lastWeekUsers = User::whereBetween('created_at', [$lastWeekStart, $lastWeekEnd])->count();

        $weeklyGrowth = $lastWeekUsers > 0
            ? round((($thisWeekUsers - $lastWeekUsers) / $lastWeekUsers) * 100, 2)
            : ($thisWeekUsers > 0 ? 100 : 0);

        // User growth percentage (overall)
        $previousMonthUsers = User::where('created_at', '<', now()->subMonth())
            ->count();
        $userGrowthPercentage = $previousMonthUsers > 0
            ? round((($totalUsers - $previousMonthUsers) / $previousMonthUsers) * 100, 2)
            : ($totalUsers > 0 ? 100 : 0);

        // Recent Users (last 5)
        $recentUsers = User::latest()->take(5)->get();

        // Monthly data for the current year
        $monthlyData = [];
        $year = date('Y');

        for ($month = 1; $month <= 12; $month++) {
            $monthName = date('F', mktime(0, 0, 0, $month, 1));
            $monthCount = User::whereMonth('created_at', $month)
                ->whereYear('created_at', $year)
                ->count();

            // Get previous month count for growth calculation
            $prevMonthCount = User::whereMonth('created_at', $month - 1)
                ->whereYear('created_at', $month == 1 ? $year - 1 : $year)
                ->count();

            $growth = $prevMonthCount > 0
                ? round((($monthCount - $prevMonthCount) / $prevMonthCount) * 100, 2)
                : ($monthCount > 0 ? 100 : 0);

            $monthlyData[] = [
                'month' => $monthName,
                'count' => $monthCount,
                'growth' => $growth,
                'percentage' => $totalUsers > 0 ? round(($monthCount / $totalUsers) * 100, 2) : 0
            ];
        }
        return view('backend.layouts.dashboard', compact(
            'totalUsers',
            'activeUsers',
            'activeUsersPercentage',
            'thisMonthUsers',
            'monthlyGrowth',
            'thisWeekUsers',
            'weeklyGrowth',
            'userGrowthPercentage',
            'recentUsers',
            'monthlyData'
        ));
    }
}

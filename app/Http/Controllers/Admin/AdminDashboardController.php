<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\NfcDevice;
use App\Models\NfcOrder;
use App\Models\Portfolio;
use App\Models\PortfolioEntitlement;
use App\Models\PortfolioOrder;
use App\Models\PortfolioPaymentMethod;
use App\Models\PortfolioPlan;
use App\Models\PortfolioSetting;
use App\Models\PortfolioVoucher;
use App\Models\Profile;
use App\Models\ProfileComponent;
use App\Models\ProfileEvent;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminDashboardController extends Controller
{
    /**
     * Display Super Admin platform overview.
     */
    public function index(Request $request): Response
    {
        $totalUsers = User::count();
        $totalProfiles = Profile::count();
        $totalComponents = ProfileComponent::count();
        $totalNfc = NfcDevice::count();
        $activeNfc = NfcDevice::where('status', 'active')->count();

        $totalViews = ProfileEvent::where('event_type', 'view')->count();
        $totalClicks = ProfileEvent::where('event_type', 'click')->count();

        $users = User::with(['profile', 'nfcDevices', 'portfolioEntitlement'])
            ->latest()
            ->paginate(15);

        // All users list for card/entitlement assignment dropdown
        $allUsers = User::select('id', 'name', 'email')
            ->with(['profile:id,user_id,username', 'portfolioEntitlement'])
            ->get()
            ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        $nfcDevices = NfcDevice::with(['user.profile'])
            ->latest()
            ->get();

        // NFC Orders submitted by users
        $nfcOrders = NfcOrder::with(['user.profile', 'device'])
            ->latest()
            ->get();

        // Available unassigned cards in stock that Admin has already generated
        $unassignedCards = NfcDevice::whereNull('user_id')
            ->where('status', '!=', 'suspended')
            ->latest()
            ->get();

        $auditLogs = AuditLog::with('actor')
            ->latest()
            ->take(30)
            ->get();

        // --- Portfolio Management Data ---
        $totalPortfolios = Portfolio::count();
        $publishedPortfolios = Portfolio::where('is_published', true)->count();
        $activeSubscribers = PortfolioEntitlement::where('status', 'active')
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })->count();
        $pendingPortfolioOrders = PortfolioOrder::where('payment_status', 'under_review')->count();
        $totalPortfolioRevenue = PortfolioOrder::where('payment_status', 'paid')->sum('final_amount');

        $portfolioPlans = PortfolioPlan::latest()->get();
        $portfolioVouchers = PortfolioVoucher::latest()->get();
        $portfolioPaymentMethods = PortfolioPaymentMethod::latest()->get();
        $portfolioOrders = PortfolioOrder::with(['user.profile', 'plan', 'voucher', 'payment'])
            ->latest()
            ->take(50)
            ->get();
        $portfolioEntitlements = PortfolioEntitlement::with('user.profile')
            ->latest()
            ->get();
        $userPortfolios = Portfolio::with(['user.profile', 'pages'])
            ->latest()
            ->get();

        return Inertia::render('Admin/Dashboard', [
            'stats' => [
                'total_users' => $totalUsers,
                'total_profiles' => $totalProfiles,
                'total_components' => $totalComponents,
                'total_nfc' => $totalNfc,
                'active_nfc' => $activeNfc,
                'total_orders' => $nfcOrders->count(),
                'total_views' => $totalViews,
                'total_clicks' => $totalClicks,
                // Portfolio Stats
                'total_portfolios' => $totalPortfolios,
                'published_portfolios' => $publishedPortfolios,
                'active_subscribers' => $activeSubscribers,
                'pending_portfolio_orders' => $pendingPortfolioOrders,
                'total_portfolio_revenue' => $totalPortfolioRevenue,
            ],
            'users' => $users,
            'allUsers' => $allUsers,
            'nfcDevices' => $nfcDevices,
            'nfcOrders' => $nfcOrders,
            'unassignedCards' => $unassignedCards,
            'auditLogs' => $auditLogs,
            // Portfolio Props
            'portfolioPlans' => $portfolioPlans,
            'portfolioVouchers' => $portfolioVouchers,
            'portfolioPaymentMethods' => $portfolioPaymentMethods,
            'portfolioOrders' => $portfolioOrders,
            'portfolioEntitlements' => $portfolioEntitlements,
            'userPortfolios' => $userPortfolios,
            'portfolioSettings' => [
                'max_pdf_files' => (int) PortfolioSetting::get('max_pdf_files', 10),
                'max_pdf_size_mb' => (int) PortfolioSetting::get('max_pdf_size_mb', 5),
                'terms_and_conditions' => PortfolioSetting::get('terms_and_conditions', ''),
            ],
        ]);
    }
}

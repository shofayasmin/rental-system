<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use App\Models\RentalRequest;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Http\Request;

class TenantDashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $tenantId = auth()->id();

        $activeContracts = Contract::query()
            ->where('contracts.status', 'active')
            ->whereHas('rentalRequest', function ($q) use ($tenantId) {
                $q->where('tenant_id', $tenantId);
            })
            ->count();

        $pendingRequests = RentalRequest::query()
            ->where('tenant_id', $tenantId)
            ->whereIn('status', ['pending_review', 'awaiting_payment'])
            ->count();

        $expiringSoon = Contract::query()
            ->where('contracts.status', 'active')
            ->whereHas('rentalRequest', function ($q) use ($tenantId) {
                $q->where('tenant_id', $tenantId);
            })
            ->whereDate('end_date', '<=', now()->addDays(30))
            ->whereDate('end_date', '>=', now())
            ->count();

        $totalSpent = Transaction::query()
            ->where('tenant_id', $tenantId)
            ->where('status', 'paid')
            ->sum('amount');

        $recentRequests = RentalRequest::query()
            ->where('tenant_id', $tenantId)
            ->with('property')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        return view('tenant.dashboard', [
            'activeContracts' => $activeContracts,
            'pendingRequests' => $pendingRequests,
            'expiringSoon' => $expiringSoon,
            'totalSpent' => $totalSpent,
            'recentRequests' => $recentRequests,
        ]);
    }
}

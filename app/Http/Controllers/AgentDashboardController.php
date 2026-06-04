<?php

namespace App\Http\Controllers;

use App\Models\Property;
use App\Models\RentalRequest;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AgentDashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $agentId = auth()->id();

        $validated = $request->validate([
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);

        $endDate = isset($validated['end_date'])
            ? Carbon::parse($validated['end_date'])->endOfDay()
            : now()->endOfDay();

        $startDate = isset($validated['start_date'])
            ? Carbon::parse($validated['start_date'])->startOfDay()
            : $endDate->copy()->subDays(29)->startOfDay();

        $propertyIds = Property::where('agent_id', $agentId)->pluck('id');

        $propertyStatusCounts = Property::query()
            ->where('agent_id', $agentId)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $totalProperties = array_sum($propertyStatusCounts->toArray());
        $rentedProperties = (int) ($propertyStatusCounts['rented'] ?? 0);
        $toLetProperties = (int) ($propertyStatusCounts['to-let'] ?? 0);
        $maintenanceProperties = (int) ($propertyStatusCounts['maintenance'] ?? 0);

        $occupancyRate = $totalProperties > 0
            ? ($rentedProperties / $totalProperties) * 100
            : null;

        $pendingRequests = RentalRequest::query()
            ->whereIn('rental_requests.property_id', $propertyIds)
            ->where('rental_requests.status', 'pending_review')
            ->count();

        $totalRentalsAllTime = RentalRequest::query()
            ->whereIn('rental_requests.property_id', $propertyIds)
            ->where('rental_requests.status', 'paid')
            ->whereNotNull('rental_requests.paid_at')
            ->count();

        $paidRequestsQuery = RentalRequest::query()
            ->whereIn('rental_requests.property_id', $propertyIds)
            ->where('rental_requests.status', 'paid')
            ->whereNotNull('rental_requests.paid_at')
            ->whereBetween('rental_requests.created_at', [$startDate, $endDate]);

        $newBookings = (clone $paidRequestsQuery)->count();

        $totalRevenue = (clone $paidRequestsQuery)
            ->join('transactions', 'transactions.rental_request_id', '=', 'rental_requests.id')
            ->where('transactions.status', 'paid')
            ->sum('transactions.amount');

        $monthlyRentals = RentalRequest::query()
            ->whereIn('rental_requests.property_id', $propertyIds)
            ->where('rental_requests.status', 'paid')
            ->whereNotNull('rental_requests.paid_at')
            ->whereBetween('rental_requests.created_at', [$startDate, $endDate])
            ->selectRaw("DATE_FORMAT(rental_requests.created_at, '%Y-%m') as month_key")
            ->selectRaw('COUNT(*) as total_rentals')
            ->groupBy('month_key')
            ->pluck('total_rentals', 'month_key');

        $monthlyRevenue = RentalRequest::query()
            ->join('transactions', 'transactions.rental_request_id', '=', 'rental_requests.id')
            ->whereIn('rental_requests.property_id', $propertyIds)
            ->where('rental_requests.status', 'paid')
            ->whereNotNull('rental_requests.paid_at')
            ->where('transactions.status', 'paid')
            ->whereBetween('rental_requests.created_at', [$startDate, $endDate])
            ->selectRaw("DATE_FORMAT(rental_requests.created_at, '%Y-%m') as month_key")
            ->selectRaw('SUM(transactions.amount) as revenue')
            ->groupBy('month_key')
            ->pluck('revenue', 'month_key');

        $monthLabels = [];
        $monthKeys = [];
        $cursor = $startDate->copy()->startOfMonth();
        $lastMonth = $endDate->copy()->startOfMonth();

        while ($cursor->lte($lastMonth)) {
            $monthKeys[] = $cursor->format('Y-m');
            $monthLabels[] = $cursor->format('M Y');
            $cursor->addMonth();
        }

        $trendTotalRentals = [];
        $trendRevenue = [];

        foreach ($monthKeys as $monthKey) {
            $trendTotalRentals[] = (int) ($monthlyRentals[$monthKey] ?? 0);
            $trendRevenue[] = round((float) ($monthlyRevenue[$monthKey] ?? 0), 2);
        }

        $recentRequests = RentalRequest::query()
            ->whereIn('rental_requests.property_id', $propertyIds)
            ->with(['property', 'tenant'])
            ->orderBy('rental_requests.created_at', 'desc')
            ->limit(5)
            ->get();

        return view('agent.dashboard', [
            'startDate' => $startDate->toDateString(),
            'endDate' => $endDate->toDateString(),
            'totalProperties' => $totalProperties,
            'rentedProperties' => $rentedProperties,
            'toLetProperties' => $toLetProperties,
            'maintenanceProperties' => $maintenanceProperties,
            'occupancyRate' => $occupancyRate,
            'pendingRequests' => $pendingRequests,
            'totalRentalsAllTime' => $totalRentalsAllTime,
            'newBookings' => $newBookings,
            'totalRevenue' => $totalRevenue,
            'trendLabels' => $monthLabels,
            'trendTotalRentals' => $trendTotalRentals,
            'trendRevenue' => $trendRevenue,
            'recentRequests' => $recentRequests,
        ]);
    }
}

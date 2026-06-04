<?php

namespace App\Http\Controllers;

use App\Models\Property;
use App\Models\RentalRequest;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AdminDashboardController extends Controller
{
    public function __invoke(Request $request)
    {
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

        $paidRequestsQuery = RentalRequest::query()
            ->where('rental_requests.status', 'paid')
            ->whereNotNull('rental_requests.paid_at')
            ->whereBetween('rental_requests.created_at', [$startDate, $endDate]);

        $totalRentalsAllTime = RentalRequest::query()
            ->where('rental_requests.status', 'paid')
            ->whereNotNull('rental_requests.paid_at')
            ->count();

        $newBookings = (clone $paidRequestsQuery)->count();

        $averageRentPrice = (clone $paidRequestsQuery)
            ->join('properties', 'properties.id', '=', 'rental_requests.property_id')
            ->avg('properties.rent_price');

        $averageTimeToRentDays = (clone $paidRequestsQuery)
            ->join('property_availability_cycles as pac', 'pac.id', '=', 'rental_requests.availability_cycle_id')
            ->whereNotNull('pac.available_from_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, pac.available_from_at, rental_requests.paid_at)) / 24 as avg_days')
            ->value('avg_days');

        $monthlyRentals = RentalRequest::query()
            ->where('rental_requests.status', 'paid')
            ->whereNotNull('rental_requests.paid_at')
            ->whereBetween('rental_requests.created_at', [$startDate, $endDate])
            ->selectRaw("DATE_FORMAT(rental_requests.created_at, '%Y-%m') as month_key")
            ->selectRaw('COUNT(*) as total_rentals')
            ->groupBy('month_key')
            ->pluck('total_rentals', 'month_key');

        $monthlyAverageRentPrice = RentalRequest::query()
            ->join('properties', 'properties.id', '=', 'rental_requests.property_id')
            ->where('rental_requests.status', 'paid')
            ->whereNotNull('rental_requests.paid_at')
            ->whereBetween('rental_requests.created_at', [$startDate, $endDate])
            ->selectRaw("DATE_FORMAT(rental_requests.created_at, '%Y-%m') as month_key")
            ->selectRaw('AVG(properties.rent_price) as avg_rent_price')
            ->groupBy('month_key')
            ->pluck('avg_rent_price', 'month_key');

        $totalRevenue = (clone $paidRequestsQuery)
            ->join('transactions', 'transactions.rental_request_id', '=', 'rental_requests.id')
            ->where('transactions.status', 'paid')
            ->sum('transactions.amount');

        $monthlyRevenue = RentalRequest::query()
            ->join('transactions', 'transactions.rental_request_id', '=', 'rental_requests.id')
            ->where('rental_requests.status', 'paid')
            ->whereNotNull('rental_requests.paid_at')
            ->where('transactions.status', 'paid')
            ->whereBetween('rental_requests.created_at', [$startDate, $endDate])
            ->selectRaw("DATE_FORMAT(rental_requests.created_at, '%Y-%m') as month_key")
            ->selectRaw('SUM(transactions.amount) as revenue')
            ->groupBy('month_key')
            ->pluck('revenue', 'month_key');

        $monthlyAverageTimeToRent = RentalRequest::query()
            ->join('property_availability_cycles as pac', 'pac.id', '=', 'rental_requests.availability_cycle_id')
            ->where('rental_requests.status', 'paid')
            ->whereNotNull('rental_requests.paid_at')
            ->whereNotNull('pac.available_from_at')
            ->whereBetween('rental_requests.created_at', [$startDate, $endDate])
            ->selectRaw("DATE_FORMAT(rental_requests.created_at, '%Y-%m') as month_key")
            ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, pac.available_from_at, rental_requests.paid_at)) / 24 as avg_days')
            ->groupBy('month_key')
            ->pluck('avg_days', 'month_key');

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
        $trendAverageRentPrice = [];
        $trendAverageTimeToRentDays = [];
        $trendRevenue = [];

        foreach ($monthKeys as $monthKey) {
            $trendTotalRentals[] = (int) ($monthlyRentals[$monthKey] ?? 0);
            $trendAverageRentPrice[] = round((float) ($monthlyAverageRentPrice[$monthKey] ?? 0), 2);
            $trendAverageTimeToRentDays[] = round((float) ($monthlyAverageTimeToRent[$monthKey] ?? 0), 2);
            $trendRevenue[] = round((float) ($monthlyRevenue[$monthKey] ?? 0), 2);
        }

        $propertyStatusCounts = Property::query()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $rentedProperties = (int) ($propertyStatusCounts['rented'] ?? 0);
        $toLetProperties = (int) ($propertyStatusCounts['to-let'] ?? 0);
        $maintenanceProperties = (int) ($propertyStatusCounts['maintenance'] ?? 0);
        $totalProperties = $rentedProperties + $toLetProperties + $maintenanceProperties;

        $occupancyRate = $totalProperties > 0
            ? ($rentedProperties / $totalProperties) * 100
            : null;

        return view('admin.dashboard', [
            'startDate' => $startDate->toDateString(),
            'endDate' => $endDate->toDateString(),
            'totalRentalsAllTime' => $totalRentalsAllTime,
            'newBookings' => $newBookings,
            'totalRevenue' => $totalRevenue,
            'averageRentPrice' => $averageRentPrice,
            'averageTimeToRentDays' => $averageTimeToRentDays,
            'occupancyRate' => $occupancyRate,
            'rentedProperties' => $rentedProperties,
            'toLetProperties' => $toLetProperties,
            'maintenanceProperties' => $maintenanceProperties,
            'totalProperties' => $totalProperties,
            'trendLabels' => $monthLabels,
            'trendTotalRentals' => $trendTotalRentals,
            'trendRevenue' => $trendRevenue,
            'trendAverageRentPrice' => $trendAverageRentPrice,
            'trendAverageTimeToRentDays' => $trendAverageTimeToRentDays,
        ]);
    }
}

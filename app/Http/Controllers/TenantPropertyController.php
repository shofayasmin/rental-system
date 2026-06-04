<?php

namespace App\Http\Controllers;

use App\Models\District;
use App\Models\Property;
use App\Models\Province;
use App\Models\Regency;
use App\Models\Village;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TenantPropertyController extends Controller
{

    public function index(Request $request)
    {
        $query = Property::whereIn('status', ['to-let', 'rented', 'maintenance']);

        if ($request->filled('q')) {
            $keyword = trim((string) $request->string('q'));
            $query->where(function ($subQuery) use ($keyword) {
                $subQuery
                    ->where('title', 'like', '%' . $keyword . '%')
                    ->orWhere('address', 'like', '%' . $keyword . '%')
                    ->orWhereHas('regency', fn ($q) => $q->where('name', 'like', '%' . $keyword . '%'))
                    ->orWhereHas('district', fn ($q) => $q->where('name', 'like', '%' . $keyword . '%'))
                    ->orWhereHas('village', fn ($q) => $q->where('name', 'like', '%' . $keyword . '%'));
            });
        }

        if ($request->filled('province_id')) {
            $query->where('province_id', $request->integer('province_id'));
        }

        if ($request->filled('regency_id')) {
            $query->where('regency_id', $request->integer('regency_id'));
        }

        if ($request->filled('district_id')) {
            $query->where('district_id', $request->integer('district_id'));
        }

        if ($request->filled('village_id')) {
            $query->where('village_id', $request->integer('village_id'));
        }

        if ($request->filled('min_price')) {
            $query->where('rent_price', '>=', $request->min_price);
        }

        if ($request->filled('max_price')) {
            $query->where('rent_price', '<=', $request->max_price);
        }

        if ($request->filled('min_area')) {
            $query->where('area', '>=', $request->min_area);
        }

        if ($request->filled('max_area')) {
            $query->where('area', '<=', $request->max_area);
        }

        if ($request->filled('min_bedrooms')) {
            $query->where('bedrooms', '>=', $request->integer('min_bedrooms'));
        }

        if ($request->filled('max_bedrooms')) {
            $query->where('bedrooms', '<=', $request->integer('max_bedrooms'));
        }

        if ($request->filled('min_bathrooms')) {
            $query->where('bathrooms', '>=', $request->integer('min_bathrooms'));
        }

        if ($request->filled('max_bathrooms')) {
            $query->where('bathrooms', '<=', $request->integer('max_bathrooms'));
        }

        $sort = $request->string('sort')->toString();
        if (!in_array($sort, ['price_desc', 'price_asc', 'newest', 'oldest', 'area_desc', 'area_asc'], true)) {
            $sort = 'newest';
        }

        switch ($sort) {
            case 'price_desc':
                $query->orderBy('rent_price', 'desc')
                    ->orderByDesc('created_at');
                break;
            case 'price_asc':
                $query->orderBy('rent_price', 'asc')
                    ->orderByDesc('created_at');
                break;
            case 'newest':
                $query->orderByDesc('created_at');
                break;
            case 'oldest':
                $query->orderBy('created_at', 'asc');
                break;
            case 'area_desc':
                $query->orderByRaw('COALESCE(area, 0) DESC')
                    ->orderByDesc('created_at');
                break;
            case 'area_asc':
                $query->orderByRaw('COALESCE(area, 0) ASC')
                    ->orderByDesc('created_at');
                break;
            default:
                $query->orderByDesc('created_at');
                break;
        }

        $selectedProvinceId = $request->integer('province_id');
        $selectedRegencyId = $request->integer('regency_id');
        $selectedDistrictId = $request->integer('district_id');

        $provinces = Province::query()
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        $regencies = $selectedProvinceId
            ? Regency::query()
                ->select('id', 'name')
                ->where('province_id', $selectedProvinceId)
                ->orderBy('name')
                ->get()
            : collect();

        $districts = $selectedRegencyId
            ? District::query()
                ->select('id', 'name')
                ->where('regency_id', $selectedRegencyId)
                ->orderBy('name')
                ->get()
            : collect();

        $villages = $selectedDistrictId
            ? Village::query()
                ->select('id', 'name')
                ->where('district_id', $selectedDistrictId)
                ->orderBy('name')
                ->get()
            : collect();

        $properties = $query->with('photos', 'agent', 'province', 'regency', 'district', 'village')->get();

        return view('tenant.properties.index', compact(
            'properties',
            'provinces',
            'regencies',
            'districts',
            'villages'
        ));
    }

    public function show(Property $property)
    {
        if ($redirect = $this->redirectNonTenantUser()) {
            return $redirect;
        }

        $property->loadMissing(
            'agent',
            'photos',
            'facilityItems',
            'province',
            'regency',
            'district',
            'village'
        );

        return view('tenant.properties.show', compact('property'));
    }

    public function photos(Property $property)
    {
        if ($redirect = $this->redirectNonTenantUser()) {
            return $redirect;
        }

        $property->loadMissing(
            'agent',
            'photos',
            'province',
            'regency',
            'district',
            'village'
        );

        return view('tenant.properties.photos', compact('property'));
    }

    public function regencies(Province $province): JsonResponse
    {
        return response()->json(
            $province->regencies()
                ->select('id', 'name')
                ->orderBy('name')
                ->get()
        );
    }

    public function districts(Regency $regency): JsonResponse
    {
        return response()->json(
            $regency->districts()
                ->select('id', 'name')
                ->orderBy('name')
                ->get()
        );
    }

    public function villages(District $district): JsonResponse
    {
        return response()->json(
            $district->villages()
                ->select('id', 'name')
                ->orderBy('name')
                ->get()
        );
    }

    private function redirectNonTenantUser()
    {
        if (!Auth::check() || Auth::user()->role === 'tenant') {
            return null;
        }

        return match (Auth::user()->role) {
            'agent' => redirect('/agent/dashboard'),
            'admin' => redirect('/admin/dashboard'),
            default => abort(403),
        };
    }
}

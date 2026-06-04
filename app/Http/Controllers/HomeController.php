<?php

namespace App\Http\Controllers;

use App\Models\Property;
use App\Models\Province;
use Illuminate\Support\Facades\Auth;

class HomeController extends Controller
{
    public function __invoke()
    {
        if (Auth::check() && Auth::user()->role === 'agent') {
            return redirect('/agent/dashboard');
        }

        $provinces = Province::select('id', 'name')->orderBy('name')->get();

        $featured = Property::where('status', 'to-let')
            ->with('photos', 'province', 'regency', 'district')
            ->latest()
            ->limit(6)
            ->get();

        return view('home.index', compact('provinces', 'featured'));
    }
}

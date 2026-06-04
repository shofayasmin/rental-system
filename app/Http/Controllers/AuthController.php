<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();

            $user = Auth::user();

            if (!$user->enabled) {
                Auth::logout();
                return back()->withErrors([
                    'email' => 'Your account has been disabled by administrator.'
                ]);
            }
            \DB::table('login_audit')->insert([
                'user_id' => $user->id,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            return match ($user->role) {
                'admin'  => redirect('/admin/dashboard'),
                'agent'  => redirect('/agent/dashboard'),
                default  => redirect('/tenant/dashboard'),
            };
        }

        return back()->withErrors([
            'email' => 'Email or password is incorrect',
        ]);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }

    public function showRegister()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'phone' => 'required|string|max:20',
            'password' => 'required|min:6|confirmed',
        ]);

        User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'password' => Hash::make($data['password']),
            'role' => 'tenant',
            'enabled' => 1,
        ]);

        return redirect('/login')
            ->with('success', 'Registration successful. Please login.');
    }
}

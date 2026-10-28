<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            $user = Auth::user();
            if (!$user->is_active) {
                Auth::logout();
                return back()->withErrors(['email' => 'Akun ini telah dinonaktifkan oleh Administrator.']);
            }

            // Arahkan Kasir ke halaman POS, Admin ke Dashboard
            if ($user->isCashier()) {
                return redirect()->intended(route('pos.index'))->with('success', "Selamat datang, {$user->name}!");
            }

            return redirect()->intended(route('dashboard'))->with('success', "Selamat datang, {$user->name}!");
        }

        return back()->withErrors([
            'email' => 'Kombinasi email dan password yang Anda masukkan tidak cocok.',
        ])->onlyInput('email');
    }

    public function quickLogin(Request $request)
    {
        $role = $request->input('role', 'admin');
        $user = User::where('role', $role)->where('is_active', true)->first();

        if ($user) {
            Auth::login($user);
            $request->session()->regenerate();

            if ($user->isCashier()) {
                return redirect()->route('pos.index')->with('success', "Login cepat berhasil sebagai {$user->name} ({$user->role})");
            }
            return redirect()->route('dashboard')->with('success', "Login cepat berhasil sebagai {$user->name} ({$user->role})");
        }

        return redirect()->route('login')->with('error', 'Pengguna demo tidak ditemukan.');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'Anda telah berhasil keluar dari sistem.');
    }
}

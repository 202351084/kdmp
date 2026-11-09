<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()->withErrors(['email' => 'Email atau kata sandi salah.'])->onlyInput('email');
        }

        $user = Auth::user();

        // Cek apakah akun aktif (disetujui admin)
        if (!$user->is_active) {
            Auth::logout();
            return back()->withErrors(['email' => 'Akun Anda sedang menunggu persetujuan admin atau dinonaktifkan.'])->onlyInput('email');
        }

        $request->session()->regenerate();

        $isPurchasingUser = $user->hasPeran('pembelian');
        $isSalesUser = $user->hasPeran('kasir') || $user->hasPeran('penjualan');
        if ($isPurchasingUser || $isSalesUser) {
            $route = $isPurchasingUser
                ? 'pembelian.pembelian.index'
                : 'penjualan.penjualan.index';

            return redirect()->route($route);
        }

        if ($user->id_entitas === null) {
            return redirect()->intended(route('admin.dashboard'));
        }

        return redirect()->intended(route('dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}

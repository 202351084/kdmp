<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RestrictPurchasingRole
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $isPurchasingUser = $user?->hasPeran('pembelian') ?? false;
        $isSalesUser = ($user?->hasPeran('kasir') ?? false)
            || ($user?->hasPeran('penjualan') ?? false);

        if (! $isPurchasingUser && ! $isSalesUser) {
            return $next($request);
        }

        if ($request->routeIs(
            'logout',
            'login',
            'login.store',
            'register',
            'register.store',
            'landing',
        )) {
            return $next($request);
        }

        if ($request->routeIs('dashboard')) {
            return redirect()->route($this->homeRoute($isPurchasingUser, $isSalesUser));
        }

        $allowedRoutes = [];
        if ($isPurchasingUser) {
            $allowedRoutes[] = 'pembelian.*';
        }
        if ($isSalesUser) {
            $allowedRoutes[] = 'penjualan.*';
        }

        if ($request->routeIs(...$allowedRoutes)) {
            return $next($request);
        }

        abort(403, 'Akun ini hanya dapat mengakses modul yang sesuai dengan perannya.');
    }

    private function homeRoute(bool $isPurchasingUser, bool $isSalesUser): string
    {
        return $isPurchasingUser || ! $isSalesUser
            ? 'pembelian.pembelian.index'
            : 'penjualan.penjualan.index';
    }
}

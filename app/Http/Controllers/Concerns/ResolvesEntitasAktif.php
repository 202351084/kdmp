<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Tenant\Entitas;
use Symfony\Component\HttpKernel\Exception\HttpException;

trait ResolvesEntitasAktif
{
    protected function entitasAktifId(): ?int
    {
        $user = auth()->user();

        if ($user && $user->id_entitas !== null) {
            return (int) $user->id_entitas;
        }

        if (app()->bound('entitas_aktif') && app('entitas_aktif') !== null) {
            return (int) app('entitas_aktif');
        }

        $pilihan = request()->session()->get('entitas_aktif_pilihan');

        return $pilihan !== null && $pilihan !== '' ? (int) $pilihan : null;
    }

    protected function requireEntitasAktifId(): int
    {
        $id = $this->entitasAktifId();

        if (! $id) {
            throw new HttpException(403, 'Silakan pilih Entitas terlebih dahulu pada dropdown di bagian atas halaman.');
        }

        return $id;
    }

    protected function entitasAktif(): ?Entitas
    {
        $id = $this->entitasAktifId();

        return $id ? Entitas::find($id) : null;
    }
}


<?php

namespace App\Services;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class StokMinimumService
{
    public function tersediaPerBarang(?int $entitasId = null): Builder
    {
        return DB::table('stok')
            ->join('gudang as gudang_stok_minimum', 'gudang_stok_minimum.id_gudang', '=', 'stok.id_gudang')
            ->when(
                $entitasId !== null,
                fn (Builder $query) => $query->where('gudang_stok_minimum.id_entitas', $entitasId),
            )
            ->select('gudang_stok_minimum.id_entitas', 'stok.id_barang')
            ->selectRaw('SUM(stok.qty_on_hand - stok.qty_reserved) AS qty_tersedia')
            ->groupBy('gudang_stok_minimum.id_entitas', 'stok.id_barang');
    }

    public function jumlahBarangMenipis(?int $entitasId = null): int
    {
        return DB::table('barang_per_koperasi as pengaturan')
            ->leftJoinSub(
                $this->tersediaPerBarang($entitasId),
                'saldo_barang',
                function ($join): void {
                    $join->on('saldo_barang.id_entitas', '=', 'pengaturan.id_entitas')
                        ->on('saldo_barang.id_barang', '=', 'pengaturan.id_barang');
                },
            )
            ->where('pengaturan.stok_minimum', '>', 0)
            ->whereRaw('COALESCE(saldo_barang.qty_tersedia, 0) <= pengaturan.stok_minimum')
            ->when($entitasId !== null, fn (Builder $query) => $query->where('pengaturan.id_entitas', $entitasId))
            ->count();
    }
}

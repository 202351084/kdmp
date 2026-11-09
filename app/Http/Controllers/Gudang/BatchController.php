<?php

namespace App\Http\Controllers\Gudang;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class BatchController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless(
            Schema::hasTable('stok_batch')
                && Schema::hasColumn('barang_per_koperasi', 'lacak_batch'),
            404,
            'Pelacakan batch belum tersedia.',
        );

        $entitasId = auth()->user()->id_entitas
            ?? (app()->bound('entitas_aktif') ? app('entitas_aktif') : null);
        $status = $request->query('status');
        $query = DB::table('stok_batch')
            ->join('gudang', 'gudang.id_gudang', '=', 'stok_batch.id_gudang')
            ->join('master_barang', 'master_barang.id_barang', '=', 'stok_batch.id_barang')
            ->leftJoin('satuan', 'satuan.id', '=', 'master_barang.id_satuan_dasar')
            ->join('barang_per_koperasi as pengaturan', function ($join): void {
                $join->on('pengaturan.id_entitas', '=', 'gudang.id_entitas')
                    ->on('pengaturan.id_barang', '=', 'stok_batch.id_barang')
                    ->where('pengaturan.lacak_batch', true);
            })
            ->where('stok_batch.qty_on_hand', '>', 0)
            ->when(
                $entitasId !== null,
                fn ($query) => $query->where('gudang.id_entitas', $entitasId),
            )
            ->when($request->filled('q'), function ($query) use ($request): void {
                $search = $request->string('q')->toString();
                $query->where(function ($query) use ($search): void {
                    $query->where('master_barang.nama_barang', 'like', "%{$search}%")
                        ->orWhere('master_barang.kode_barang', 'like', "%{$search}%")
                        ->orWhere('stok_batch.kode_batch', 'like', "%{$search}%")
                        ->orWhere('gudang.nama_gudang', 'like', "%{$search}%");
                });
            });

        if ($status === 'segera') {
            $query->whereBetween('stok_batch.tanggal_kedaluwarsa', [
                now()->toDateString(),
                now()->addDays(30)->toDateString(),
            ]);
        } elseif ($status === 'kedaluwarsa') {
            $query->where('stok_batch.tanggal_kedaluwarsa', '<', now()->toDateString());
        } elseif ($status === 'tanpa-tanggal') {
            $query->whereNull('stok_batch.tanggal_kedaluwarsa');
        } else {
            $status = '';
        }

        $items = $query
            ->select([
                'stok_batch.id_batch',
                'stok_batch.kode_batch',
                'stok_batch.tanggal_kedaluwarsa',
                'stok_batch.qty_on_hand',
                'stok_batch.is_stok_awal',
                'gudang.nama_gudang',
                'master_barang.kode_barang',
                'master_barang.nama_barang',
                'satuan.kode_satuan',
            ])
            ->orderByRaw('stok_batch.tanggal_kedaluwarsa IS NULL')
            ->orderBy('stok_batch.tanggal_kedaluwarsa')
            ->orderBy('master_barang.nama_barang')
            ->paginate(20)
            ->withQueryString();

        return view('gudang.batch.index', [
            'items' => $items,
            'filterStatus' => $status,
            'title' => 'Batch dan Kedaluwarsa',
        ]);
    }
}

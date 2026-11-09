<?php

namespace App\Http\Controllers\Gudang;

use App\Http\Controllers\Concerns\ModuleCrudController;
use App\Models\Gudang\KartuStok;
use App\Models\Master\Barang;
use App\Models\Master\Gudang;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/** Laporan mutasi stok, append-only — hanya index (read-only). */
class KartuStokController extends ModuleCrudController
{
    protected string $model = KartuStok::class;
    protected string $view = 'gudang.kartu-stok';
    protected string $title = 'Kartu Stok';
    protected string $routeBase = 'gudang.kartu-stok';
    protected array $withRelations = ['barang'];

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'id_gudang' => ['nullable', 'integer', 'exists:gudang,id_gudang'],
            'id_barang' => ['nullable', 'integer', 'exists:master_barang,id_barang'],
            'jenis_mutasi' => ['nullable', 'in:IN,OUT,ADJ_IN,ADJ_OUT'],
            'tanggal_mulai' => ['nullable', 'date'],
            'tanggal_selesai' => ['nullable', 'date'],
        ]);

        if (
            ! empty($filters['tanggal_mulai'])
            && ! empty($filters['tanggal_selesai'])
            && $filters['tanggal_mulai'] > $filters['tanggal_selesai']
        ) {
            throw ValidationException::withMessages([
                'tanggal_selesai' => 'Tanggal selesai harus sama atau setelah tanggal mulai.',
            ]);
        }

        $query = KartuStok::query()->with(['barang.satuanDasar', 'gudang'])
            ->when(
                $filters['id_gudang'] ?? null,
                fn ($query, $idGudang) => $query->where('id_gudang', $idGudang),
            )
            ->when(
                $filters['id_barang'] ?? null,
                fn ($query, $idBarang) => $query->where('id_barang', $idBarang),
            )
            ->when(
                $filters['jenis_mutasi'] ?? null,
                fn ($query, $jenisMutasi) => $query->where('jenis_mutasi', $jenisMutasi),
            )
            ->when(
                $filters['tanggal_mulai'] ?? null,
                fn ($query, $tanggalMulai) => $query->whereDate('tanggal', '>=', $tanggalMulai),
            )
            ->when(
                $filters['tanggal_selesai'] ?? null,
                fn ($query, $tanggalSelesai) => $query->whereDate('tanggal', '<=', $tanggalSelesai),
            );

        $items = $query->orderByDesc('tanggal')->orderByDesc('id_kartu')->paginate(20)->withQueryString();

        return view('gudang.kartu-stok.index', [
            'items' => $items,
            'gudang' => Gudang::query()->where('is_active', true)->orderBy('nama_gudang')->get(),
            'barang' => Barang::query()->where('is_active', true)->orderBy('nama_barang')->get(),
            'title' => $this->title,
        ]);
    }
}

<?php

namespace App\Http\Controllers\Konsinyasi;

use App\Http\Controllers\Concerns\ModuleCrudController;
use App\Models\Konsinyasi\KartuKonsinyasi;
use App\Models\Konsinyasi\StokKonsinyasi;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Posisi stok titipan di gudang penerima — read-only. */
class StokKonsinyasiController extends ModuleCrudController
{
    protected string $model = StokKonsinyasi::class;
    protected string $view = 'konsinyasi.stok';
    protected string $title = 'Stok Konsinyasi';
    protected string $routeBase = 'konsinyasi.stok';
    protected array $withRelations = ['barang', 'kiriman'];

    public function index(Request $request): View
    {
        $query = StokKonsinyasi::query()
            ->diKoperasi()
            ->with(['barang.satuanDasar', 'kiriman.koperasiPemilik', 'kiriman.gudangTujuan']);

        if ($search = trim((string) $request->query('q', ''))) {
            $query->where(function ($builder) use ($search) {
                $builder->whereHas('barang', fn ($barang) => $barang->where('nama_barang', 'like', "%{$search}%"))
                    ->orWhereHas('kiriman', fn ($kiriman) => $kiriman->where('kode_kiriman', 'like', "%{$search}%"))
                    ->orWhereHas('kiriman.koperasiPemilik', fn ($pemilik) => $pemilik->where('nama_entitas', 'like', "%{$search}%"));
            });
        }

        if ($request->filled('status') && in_array($request->query('status'), ['aktif', 'habis', 'dikembalikan'], true)) {
            $query->where('status', $request->query('status'));
        }

        return view('konsinyasi.stok.index', [
            'items' => $query->latest('id_stok_konsinyasi')->paginate(15)->withQueryString(),
            'title' => $this->title,
        ]);
    }

    public function show(int|string $id): View
    {
        $item = StokKonsinyasi::query()
            ->diKoperasi()
            ->with(['barang.satuanDasar', 'kiriman.koperasiPemilik', 'kiriman.gudangTujuan'])
            ->findOrFail($id);

        $mutasi = KartuKonsinyasi::query()
            ->where('id_stok_konsinyasi', $item->id_stok_konsinyasi)
            ->orderByDesc('tanggal')
            ->orderByDesc('id_kartu_kons')
            ->get();

        return view('konsinyasi.stok.show', [
            'item' => $item,
            'mutasi' => $mutasi,
            'title' => 'Detail Stok Konsinyasi',
        ]);
    }
}

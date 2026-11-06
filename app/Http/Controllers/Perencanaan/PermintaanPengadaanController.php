<?php

namespace App\Http\Controllers\Perencanaan;

use App\Http\Controllers\Concerns\ModuleCrudController;
use App\Models\Master\Barang;
use App\Models\Master\Gudang;
use App\Models\Master\UnitUsaha;
use App\Models\Perencanaan\HasilKalkulasi;
use App\Models\Perencanaan\PermintaanPengadaan;
use App\Services\Perencanaan\PerbandinganHargaService;
use App\Support\Perencanaan\PeriodeKalkulasi;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PermintaanPengadaanController extends ModuleCrudController
{
    protected string $model = PermintaanPengadaan::class;
    protected string $view = 'perencanaan.permintaan-pengadaan';
    protected string $title = 'Permintaan Pengadaan (PR)';
    protected string $routeBase = 'perencanaan.permintaan-pengadaan';
    protected array $withRelations = ['pihak'];

    public function fromHasil(HasilKalkulasi $hasil): RedirectResponse
    {
        $this->assertHasilBisaDiproses($hasil);

        return redirect()->route('perencanaan.permintaan-pengadaan.create', [
            'id_hasil' => $hasil->id_hasil,
        ]);
    }

    public function index(Request $request): View
    {
        $user = auth()->user();
        $idWilayah = $user?->entitas?->id_wilayah;
        $hargaService = app(PerbandinganHargaService::class);
        $items = PermintaanPengadaan::query()
            ->with(['pihak', 'unitUsaha', 'gudang', 'detail.barang'])
            ->latest('id_permintaan')
            ->paginate(15)
            ->withQueryString();
        $calonPerencanaan = HasilKalkulasi::query()
            ->with(['komoditas', 'wilayah'])
            ->where('status_surplus_defisit', 'defisit')
            ->when($idWilayah, fn ($query) => $query->where('id_wilayah', $idWilayah))
            ->latest('id_hasil')
            ->get()
            ->map(function (HasilKalkulasi $hasil) use ($hargaService, $user): array {
                $periode = PeriodeKalkulasi::fromMonth((int) $hasil->bulan);
                $comparison = $hargaService->bandingkan(
                    (int) $user->id_entitas,
                    (int) $hasil->id_komoditas,
                    (int) $hasil->tahun,
                    $periode,
                );

                return [
                    'hasil' => $hasil,
                    'sektor' => in_array($hasil->komoditas?->kategori, ['pertanian', 'perkebunan', 'peternakan'], true)
                        ? $hasil->komoditas->kategori
                        : 'lainnya',
                    'periode' => $periode,
                    'calonSupplier' => $hargaService->calonSupplier(
                        (int) $hasil->id_komoditas,
                        (int) $hasil->tahun,
                        $periode,
                    ),
                    'hargaTerendah' => $comparison['lowest'],
                ];
            });
        $calonPerencanaanSektor = $calonPerencanaan
            ->groupBy('sektor')
            ->sortKeys()
            ->map(function ($items) {
                return $items
                    ->groupBy(fn (array $rencana): int => (int) $rencana['hasil']->id_komoditas)
                    ->map(fn ($komoditasItems) => $komoditasItems->sortBy([
                        ['periode', 'asc'],
                        ['hasil.bulan', 'asc'],
                    ])->values())
                    ->sortBy(fn ($komoditasItems) => $komoditasItems->first()['hasil']->komoditas?->nama ?? '')
                    ->values();
            });

        return view('perencanaan.permintaan-pengadaan.index', [
            'title' => $this->title,
            'items' => $items,
            'calonPerencanaanSektor' => $calonPerencanaanSektor,
        ]);
    }

    public function show(int|string $id): View
    {
        $item = PermintaanPengadaan::query()
            ->with(['pihak', 'unitUsaha', 'gudang', 'detail.barang', 'detail'])
            ->findOrFail($id);

        return view('perencanaan.permintaan-pengadaan.show', [
            'title' => 'Detail '.$this->title,
            'item' => $item,
        ]);
    }

    public function create(): View
    {
        $hargaService = app(PerbandinganHargaService::class);
        $idHasil = request()->query('id_hasil');
        $hasil = $idHasil ? HasilKalkulasi::with(['komoditas', 'wilayah'])->find($idHasil) : null;

        if ($idHasil !== null && ! $hasil) {
            abort(404, 'Hasil kalkulasi tidak ditemukan.');
        }

        if ($hasil) {
            $this->assertHasilBisaDiproses($hasil);
        }

        $barang = $hasil
            ? Barang::query()
                ->with('satuanDasar')
                ->where('id_komoditas', $hasil->id_komoditas)
                ->where('is_active', true)
                ->first()
            : null;
        $user = auth()->user();
        $gudangs = $user
            ? Gudang::query()->where('id_entitas', $user->id_entitas)->where('is_active', true)->orderBy('nama_gudang')->get()
            : collect();
        $satuanBarang = $barang?->satuanDasar?->kode_satuan;
        $hargaComparison = $hasil
            ? $hargaService->bandingkan(
                (int) $user->id_entitas,
                (int) $hasil->id_komoditas,
                (int) $hasil->tahun,
                PeriodeKalkulasi::fromMonth((int) $hasil->bulan),
                $satuanBarang,
            )
            : ['items' => collect(), 'lowest' => null, 'highest' => null, 'difference' => null, 'satuan' => null];

        return view('perencanaan.permintaan-pengadaan.create', [
            'title' => 'PR - Pengadaan Defisit',
            'routeBase' => $this->routeBase,
            'hasil' => $hasil,
            'periodeLabel' => $hasil
                ? PeriodeKalkulasi::detail(PeriodeKalkulasi::fromMonth((int) $hasil->bulan))['label'].' '.$hasil->tahun
                : null,
            'barang' => $barang,
            'jumlahDiminta' => $hasil ? max(0, abs((float) $hasil->selisih)) : 0,
            'hargaPerkiraan' => $hargaComparison['lowest'],
            'satuanHarga' => $hargaComparison['satuan'],
            'tanggalPengajuan' => now()->toDateString(),
            'gudangs' => $gudangs,
            'calonSupplier' => $hasil
                ? $hargaService->calonSupplier((int) $hasil->id_komoditas, (int) $hasil->tahun, PeriodeKalkulasi::fromMonth((int) $hasil->bulan))
                : collect(),
            'perbandinganHarga' => $hargaComparison['items']->keyBy('id_wilayah_sumber'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $hargaService = app(PerbandinganHargaService::class);
        $validated = $request->validate([
            'id_hasil' => ['required', 'integer', 'exists:hasil_kalkulasi,id_hasil'],
            'id_barang' => ['required', 'integer', 'exists:master_barang,id_barang'],
            'id_gudang' => ['required', 'integer', 'exists:gudang,id_gudang'],
            'tgl_pengajuan' => ['required', 'date'],
            'catatan' => ['nullable', 'string', 'max:5000'],
        ]);
        $hasil = HasilKalkulasi::with('komoditas')->findOrFail((int) $validated['id_hasil']);
        $this->assertHasilBisaDiproses($hasil);
        $user = auth()->user();

        $barang = Barang::query()->whereKey($validated['id_barang'])
            ->with('satuanDasar')
            ->where('id_komoditas', $hasil->id_komoditas)->where('is_active', true)->firstOrFail();
        $gudang = Gudang::query()->whereKey($validated['id_gudang'])
            ->where('id_entitas', $user->id_entitas)->where('is_active', true)->firstOrFail();

        if (! $hasil->id_unit_usaha_rekomendasi) {
            return back()->withInput()->withErrors(['id_hasil' => 'Hasil kalkulasi belum memiliki unit usaha rekomendasi yang valid.']);
        }
        if (! UnitUsaha::query()->whereKey($hasil->id_unit_usaha_rekomendasi)->exists()) {
            return back()->withInput()->withErrors(['id_hasil' => 'Unit usaha rekomendasi tidak ditemukan.']);
        }

        $jumlah = abs((float) $hasil->selisih);
        if ($jumlah <= 0) {
            return back()->withInput()->withErrors(['id_hasil' => 'Jumlah kebutuhan defisit tidak valid.']);
        }
        $comparison = $hargaService->bandingkan(
            (int) $user->id_entitas,
            (int) $hasil->id_komoditas,
            (int) $hasil->tahun,
            PeriodeKalkulasi::fromMonth((int) $hasil->bulan),
            $barang->satuanDasar?->kode_satuan,
        );
        $harga = $comparison['lowest'] ?? 0;
        $total = $jumlah * $harga;

        DB::transaction(function () use ($validated, $user, $hasil, $barang, $gudang, $jumlah, $harga, $total): void {
            $pr = PermintaanPengadaan::create([
                'id_entitas' => $user->id_entitas,
                'kode_permintaan' => PermintaanPengadaan::generateKode((int) $user->id_entitas),
                'tgl_pengajuan' => $validated['tgl_pengajuan'],
                'id_unit_usaha' => $hasil->id_unit_usaha_rekomendasi,
                'id_gudang' => $gudang->id_gudang,
                'total_nilai' => $total,
                'status' => 'draft',
                'catatan' => $validated['catatan'] ?? null,
                'created_by' => auth()->id(),
            ]);
            $pr->detail()->create([
                'id_barang' => $barang->id_barang,
                'id_hasil' => $hasil->id_hasil,
                'jumlah_diminta' => $jumlah,
                'harga_perkiraan' => $harga,
                'subtotal' => $total,
            ]);
        });

        return redirect()->route($this->routeBase . '.index')->with('status', 'Draft PR berhasil disimpan.');
    }

    private function assertHasilBisaDiproses(HasilKalkulasi $hasil): void
    {
        $user = auth()->user();

        if (! $user || ! $user->id_entitas) {
            abort(403, 'Hanya pengguna desa yang dapat membuat PR.');
        }

        $idWilayahUser = $user->entitas?->id_wilayah;

        if ((int) $hasil->id_wilayah !== (int) $idWilayahUser) {
            abort(403, 'Hasil kalkulasi tidak milik koperasi aktif Anda.');
        }

        if ($hasil->status_surplus_defisit !== 'defisit') {
            abort(422, 'Hanya komoditas dengan status defisit yang dapat dibuatkan PR.');
        }
    }
}

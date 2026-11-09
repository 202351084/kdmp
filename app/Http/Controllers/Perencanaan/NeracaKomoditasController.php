<?php

namespace App\Http\Controllers\Perencanaan;

use App\Http\Controllers\Concerns\ModuleCrudController;
use App\Http\Controllers\Concerns\ResolvesWilayahAktif;
use App\Models\Master\Komoditas;
use App\Models\Master\Barang;
use App\Models\Master\Gudang;
use App\Models\Perencanaan\PermintaanPengadaan;
use App\Models\Perencanaan\HasilKalkulasi;
use App\Models\Perencanaan\KebutuhanKomoditas;
use App\Models\Perencanaan\KetersediaanKomoditas;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use App\Support\Perencanaan\PeriodeKalkulasi;

class NeracaKomoditasController extends ModuleCrudController
{
    use ResolvesWilayahAktif;

    protected string $model = HasilKalkulasi::class;
    protected string $view = 'perencanaan.neraca-komoditas';
    protected string $title = 'Surplus Defisit';
    protected string $routeBase = 'perencanaan.neraca-komoditas';
    protected array $withRelations = ['wilayah', 'komoditas'];

    public function index(Request $request): View
    {
        $idWilayah = $this->wilayahAktifId();

        $tahun = (int) $request->query('tahun', 2026);
        $bulanQuery = $request->query('bulan');
        $periode = $request->query('periode');
        $bulan = $periode ? PeriodeKalkulasi::detail($periode)['bulan_awal'] : ($bulanQuery ? (int) $bulanQuery : null);
        $idKomoditas = $request->filled('id_komoditas') ? (int) $request->query('id_komoditas') : null;
        $status = $request->query('status_surplus_defisit');

        $query = HasilKalkulasi::with(['wilayah', 'komoditas'])
            ->where('id_wilayah', $idWilayah)
            ->where('tahun', $tahun);

        $this->onlyLatestVersion($query);

        if ($bulan !== null) {
            $query->where('bulan', $bulan);
        }

        if ($idKomoditas !== null) {
            $query->where('id_komoditas', $idKomoditas);
        }

        if ($status !== null && in_array($status, ['surplus', 'defisit', 'seimbang'], true)) {
            $query->where('status_surplus_defisit', $status);
        }

        if ($search = $request->query('q')) {
            $query->whereHas('komoditas', function ($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%");
            });
        }

        $items = $query->orderBy('tahun', 'desc')
            ->orderBy('bulan', 'desc')
            ->orderBy('id_komoditas')
            ->paginate($this->perPage)
            ->withQueryString();

        $daftarKomoditas = Komoditas::where('is_active', true)->orderBy('nama')->get();

        // Satuan komoditas
        $satuanMap = KebutuhanKomoditas::where('id_wilayah', $idWilayah)
            ->where('tahun', $tahun)
            ->when($bulan, fn ($q) => $q->where('bulan', $bulan))
            ->pluck('satuan', 'id_komoditas')
            ->toArray();

        // Ringkasan status neraca
        $baseQuery = HasilKalkulasi::where('id_wilayah', $idWilayah)
            ->where('tahun', $tahun)
            ->when($bulan, fn ($q) => $q->where('bulan', $bulan));

        $this->onlyLatestVersion($baseQuery);

        $totalSurplus = (clone $baseQuery)->where('status_surplus_defisit', 'surplus')->count();
        $totalDefisit = (clone $baseQuery)->where('status_surplus_defisit', 'defisit')->count();
        $totalSeimbang = (clone $baseQuery)->where('status_surplus_defisit', 'seimbang')->count();

        // Kelengkapan data standar kebutuhan per komoditas
        $kelengkapanStandarRaw = KebutuhanKomoditas::where('id_wilayah', $idWilayah)
            ->where('tahun', $tahun)
            ->when($bulan, fn ($q) => $q->where('bulan', $bulan))
            ->selectRaw('id_komoditas, COUNT(CASE WHEN id_standar IS NOT NULL AND per_kapita_harian > 0 THEN 1 END) as valid_count, COUNT(*) as total_groups')
            ->groupBy('id_komoditas')
            ->get();

        $kelengkapanStandarMap = [];
        $totalKomoditasKurangStandar = 0;
        foreach ($kelengkapanStandarRaw as $row) {
            $kelengkapanStandarMap[$row->id_komoditas] = [
                'valid' => (int) $row->valid_count,
                'total' => (int) $row->total_groups,
                'is_lengkap' => ((int) $row->valid_count > 0 && (int) $row->valid_count >= (int) $row->total_groups),
            ];
            if ((int) $row->valid_count < (int) $row->total_groups) {
                $totalKomoditasKurangStandar++;
            }
        }

        return view('perencanaan.neraca-komoditas.index', [
            'items' => $items,
            'title' => $this->title,
            'routeBase' => $this->routeBase,
            'tahun' => $tahun,
            'bulan' => $bulan,
            'idKomoditas' => $idKomoditas,
            'statusFilter' => $status,
            'daftarKomoditas' => $daftarKomoditas,
            'satuanMap' => $satuanMap,
            'totalSurplus' => $totalSurplus,
            'totalDefisit' => $totalDefisit,
            'totalSeimbang' => $totalSeimbang,
            'kelengkapanStandarMap' => $kelengkapanStandarMap,
            'totalKomoditasKurangStandar' => $totalKomoditasKurangStandar,
        ]);
    }

    private function onlyLatestVersion(Builder $query): Builder
    {
        return $query->whereRaw(
            'hasil_kalkulasi.versi = (SELECT MAX(h2.versi) FROM hasil_kalkulasi h2 WHERE h2.id_wilayah = hasil_kalkulasi.id_wilayah AND h2.id_komoditas = hasil_kalkulasi.id_komoditas AND h2.tahun = hasil_kalkulasi.tahun AND h2.bulan = hasil_kalkulasi.bulan)'
        );
    }

    public function show(int|string $id): View
    {
        $idWilayah = $this->wilayahAktifId();
        $hasil = HasilKalkulasi::with(['wilayah', 'komoditas', 'unitUsahaRekomendasi'])
            ->where('id_wilayah', $idWilayah)
            ->findOrFail($id);

        $rincianKebutuhan = KebutuhanKomoditas::with('standar')
            ->where('id_wilayah', $idWilayah)
            ->where('id_komoditas', $hasil->id_komoditas)
            ->where('tahun', $hasil->tahun)
            ->where('bulan', $hasil->bulan)
            ->orderBy('kelompok_umur')
            ->get();

        $ketersediaan = KetersediaanKomoditas::where('id_wilayah', $idWilayah)
            ->where('id_komoditas', $hasil->id_komoditas)
            ->where('tahun', $hasil->tahun)
            ->where('bulan', $hasil->bulan)
            ->first();

        $satuan = $rincianKebutuhan->first()?->satuan ?? ($ketersediaan?->satuan ?? 'KG');

        $totalKelompok = $rincianKebutuhan->count();
        $kelompokValidStandar = $rincianKebutuhan->filter(fn ($rk) => $rk->id_standar !== null && (float) $rk->per_kapita_harian > 0)->count();
        $standarLengkap = ($totalKelompok > 0 && $kelompokValidStandar >= $totalKelompok);

        return view('perencanaan.neraca-komoditas.show', [
            'hasil' => $hasil,
            'rincianKebutuhan' => $rincianKebutuhan,
            'ketersediaan' => $ketersediaan,
            'satuan' => $satuan,
            'standarLengkap' => $standarLengkap,
            'kelompokValidStandar' => $kelompokValidStandar,
            'totalKelompok' => $totalKelompok,
            'title' => 'Detail Neraca: ' . ($hasil->komoditas?->nama ?? 'Komoditas'),
            'routeBase' => $this->routeBase,
        ]);
    }

    public function ajukanKeGudang(int|string $id): RedirectResponse
    {
        $idWilayah = $this->wilayahAktifId();
        $hasil = HasilKalkulasi::query()
            ->where('id_wilayah', $idWilayah)
            ->whereKey($id)
            ->where('status_surplus_defisit', 'surplus')
            ->with('komoditas')
            ->firstOrFail();
        $user = auth()->user();

        $barang = Barang::query()
            ->where('id_komoditas', $hasil->id_komoditas)
            ->where('is_active', true)
            ->first();
        if (! $barang) {
            return back()->with('error', 'Komoditas surplus belum memiliki master barang aktif untuk diajukan ke gudang.');
        }

        $gudang = Gudang::query()
            ->where('id_entitas', $user->id_entitas)
            ->where('is_active', true)
            ->orderBy('nama_gudang')
            ->first();
        if (! $gudang) {
            return back()->with('error', 'Belum ada gudang aktif untuk entitas ini.');
        }

        $sudahDiajukan = DB::table('permintaan_pengadaan_detail')
            ->join('permintaan_pengadaan', 'permintaan_pengadaan.id_permintaan', '=', 'permintaan_pengadaan_detail.id_permintaan')
            ->where('permintaan_pengadaan_detail.id_hasil', $hasil->id_hasil)
            ->whereNotIn('permintaan_pengadaan.status', ['dibatalkan', 'ditolak'])
            ->exists();
        if ($sudahDiajukan) {
            return back()->with('error', 'Surplus komoditas ini sudah memiliki pengajuan ke gudang yang masih aktif.');
        }

        DB::transaction(function () use ($hasil, $barang, $gudang, $user): void {
            $pengajuan = PermintaanPengadaan::create([
                'id_entitas' => $user->id_entitas,
                'kode_permintaan' => PermintaanPengadaan::generateKode((int) $user->id_entitas),
                'tgl_pengajuan' => now()->toDateString(),
                'id_unit_usaha' => $barang->id_unit_usaha,
                'id_gudang' => $gudang->id_gudang,
                'total_nilai' => 0,
                'status' => 'draft',
                'catatan' => 'Pengajuan penerimaan surplus dari neraca komoditas. Verifikasi volume dan kualitas sebelum penerimaan gudang.',
                'created_by' => auth()->id(),
            ]);
            $pengajuan->detail()->create([
                'id_barang' => $barang->id_barang,
                'id_hasil' => $hasil->id_hasil,
                'jumlah_diminta' => (float) $hasil->selisih,
                'harga_perkiraan' => 0,
                'subtotal' => 0,
            ]);
        });

        return redirect()->route('perencanaan.neraca-komoditas.index', [
            'tahun' => $hasil->tahun,
            'bulan' => $hasil->bulan,
        ])->with('status', 'Pengajuan penerimaan surplus berhasil dibuat sebagai draft untuk diverifikasi gudang.');
    }
}

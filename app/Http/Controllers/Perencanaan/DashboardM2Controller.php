<?php

namespace App\Http\Controllers\Perencanaan;

use App\Http\Controllers\Concerns\ResolvesWilayahAktif;
use App\Http\Controllers\Controller;
use App\Models\Master\Komoditas;
use App\Models\Perencanaan\DemografiDesa;
use App\Models\Perencanaan\HasilKalkulasi;
use App\Models\Perencanaan\KebutuhanKomoditas;
use App\Models\Perencanaan\KetersediaanKomoditas;
use App\Models\Survei\SesiSurvei;
use App\Models\Tenant\Wilayah;
use App\Support\Perencanaan\PeriodeKalkulasi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardM2Controller extends Controller
{
    use ResolvesWilayahAktif;

    public function index(Request $request): View
    {
        $idWilayah = $this->wilayahAktifId();
        $wilayah = Wilayah::find($idWilayah);

        $tahun = (int) $request->query('tahun', 2026);
        $bulanQuery = $request->query('bulan');
        $periode = $request->query('periode');
        $bulan = $periode ? PeriodeKalkulasi::detail($periode)['bulan_awal'] : ($bulanQuery ? (int) $bulanQuery : null);
        $kategori = $request->query('kategori');

        // Query dasar hasil kalkulasi
        $queryHasil = HasilKalkulasi::with(['komoditas', 'wilayah'])
            ->where('id_wilayah', $idWilayah)
            ->where('tahun', $tahun);

        if ($bulan !== null) {
            $queryHasil->where('bulan', $bulan);
        }

        if (!empty($kategori)) {
            $queryHasil->whereHas('komoditas', fn ($q) => $q->where('kategori', $kategori));
        }

        $daftarHasil = (clone $queryHasil)->orderBy('id_komoditas')->get();
        $distribusiNeraca = $daftarHasil
            ->groupBy(fn (HasilKalkulasi $hasil): string => in_array($hasil->komoditas?->kategori, ['pertanian', 'perkebunan', 'peternakan'], true)
                ? (string) $hasil->komoditas->kategori
                : 'lainnya')
            ->sortKeys()
            ->map(function ($hasilSektor) {
                return $hasilSektor
                    ->groupBy('id_komoditas')
                    ->map(function ($periodeKomoditas) {
                        $terbaru = $periodeKomoditas->sortByDesc(fn (HasilKalkulasi $hasil): string => sprintf('%04d-%02d', $hasil->tahun, $hasil->bulan))->first();

                        return [
                            'komoditas' => $terbaru->komoditas,
                            'items' => $periodeKomoditas->sortByDesc('bulan')->values(),
                            'total_kebutuhan' => (float) $periodeKomoditas->sum('total_kebutuhan'),
                            'total_ketersediaan' => (float) $periodeKomoditas->sum('total_ketersediaan'),
                            'status_count' => $periodeKomoditas->countBy('status_surplus_defisit'),
                            'status_terbaru' => $terbaru->status_surplus_defisit,
                        ];
                    })
                    ->sortBy(fn (array $komoditas): string => $komoditas['komoditas']?->nama ?? '')
                    ->values();
            });

        // 1. Ringkasan Status Komoditas
        $totalKomoditasTerhitung = $daftarHasil->count();
        $totalSurplus = $daftarHasil->where('status_surplus_defisit', 'surplus')->count();
        $totalDefisit = $daftarHasil->where('status_surplus_defisit', 'defisit')->count();
        $totalSeimbang = $daftarHasil->where('status_surplus_defisit', 'seimbang')->count();

        // Akumulasi Volume
        $totalKebutuhanVolume = (float) $daftarHasil->sum('total_kebutuhan');
        $totalKetersediaanVolume = (float) $daftarHasil->sum('total_ketersediaan');
        $totalDefisitVolume = (float) $daftarHasil->where('status_surplus_defisit', 'defisit')->sum(fn ($item) => abs((float) $item->selisih));
        $totalSurplusVolume = (float) $daftarHasil->where('status_surplus_defisit', 'surplus')->sum(fn ($item) => (float) $item->selisih);

        // 2. Daftar Komoditas dengan Defisit Terbesar (Top Deficits)
        $komoditasDefisitTerbesar = $daftarHasil
            ->where('status_surplus_defisit', 'defisit')
            ->sortBy('selisih')
            ->take(5)
            ->values();

        // 3. Sumber data survei: demografi
        $dataDemografi = DemografiDesa::where('id_wilayah', $idWilayah)
            ->where('tahun', $tahun)
            ->get();
        $totalPenduduk = $dataDemografi->sum('jumlah_penduduk');
        $adaDataDemografi = $totalPenduduk > 0;

        // Distribusi demografi per kelompok umur
        $demografiPerKelompok = [
            'balita' => $dataDemografi->where('kelompok_umur', 'balita')->sum('jumlah_penduduk'),
            'anak' => $dataDemografi->where('kelompok_umur', 'anak')->sum('jumlah_penduduk'),
            'remaja' => $dataDemografi->where('kelompok_umur', 'remaja')->sum('jumlah_penduduk'),
            'dewasa' => $dataDemografi->where('kelompok_umur', 'dewasa')->sum('jumlah_penduduk'),
            'lansia' => $dataDemografi->where('kelompok_umur', 'lansia')->sum('jumlah_penduduk'),
        ];

        // 4. Sumber data survei: potensi produksi panen
        $jumlahCatatanProduksi = KetersediaanKomoditas::where('id_wilayah', $idWilayah)
            ->where('tahun', $tahun)
            ->when($bulan, fn ($q) => $q->where('bulan', $bulan))
            ->count();

        // 5. Informasi Waktu Kalkulasi & Versi Terakhir
        $kalkulasiTerakhir = HasilKalkulasi::where('id_wilayah', $idWilayah)
            ->where('tahun', $tahun)
            ->when($bulan, fn ($q) => $q->where('bulan', $bulan))
            ->latest('created_at')
            ->first();

        $waktuKalkulasiTerakhir = $kalkulasiTerakhir?->created_at;
        $versiAktif = $kalkulasiTerakhir?->versi ?? 1;

        // Sesi survei terakhir untuk wilayah ini
        $sesiSurveiTerakhir = SesiSurvei::where('id_wilayah', $idWilayah)
            ->latest('tanggal_survei')
            ->first();

        // Master Data untuk Dropdown & Filter
        $daftarKomoditas = Komoditas::where('is_active', true)->orderBy('nama')->get();
        $daftarKategori = Komoditas::where('is_active', true)
            ->whereNotNull('kategori')
            ->select('kategori')
            ->distinct()
            ->pluck('kategori');

        // Persentase Distribusi untuk Visualisasi Donut / Bar
        $persenSurplus = $totalKomoditasTerhitung > 0 ? round(($totalSurplus / $totalKomoditasTerhitung) * 100, 1) : 0;
        $persenDefisit = $totalKomoditasTerhitung > 0 ? round(($totalDefisit / $totalKomoditasTerhitung) * 100, 1) : 0;
        $persenSeimbang = $totalKomoditasTerhitung > 0 ? round(($totalSeimbang / $totalKomoditasTerhitung) * 100, 1) : 0;

        return view('perencanaan.dashboard.index', [
            'wilayah' => $wilayah,
            'tahun' => $tahun,
            'bulan' => $bulan,
            'periode' => $periode,
            'kategori' => $kategori,
            'daftarHasil' => $daftarHasil,
            'distribusiNeraca' => $distribusiNeraca,
            'totalKomoditasTerhitung' => $totalKomoditasTerhitung,
            'totalSurplus' => $totalSurplus,
            'totalDefisit' => $totalDefisit,
            'totalSeimbang' => $totalSeimbang,
            'totalKebutuhanVolume' => $totalKebutuhanVolume,
            'totalKetersediaanVolume' => $totalKetersediaanVolume,
            'totalDefisitVolume' => $totalDefisitVolume,
            'totalSurplusVolume' => $totalSurplusVolume,
            'komoditasDefisitTerbesar' => $komoditasDefisitTerbesar,
            'totalPenduduk' => $totalPenduduk,
            'adaDataDemografi' => $adaDataDemografi,
            'demografiPerKelompok' => $demografiPerKelompok,
            'jumlahCatatanProduksi' => $jumlahCatatanProduksi,
            'waktuKalkulasiTerakhir' => $waktuKalkulasiTerakhir,
            'versiAktif' => $versiAktif,
            'sesiSurveiTerakhir' => $sesiSurveiTerakhir,
            'daftarKomoditas' => $daftarKomoditas,
            'daftarKategori' => $daftarKategori,
            'persenSurplus' => $persenSurplus,
            'persenDefisit' => $persenDefisit,
            'persenSeimbang' => $persenSeimbang,
        ]);
    }
}

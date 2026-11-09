<?php

namespace App\Http\Controllers\Perencanaan;

use App\Http\Controllers\Concerns\ModuleCrudController;
use App\Http\Controllers\Concerns\ResolvesWilayahAktif;
use App\Models\Master\Komoditas;
use App\Models\Perencanaan\DemografiDesa;
use App\Models\Perencanaan\HasilKalkulasi;
use App\Models\Perencanaan\KebutuhanKomoditas;
use App\Models\Perencanaan\KetersediaanKomoditas;
use App\Services\Perencanaan\KalkulasiKebutuhanService;
use App\Support\Perencanaan\PeriodeKalkulasi;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

class KebutuhanKomoditasController extends ModuleCrudController
{
    use ResolvesWilayahAktif;

    protected string $model = KebutuhanKomoditas::class;
    protected string $view = 'perencanaan.kebutuhan-komoditas';
    protected string $title = 'Kebutuhan Komoditas (Hasil Kalkulasi)';
    protected string $routeBase = 'perencanaan.kebutuhan-komoditas';
    protected array $withRelations = ['wilayah', 'komoditas', 'standar'];

    public function index(Request $request): View
    {
        $idWilayah = $this->wilayahAktifId();

        $tahun = (int) $request->query('tahun', 2026);
        $bulanQuery = $request->query('bulan');
        $periode = $request->query('periode');
        $bulan = $periode ? PeriodeKalkulasi::detail($periode)['bulan_awal'] : ($bulanQuery ? (int) $bulanQuery : null);
        $idKomoditas = $request->filled('id_komoditas') ? (int) $request->query('id_komoditas') : null;

        $query = KebutuhanKomoditas::with(['wilayah', 'komoditas', 'standar'])
            ->where('id_wilayah', $idWilayah)
            ->where('tahun', $tahun);

        if ($bulan !== null) {
            $query->where('bulan', $bulan);
        }

        if ($idKomoditas !== null) {
            $query->where('id_komoditas', $idKomoditas);
        }

        if ($search = $request->query('q')) {
            $query->whereHas('komoditas', function ($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%");
            });
        }

        $items = $query->orderBy('id_komoditas')
            ->orderBy('bulan')
            ->orderBy('kelompok_umur')
            ->get();

        // Data pendukung untuk filter dan kalkulasi
        $daftarKomoditas = Komoditas::where('is_active', true)->orderBy('nama')->get();
        $totalPendudukTahunIni = DemografiDesa::where('id_wilayah', $idWilayah)
            ->where('tahun', $tahun)
            ->sum('jumlah_penduduk');

        $adaDataDemografi = $totalPendudukTahunIni > 0;

        $ringkasanKebutuhan = KebutuhanKomoditas::where('id_wilayah', $idWilayah)
            ->where('tahun', $tahun)
            ->when($bulan, fn ($q) => $q->where('bulan', $bulan))
            ->when($idKomoditas, fn ($q) => $q->where('id_komoditas', $idKomoditas))
            ->sum('kebutuhan_bulanan');

        $totalKomoditasTerhitung = KebutuhanKomoditas::where('id_wilayah', $idWilayah)
            ->where('tahun', $tahun)
            ->when($bulan, fn ($q) => $q->where('bulan', $bulan))
            ->distinct('id_komoditas')
            ->count('id_komoditas');

        $totalBarisTanpaStandar = KebutuhanKomoditas::where('id_wilayah', $idWilayah)
            ->where('tahun', $tahun)
            ->when($bulan, fn ($q) => $q->where('bulan', $bulan))
            ->when($idKomoditas, fn ($q) => $q->where('id_komoditas', $idKomoditas))
            ->where(function ($q) {
                $q->whereNull('id_standar')->orWhere('per_kapita_harian', '<=', 0);
            })
            ->count();

        return view('perencanaan.kebutuhan-komoditas.index', [
            'items' => $items,
            'title' => $this->title,
            'routeBase' => $this->routeBase,
            'tahun' => $tahun,
            'bulan' => $bulan,
            'idKomoditas' => $idKomoditas,
            'daftarKomoditas' => $daftarKomoditas,
            'adaDataDemografi' => $adaDataDemografi,
            'totalPenduduk' => $totalPendudukTahunIni,
            'ringkasanKebutuhan' => $ringkasanKebutuhan,
            'totalKomoditasTerhitung' => $totalKomoditasTerhitung,
            'totalBarisTanpaStandar' => $totalBarisTanpaStandar,
        ]);
    }

    /**
     * Menjalankan kalkulasi kebutuhan bulanan komoditas via service.
     */
    public function kalkulasi(Request $request, KalkulasiKebutuhanService $service): RedirectResponse
    {
        $validated = $request->validate([
            'tahun' => ['required', 'integer', 'min:2020', 'max:2100'],
            'periode' => ['nullable', 'in:ganjil,genap'],
            'bulan' => ['nullable', 'integer', 'min:1', 'max:12'],
            'id_komoditas' => ['nullable', 'exists:komoditas,id'],
            'redirect_to' => ['nullable', 'string', 'in:dashboard,kebutuhan,neraca'],
        ]);
        $legacyBulan = ($validated['periode'] ?? null) === null && isset($validated['bulan'])
            ? (int) $validated['bulan']
            : null;
        $validated['periode'] ??= $legacyBulan !== null
            ? PeriodeKalkulasi::fromMonth($legacyBulan)
            : null;
        if ($validated['periode'] === null) {
            return back()->withErrors(['periode' => 'Periode perencanaan wajib dipilih.'])->withInput();
        }

        try {
            $idWilayah = $this->wilayahAktifId();
            $hasil = $service->hitungKebutuhan(
                $idWilayah,
                (int) $validated['tahun'],
                $legacyBulan ?? $validated['periode'],
                !empty($validated['id_komoditas']) ? (int) $validated['id_komoditas'] : null
            );

            $labelPeriode = PeriodeKalkulasi::detail($validated['periode'])['label'];
            $pesan = "Kalkulasi kebutuhan berhasil dijalankan untuk {$labelPeriode} {$validated['tahun']}. Terhitung {$hasil['total_baris_kebutuhan']} baris kebutuhan dan diperbarui {$hasil['total_hasil_kalkulasi']} neraca komoditas.";

            $redirectTo = $validated['redirect_to'] ?? 'kebutuhan';

            if ($redirectTo === 'dashboard') {
                return redirect()->route('perencanaan.dashboard', [
                    'tahun' => $validated['tahun'],
                    'periode' => $validated['periode'],
                ])->with('status', $pesan);
            }

            if ($redirectTo === 'neraca') {
                return redirect()->route('perencanaan.neraca-komoditas.index', [
                    'tahun' => $validated['tahun'],
                    'periode' => $validated['periode'],
                    'id_komoditas' => $validated['id_komoditas'] ?? null,
                ])->with('status', $pesan);
            }

            return redirect()->route('perencanaan.kebutuhan-komoditas.index', [
                'tahun' => $validated['tahun'],
                'periode' => $validated['periode'],
                'id_komoditas' => $validated['id_komoditas'] ?? null,
            ])->with('status', $pesan);
        } catch (Throwable $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }
    }

    public function show(int|string $id): View
    {
        $idWilayah = $this->wilayahAktifId();
        $item = KebutuhanKomoditas::with(['wilayah', 'komoditas', 'standar'])
            ->where('id_wilayah', $idWilayah)
            ->findOrFail($id);

        $rincianKelompok = KebutuhanKomoditas::with('standar')
            ->where('id_wilayah', $idWilayah)
            ->where('id_komoditas', $item->id_komoditas)
            ->where('tahun', $item->tahun)
            ->where('bulan', $item->bulan)
            ->orderBy('kelompok_umur')
            ->get();

        $hasilNeraca = HasilKalkulasi::where('id_wilayah', $idWilayah)
            ->where('id_komoditas', $item->id_komoditas)
            ->where('tahun', $item->tahun)
            ->where('bulan', $item->bulan)
            ->latest('versi')
            ->first();

        $ketersediaan = KetersediaanKomoditas::where('id_wilayah', $idWilayah)
            ->where('id_komoditas', $item->id_komoditas)
            ->where('tahun', $item->tahun)
            ->where('bulan', $item->bulan)
            ->first();

        $totalKelompok = $rincianKelompok->count();
        $kelompokValidStandar = $rincianKelompok->filter(fn ($rk) => $rk->id_standar !== null && (float) $rk->per_kapita_harian > 0)->count();
        $standarLengkap = ($totalKelompok > 0 && $kelompokValidStandar >= $totalKelompok);

        return view('perencanaan.kebutuhan-komoditas.show', [
            'item' => $item,
            'rincianKelompok' => $rincianKelompok,
            'hasilNeraca' => $hasilNeraca,
            'ketersediaan' => $ketersediaan,
            'standarLengkap' => $standarLengkap,
            'kelompokValidStandar' => $kelompokValidStandar,
            'totalKelompok' => $totalKelompok,
            'title' => 'Detail Kalkulasi: ' . ($item->komoditas?->nama ?? 'Komoditas'),
            'routeBase' => $this->routeBase,
        ]);
    }
}

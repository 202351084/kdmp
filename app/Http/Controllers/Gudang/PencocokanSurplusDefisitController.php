<?php

namespace App\Http\Controllers\Gudang;

use App\Http\Controllers\Concerns\ResolvesWilayahAktif;
use App\Http\Controllers\Controller;
use App\Models\Perencanaan\HasilKalkulasi;
use App\Models\Perencanaan\KebutuhanKomoditas;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PencocokanSurplusDefisitController extends Controller
{
    use ResolvesWilayahAktif;

    public function index(Request $request): View
    {
        $validated = $request->validate([
            'tahun' => ['nullable', 'integer', 'between:2000,2100'],
            'bulan' => ['nullable', 'integer', 'between:1,12'],
        ]);

        $idWilayah = $this->wilayahAktifId();
        $tahun = (int) ($validated['tahun'] ?? now()->year);
        $bulan = isset($validated['bulan']) ? (int) $validated['bulan'] : null;

        $hasilTerbaru = HasilKalkulasi::query()
            ->with(['komoditas', 'wilayah'])
            ->where('tahun', $tahun)
            ->when($bulan !== null, fn (Builder $query) => $query->where('bulan', $bulan))
            ->where('status', 'disetujui')
            ->whereHas('wilayah', fn (Builder $query) => $query
                ->where('tingkat', 'desa')
                ->whereHas('entitas', fn (Builder $entitas) => $entitas
                    ->where('is_active', true)
                    ->where('status', 'APPROVED')))
            ->whereRaw(
                'hasil_kalkulasi.versi = (SELECT MAX(h2.versi) FROM hasil_kalkulasi h2 WHERE h2.id_wilayah = hasil_kalkulasi.id_wilayah AND h2.id_komoditas = hasil_kalkulasi.id_komoditas AND h2.tahun = hasil_kalkulasi.tahun AND h2.bulan = hasil_kalkulasi.bulan)'
            )
            ->orderBy('bulan')
            ->orderBy('id_komoditas')
            ->get();

        $neracaDesaSaya = $hasilTerbaru
            ->where('id_wilayah', $idWilayah)
            ->whereIn('status_surplus_defisit', ['surplus', 'defisit'])
            ->values();

        $pasangan = $neracaDesaSaya->mapWithKeys(function (HasilKalkulasi $hasil) use ($hasilTerbaru): array {
            $statusTujuan = $hasil->status_surplus_defisit === 'surplus' ? 'defisit' : 'surplus';
            $jumlahSaya = abs((float) $hasil->selisih);

            $kandidat = $hasilTerbaru
                ->filter(fn (HasilKalkulasi $calon) => (int) $calon->id_wilayah !== (int) $hasil->id_wilayah
                    && (int) $calon->id_komoditas === (int) $hasil->id_komoditas
                    && (int) $calon->bulan === (int) $hasil->bulan
                    && $calon->status_surplus_defisit === $statusTujuan)
                ->map(fn (HasilKalkulasi $calon) => [
                    'hasil' => $calon,
                    'jumlah_cocok' => min($jumlahSaya, abs((float) $calon->selisih)),
                ])
                ->values();

            return [$hasil->id_hasil => $kandidat];
        });

        $satuanMap = KebutuhanKomoditas::query()
            ->where('id_wilayah', $idWilayah)
            ->where('tahun', $tahun)
            ->when($bulan !== null, fn (Builder $query) => $query->where('bulan', $bulan))
            ->orderByDesc('bulan')
            ->pluck('satuan', 'id_komoditas');

        return view('gudang.pencocokan-surplus-defisit', [
            'neracaDesaSaya' => $neracaDesaSaya,
            'pasangan' => $pasangan,
            'tahun' => $tahun,
            'bulan' => $bulan,
            'satuanMap' => $satuanMap,
        ]);
    }
}

<?php

namespace App\Http\Controllers\Perencanaan;

use App\Http\Controllers\Concerns\ResolvesWilayahAktif;
use App\Http\Controllers\Controller;
use App\Models\Master\Komoditas;
use App\Models\Perencanaan\DemografiDesa;
use App\Models\Perencanaan\HasilKalkulasi;
use App\Models\Perencanaan\KebutuhanKomoditas;
use App\Models\Tenant\Wilayah;
use App\Support\Perencanaan\PeriodeKalkulasi;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LaporanM2Controller extends Controller
{
    use ResolvesWilayahAktif;

    public function index(Request $request): View
    {
        $idWilayah = $this->wilayahAktifId();
        $wilayah = Wilayah::find($idWilayah);

        $tahun = (int) $request->query('tahun', 2026);
        $bulanQuery = $request->query('bulan');
        $validatedQuery = $request->validate([
            'periode' => ['nullable', 'in:ganjil,genap'],
        ]);
        $periode = $validatedQuery['periode'] ?? 'ganjil';
        $detailPeriode = PeriodeKalkulasi::detail($periode);
        $bulan = $bulanQuery ? (int) $bulanQuery : null;
        $kategori = $request->query('kategori');
        $status = $request->query('status');

        $query = HasilKalkulasi::with(['komoditas', 'wilayah'])
            ->where('id_wilayah', $idWilayah)
            ->where('tahun', $tahun);

        if ($bulan !== null) {
            $query->where('bulan', $bulan);
        } else {
            $query->whereIn('bulan', $detailPeriode['bulan']);
        }

        if (!empty($kategori)) {
            $query->whereHas('komoditas', fn ($q) => $q->where('kategori', $kategori));
        }

        if (!empty($status) && in_array($status, ['surplus', 'defisit', 'seimbang'], true)) {
            $query->where('status_surplus_defisit', $status);
        }

        $items = $query->orderBy('bulan')->orderBy('id_komoditas')->get();

        // Cari satuan per komoditas dari kebutuhan_komoditas
        $satuanMap = KebutuhanKomoditas::where('id_wilayah', $idWilayah)
            ->where('tahun', $tahun)
            ->when($bulan, fn ($q) => $q->where('bulan', $bulan))
            ->pluck('satuan', 'id_komoditas')
            ->toArray();

        // Agregasi
        $totalSurplus = $items->where('status_surplus_defisit', 'surplus')->count();
        $totalDefisit = $items->where('status_surplus_defisit', 'defisit')->count();
        $totalSeimbang = $items->where('status_surplus_defisit', 'seimbang')->count();

        $totalKebutuhan = (float) $items->sum('total_kebutuhan');
        $totalKetersediaan = (float) $items->sum('total_ketersediaan');
        $totalDefisitVolume = (float) $items->where('status_surplus_defisit', 'defisit')->sum(fn ($i) => abs((float) $i->selisih));
        $totalSurplusVolume = (float) $items->where('status_surplus_defisit', 'surplus')->sum(fn ($i) => (float) $i->selisih);

        $totalPenduduk = DemografiDesa::where('id_wilayah', $idWilayah)
            ->where('tahun', $tahun)
            ->sum('jumlah_penduduk');

        $daftarKategori = Komoditas::where('is_active', true)
            ->whereNotNull('kategori')
            ->select('kategori')
            ->distinct()
            ->pluck('kategori');

        return view('perencanaan.laporan.index', [
            'items' => $items,
            'satuanMap' => $satuanMap,
            'wilayah' => $wilayah,
            'tahun' => $tahun,
            'bulan' => $bulan,
            'periode' => $periode,
            'kategori' => $kategori,
            'statusFilter' => $status,
            'daftarKategori' => $daftarKategori,
            'totalSurplus' => $totalSurplus,
            'totalDefisit' => $totalDefisit,
            'totalSeimbang' => $totalSeimbang,
            'totalKebutuhan' => $totalKebutuhan,
            'totalKetersediaan' => $totalKetersediaan,
            'totalDefisitVolume' => $totalDefisitVolume,
            'totalSurplusVolume' => $totalSurplusVolume,
            'totalPenduduk' => $totalPenduduk,
        ]);
    }
}

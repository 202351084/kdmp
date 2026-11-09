<?php

namespace App\Http\Controllers\Perencanaan;

use App\Http\Controllers\Concerns\ResolvesWilayahAktif;
use App\Http\Controllers\Controller;
use App\Models\Perencanaan\DemografiDesa;
use App\Models\Survei\SesiSurvei;
use App\Models\Tenant\Wilayah;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DemografiController extends Controller
{
    use ResolvesWilayahAktif;

    public function index(Request $request): View
    {
        $idWilayah = $this->wilayahAktifId();
        $wilayah = Wilayah::find($idWilayah);

        $tahun = (int) $request->query('tahun', 2026);

        $records = DemografiDesa::with('sesiSurvei')
            ->where('id_wilayah', $idWilayah)
            ->where('tahun', $tahun)
            ->get();

        $daftarTahun = DemografiDesa::where('id_wilayah', $idWilayah)
            ->distinct()
            ->orderBy('tahun', 'desc')
            ->pluck('tahun')
            ->toArray();

        if (!in_array($tahun, $daftarTahun, true)) {
            $daftarTahun[] = $tahun;
            rsort($daftarTahun);
        }

        $kelompokUmurList = ['balita', 'anak', 'remaja', 'dewasa', 'lansia'];
        $populasiMap = [];
        $totalPenduduk = 0;

        foreach ($kelompokUmurList as $ku) {
            $rec = $records->firstWhere('kelompok_umur', $ku);
            $jml = $rec ? (int) $rec->jumlah_penduduk : 0;
            $totalPenduduk += $jml;
            $populasiMap[$ku] = [
                'record' => $rec,
                'jumlah' => $jml,
            ];
        }

        $sesiSurveiM1 = SesiSurvei::where('id_wilayah', $idWilayah)
            ->where('tahun', $tahun)
            ->latest('tanggal_survei')
            ->first();

        return view('perencanaan.demografi.index', [
            'wilayah' => $wilayah,
            'tahun' => $tahun,
            'daftarTahun' => $daftarTahun,
            'records' => $records,
            'populasiMap' => $populasiMap,
            'totalPenduduk' => $totalPenduduk,
            'sesiSurveiM1' => $sesiSurveiM1,
            'kelompokUmurList' => $kelompokUmurList,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'tahun' => ['required', 'integer', 'min:2020', 'max:2100'],
            'jumlah_penduduk' => ['required', 'array'],
            'jumlah_penduduk.*' => ['required', 'integer', 'min:0'],
        ]);

        $idWilayah = $this->wilayahAktifId();

        foreach (['balita', 'anak', 'remaja', 'dewasa', 'lansia'] as $ku) {
            if (isset($validated['jumlah_penduduk'][$ku])) {
                DemografiDesa::updateOrCreate(
                    [
                        'id_wilayah' => $idWilayah,
                        'tahun' => (int) $validated['tahun'],
                        'kelompok_umur' => $ku,
                    ],
                    [
                        'jumlah_penduduk' => (int) $validated['jumlah_penduduk'][$ku],
                        'created_at' => now(),
                    ]
                );
            }
        }

        return redirect()->route('perencanaan.demografi.index', ['tahun' => $validated['tahun']])
            ->with('status', "Data demografi penduduk tahun {$validated['tahun']} berhasil diperbarui.");
    }

    public function update(Request $request, int|string $id): RedirectResponse
    {
        $idWilayah = $this->wilayahAktifId();
        $record = DemografiDesa::where('id_wilayah', $idWilayah)->findOrFail($id);

        $validated = $request->validate([
            'jumlah_penduduk' => ['required', 'integer', 'min:0'],
        ]);

        $record->update(['jumlah_penduduk' => (int) $validated['jumlah_penduduk']]);

        return back()->with('status', "Populasi kelompok umur " . ucfirst($record->kelompok_umur) . " berhasil diperbarui.");
    }
}


<?php

namespace App\Http\Controllers\Perencanaan;

use App\Http\Controllers\Concerns\ResolvesWilayahAktif;
use App\Http\Controllers\Controller;
use App\Models\Master\Komoditas;
use App\Models\Perencanaan\KetersediaanKomoditas;
use App\Models\Tenant\Wilayah;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;

class PotensiProduksiController extends Controller
{
    use ResolvesWilayahAktif;

    public function index(Request $request): View
    {
        $idWilayah = $this->wilayahAktifId();
        $wilayah = Wilayah::find($idWilayah);

        $tahun = (int) $request->query('tahun', 2026);
        $bulan = $request->filled('bulan') ? (int) $request->query('bulan') : null;
        $idKomoditas = $request->filled('id_komoditas') ? (int) $request->query('id_komoditas') : null;
        $search = $request->query('q');

        $query = KetersediaanKomoditas::with(['komoditas', 'sesiSurvei'])
            ->where('id_wilayah', $idWilayah)
            ->where('tahun', $tahun);

        if ($bulan !== null) {
            $query->where('bulan', $bulan);
        }

        if ($idKomoditas !== null) {
            $query->where('id_komoditas', $idKomoditas);
        }

        if (!empty($search)) {
            $query->whereHas('komoditas', fn ($q) => $q->where('nama', 'like', "%{$search}%"));
        }

        $items = (clone $query)->orderBy('bulan', 'asc')
            ->orderBy('id_komoditas', 'asc')
            ->paginate(15)
            ->withQueryString();

        $baseStat = KetersediaanKomoditas::where('id_wilayah', $idWilayah)->where('tahun', $tahun);
        $totalCatatan = (clone $baseStat)->when($bulan, fn ($q) => $q->where('bulan', $bulan))->count();
        $totalVolume = (float) (clone $baseStat)->when($bulan, fn ($q) => $q->where('bulan', $bulan))->sum('jumlah_produksi');
        $jumlahKomoditas = (clone $baseStat)->when($bulan, fn ($q) => $q->where('bulan', $bulan))->distinct('id_komoditas')->count('id_komoditas');

        $daftarKomoditas = Komoditas::where('is_active', true)->orderBy('nama')->get();
        $daftarSatuan = DB::table('satuan')->where('is_active', true)->pluck('kode_satuan')->toArray();
        if (empty($daftarSatuan)) {
            $daftarSatuan = ['KG', 'LTR', 'BUTIR', 'TON', 'IKAT'];
        }

        return view('perencanaan.potensi-produksi.index', [
            'items' => $items,
            'wilayah' => $wilayah,
            'tahun' => $tahun,
            'bulan' => $bulan,
            'idKomoditas' => $idKomoditas,
            'search' => $search,
            'daftarKomoditas' => $daftarKomoditas,
            'daftarSatuan' => $daftarSatuan,
            'totalCatatan' => $totalCatatan,
            'totalVolume' => $totalVolume,
            'jumlahKomoditas' => $jumlahKomoditas,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'tahun' => ['required', 'integer', 'min:2020', 'max:2100'],
            'bulan' => ['required', 'integer', 'min:1', 'max:12'],
            'id_komoditas' => ['required', 'exists:komoditas,id'],
            'jumlah_produksi' => ['required', 'numeric', 'min:0'],
            'satuan' => ['required', 'string', 'max:20'],
        ]);

        $idWilayah = $this->wilayahAktifId();

        KetersediaanKomoditas::updateOrCreate(
            [
                'id_wilayah' => $idWilayah,
                'id_komoditas' => (int) $validated['id_komoditas'],
                'tahun' => (int) $validated['tahun'],
                'bulan' => (int) $validated['bulan'],
            ],
            [
                'jumlah_produksi' => (float) $validated['jumlah_produksi'],
                'satuan' => strtoupper(trim($validated['satuan'])),
                'created_at' => now(),
            ]
        );

        $namaBulan = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ][$validated['bulan']] ?? $validated['bulan'];

        return redirect()->route('perencanaan.potensi-produksi.index', [
            'tahun' => $validated['tahun'],
            'bulan' => $validated['bulan'],
        ])->with('status', "Data potensi produksi panen {$namaBulan} {$validated['tahun']} berhasil disimpan.");
    }

    public function storeKomoditas(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:150'],
            'kategori' => ['required', 'in:pertanian,perkebunan,peternakan,perikanan'],
        ]);

        $nama = trim($validated['nama']);
        $komoditas = Komoditas::firstOrCreate(
            ['nama' => $nama],
            ['kategori' => $validated['kategori'], 'is_active' => true]
        );

        return response()->json([
            'id' => $komoditas->id,
            'nama' => $komoditas->nama,
            'kategori' => $komoditas->kategori,
            'created' => $komoditas->wasRecentlyCreated,
        ], $komoditas->wasRecentlyCreated ? 201 : 200);
    }

    public function update(Request $request, int|string $id): RedirectResponse
    {
        $idWilayah = $this->wilayahAktifId();
        $record = KetersediaanKomoditas::where('id_wilayah', $idWilayah)->findOrFail($id);

        $validated = $request->validate([
            'jumlah_produksi' => ['required', 'numeric', 'min:0'],
            'satuan' => ['required', 'string', 'max:20'],
            'kategori' => ['required', 'in:pertanian,perkebunan,peternakan,perikanan'],
        ]);

        $record->update([
            'jumlah_produksi' => (float) $validated['jumlah_produksi'],
            'satuan' => strtoupper(trim($validated['satuan'])),
        ]);
        $record->komoditas()->update(['kategori' => $validated['kategori']]);

        return back()->with('status', "Data produksi panen komoditas " . ($record->komoditas?->nama ?? '') . " berhasil diperbarui.");
    }

    public function destroy(int|string $id): RedirectResponse
    {
        $idWilayah = $this->wilayahAktifId();
        $record = KetersediaanKomoditas::where('id_wilayah', $idWilayah)->findOrFail($id);
        $namaKomoditas = $record->komoditas?->nama ?? 'Komoditas';

        $record->delete();

        return back()->with('status', "Catatan potensi panen {$namaKomoditas} berhasil dihapus.");
    }
}

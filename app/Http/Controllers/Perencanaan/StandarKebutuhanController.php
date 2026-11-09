<?php

namespace App\Http\Controllers\Perencanaan;

use App\Http\Controllers\Controller;
use App\Models\Master\Komoditas;
use App\Models\Perencanaan\StandarKebutuhanKomoditas;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class StandarKebutuhanController extends Controller
{
    public function index(Request $request): View
    {
        $idKomoditas = $request->filled('id_komoditas') ? (int) $request->query('id_komoditas') : null;
        $kelompokUmur = $request->query('kelompok_umur');
        $sektor = $request->query('sektor');
        $search = $request->query('q');

        $query = StandarKebutuhanKomoditas::with('komoditas');

        if ($idKomoditas !== null) {
            $query->where('id_komoditas', $idKomoditas);
        }

        if (!empty($kelompokUmur)) {
            $query->where('kelompok_umur', $kelompokUmur);
        }

        if (!empty($sektor)) {
            $query->where('sektor', $sektor);
        }

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('komoditas', fn ($sub) => $sub->where('nama', 'like', "%{$search}%"))
                  ->orWhere('sumber', 'like', "%{$search}%");
            });
        }

        $items = (clone $query)->orderByRaw("CASE kelompok_umur
                WHEN 'balita' THEN 1
                WHEN 'anak' THEN 2
                WHEN 'remaja' THEN 3
                WHEN 'dewasa' THEN 4
                WHEN 'lansia' THEN 5
                ELSE 99 END")
            ->orderBy('id_komoditas', 'asc')
            ->orderBy('berlaku_mulai', 'desc')
            ->get();

        $totalStandar = StandarKebutuhanKomoditas::count();
        $jumlahKomoditasTerstandar = StandarKebutuhanKomoditas::distinct('id_komoditas')->count('id_komoditas');
        $daftarSektor = StandarKebutuhanKomoditas::distinct('sektor')->pluck('sektor')->toArray();

        $daftarKomoditas = Komoditas::where('is_active', true)->orderBy('nama')->get();
        $daftarSatuan = DB::table('satuan')->where('is_active', true)->pluck('kode_satuan')->toArray();
        if (empty($daftarSatuan)) {
            $daftarSatuan = ['KG', 'LTR', 'BUTIR', 'GRAM', 'PORSI'];
        }

        return view('perencanaan.standar-kebutuhan.index', [
            'items' => $items,
            'idKomoditas' => $idKomoditas,
            'kelompokUmur' => $kelompokUmur,
            'sektor' => $sektor,
            'search' => $search,
            'totalStandar' => $totalStandar,
            'jumlahKomoditasTerstandar' => $jumlahKomoditasTerstandar,
            'daftarSektor' => $daftarSektor,
            'daftarKomoditas' => $daftarKomoditas,
            'daftarSatuan' => $daftarSatuan,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'sektor' => ['required', 'string', 'max:50'],
            'id_komoditas' => ['required', 'exists:komoditas,id'],
            'kelompok_umur' => ['required', 'in:balita,anak,remaja,dewasa,lansia'],
            'per_kapita_harian' => ['required', 'numeric', 'min:0'],
            'satuan' => ['required', 'string', 'max:20'],
            'satuan_periode' => ['required', 'in:harian,bulanan'],
            'sumber' => ['nullable', 'string', 'max:150'],
            'berlaku_mulai' => ['required', 'date'],
            'berlaku_sampai' => ['nullable', 'date', 'after_or_equal:berlaku_mulai'],
        ]);

        StandarKebutuhanKomoditas::create([
            'sektor' => strtolower(trim($validated['sektor'])),
            'id_komoditas' => (int) $validated['id_komoditas'],
            'kelompok_umur' => $validated['kelompok_umur'],
            'per_kapita_harian' => (float) $validated['per_kapita_harian'],
            'satuan' => strtoupper(trim($validated['satuan'])),
            'satuan_periode' => $validated['satuan_periode'],
            'sumber' => $validated['sumber'] ? trim($validated['sumber']) : null,
            'berlaku_mulai' => $validated['berlaku_mulai'],
            'berlaku_sampai' => $validated['berlaku_sampai'] ?? null,
            'created_at' => now(),
        ]);

        return redirect()->route('perencanaan.standar-kebutuhan.index', [
            'id_komoditas' => $validated['id_komoditas'],
        ])->with('status', 'Standar kebutuhan gizi komoditas berhasil ditambahkan.');
    }

    public function update(Request $request, int|string $id): RedirectResponse
    {
        $record = StandarKebutuhanKomoditas::findOrFail($id);

        $validated = $request->validate([
            'sektor' => ['required', 'string', 'max:50'],
            'per_kapita_harian' => ['required', 'numeric', 'min:0'],
            'satuan' => ['required', 'string', 'max:20'],
            'satuan_periode' => ['required', 'in:harian,bulanan'],
            'sumber' => ['nullable', 'string', 'max:150'],
            'berlaku_mulai' => ['required', 'date'],
            'berlaku_sampai' => ['nullable', 'date', 'after_or_equal:berlaku_mulai'],
        ]);

        $record->update([
            'sektor' => strtolower(trim($validated['sektor'])),
            'per_kapita_harian' => (float) $validated['per_kapita_harian'],
            'satuan' => strtoupper(trim($validated['satuan'])),
            'satuan_periode' => $validated['satuan_periode'],
            'sumber' => $validated['sumber'] ? trim($validated['sumber']) : null,
            'berlaku_mulai' => $validated['berlaku_mulai'],
            'berlaku_sampai' => $validated['berlaku_sampai'] ?? null,
        ]);

        return back()->with('status', 'Standar kebutuhan komoditas berhasil diperbarui.');
    }

    public function destroy(int|string $id): RedirectResponse
    {
        $record = StandarKebutuhanKomoditas::findOrFail($id);
        $nama = $record->komoditas?->nama ?? 'Standar';

        $record->delete();

        return back()->with('status', "Standar kebutuhan komoditas {$nama} berhasil dihapus.");
    }
}

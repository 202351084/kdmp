<?php

namespace App\Http\Controllers\Survei;

use App\Http\Controllers\Controller;
use App\Models\Survei\RtDesa;
use App\Models\Survei\SesiSurvei;
use App\Models\Tenant\Wilayah;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class RtDesaController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $idWilayah = ($user->id_entitas && $user->entitas) ? $user->entitas->id_wilayah : null;

        // Bersihkan data duplikat jika ada, pertahankan 1 record master per RT
        $duplicates = RtDesa::select('id_wilayah', 'nama_rt', 'rw', 'dusun', DB::raw('COUNT(*) as total'), DB::raw('MAX(id) as keep_id'))
            ->groupBy('id_wilayah', 'nama_rt', 'rw', 'dusun')
            ->having('total', '>', 1)
            ->get();

        foreach ($duplicates as $dup) {
            RtDesa::where('id_wilayah', $dup->id_wilayah)
                ->where('nama_rt', $dup->nama_rt)
                ->where('rw', $dup->rw)
                ->where('dusun', $dup->dusun)
                ->where('id', '!=', $dup->keep_id)
                ->delete();
        }

        $query = RtDesa::with('wilayah');

        if ($idWilayah) {
            $query->where('id_wilayah', $idWilayah);
            $activeSesi = SesiSurvei::where('id_wilayah', $idWilayah)->latest('id')->first();
        } else {
            $activeSesi = SesiSurvei::latest('id')->first();
        }

        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('nama_rt', 'like', "%{$search}%")
                  ->orWhere('dusun', 'like', "%{$search}%")
                  ->orWhere('nama_ketua_rt', 'like', "%{$search}%");
            });
        }

        $totalKk = (clone $query)->sum('total_kk_baseline');
        $totalJiwa = (clone $query)->sum('total_jiwa_baseline');

        $rts = $query->orderBy('dusun')->orderBy('nama_rt')->paginate(15)->withQueryString();

        return view('survei.rt.index', compact('rts', 'totalKk', 'totalJiwa', 'activeSesi'));
    }

    public function create()
    {
        $user = auth()->user();
        if ($user->id_entitas && $user->entitas) {
            $wilayahs = Wilayah::where('id', $user->entitas->id_wilayah)->get();
        } else {
            $wilayahs = Wilayah::where('tingkat', 'Desa')->get();
        }
        return view('survei.rt.create', compact('wilayahs'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'id_wilayah' => 'required|exists:wilayah,id',
            'dusun' => 'nullable|string|max:255',
            'rw' => 'nullable|string|max:5',
            'nama_rt' => [
                'required', 
                'string', 
                'max:255',
                Rule::unique('rt_desas')
                    ->where('id_wilayah', $request->id_wilayah)
                    ->where('dusun', $request->dusun)
                    ->where('rw', $request->rw)
            ],
            'nama_ketua_rt' => 'nullable|string|max:255',
            'no_hp' => 'nullable|string|max:20',
            'total_kk_baseline' => 'nullable|integer|min:0',
            'total_jiwa_baseline' => 'nullable|integer|min:0',
        ], [
            'nama_rt.unique' => 'Nama RT ini sudah ada di desa/dusun ini.'
        ]);

        // Auto format RT/RW (e.g., "rt 5" -> "RT 05", "5" -> "05")
        $formatNumber = function($val, $prefix) {
            if (!$val) return null;
            $clean = preg_replace('/^' . $prefix . '\s*/i', '', trim($val));
            if (preg_match('/^\d$/', $clean)) {
                $clean = '0' . $clean;
            }
            return strtoupper($prefix) . ' ' . $clean;
        };

        $validated['nama_rt'] = $formatNumber($validated['nama_rt'], 'RT');
        $validated['rw'] = $formatNumber($validated['rw'], 'RW');

        RtDesa::create($validated);

        return redirect()->route('survei.rt.index')->with('success', 'Data RT berhasil ditambahkan ke Master Data Desa.');
    }

    public function edit(RtDesa $rt)
    {
        $user = auth()->user();
        if ($user->id_entitas && $user->entitas) {
            $wilayahs = Wilayah::where('id', $user->entitas->id_wilayah)->get();
        } else {
            $wilayahs = Wilayah::where('tingkat', 'Desa')->get();
        }
        return view('survei.rt.edit', compact('rt', 'wilayahs'));
    }

    public function update(Request $request, RtDesa $rt)
    {
        $validated = $request->validate([
            'id_wilayah' => 'required|exists:wilayah,id',
            'dusun' => 'nullable|string|max:255',
            'rw' => 'nullable|string|max:5',
            'nama_rt' => [
                'required',
                'string',
                'max:255',
                Rule::unique('rt_desas')
                    ->where('id_wilayah', $request->id_wilayah)
                    ->where('dusun', $request->dusun)
                    ->where('rw', $request->rw)
                    ->ignore($rt->id)
            ],
            'nama_ketua_rt' => 'nullable|string|max:255',
            'no_hp' => 'nullable|string|max:20',
            'total_kk_baseline' => 'nullable|integer|min:0',
            'total_jiwa_baseline' => 'nullable|integer|min:0',
        ], [
            'nama_rt.unique' => 'Nama RT ini sudah ada di desa/dusun ini.'
        ]);

        // Auto format RT/RW
        $formatNumber = function($val, $prefix) {
            if (!$val) return null;
            $clean = preg_replace('/^' . $prefix . '\s*/i', '', trim($val));
            if (preg_match('/^\d$/', $clean)) {
                $clean = '0' . $clean;
            }
            return strtoupper($prefix) . ' ' . $clean;
        };

        $validated['nama_rt'] = $formatNumber($validated['nama_rt'], 'RT');
        $validated['rw'] = $formatNumber($validated['rw'], 'RW');

        $rt->update($validated);

        return redirect()->route('survei.rt.index')->with('success', 'Data RT berhasil diperbarui.');
    }

    public function destroy(RtDesa $rt)
    {
        $rt->delete();
        return redirect()->route('survei.rt.index')->with('success', 'Data RT berhasil dihapus dari Master Data.');
    }
}

<?php

namespace App\Http\Controllers\Survei;

use App\Http\Controllers\Controller;
use App\Models\Survei\SesiSurvei;
use App\Models\Survei\RtDesa;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class PublicSurveiRtController extends Controller
{
    /**
     * Tampilkan halaman survei publik untuk pengurus RT.
     */
    public function show(string $token): View
    {
        $sesi = SesiSurvei::where('token_rt', $token)
            ->with(['wilayah.parent.parent', 'petugas'])
            ->firstOrFail();

        // Validasi: hanya boleh diakses jika periode sesi sama dengan bulan & tahun saat ini
        if ($sesi->bulan != (int) date('n') || $sesi->tahun != (int) date('Y')) {
            abort(403, "Tautan survei ini sudah kedaluwarsa. Survei ini ditutup karena hanya berlaku untuk periode bulan " . $sesi->bulan . " tahun " . $sesi->tahun . ".");
        }

        // Ambil daftar RT master yang terdaftar di desa ini
        $rts = RtDesa::where('id_wilayah', $sesi->id_wilayah)
            ->orderBy('dusun')
            ->orderBy('nama_rt')
            ->get();

        return view('survei.public.rt', [
            'sesi'  => $sesi,
            'rts'   => $rts,
            'token' => $token,
        ]);
    }

    /**
     * Simpan RT baru dari form publik.
     */
    public function store(Request $request, string $token): RedirectResponse
    {
        $sesi = SesiSurvei::where('token_rt', $token)->firstOrFail();

        if ($sesi->bulan != (int) date('n') || $sesi->tahun != (int) date('Y')) {
            abort(403, "Tautan survei ini sudah kedaluwarsa.");
        }

        $validated = $request->validate([
            'nama_rt'             => 'required|string|max:10',
            'rw'                  => 'nullable|string|max:5',
            'dusun'               => 'nullable|string|max:100',
            'nama_ketua_rt'       => 'nullable|string|max:100',
            'no_hp'               => 'nullable|string|max:20',
            'total_kk_baseline'   => 'required|integer|min:1',
            'total_jiwa_baseline' => 'required|integer|min:1',
        ]);

        // Auto format RT/RW (e.g., "rt 5" -> "RT 05", "5" -> "05")
        $formatNumber = function($val, $prefix) {
            if (!$val) return null;
            // Remove the prefix if it exists (case insensitive)
            $clean = preg_replace('/^' . $prefix . '\s*/i', '', trim($val));
            // If it's a single digit, pad it with 0
            if (preg_match('/^\d$/', $clean)) {
                $clean = '0' . $clean;
            }
            return strtoupper($prefix) . ' ' . $clean;
        };

        $nama_rt = $formatNumber($validated['nama_rt'], 'RT');
        $rw = $formatNumber($validated['rw'], 'RW');

        $validated['id_wilayah'] = $sesi->id_wilayah;

        RtDesa::updateOrCreate(
            [
                'id_wilayah' => $sesi->id_wilayah,
                'nama_rt'    => $nama_rt,
                'rw'         => $rw,
                // Treat null dusun as empty string for matching purposes, or just match strictly
                'dusun'      => $validated['dusun'] ?? null,
            ],
            [
                'nama_ketua_rt'       => $validated['nama_ketua_rt'] ?? null,
                'no_hp'               => $validated['no_hp'] ?? null,
                'total_kk_baseline'   => $validated['total_kk_baseline'],
                'total_jiwa_baseline' => $validated['total_jiwa_baseline'],
            ]
        );

        return redirect()->back()->with('success', 'Data RT berhasil disimpan ke Master Data Desa! Terima kasih atas partisipasi Anda.');
    }

    /**
     * Update data RT yang sudah ada (misal admin sudah buat, RT tinggal isi jumlah KK).
     */
    public function update(Request $request, string $token, int $id): RedirectResponse
    {
        $sesi = SesiSurvei::where('token_rt', $token)->firstOrFail();

        if ($sesi->bulan != (int) date('n') || $sesi->tahun != (int) date('Y')) {
            abort(403, "Tautan survei ini sudah kedaluwarsa.");
        }

        $rt = RtDesa::where('id', $id)->where('id_wilayah', $sesi->id_wilayah)->firstOrFail();

        $validated = $request->validate([
            'nama_ketua_rt'       => 'nullable|string|max:100',
            'no_hp'               => 'nullable|string|max:20',
            'total_kk_baseline'   => 'required|integer|min:1',
            'total_jiwa_baseline' => 'required|integer|min:1',
        ]);

        $rt->update($validated);

        return redirect()->back()->with('success', 'Data RT berhasil diperbarui! Terima kasih atas partisipasi Anda.');
    }
}

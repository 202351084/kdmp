<?php

namespace App\Http\Controllers\Survei;

use App\Http\Controllers\Controller;
use App\Models\Survei\SesiSurvei;
use App\Models\Survei\NarasumberSesi;
use App\Models\Survei\DemografiNarasumber;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

/**
 * Controller untuk pengisian Sesi 1 (Data Demografi) via link token publik.
 * Tidak memerlukan login — diakses petugas lapangan via /survei/isi/{token}/sesi1
 */
class PublicSesi1Controller extends Controller
{
    private const KELOMPOK_UMUR = ['Balita', 'Anak', 'Remaja', 'Dewasa', 'Lansia'];

    // ─── Halaman Utama Sesi 1 (publik) ───────────────────────────────────────

    public function show(string $token): View
    {
        $sesi = SesiSurvei::where('token_masyarakat', $token)
            ->with(['wilayah.parent.parent', 'petugas', 'narasumber.demografi'])
            ->firstOrFail();

        $rekap = $this->hitungRekapDemografi($sesi);

        $kkPerNarasumber = $sesi->narasumber->mapWithKeys(function ($ns) {
            $kk = $ns->demografi->first()?->jumlah_kk ?? 0;
            return [$ns->id_narasumber => $kk];
        });

        return view('survei.public.sesi1.index', [
            'sesi'             => $sesi,
            'token'            => $token,
            'narasumbers'      => $sesi->narasumber,
            'rekap'            => $rekap,
            'kelompokUmur'     => self::KELOMPOK_UMUR,
            'kkPerNarasumber'  => $kkPerNarasumber,
            'totalKK'          => $kkPerNarasumber->sum(),
            'kategoriList'     => NarasumberSesi::KATEGORI_LIST,
        ]);
    }

    // ─── Tambah Narasumber ───────────────────────────────────────────────────

    public function storeNarasumber(Request $request, string $token): RedirectResponse
    {
        $sesi = SesiSurvei::where('token_masyarakat', $token)->firstOrFail();

        $validated = $request->validate([
            'nama_narasumber' => 'required|string|max:100',
            'kategori'        => 'required|in:' . implode(',', NarasumberSesi::KATEGORI_LIST),
            'nomor_kontak'    => 'nullable|string|max:30',
            'keterangan'      => 'nullable|string|max:500',
        ]);

        $validated['id_sesi'] = $sesi->id;
        $validated['status']  = NarasumberSesi::STATUS_BELUM;

        NarasumberSesi::create($validated);

        if ($sesi->status_sesi1 === 'belum_diisi') {
            $sesi->update(['status_sesi1' => 'sedang_diisi']);
        }

        return redirect()
            ->route('survei.public.sesi1.show', $token)
            ->with('success', 'Narasumber berhasil ditambahkan.');
    }

    // ─── Form Input Demografi per Narasumber ─────────────────────────────────

    public function showNarasumber(string $token, NarasumberSesi $narasumber): View
    {
        $sesi = SesiSurvei::where('token_masyarakat', $token)
            ->with('wilayah')
            ->firstOrFail();

        abort_if($narasumber->id_sesi !== $sesi->id, 403);

        $demografiAda = $narasumber->demografi->keyBy('kelompok_umur');
        $jumlahKK     = $demografiAda->first()?->jumlah_kk ?? 0;
        $sumberAda    = $demografiAda->first();

        return view('survei.public.sesi1.narasumber', [
            'sesi'           => $sesi,
            'token'          => $token,
            'narasumber'     => $narasumber,
            'kelompokUmur'   => self::KELOMPOK_UMUR,
            'demografiAda'   => $demografiAda,
            'jumlahKK'       => $jumlahKK,
            'sumberAda'      => $sumberAda,
            'sumberDataList' => DemografiNarasumber::SUMBER_DATA,
            'kategoriList'   => NarasumberSesi::KATEGORI_LIST,
        ]);
    }

    // ─── Simpan Demografi ────────────────────────────────────────────────────

    public function storeDemografi(Request $request, string $token, NarasumberSesi $narasumber): RedirectResponse
    {
        $sesi = SesiSurvei::where('token_masyarakat', $token)->firstOrFail();

        abort_if($narasumber->id_sesi !== $sesi->id, 403);

        $validated = $request->validate([
            'nama_narasumber'   => 'nullable|string|max:100',
            'kategori'          => 'nullable|in:' . implode(',', NarasumberSesi::KATEGORI_LIST),
            'nomor_kontak'      => 'nullable|string|max:30',
            'keterangan'        => 'nullable|string|max:500',
            'jumlah_kk'          => 'required|integer|min:0',
            'jumlah_laki'        => 'required|array',
            'jumlah_laki.*'      => 'required|integer|min:0',
            'jumlah_perempuan'   => 'required|array',
            'jumlah_perempuan.*' => 'required|integer|min:0',
            'sumber_data'        => 'required|in:' . implode(',', DemografiNarasumber::SUMBER_DATA),
            'tanggal_data'       => 'nullable|date',
            'keterangan_sumber'  => 'nullable|string|max:500',
            'aksi'               => 'required|in:draft,lengkap',
        ]);

        foreach (self::KELOMPOK_UMUR as $kelompok) {
            DemografiNarasumber::updateOrCreate(
                ['id_narasumber' => $narasumber->id_narasumber, 'kelompok_umur' => $kelompok],
                [
                    'id_sesi'           => $sesi->id,
                    'jumlah_kk'         => (int) $validated['jumlah_kk'],
                    'jumlah_laki'       => (int) ($validated['jumlah_laki'][$kelompok] ?? 0),
                    'jumlah_perempuan'  => (int) ($validated['jumlah_perempuan'][$kelompok] ?? 0),
                    'sumber_data'       => $validated['sumber_data'],
                    'tanggal_data'      => $validated['tanggal_data'] ?? null,
                    'keterangan_sumber' => $validated['keterangan_sumber'] ?? null,
                ]
            );
        }

        $narasumberData = [
            'status' => $validated['aksi'] === 'lengkap'
                ? NarasumberSesi::STATUS_LENGKAP
                : NarasumberSesi::STATUS_DRAFT,
        ];
        if (!empty($validated['nama_narasumber'])) $narasumberData['nama_narasumber'] = $validated['nama_narasumber'];
        if (!empty($validated['kategori'])) $narasumberData['kategori'] = $validated['kategori'];
        if (array_key_exists('nomor_kontak', $validated)) $narasumberData['nomor_kontak'] = $validated['nomor_kontak'];
        if (array_key_exists('keterangan', $validated)) $narasumberData['keterangan'] = $validated['keterangan'];

        $narasumber->update($narasumberData);

        return redirect()
            ->route('survei.public.sesi1.show', $token)
            ->with('success', 'Data demografi berhasil disimpan.');
    }

    // ─── Edit Info Narasumber ────────────────────────────────────────────────

    public function updateNarasumber(Request $request, string $token, NarasumberSesi $narasumber): RedirectResponse
    {
        $sesi = SesiSurvei::where('token_masyarakat', $token)->firstOrFail();

        abort_if($narasumber->id_sesi !== $sesi->id, 403);

        $validated = $request->validate([
            'nama_narasumber' => 'required|string|max:100',
            'kategori'        => 'required|in:' . implode(',', NarasumberSesi::KATEGORI_LIST),
            'nomor_kontak'    => 'nullable|string|max:30',
            'keterangan'      => 'nullable|string|max:500',
        ]);

        $narasumber->update($validated);

        return redirect()
            ->route('survei.public.sesi1.show', $token)
            ->with('success', 'Informasi narasumber berhasil diperbarui.');
    }

    // ─── Hapus Narasumber ────────────────────────────────────────────────────

    public function destroyNarasumber(string $token, NarasumberSesi $narasumber): RedirectResponse
    {
        $sesi = SesiSurvei::where('token_masyarakat', $token)->firstOrFail();

        abort_if($narasumber->id_sesi !== $sesi->id, 403);

        $narasumber->demografi()->delete();
        $narasumber->delete();

        return redirect()
            ->route('survei.public.sesi1.show', $token)
            ->with('success', 'Narasumber berhasil dihapus.');
    }

    // ─── Selesaikan Sesi 1 ───────────────────────────────────────────────────

    public function selesaikan(Request $request, string $token): RedirectResponse
    {
        $sesi = SesiSurvei::where('token_masyarakat', $token)->firstOrFail();

        $request->validate(['konfirmasi' => 'required|accepted']);

        $sesi->update([
            'status_sesi1'    => 'selesai',
            'selesai_sesi1_at' => now(),
        ]);

        return redirect()
            ->route('survei.public.show', $token)
            ->with('success', 'Sesi 1 Data Demografi Desa berhasil diselesaikan.');
    }

    // ─── Private Helper ──────────────────────────────────────────────────────

    private function hitungRekapDemografi(SesiSurvei $sesi): array
    {
        $rekap = [];
        foreach (self::KELOMPOK_UMUR as $ku) {
            $rekap[$ku] = ['laki' => 0, 'perempuan' => 0, 'total' => 0];
        }
        foreach ($sesi->narasumber as $ns) {
            foreach ($ns->demografi as $dem) {
                if (isset($rekap[$dem->kelompok_umur])) {
                    $rekap[$dem->kelompok_umur]['laki']      += $dem->jumlah_laki;
                    $rekap[$dem->kelompok_umur]['perempuan'] += $dem->jumlah_perempuan;
                    $rekap[$dem->kelompok_umur]['total']     += $dem->jumlah_laki + $dem->jumlah_perempuan;
                }
            }
        }
        return $rekap;
    }
}

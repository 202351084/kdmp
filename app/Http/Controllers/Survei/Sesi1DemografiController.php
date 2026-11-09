<?php

namespace App\Http\Controllers\Survei;

use App\Http\Controllers\Controller;
use App\Models\Survei\SesiSurvei;
use App\Models\Survei\NarasumberSesi;
use App\Models\Survei\DemografiNarasumber;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class Sesi1DemografiController extends Controller
{
    private const KELOMPOK_UMUR = ['Balita', 'Anak', 'Remaja', 'Dewasa', 'Lansia'];

    private function checkHakAksesWilayah(SesiSurvei $sesi): void
    {
        $user = auth()->user();
        if ($user && $user->id_entitas) {
            $userWilayahId = $user->entitas?->id_wilayah;
            if ($userWilayahId && (int) $sesi->id_wilayah !== (int) $userWilayahId) {
                abort(403, 'Anda tidak memiliki hak akses untuk mengelola sesi survei desa ini.');
            }
        }
    }

    // ─── Halaman Utama Sesi 1 ────────────────────────────────────────────────

    public function show(SesiSurvei $sesi): View
    {
        $this->checkHakAksesWilayah($sesi);

        $sesi->load(['wilayah.parent.parent', 'narasumber.demografi']);

        // Auto-copy dari periode sebelumnya jika data narasumber di sesi ini masih kosong
        if ($sesi->narasumber->isEmpty()) {
            if ($sesi->salinDataDariPeriodeSebelumnya()) {
                $sesi->load(['wilayah.parent.parent', 'narasumber.demografi']);
                session()->flash('info', 'Data narasumber & demografi dari periode sebelumnya otomatis disalin. Silakan sesuaikan jika ada perubahan.');
            }
        }

        // Hitung rekap demografi dari semua narasumber
        $rekap = $this->hitungRekapDemografi($sesi);

        // Hitung total KK dari semua narasumber (dengan info potensi tumpang tindih)
        $kkPerNarasumber = $sesi->narasumber->mapWithKeys(function ($ns) {
            // Ambil jumlah KK dari baris pertama demografi (tersimpan di setiap baris)
            $kk = $ns->demografi->first()?->jumlah_kk ?? 0;
            return [$ns->id_narasumber => $kk];
        });

        $totalKK = $kkPerNarasumber->sum();

        return view('survei.sesi1.index', [
            'title'             => 'Sesi 1 — Data Demografi Desa',
            'sesi'              => $sesi,
            'narasumbers'       => $sesi->narasumber,
            'rekap'             => $rekap,
            'kelompokUmur'      => self::KELOMPOK_UMUR,
            'kkPerNarasumber'   => $kkPerNarasumber,
            'totalKK'           => $totalKK,
            'kategoriList'      => NarasumberSesi::KATEGORI_LIST,
            'labelStatusSesi1'  => SesiSurvei::LABEL_STATUS_SESI1,
        ]);
    }

    // ─── Tambah Narasumber ───────────────────────────────────────────────────

    public function storeNarasumber(Request $request, SesiSurvei $sesi): RedirectResponse
    {
        $validated = $request->validate([
            'nama_narasumber' => 'required|string|max:100',
            'kategori'        => 'required|in:' . implode(',', NarasumberSesi::KATEGORI_LIST),
            'nomor_kontak'    => 'nullable|string|max:30',
            'keterangan'      => 'nullable|string|max:500',
        ]);

        $validated['id_sesi'] = $sesi->id;
        $validated['status']  = NarasumberSesi::STATUS_BELUM;

        $narasumber = NarasumberSesi::create($validated);

        // Update status sesi1 ke sedang_diisi jika masih belum_diisi
        if ($sesi->status_sesi1 === 'belum_diisi') {
            $sesi->update(['status_sesi1' => 'sedang_diisi']);
        }

        return redirect()
            ->route('survei.sesi1.show', $sesi->id)
            ->with('success', "Narasumber \"{$narasumber->nama_narasumber}\" berhasil ditambahkan.");
    }

    // ─── Form Input Data Narasumber ──────────────────────────────────────────

    public function showNarasumber(SesiSurvei $sesi, NarasumberSesi $narasumber): View
    {
        abort_if($narasumber->id_sesi !== $sesi->id, 403);

        $sesi->load('wilayah.parent.parent');

        // Susun data demografi yang sudah ada menjadi keyed by kelompok_umur
        $demografiAda = $narasumber->demografi->keyBy('kelompok_umur');

        // Data KK dari baris pertama demografi
        $jumlahKK = $demografiAda->first()?->jumlah_kk ?? 0;

        // Data sumber dari baris pertama
        $sumberAda = $demografiAda->first();

        return view('survei.sesi1.narasumber', [
            'title'          => 'Input Data Demografi — ' . $narasumber->nama_narasumber,
            'sesi'           => $sesi,
            'narasumber'     => $narasumber,
            'kelompokUmur'   => self::KELOMPOK_UMUR,
            'demografiAda'   => $demografiAda,
            'jumlahKK'       => $jumlahKK,
            'sumberAda'      => $sumberAda,
            'sumberDataList' => DemografiNarasumber::SUMBER_DATA,
            'kategoriList'   => NarasumberSesi::KATEGORI_LIST,
        ]);
    }

    // ─── Simpan Data Demografi Narasumber ─────────────────────────────────────

    public function storeDemografi(Request $request, SesiSurvei $sesi, NarasumberSesi $narasumber): RedirectResponse
    {
        abort_if($narasumber->id_sesi !== $sesi->id, 403);

        $validated = $request->validate([
            'nama_narasumber'   => 'nullable|string|max:100',
            'kategori'          => 'nullable|in:' . implode(',', NarasumberSesi::KATEGORI_LIST),
            'nomor_kontak'      => 'nullable|string|max:30',
            'keterangan'        => 'nullable|string|max:500',
            'jumlah_kk'         => 'required|integer|min:0',
            'jumlah_laki'       => 'required|array',
            'jumlah_laki.*'     => 'required|integer|min:0',
            'jumlah_perempuan'  => 'required|array',
            'jumlah_perempuan.*' => 'required|integer|min:0',
            'sumber_data'       => 'required|in:' . implode(',', DemografiNarasumber::SUMBER_DATA),
            'tanggal_data'      => 'nullable|date',
            'keterangan_sumber' => 'nullable|string|max:500',
            'aksi'              => 'required|in:draft,lengkap',
        ]);

        foreach (self::KELOMPOK_UMUR as $kelompok) {
            DemografiNarasumber::updateOrCreate(
                [
                    'id_narasumber' => $narasumber->id_narasumber,
                    'kelompok_umur' => $kelompok,
                ],
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

        // Update status & info narasumber
        $statusNarasumber = $validated['aksi'] === 'lengkap'
            ? NarasumberSesi::STATUS_LENGKAP
            : NarasumberSesi::STATUS_DRAFT;

        $narasumberData = ['status' => $statusNarasumber];
        if (!empty($validated['nama_narasumber'])) $narasumberData['nama_narasumber'] = $validated['nama_narasumber'];
        if (!empty($validated['kategori'])) $narasumberData['kategori'] = $validated['kategori'];
        if (array_key_exists('nomor_kontak', $validated)) $narasumberData['nomor_kontak'] = $validated['nomor_kontak'];
        if (array_key_exists('keterangan', $validated)) $narasumberData['keterangan'] = $validated['keterangan'];

        $narasumber->update($narasumberData);

        $pesanAksi = $validated['aksi'] === 'lengkap'
            ? 'ditandai Lengkap'
            : 'disimpan sebagai Draft';

        return redirect()
            ->route('survei.sesi1.show', $sesi->id)
            ->with('success', "Data narasumber \"{$narasumber->nama_narasumber}\" berhasil {$pesanAksi}.");
    }

    // ─── Edit Info Narasumber ────────────────────────────────────────────────
    public function updateNarasumber(Request $request, SesiSurvei $sesi, NarasumberSesi $narasumber): RedirectResponse
    {
        abort_if($narasumber->id_sesi !== $sesi->id, 403);

        $validated = $request->validate([
            'nama_narasumber' => 'required|string|max:100',
            'kategori'        => 'required|in:' . implode(',', NarasumberSesi::KATEGORI_LIST),
            'nomor_kontak'    => 'nullable|string|max:30',
            'keterangan'      => 'nullable|string|max:500',
        ]);

        $narasumber->update($validated);

        return redirect()
            ->route('survei.sesi1.show', $sesi->id)
            ->with('success', "Informasi narasumber \"{$narasumber->nama_narasumber}\" berhasil diperbarui.");
    }

    // ─── Simpan Draft Sesi 1 ─────────────────────────────────────────────────

    public function simpanDraft(Request $request, SesiSurvei $sesi): RedirectResponse
    {
        $validated = $request->validate([
            'jumlah_kk_verifikasi' => 'nullable|integer|min:0',
        ]);

        $sesi->update([
            'jumlah_kk_verifikasi' => $validated['jumlah_kk_verifikasi'] ?? null,
            'status_sesi1'         => 'sedang_diisi',
        ]);

        return redirect()
            ->route('survei.sesi1.show', $sesi->id)
            ->with('success', 'Draft Sesi 1 berhasil disimpan.');
    }

    // ─── Hapus Narasumber ─────────────────────────────────────────────────────

    public function destroyNarasumber(SesiSurvei $sesi, NarasumberSesi $narasumber): RedirectResponse
    {
        abort_if($narasumber->id_sesi !== $sesi->id, 403);

        $nama = $narasumber->nama_narasumber;

        // Hapus data demografi terkait, lalu hapus narasumber
        $narasumber->demografi()->delete();
        $narasumber->delete();

        return redirect()
            ->route('survei.sesi1.show', $sesi->id)
            ->with('success', "Narasumber \"{$nama}\" berhasil dihapus.");
    }

    // ─── Selesaikan Sesi 1 ───────────────────────────────────────────────────

    public function selesaikan(Request $request, SesiSurvei $sesi): RedirectResponse
    {
        $validated = $request->validate([
            'konfirmasi'           => 'required|accepted',
            'jumlah_kk_verifikasi' => 'nullable|integer|min:0',
        ]);

        $sesi->update([
            'status_sesi1'              => 'selesai',
            'jumlah_kk_verifikasi'      => $validated['jumlah_kk_verifikasi'] ?? null,
            'selesai_sesi1_at'          => now(),
            'diselesaikan_sesi1_oleh'   => auth()->id(),
        ]);

        return redirect()
            ->route('survei.sesi.show', $sesi->id)
            ->with('success', 'Sesi 1 Data Demografi Desa berhasil diselesaikan.');
    }

    // ─── Salin Data dari Periode Sebelumnya ──────────────────────────────────
    public function salinSebelumnya(SesiSurvei $sesi): RedirectResponse
    {
        $helper = new SesiSurveiController();
        $berhasil = $helper->salinDataNarasumberSesiSebelumnya($sesi);

        if ($berhasil) {
            return redirect()
                ->route('survei.sesi1.show', $sesi->id)
                ->with('success', 'Data narasumber & demografi dari periode sebelumnya berhasil disalin! Silakan sesuaikan jika ada perubahan.');
        }

        return redirect()
            ->route('survei.sesi1.show', $sesi->id)
            ->with('error', 'Tidak ditemukan data survei periode sebelumnya untuk desa ini.');
    }

    // ─── Private Helper ───────────────────────────────────────────────────────

    /**
     * Hitung rekap demografi dari semua narasumber pada sesi ini.
     * Rekap = penjumlahan seluruh data narasumber (bukan rata-rata).
     * Karena scope narasumber bisa berbeda, sistem menampilkan peringatan.
     */
    private function hitungRekapDemografi(SesiSurvei $sesi): array
    {
        $rekap = [];

        foreach (self::KELOMPOK_UMUR as $kelompok) {
            $rekap[$kelompok] = ['laki' => 0, 'perempuan' => 0, 'total' => 0];
        }

        foreach ($sesi->narasumber as $narasumber) {
            foreach ($narasumber->demografi as $dem) {
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

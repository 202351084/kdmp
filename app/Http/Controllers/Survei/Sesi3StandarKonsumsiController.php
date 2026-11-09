<?php

namespace App\Http\Controllers\Survei;

use App\Http\Controllers\Controller;
use App\Models\Master\Komoditas;
use App\Models\Survei\SesiSurvei;
use App\Models\Survei\StandarKonsumsi;
use App\Models\Survei\DemografiNarasumber;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class Sesi3StandarKonsumsiController extends Controller
{
    private const KELOMPOK_UMUR  = ['Balita', 'Anak', 'Remaja', 'Dewasa', 'Lansia'];
    private const GENDER         = ['L', 'P'];

    // ─── Guard Wilayah ────────────────────────────────────────────────────────

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

    // ─── Halaman Utama Sesi 3 ────────────────────────────────────────────────

    public function show(SesiSurvei $sesi): View
    {
        $this->checkHakAksesWilayah($sesi);

        $sesi->load(['wilayah.parent.parent.parent', 'petugas', 'narasumber.demografi']);

        // Semua komoditas aktif untuk dropdown pilih
        $komoditasList = Komoditas::where('is_active', true)
            ->orderByRaw("
                CASE 
                    WHEN kategori = 'Pertanian' THEN 1
                    WHEN kategori = 'Peternakan' THEN 2
                    WHEN kategori = 'Perikanan' THEN 3
                    WHEN kategori = 'Perkebunan' THEN 99
                    ELSE 50
                END
            ")
            ->orderBy('nama')
            ->get();

        // Standar konsumsi yang sudah tersimpan untuk sesi ini, di-group per komoditas
        $standarAda = StandarKonsumsi::with('komoditas')
            ->where('id_sesi', $sesi->id)
            ->whereIn('id_komoditas', $komoditasList->pluck('id'))
            ->get()
            ->groupBy('id_komoditas')
            ->sortBy(function ($group) {
                $kategori = $group->first()->komoditas->kategori;
                $order = [
                    'Pertanian'  => 1,
                    'Peternakan' => 2,
                    'Perikanan'  => 3,
                    'Perkebunan' => 99,
                ];
                return $order[$kategori] ?? 50;
            });

        // Ringkasan demografi dari Sesi 1 (READ-ONLY, referensi surveyor)
        $rekapDemografi = $this->hitungRekapDemografi($sesi);

        return view('survei.sesi3.index', [
            'title'             => 'Sesi 3 — Standar Konsumsi Komoditas',
            'sesi'              => $sesi,
            'komoditasList'     => $komoditasList,
            'standarAda'        => $standarAda,
            'rekapDemografi'    => $rekapDemografi,
            'kelompokUmur'      => self::KELOMPOK_UMUR,
            'genderList'        => StandarKonsumsi::KATEGORI_GENDER,
            'satuanList'        => StandarKonsumsi::SATUAN_LIST,
            'periodeList'       => StandarKonsumsi::PERIODE_LIST,
            'sumberList'        => StandarKonsumsi::SUMBER_LIST,
            'labelStatusSesi3'  => SesiSurvei::LABEL_STATUS_SESI3,
        ]);
    }

    // ─── Simpan / Update Standar Konsumsi ─────────────────────────────────────

    public function store(Request $request, SesiSurvei $sesi): RedirectResponse
    {
        $this->checkHakAksesWilayah($sesi);

        $validated = $request->validate([
            'id_komoditas'    => 'required|exists:komoditas,id',
            'satuan'          => 'required|string|max:50',
            'periode'         => 'required|in:harian,mingguan,bulanan,tahunan',
            'sumber_standar'  => 'required|string|max:255',
            'periode_standar' => 'nullable|string|max:20',
            'keterangan'      => 'nullable|string|max:1000',
            'nilai'           => 'required|array',
            'nilai.*.*'       => 'required|numeric|min:0',
        ]);

        DB::transaction(function () use ($validated, $sesi) {
            foreach (self::KELOMPOK_UMUR as $umur) {
                foreach (self::GENDER as $gender) {
                    $nilaiKonsumsi = (float) ($validated['nilai'][$umur][$gender] ?? 0);

                    StandarKonsumsi::updateOrCreate(
                        [
                            'id_sesi'         => $sesi->id,
                            'id_komoditas'    => $validated['id_komoditas'],
                            'kategori_gender' => $gender,
                            'kategori_umur'   => $umur,
                        ],
                        [
                            'nilai_konsumsi'          => $nilaiKonsumsi,
                            'satuan'                  => $validated['satuan'],
                            'periode'                 => $validated['periode'],
                            'nilai_per_tahun_standar' => StandarKonsumsi::konversiKeTahunan($nilaiKonsumsi, $validated['periode']),
                            'sumber_standar'          => $validated['sumber_standar'],
                            'periode_standar'         => $validated['periode_standar'] ?? null,
                            'keterangan'              => $validated['keterangan'] ?? null,
                        ]
                    );
                }
            }
        });

        // Update status sesi3 ke sedang_diisi jika masih belum_diisi
        if (($sesi->status_sesi3 ?? 'belum_diisi') === 'belum_diisi') {
            $sesi->update(['status_sesi3' => 'sedang_diisi']);
        }

        $namaKomoditas = Komoditas::find($validated['id_komoditas'])?->nama ?? 'Komoditas';

        return redirect()
            ->route('survei.sesi3.show', $sesi->id)
            ->with('success', "Standar konsumsi untuk \"{$namaKomoditas}\" berhasil disimpan.");
    }

    // ─── Hapus Standar Konsumsi satu Komoditas ───────────────────────────────

    public function destroy(Request $request, SesiSurvei $sesi): RedirectResponse
    {
        $this->checkHakAksesWilayah($sesi);

        $request->validate(['id_komoditas' => 'required|exists:komoditas,id']);

        $idKomoditas = $request->id_komoditas;
        $nama        = Komoditas::find($idKomoditas)?->nama ?? 'Komoditas';

        StandarKonsumsi::where('id_sesi', $sesi->id)
            ->where('id_komoditas', $idKomoditas)
            ->delete();

        return redirect()
            ->route('survei.sesi3.show', $sesi->id)
            ->with('success', "Standar konsumsi untuk \"{$nama}\" berhasil dihapus.");
    }

    // ─── Salin Komoditas dari Sesi 2 ─────────────────────────────────────────

    public function salinDariSesi2(SesiSurvei $sesi): RedirectResponse
    {
        $this->checkHakAksesWilayah($sesi);

        // Ambil komoditas unik dari Sesi 2 (Produksi)
        $komoditasSesi2 = \App\Models\Survei\ProduksiNarasumber::where('id_sesi', $sesi->id)
            ->distinct()
            ->pluck('id_komoditas');

        if ($komoditasSesi2->isEmpty()) {
            return redirect()
                ->route('survei.sesi3.show', $sesi->id)
                ->with('error', 'Belum ada data produksi komoditas di Sesi 2.');
        }

        $berhasil = 0;

        DB::transaction(function () use ($sesi, $komoditasSesi2, &$berhasil) {
            foreach ($komoditasSesi2 as $idKomoditas) {
                // Cek apakah komoditas ini sudah ada di Sesi 3
                $sudahAda = StandarKonsumsi::where('id_sesi', $sesi->id)
                    ->where('id_komoditas', $idKomoditas)
                    ->exists();

                if (!$sudahAda) {
                    // Buat standar default 0
                    foreach (self::KELOMPOK_UMUR as $umur) {
                        foreach (self::GENDER as $gender) {
                            StandarKonsumsi::create([
                                'id_sesi'         => $sesi->id,
                                'id_komoditas'    => $idKomoditas,
                                'kategori_gender' => $gender,
                                'kategori_umur'   => $umur,
                                'nilai_konsumsi'  => 0,
                                'satuan'          => 'kg/orang/bulan',
                                'periode'         => 'bulanan',
                                'sumber_standar'  => 'Belum diisi',
                            ]);
                        }
                    }
                    $berhasil++;
                }
            }
        });

        // Update status sesi3
        if (($sesi->status_sesi3 ?? 'belum_diisi') === 'belum_diisi') {
            $sesi->update(['status_sesi3' => 'sedang_diisi']);
        }

        if ($berhasil > 0) {
            return redirect()
                ->route('survei.sesi3.show', $sesi->id)
                ->with('success', "Berhasil menyalin {$berhasil} komoditas dari Sesi 2. Silakan edit standar konsumsinya.");
        }

        return redirect()
            ->route('survei.sesi3.show', $sesi->id)
            ->with('success', 'Semua komoditas dari Sesi 2 sudah ada di Sesi 3.');
    }

    // ─── Simpan Draft ────────────────────────────────────────────────────────

    public function simpanDraft(SesiSurvei $sesi): RedirectResponse
    {
        $this->checkHakAksesWilayah($sesi);

        $sesi->update(['status_sesi3' => 'sedang_diisi']);

        return redirect()
            ->route('survei.sesi3.show', $sesi->id)
            ->with('success', 'Draft Sesi 3 berhasil disimpan.');
    }

    // ─── Selesaikan Sesi 3 ───────────────────────────────────────────────────

    public function selesaikan(Request $request, SesiSurvei $sesi): RedirectResponse
    {
        $this->checkHakAksesWilayah($sesi);

        $request->validate(['konfirmasi' => 'required|accepted']);

        $sesi->update([
            'status_sesi3'             => 'selesai',
            'selesai_sesi3_at'         => now(),
            'diselesaikan_sesi3_oleh'  => auth()->id(),
        ]);

        return redirect()
            ->route('survei.sesi.show', $sesi->id)
            ->with('success', 'Sesi 3 Standar Konsumsi Komoditas berhasil diselesaikan.');
    }

    // ─── Private Helper ───────────────────────────────────────────────────────

    /**
     * Hitung rekap demografi dari semua narasumber Sesi 1 (READ-ONLY referensi).
     */
    private function hitungRekapDemografi(SesiSurvei $sesi): array
    {
        $rekap = [];
        foreach (self::KELOMPOK_UMUR as $kelompok) {
            $rekap[$kelompok] = ['laki' => 0, 'perempuan' => 0, 'total' => 0];
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

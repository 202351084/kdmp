<?php

namespace App\Services\Perencanaan;

use App\Models\Master\Komoditas;
use App\Models\Perencanaan\DemografiDesa;
use App\Models\Perencanaan\HasilKalkulasi;
use App\Models\Perencanaan\KebutuhanKomoditas;
use App\Models\Perencanaan\KetersediaanKomoditas;
use App\Models\Perencanaan\StandarKebutuhanKomoditas;
use App\Support\Perencanaan\PeriodeKalkulasi;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class KalkulasiKebutuhanService
{
    /**
     * Menjalankan kalkulasi kebutuhan per periode enam bulanan berdasarkan data demografi
     * dan standar gizi per kapita harian.
     *
     * @param int $idWilayah ID wilayah desa
     * @param int $tahun Tahun perencanaan (contoh: 2026)
     * @param string $periode Periode ganjil (Januari-Juni) atau genap (Juli-Desember)
     * @param int|null $idKomoditas ID komoditas spesifik (opsional, jika null hitung semua komoditas aktif)
     * @return array Ringkasan hasil kalkulasi
     *
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    public function hitungKebutuhan(int $idWilayah, int $tahun, string|int $periode, ?int $idKomoditas = null): array
    {
        $legacyMonthly = is_int($periode);
        $bulanLegacy = $legacyMonthly ? $periode : null;
        $periode = $legacyMonthly ? PeriodeKalkulasi::fromMonth($periode) : strtolower($periode);
        $detailPeriode = PeriodeKalkulasi::detail($periode);
        if ($legacyMonthly) {
            $detailPeriode['bulan_awal'] = $bulanLegacy;
            $detailPeriode['bulan_akhir'] = $bulanLegacy;
            $detailPeriode['bulan'] = [$bulanLegacy];
        }
        $bulan = $detailPeriode['bulan_awal'];

        if ($tahun < 2020 || $tahun > 2100) {
            throw new InvalidArgumentException("Tahun perencanaan tidak valid.");
        }

        return DB::transaction(function () use ($idWilayah, $tahun, $periode, $detailPeriode, $bulan, $idKomoditas, $legacyMonthly) {
            // 1. Ambil data demografi penduduk untuk wilayah & tahun terkait
            $demografi = DemografiDesa::where('id_wilayah', $idWilayah)
                ->where('tahun', $tahun)
                ->get();

            if ($demografi->isEmpty()) {
                throw new RuntimeException("Data demografi penduduk untuk tahun {$tahun} belum tersedia pada wilayah ini. Pastikan data survey kependudukan sudah diisi.");
            }

            $kelompokUmurWajib = ['balita', 'anak', 'remaja', 'dewasa', 'lansia'];
            $kelompokUmurTerisi = $demografi->pluck('kelompok_umur')->unique()->all();
            $kelompokUmurKurang = array_values(array_diff($kelompokUmurWajib, $kelompokUmurTerisi));

            if ($kelompokUmurKurang !== []) {
                throw new RuntimeException(
                    'Kalkulasi dibatalkan karena data demografi belum lengkap. Lengkapi kelompok umur: '
                    . implode(', ', $kelompokUmurKurang) . '.'
                );
            }

            // 2. Tentukan komoditas yang akan dikalkulasi
            $queryKomoditas = Komoditas::where('is_active', true);
            if ($idKomoditas !== null) {
                $queryKomoditas->where('id', $idKomoditas);
            }
            $daftarKomoditas = $queryKomoditas->get();

            if ($daftarKomoditas->isEmpty()) {
                throw new RuntimeException("Tidak ada komoditas aktif yang ditemukan untuk dikalkulasi.");
            }

            // Snapshot disimpan pada bulan awal periode; sumber produksi tetap diagregasi enam bulan.
            $startOfPeriod = Carbon::createFromDate($tahun, $detailPeriode['bulan_awal'], 1);
            $endOfPeriod = Carbon::createFromDate($tahun, $detailPeriode['bulan_akhir'], 1)->endOfMonth();
            $jumlahHariDalamPeriode = 0;
            foreach ($detailPeriode['bulan'] as $month) {
                $jumlahHariDalamPeriode += Carbon::createFromDate($tahun, $month, 1)->daysInMonth;
            }
            $startOfMonth = $startOfPeriod->copy()->startOfMonth()->toDateString();
            $endOfMonth = $endOfPeriod->toDateString();

            $totalBarisKebutuhan = 0;
            $totalHasilKalkulasi = 0;
            $ringkasanHasil = [];
            $komoditasTanpaStandar = [];

            foreach ($daftarKomoditas as $komoditas) {
                $subtotalKebutuhanKomoditas = 0.0;
                $satuanKomoditas = 'KG';
                $standarPerKelompokUmur = [];

                foreach ($demografi as $demo) {
                    $queryStandar = StandarKebutuhanKomoditas::where('id_komoditas', $komoditas->id)
                        ->where('kelompok_umur', $demo->kelompok_umur)
                        ->when(!$legacyMonthly, function ($query) use ($startOfMonth, $endOfMonth) {
                            $query->whereRaw('berlaku_mulai <= ?', [$endOfMonth])
                                ->where(function ($q) use ($startOfMonth) {
                                    $q->whereNull('berlaku_sampai')
                                        ->orWhereRaw('berlaku_sampai >= ?', [$startOfMonth]);
                                });
                        });
                    $standarPerKelompokUmur[$demo->kelompok_umur] = $queryStandar
                        ->latest('berlaku_mulai')
                        ->first();
                }

                $kelompokTanpaStandar = collect($standarPerKelompokUmur)
                    ->filter(fn ($standar) => $standar === null || (float) $standar->per_kapita_harian <= 0)
                    ->keys()
                    ->all();

                if ($kelompokTanpaStandar !== []) {
                    $komoditasTanpaStandar[] = [
                        'id_komoditas' => $komoditas->id,
                        'nama' => $komoditas->nama,
                        'kelompok_umur' => $kelompokTanpaStandar,
                    ];
                    continue;
                }

                $satuanStandar = collect($standarPerKelompokUmur)
                    ->map(fn ($standar) => strtoupper(trim((string) $standar->satuan)))
                    ->unique()
                    ->values();

                if ($satuanStandar->count() > 1) {
                    throw new RuntimeException(
                        "Kalkulasi {$komoditas->nama} dibatalkan karena satuan standar kebutuhan tidak konsisten: "
                        . $satuanStandar->implode(', ') . '.'
                    );
                }

                // 4. Hitung kebutuhan per kelompok umur
                foreach ($demografi as $demo) {
                    $standar = $standarPerKelompokUmur[$demo->kelompok_umur];

                    $perKapitaHarian = (float) ($standar ? $standar->per_kapita_harian : 0);
                    $satuan = $standar ? $standar->satuan : $satuanKomoditas;
                    $satuanKomoditas = $satuan;
                    $faktorMusiman = 1.0; // Default faktor musiman

                    // Standar harian dikalikan seluruh hari periode; standar bulanan dikalikan enam.
                    $faktorHari = ($standar && $standar->satuan_periode === 'bulanan')
                        ? ($legacyMonthly ? 1 : 6)
                        : $jumlahHariDalamPeriode;

                    // Formula: jumlah_penduduk * per_kapita_harian * faktor_hari * faktor_musiman
                    $kebutuhanBulanan = round($demo->jumlah_penduduk * $perKapitaHarian * $faktorHari * $faktorMusiman, 4);
                    $subtotalKebutuhanKomoditas += $kebutuhanBulanan;

                    // Snapshot ke tabel kebutuhan_komoditas (idempotent)
                    KebutuhanKomoditas::updateOrCreate(
                        [
                            'id_wilayah' => $idWilayah,
                            'id_komoditas' => $komoditas->id,
                            'tahun' => $tahun,
                            'bulan' => $bulan,
                            'kelompok_umur' => $demo->kelompok_umur,
                        ],
                        [
                            'jumlah_penduduk' => $demo->jumlah_penduduk,
                            'per_kapita_harian' => $perKapitaHarian,
                            'faktor_musiman' => $faktorMusiman,
                            'kebutuhan_bulanan' => $kebutuhanBulanan,
                            'satuan' => $satuan,
                            'id_standar' => $standar?->id,
                            'created_at' => now(),
                        ]
                    );

                    $totalBarisKebutuhan++;
                }

                // 5. Ambil data ketersediaan / potensi produksi lokal jika ada
                $ketersediaan = KetersediaanKomoditas::where('id_wilayah', $idWilayah)
                    ->where('id_komoditas', $komoditas->id)
                    ->where('tahun', $tahun)
                    ->whereIn('bulan', $detailPeriode['bulan'])
                    ->first();

                $totalKetersediaan = (float) ($ketersediaan ? $ketersediaan->jumlah_produksi : 0);
                $totalKebutuhan = round($subtotalKebutuhanKomoditas, 4);
                $selisih = round($totalKetersediaan - $totalKebutuhan, 4);

                // Tentukan status surplus / defisit / seimbang
                if ($selisih > 0.0001) {
                    $statusSurplusDefisit = 'surplus';
                } elseif ($selisih < -0.0001) {
                    $statusSurplusDefisit = 'defisit';
                } else {
                    $statusSurplusDefisit = 'seimbang';
                }

                // Hitung persentase kecukupan pangan
                $persentaseKecukupan = $totalKebutuhan > 0
                    ? round(($totalKetersediaan / $totalKebutuhan) * 100, 2)
                    : 100.00;

                // 6. Manajemen Versi hasil_kalkulasi
                $existingHasil = HasilKalkulasi::where('id_wilayah', $idWilayah)
                    ->where('id_komoditas', $komoditas->id)
                    ->where('tahun', $tahun)
                    ->where('bulan', $bulan)
                    ->latest('versi')
                    ->first();

                $versi = 1;
                if ($existingHasil && $existingHasil->status === 'disetujui') {
                    $versi = $existingHasil->versi + 1;
                } elseif ($existingHasil) {
                    $versi = $existingHasil->versi;
                }

                $hasil = HasilKalkulasi::updateOrCreate(
                    [
                        'id_wilayah' => $idWilayah,
                        'id_komoditas' => $komoditas->id,
                        'tahun' => $tahun,
                        'bulan' => $bulan,
                        'versi' => $versi,
                    ],
                    [
                        'total_kebutuhan' => $totalKebutuhan,
                        'total_ketersediaan' => $totalKetersediaan,
                        'selisih' => $selisih,
                        'status_surplus_defisit' => $statusSurplusDefisit,
                        'persentase_kecukupan' => $persentaseKecukupan,
                        'status' => 'draft',
                        'created_at' => now(),
                    ]
                );

                $totalHasilKalkulasi++;
                $ringkasanHasil[] = [
                    'komoditas' => $komoditas->nama,
                    'total_kebutuhan' => $totalKebutuhan,
                    'total_ketersediaan' => $totalKetersediaan,
                    'selisih' => $selisih,
                    'status' => $statusSurplusDefisit,
                    'persentase' => $persentaseKecukupan,
                    'standar_lengkap' => true,
                ];
            }

            return [
                'id_wilayah' => $idWilayah,
                'tahun' => $tahun,
                'periode' => $periode,
                'bulan' => $bulan,
                'jumlah_hari' => $jumlahHariDalamPeriode,
                'total_komoditas' => count($daftarKomoditas),
                'total_baris_kebutuhan' => $totalBarisKebutuhan,
                'total_hasil_kalkulasi' => $totalHasilKalkulasi,
                'komoditas_dilewati' => $komoditasTanpaStandar,
                'rincian' => $ringkasanHasil,
            ];
        });
    }
}

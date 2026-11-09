<?php

namespace App\Services\Survei;

use App\Models\Master\Komoditas;
use App\Models\Perencanaan\DemografiDesa;
use App\Models\Perencanaan\KetersediaanKomoditas;
use App\Models\Perencanaan\StandarKebutuhanKomoditas;
use App\Models\Survei\Jawaban;
use App\Models\Survei\SesiSurvei;
use App\Models\Tenant\Entitas;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use RuntimeException;

class SurveySyncService
{
    /** Kelompok umur valid sesuai enum database */
    public const KELOMPOK_UMUR = ['balita', 'anak', 'remaja', 'dewasa', 'lansia'];

    /**
     * Sinkronisasi seluruh modul survei (Sesi 1 Demografi, Sesi 2 Produksi, Sesi 3 Standar Konsumsi)
     * ke tabel-tabel modul Perencanaan (M2/M3).
     *
     * @param SesiSurvei|int $sesi
     * @param array $options
     * @return array Ringkasan hasil sinkronisasi
     */
    public function syncSesi(SesiSurvei|int $sesi, array $options = []): array
    {
        if (is_int($sesi)) {
            $sesi = SesiSurvei::with(['jawaban.pertanyaan', 'jawaban.modul', 'wilayah'])->findOrFail($sesi);
        } else {
            $sesi->loadMissing(['jawaban.pertanyaan', 'jawaban.modul', 'wilayah']);
        }

        // 1. Validasi Tenant / Koperasi Scope
        $this->validasiTenantScope($sesi);

        return DB::transaction(function () use ($sesi) {
            $result = [
                'id_sesi' => $sesi->id,
                'id_wilayah' => $sesi->id_wilayah,
                'nama_wilayah' => $sesi->wilayah?->nama ?? 'Wilayah #'.$sesi->id_wilayah,
                'tahun' => $sesi->tahun,
                'demografi_updated' => 0,
                'ketersediaan_updated' => 0,
                'standar_updated' => 0,
                'data_gaps' => [],
                'messages' => [],
            ];

            // 2. Sinkronkan Sesi 1: Demografi
            $resDemografi = $this->syncSesi1Demografi($sesi);
            $result['demografi_updated'] = $resDemografi['count'];
            $result['demografi_detail'] = $resDemografi['detail'];

            // 3. Sinkronkan Sesi 2: Potensi Produksi / Kalender Panen
            $resProduksi = $this->syncSesi2Produksi($sesi);
            $result['ketersediaan_updated'] = $resProduksi['count'];
            $result['ketersediaan_detail'] = $resProduksi['detail'];
            if (!empty($resProduksi['data_gaps'])) {
                $result['data_gaps'] = array_merge($result['data_gaps'], $resProduksi['data_gaps']);
            }

            // 4. Sinkronkan Sesi 3: Standar Kebutuhan Konsumsi
            $resKonsumsi = $this->syncSesi3Konsumsi($sesi);
            $result['standar_updated'] = $resKonsumsi['count'];
            $result['standar_detail'] = $resKonsumsi['detail'];
            if (!empty($resKonsumsi['data_gaps'])) {
                $result['data_gaps'] = array_merge($result['data_gaps'], $resKonsumsi['data_gaps']);
            }

            // Catat log audit sinkronisasi
            Log::info("Sinkronisasi Survei Sesi #{$sesi->id} ke Perencanaan berhasil dijalankan.", [
                'id_sesi' => $sesi->id,
                'id_wilayah' => $sesi->id_wilayah,
                'tahun' => $sesi->tahun,
                'demografi_updated' => $result['demografi_updated'],
                'ketersediaan_updated' => $result['ketersediaan_updated'],
                'standar_updated' => $result['standar_updated'],
                'data_gaps' => $result['data_gaps'],
            ]);

            return $result;
        });
    }

    /**
     * Memvalidasi bahwa sesi survei berada dalam wilayah entitas yang berhak (Tenant Isolation).
     */
    protected function validasiTenantScope(SesiSurvei $sesi): void
    {
        if (app()->bound('entitas_aktif') && ($idEntitasAktif = app('entitas_aktif'))) {
            $entitas = Entitas::find($idEntitasAktif);
            if ($entitas && $entitas->id_wilayah != $sesi->id_wilayah) {
                throw new RuntimeException("Akses ditolak: Sesi survei wilayah #{$sesi->id_wilayah} tidak berada dalam cakupan entitas {$entitas->nama_entitas} (Wilayah #{$entitas->id_wilayah}).");
            }
        }
    }

    /**
     * Sinkronisasi Sesi 1 (Demografi Penduduk).
     * Melakukan agregasi gender (Laki-Laki + Perempuan) dan menerapkan aturan Latest Session Wins.
     */
    public function syncSesi1Demografi(SesiSurvei $sesi): array
    {
        $idWilayah = $sesi->id_wilayah;
        $tahun = $sesi->tahun;

        // Ambil semua jawaban yang berkaitan dengan modul demografi
        $jawabanDemografi = $sesi->jawaban->filter(function (Jawaban $j) {
            $modulKode = strtolower($j->modul?->kode ?? '');
            $pertanyaanKode = strtolower($j->pertanyaan?->kode_pertanyaan ?? '');
            return str_contains($modulKode, 'demo') || str_contains($modulKode, 'sesi_1') || str_contains($modulKode, 'sesi1') ||
                   str_contains($pertanyaanKode, 'demo') || str_contains($pertanyaanKode, 'penduduk') ||
                   !empty($j->nilai_json);
        });

        $populasiAgregat = [];
        foreach (self::KELOMPOK_UMUR as $ku) {
            $populasiAgregat[$ku] = null; // null jika tidak ada data
        }

        // Ekstraksi data populasi dari jawaban
        foreach ($jawabanDemografi as $j) {
            $this->extractDemografiFromJawaban($j, $populasiAgregat);
        }

        // Terapkan Latest Session Wins:
        // Cek apakah ada sesi lain untuk wilayah+tahun yang sama yang lebih baru dari sesi ini
        $sesiLebihBaru = SesiSurvei::where('id_wilayah', $idWilayah)
            ->where('tahun', $tahun)
            ->whereIn('status', ['terkirim', 'disetujui'])
            ->where('id', '>', $sesi->id)
            ->exists();

        $updatedCount = 0;
        $detail = [];

        foreach ($populasiAgregat as $kelompokUmur => $jumlah) {
            if ($jumlah === null) {
                continue;
            }

            // Jika ada sesi yang lebih baru, jangan timpa dengan data dari sesi lama
            if ($sesiLebihBaru) {
                $detail[$kelompokUmur] = [
                    'jumlah' => $jumlah,
                    'status' => 'skipped_older_session',
                ];
                continue;
            }

            // Simpan / update ke demografi_desa
            DemografiDesa::updateOrCreate(
                [
                    'id_wilayah' => $idWilayah,
                    'tahun' => $tahun,
                    'kelompok_umur' => $kelompokUmur,
                ],
                [
                    'jumlah_penduduk' => $jumlah,
                    'id_sesi_survei' => $sesi->id,
                    'created_at' => now(),
                ]
            );

            $updatedCount++;
            $detail[$kelompokUmur] = [
                'jumlah' => $jumlah,
                'status' => 'synced',
            ];
        }

        return [
            'count' => $updatedCount,
            'detail' => $detail,
        ];
    }

    /**
     * Parsing jawaban demografi dari berbagai kemungkinan struktur JSON / field jawaban.
     */
    protected function extractDemografiFromJawaban(Jawaban $jawaban, array &$populasiAgregat): void
    {
        $payload = $jawaban->nilai_json;

        if (is_array($payload)) {
            // Cek format: [ 'balita' => ['laki_laki' => 50, 'perempuan' => 60], ... ]
            // Atau format: [ 'rincian' => [ 'balita' => ... ] ]
            $data = isset($payload['rincian']) && is_array($payload['rincian']) ? $payload['rincian'] : $payload;

            foreach (self::KELOMPOK_UMUR as $ku) {
                if (isset($data[$ku])) {
                    $item = $data[$ku];
                    $totalKu = $this->sumGenderValues($item);
                    if ($totalKu !== null) {
                        $populasiAgregat[$ku] = ($populasiAgregat[$ku] ?? 0) + $totalKu;
                    }
                }
            }

            // Cek jika array of items: [ ['kelompok_umur' => 'balita', 'laki_laki' => 50, 'perempuan' => 60], ... ]
            if (array_is_list($data)) {
                foreach ($data as $row) {
                    if (is_array($row) && isset($row['kelompok_umur']) && in_array(strtolower($row['kelompok_umur']), self::KELOMPOK_UMUR, true)) {
                        $ku = strtolower($row['kelompok_umur']);
                        $totalKu = $this->sumGenderValues($row);
                        if ($totalKu !== null) {
                            $populasiAgregat[$ku] = ($populasiAgregat[$ku] ?? 0) + $totalKu;
                        }
                    }
                }
            }
        } elseif ($jawaban->nilai_angka !== null) {
            // Jika disimpan per pertanyaan spesifik (misal kode_pertanyaan: DEMO_BALITA_L, dll)
            $kode = strtolower($jawaban->pertanyaan?->kode_pertanyaan ?? '');
            foreach (self::KELOMPOK_UMUR as $ku) {
                if (str_contains($kode, $ku)) {
                    $populasiAgregat[$ku] = ($populasiAgregat[$ku] ?? 0) + (int) $jawaban->nilai_angka;
                }
            }
        }
    }

    /**
     * Menjumlahkan nilai pria/laki-laki + wanita/perempuan secara aman (Gender Aggregation).
     */
    protected function sumGenderValues(mixed $item): ?int
    {
        if (is_numeric($item)) {
            return (int) $item;
        }

        if (!is_array($item)) {
            return null;
        }

        $laki = $item['laki_laki'] ?? $item['laki'] ?? $item['l'] ?? $item['pria'] ?? 0;
        $perempuan = $item['perempuan'] ?? $item['wanita'] ?? $item['p'] ?? 0;
        $total = $item['jumlah'] ?? $item['total'] ?? null;

        if ($total !== null && is_numeric($total) && (int) $total > 0 && ((int) $laki + (int) $perempuan == 0)) {
            return (int) $total;
        }

        return (int) $laki + (int) $perempuan;
    }

    /**
     * Sinkronisasi Sesi 2 (Potensi Produksi Lokal / Kalender Panen Bulanan).
     * Menghasilkan satu baris ketersediaan_komoditas per bulan panen.
     */
    public function syncSesi2Produksi(SesiSurvei $sesi): array
    {
        $idWilayah = $sesi->id_wilayah;
        $tahun = $sesi->tahun;

        $jawabanProduksi = $sesi->jawaban->filter(function (Jawaban $j) {
            $modulKode = strtolower($j->modul?->kode ?? '');
            $pertanyaanKode = strtolower($j->pertanyaan?->kode_pertanyaan ?? '');
            return str_contains($modulKode, 'prod') || str_contains($modulKode, 'panen') ||
                   str_contains($modulKode, 'sesi_2') || str_contains($modulKode, 'sesi2') ||
                   str_contains($pertanyaanKode, 'prod') || str_contains($pertanyaanKode, 'panen') ||
                   (!empty($j->nilai_json) && !str_contains($modulKode, 'demo') && !str_contains($modulKode, 'konsum'));
        });

        $updatedCount = 0;
        $detail = [];
        $dataGaps = [];

        foreach ($jawabanProduksi as $j) {
            $payload = $j->nilai_json;
            if (!is_array($payload)) {
                continue;
            }

            // Format items bisa array of commodities atau single commodity object
            $items = array_is_list($payload) ? $payload : [$payload];

            foreach ($items as $item) {
                if (!is_array($item)) {
                    continue;
                }

                // Resolusi Komoditas
                $komoditas = $this->resolveKomoditas($item);
                if (!$komoditas) {
                    $namaKom = $item['komoditas'] ?? $item['nama_komoditas'] ?? $item['nama'] ?? 'Tidak Diketahui';
                    $dataGaps[] = [
                        'sesi_id' => $sesi->id,
                        'modul' => 'Sesi 2 Produksi',
                        'komoditas' => $namaKom,
                        'keterangan' => "Komoditas '{$namaKom}' tidak ditemukan pada master komoditas.",
                    ];
                    continue;
                }

                $satuan = $item['satuan'] ?? $komoditas->satuan ?? 'KG';

                // Ekstraksi Jadwal Panen Bulanan
                $panenBulanan = $this->extractMonthlyHarvest($item);

                foreach ($panenBulanan as $bulan => $qtyProduksi) {
                    if ($bulan < 1 || $bulan > 12) {
                        continue;
                    }

                    // Tulis / update 1 baris per bulan panen
                    KetersediaanKomoditas::updateOrCreate(
                        [
                            'id_wilayah' => $idWilayah,
                            'id_komoditas' => $komoditas->id,
                            'tahun' => $tahun,
                            'bulan' => $bulan,
                        ],
                        [
                            'jumlah_produksi' => $qtyProduksi,
                            'satuan' => $satuan,
                            'id_sesi_survei' => $sesi->id,
                            'created_at' => now(),
                        ]
                    );

                    $updatedCount++;
                    $detail[] = [
                        'komoditas' => $komoditas->nama,
                        'tahun' => $tahun,
                        'bulan' => $bulan,
                        'jumlah_produksi' => $qtyProduksi,
                        'satuan' => $satuan,
                    ];
                }
            }
        }

        return [
            'count' => $updatedCount,
            'detail' => $detail,
            'data_gaps' => $dataGaps,
        ];
    }

    /**
     * Ekstraksi jadwal panen bulanan dari berbagai bentuk payload.
     * Mengembalikan map: [ bulan (1-12) => qty_produksi ]
     */
    protected function extractMonthlyHarvest(array $item): array
    {
        $hasil = [];

        // Bentuk 1: 'jadwal_panen' => [ ['bulan' => 3, 'panen' => true, 'jumlah_produksi' => 18000], ... ]
        if (isset($item['jadwal_panen']) && is_array($item['jadwal_panen'])) {
            foreach ($item['jadwal_panen'] as $jp) {
                if (is_array($jp) && isset($jp['bulan'])) {
                    $b = (int) $jp['bulan'];
                    $isPanen = $jp['panen'] ?? true;
                    $qty = (float) ($jp['jumlah_produksi'] ?? $jp['produksi'] ?? $jp['jumlah'] ?? 0);
                    if ($isPanen && $qty > 0) {
                        $hasil[$b] = $qty;
                    }
                }
            }
        }

        // Bentuk 2: 'panen_bulanan' => [ 1 => 0, 2 => 0, 3 => 18000, ... ]
        if (isset($item['panen_bulanan']) && is_array($item['panen_bulanan'])) {
            foreach ($item['panen_bulanan'] as $b => $qty) {
                if (is_numeric($b) && is_numeric($qty) && (float) $qty > 0) {
                    $hasil[(int) $b] = (float) $qty;
                }
            }
        }

        // Bentuk 3: 'bulan' dan 'jumlah_produksi' langsung di root item
        if (isset($item['bulan']) && (isset($item['jumlah_produksi']) || isset($item['produksi']) || isset($item['jumlah']))) {
            $b = (int) $item['bulan'];
            $qty = (float) ($item['jumlah_produksi'] ?? $item['produksi'] ?? $item['jumlah'] ?? 0);
            if ($qty > 0) {
                $hasil[$b] = $qty;
            }
        }

        // Bentuk 4: Key 'bulan_1', 'bulan_2', ...
        for ($m = 1; $m <= 12; $m++) {
            if (isset($item['bulan_'.$m]) && is_numeric($item['bulan_'.$m]) && (float) $item['bulan_'.$m] > 0) {
                $hasil[$m] = (float) $item['bulan_'.$m];
            }
        }

        return $hasil;
    }

    /**
     * Sinkronisasi Sesi 3 (Standar Kebutuhan Konsumsi).
     * Menetapkan satuan_periode = 'bulanan', gender aggregation, dan versioning standar (berlaku_mulai/sampai).
     */
    public function syncSesi3Konsumsi(SesiSurvei $sesi): array
    {
        $jawabanKonsumsi = $sesi->jawaban->filter(function (Jawaban $j) {
            $modulKode = strtolower($j->modul?->kode ?? '');
            $pertanyaanKode = strtolower($j->pertanyaan?->kode_pertanyaan ?? '');
            return str_contains($modulKode, 'konsum') || str_contains($modulKode, 'standar') ||
                   str_contains($modulKode, 'sesi_3') || str_contains($modulKode, 'sesi3') ||
                   str_contains($pertanyaanKode, 'konsum') || str_contains($pertanyaanKode, 'standar');
        });

        $updatedCount = 0;
        $detail = [];
        $dataGaps = [];
        $berlakuMulai = $sesi->tanggal_survei ? $sesi->tanggal_survei->toDateString() : now()->toDateString();

        foreach ($jawabanKonsumsi as $j) {
            $payload = $j->nilai_json;
            if (!is_array($payload)) {
                continue;
            }

            $items = array_is_list($payload) ? $payload : [$payload];

            foreach ($items as $item) {
                if (!is_array($item)) {
                    continue;
                }

                $komoditas = $this->resolveKomoditas($item);
                if (!$komoditas) {
                    $namaKom = $item['komoditas'] ?? $item['nama_komoditas'] ?? $item['nama'] ?? 'Tidak Diketahui';
                    $dataGaps[] = [
                        'sesi_id' => $sesi->id,
                        'modul' => 'Sesi 3 Konsumsi',
                        'komoditas' => $namaKom,
                        'keterangan' => "Komoditas '{$namaKom}' tidak ditemukan pada master komoditas.",
                    ];
                    continue;
                }

                $satuan = $item['satuan'] ?? 'kg/orang/bulan';
                $sumber = $item['sumber'] ?? "Survei Sesi #{$sesi->id} ({$sesi->wilayah?->nama})";
                $sektor = $item['sektor'] ?? 'pangan';

                // Ekstraksi nilai konsumsi per kelompok umur
                $standarPerUmur = $this->extractConsumptionStandards($item);

                foreach ($standarPerUmur as $kelompokUmur => $nilaiStandar) {
                    if ($nilaiStandar === null || (float) $nilaiStandar <= 0) {
                        continue;
                    }

                    // Terapkan versioning koefisien:
                    // Cari standar aktif saat ini untuk komoditas & kelompok umur ini
                    $standarAktif = StandarKebutuhanKomoditas::where('id_komoditas', $komoditas->id)
                        ->where('kelompok_umur', $kelompokUmur)
                        ->whereNull('berlaku_sampai')
                        ->latest('berlaku_mulai')
                        ->first();

                    if ($standarAktif) {
                        if ($standarAktif->berlaku_mulai->toDateString() === $berlakuMulai) {
                            // Update row yang sama jika berlaku_mulai persis sama
                            $standarAktif->update([
                                'per_kapita_harian' => $nilaiStandar,
                                'satuan' => $satuan,
                                'satuan_periode' => 'bulanan',
                                'sumber' => $sumber,
                            ]);
                            $updatedCount++;
                            $detail[] = [
                                'komoditas' => $komoditas->nama,
                                'kelompok_umur' => $kelompokUmur,
                                'nilai' => $nilaiStandar,
                                'satuan_periode' => 'bulanan',
                                'aksi' => 'updated_existing_version',
                            ];
                            continue;
                        } elseif ($standarAktif->berlaku_mulai->toDateString() < $berlakuMulai) {
                            // Tutup masa berlaku standar lama 1 hari sebelum standar baru berlaku
                            $tglTutup = Carbon::parse($berlakuMulai)->subDay()->toDateString();
                            $standarAktif->update(['berlaku_sampai' => $tglTutup]);
                        }
                    }

                    // Buat standar baru dengan berlaku_mulai baru
                    StandarKebutuhanKomoditas::create([
                        'sektor' => $sektor,
                        'id_komoditas' => $komoditas->id,
                        'kelompok_umur' => $kelompokUmur,
                        'per_kapita_harian' => $nilaiStandar,
                        'satuan' => $satuan,
                        'satuan_periode' => 'bulanan',
                        'sumber' => $sumber,
                        'berlaku_mulai' => $berlakuMulai,
                        'berlaku_sampai' => null,
                        'created_at' => now(),
                    ]);

                    $updatedCount++;
                    $detail[] = [
                        'komoditas' => $komoditas->nama,
                        'kelompok_umur' => $kelompokUmur,
                        'nilai' => $nilaiStandar,
                        'satuan_periode' => 'bulanan',
                        'aksi' => 'created_new_version',
                    ];
                }
            }
        }

        return [
            'count' => $updatedCount,
            'detail' => $detail,
            'data_gaps' => $dataGaps,
        ];
    }

    /**
     * Ekstraksi standar konsumsi per kelompok umur dari payload.
     * Mengembalikan map: [ kelompok_umur => nilai (agregat L+P / direct) ]
     */
    protected function extractConsumptionStandards(array $item): array
    {
        $hasil = [];

        $rincian = $item['standar'] ?? $item['rincian'] ?? $item['nilai'] ?? $item;

        foreach (self::KELOMPOK_UMUR as $ku) {
            if (isset($rincian[$ku])) {
                $val = $rincian[$ku];
                if (is_numeric($val)) {
                    $hasil[$ku] = (float) $val;
                } elseif (is_array($val)) {
                    // Agregasi Laki-Laki + Perempuan
                    $laki = (float) ($val['laki_laki'] ?? $val['laki'] ?? $val['l'] ?? 0);
                    $perempuan = (float) ($val['perempuan'] ?? $val['wanita'] ?? $val['p'] ?? 0);
                    $total = $val['nilai'] ?? $val['total'] ?? null;
                    if ($total !== null && (float) $total > 0 && ($laki + $perempuan == 0)) {
                        $hasil[$ku] = (float) $total;
                    } else {
                        $hasil[$ku] = $laki + $perempuan;
                    }
                }
            }
        }

        return $hasil;
    }

    /**
     * Resolusi Komoditas dari database master berdasarkan ID atau Nama.
     */
    protected function resolveKomoditas(array $item): ?Komoditas
    {
        if (!empty($item['id_komoditas'])) {
            $k = Komoditas::find($item['id_komoditas']);
            if ($k) {
                return $k;
            }
        }

        $nama = $item['komoditas'] ?? $item['nama_komoditas'] ?? $item['nama'] ?? null;
        if (!empty($nama) && is_string($nama)) {
            $k = Komoditas::where('nama', $nama)
                ->orWhereRaw('LOWER(nama) = ?', [strtolower(trim($nama))])
                ->first();
            if ($k) {
                return $k;
            }
        }

        return null;
    }
}

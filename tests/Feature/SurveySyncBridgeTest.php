<?php

namespace Tests\Feature;

use App\Models\Master\Komoditas;
use App\Models\Pengguna;
use App\Models\Perencanaan\DemografiDesa;
use App\Models\Perencanaan\HasilKalkulasi;
use App\Models\Perencanaan\KebutuhanKomoditas;
use App\Models\Perencanaan\KetersediaanKomoditas;
use App\Models\Perencanaan\StandarKebutuhanKomoditas;
use App\Models\Survei\Jawaban;
use App\Models\Survei\ModulSurvei;
use App\Models\Survei\Pertanyaan;
use App\Models\Survei\SesiSurvei;
use App\Models\Tenant\Entitas;
use App\Models\Tenant\Wilayah;
use App\Services\Perencanaan\KalkulasiKebutuhanService;
use App\Services\Survei\SurveySyncService;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SurveySyncBridgeTest extends TestCase
{
    protected Wilayah $wilayahTest;
    protected Pengguna $petugasTest;
    protected ModulSurvei $modulDemo;
    protected ModulSurvei $modulProd;
    protected ModulSurvei $modulKonsum;

    protected function setUp(): void
    {
        parent::setUp();

        // Siapkan master data untuk test
        $this->wilayahTest = Wilayah::firstOrCreate(
            ['nama' => 'Desa Uji Coba Sync'],
            ['tingkat' => 'desa']
        );

        $this->petugasTest = Pengguna::firstOrCreate(
            ['email' => 'surveyor.test@kdmp.local'],
            ['nama' => 'Petugas Surveyor Test', 'password' => bcrypt('secret'), 'is_active' => true]
        );

        $this->modulDemo = ModulSurvei::firstOrCreate(
            ['kode' => 'M1_DEMOGRAFI'],
            ['nama' => 'Sesi 1 Demografi Kependudukan', 'versi' => 'v1']
        );

        $this->modulProd = ModulSurvei::firstOrCreate(
            ['kode' => 'M1_PRODUKSI'],
            ['nama' => 'Sesi 2 Potensi Produksi Pertanian', 'versi' => 'v1']
        );

        $this->modulKonsum = ModulSurvei::firstOrCreate(
            ['kode' => 'M1_KONSUMSI'],
            ['nama' => 'Sesi 3 Standar Konsumsi Komoditas', 'versi' => 'v1']
        );
    }

    /**
     * Test Skenario A: Sesi 1 (Demografi) dengan angka terpisah Laki-Laki & Perempuan.
     * Bridge harus menjumlahkan (male + female) per kelompok_umur ke demografi_desa.
     */
    public function test_sesi_1_demografi_gender_aggregation_menjumlahkan_l_dan_p(): void
    {
        $sesi = SesiSurvei::create([
            'id_petugas' => $this->petugasTest->id,
            'id_wilayah' => $this->wilayahTest->id,
            'tahun' => 2026,
            'tanggal_survei' => '2026-08-15',
            'status' => 'disetujui',
        ]);

        $pertanyaan = Pertanyaan::firstOrCreate(
            ['id_modul' => $this->modulDemo->id, 'kode_pertanyaan' => 'DEMO_01'],
            ['teks_pertanyaan' => 'Data Demografi Per Kelompok Umur & Gender', 'tipe_jawaban' => 'json']
        );

        // Data terpisah per gender
        $payloadGender = [
            'balita' => ['laki_laki' => 55, 'perempuan' => 65],   // Total = 120
            'anak' => ['laki_laki' => 110, 'perempuan' => 130],   // Total = 240
            'remaja' => ['laki_laki' => 180, 'perempuan' => 200], // Total = 380
            'dewasa' => ['laki_laki' => 420, 'perempuan' => 480], // Total = 900
            'lansia' => ['laki_laki' => 90, 'perempuan' => 110],  // Total = 200
        ];

        Jawaban::create([
            'id_sesi' => $sesi->id,
            'id_modul' => $this->modulDemo->id,
            'id_pertanyaan' => $pertanyaan->id,
            'nilai_json' => $payloadGender,
            'sumber' => 'manual',
        ]);

        $syncService = app(SurveySyncService::class);
        $result = $syncService->syncSesi($sesi);

        $this->assertEquals(5, $result['demografi_updated']);

        // Verifikasi isi tabel demografi_desa
        $balita = DemografiDesa::where('id_wilayah', $this->wilayahTest->id)
            ->where('tahun', 2026)
            ->where('kelompok_umur', 'balita')
            ->first();
        $this->assertNotNull($balita);
        $this->assertEquals(120, $balita->jumlah_penduduk); // 55 + 65
        $this->assertEquals($sesi->id, $balita->id_sesi_survei);

        $dewasa = DemografiDesa::where('id_wilayah', $this->wilayahTest->id)
            ->where('tahun', 2026)
            ->where('kelompok_umur', 'dewasa')
            ->first();
        $this->assertNotNull($dewasa);
        $this->assertEquals(900, $dewasa->jumlah_penduduk); // 420 + 480
    }

    /**
     * Test Skenario B: Latest Session Selection (Latest Session Wins).
     * Sesi kedua yang lebih baru untuk wilayah & tahun yang sama harus memperbarui (UPDATE)
     * data demografi_desa tanpa menduplikasi baris.
     */
    public function test_latest_session_wins_pada_demografi_desa(): void
    {
        // Sesi 1 (Agustus 2026)
        $sesi1 = SesiSurvei::create([
            'id_petugas' => $this->petugasTest->id,
            'id_wilayah' => $this->wilayahTest->id,
            'tahun' => 2026,
            'tanggal_survei' => '2026-08-01',
            'status' => 'disetujui',
        ]);

        $p1 = Pertanyaan::firstOrCreate(
            ['id_modul' => $this->modulDemo->id, 'kode_pertanyaan' => 'DEMO_01'],
            ['teks_pertanyaan' => 'Demografi', 'tipe_jawaban' => 'json']
        );

        Jawaban::create([
            'id_sesi' => $sesi1->id,
            'id_modul' => $this->modulDemo->id,
            'id_pertanyaan' => $p1->id,
            'nilai_json' => [
                'balita' => ['laki_laki' => 50, 'perempuan' => 50], // 100
                'anak' => ['laki_laki' => 100, 'perempuan' => 100],  // 200
            ],
            'sumber' => 'manual',
        ]);

        $syncService = app(SurveySyncService::class);
        $syncService->syncSesi($sesi1);

        $countAwal = DemografiDesa::where('id_wilayah', $this->wilayahTest->id)->where('tahun', 2026)->count();

        // Sesi 2 (September 2026 - Lebih baru)
        $sesi2 = SesiSurvei::create([
            'id_petugas' => $this->petugasTest->id,
            'id_wilayah' => $this->wilayahTest->id,
            'tahun' => 2026,
            'tanggal_survei' => '2026-09-01',
            'status' => 'disetujui',
        ]);

        Jawaban::create([
            'id_sesi' => $sesi2->id,
            'id_modul' => $this->modulDemo->id,
            'id_pertanyaan' => $p1->id,
            'nilai_json' => [
                'balita' => ['laki_laki' => 70, 'perempuan' => 80], // Diperbarui jadi 150
                'anak' => ['laki_laki' => 120, 'perempuan' => 130], // Diperbarui jadi 250
            ],
            'sumber' => 'manual',
        ]);

        $syncService->syncSesi($sesi2);

        $countAkhir = DemografiDesa::where('id_wilayah', $this->wilayahTest->id)->where('tahun', 2026)->count();

        // Memastikan TIDAK ada baris duplikat
        $this->assertEquals($countAwal, $countAkhir);

        // Memastikan nilai di-update ke angka sesi 2
        $balitaUpdate = DemografiDesa::where('id_wilayah', $this->wilayahTest->id)
            ->where('tahun', 2026)
            ->where('kelompok_umur', 'balita')
            ->first();

        $this->assertEquals(150, $balitaUpdate->jumlah_penduduk);
        $this->assertEquals($sesi2->id, $balitaUpdate->id_sesi_survei);
    }

    /**
     * Test Skenario C: Sesi 3 Standar Konsumsi dengan satuan "kg/orang/bulan".
     * Memastikan standar_kebutuhan_komoditas tersimpan dengan satuan_periode = 'bulanan',
     * dan hitungKebutuhan() menggunakan faktor_hari = 1 (TIDAK dikalikan 30/hari).
     */
    public function test_sesi_3_standar_konsumsi_bulanan_dan_formula_kalkulasi(): void
    {
        $komoditas = Komoditas::firstOrCreate(
            ['nama' => 'Jagung Pipil Test'],
            ['kategori' => 'pangan_pokok', 'is_active' => true]
        );

        $sesi = SesiSurvei::create([
            'id_petugas' => $this->petugasTest->id,
            'id_wilayah' => $this->wilayahTest->id,
            'tahun' => 2026,
            'tanggal_survei' => '2026-08-20',
            'status' => 'disetujui',
        ]);

        $pKonsum = Pertanyaan::firstOrCreate(
            ['id_modul' => $this->modulKonsum->id, 'kode_pertanyaan' => 'KONSUM_01'],
            ['teks_pertanyaan' => 'Standar Konsumsi Bulanan', 'tipe_jawaban' => 'json']
        );

        // Standar bulanan per orang: balita 3.0 kg/bln, dewasa 10.0 kg/bln
        Jawaban::create([
            'id_sesi' => $sesi->id,
            'id_modul' => $this->modulKonsum->id,
            'id_pertanyaan' => $pKonsum->id,
            'nilai_json' => [
                'id_komoditas' => $komoditas->id,
                'satuan' => 'kg/orang/bulan',
                'sumber' => 'Survei Pola Pangan Harapan 2026',
                'standar' => [
                    'balita' => ['laki_laki' => 1.5, 'perempuan' => 1.5], // Sum = 3.0 kg/orang/bulan
                    'anak' => ['laki_laki' => 0.5, 'perempuan' => 0.5],
                    'remaja' => ['laki_laki' => 0.5, 'perempuan' => 0.5],
                    'dewasa' => ['laki_laki' => 5.0, 'perempuan' => 5.0], // Sum = 10.0 kg/orang/bulan
                    'lansia' => ['laki_laki' => 0.5, 'perempuan' => 0.5],
                ],
            ],
            'sumber' => 'manual',
        ]);

        // Siapkan juga data demografi: 100 balita, 200 dewasa
        DemografiDesa::updateOrCreate(
            ['id_wilayah' => $this->wilayahTest->id, 'tahun' => 2026, 'kelompok_umur' => 'balita'],
            ['jumlah_penduduk' => 100, 'id_sesi_survei' => $sesi->id]
        );
        DemografiDesa::updateOrCreate(
            ['id_wilayah' => $this->wilayahTest->id, 'tahun' => 2026, 'kelompok_umur' => 'dewasa'],
            ['jumlah_penduduk' => 200, 'id_sesi_survei' => $sesi->id]
        );
        foreach (['anak', 'remaja', 'lansia'] as $kelompokUmur) {
            DemografiDesa::updateOrCreate(
                ['id_wilayah' => $this->wilayahTest->id, 'tahun' => 2026, 'kelompok_umur' => $kelompokUmur],
                ['jumlah_penduduk' => 0, 'id_sesi_survei' => $sesi->id]
            );
        }

        $syncService = app(SurveySyncService::class);
        $syncService->syncSesi($sesi);

        // Verifikasi standar_kebutuhan_komoditas
        $standarBalita = StandarKebutuhanKomoditas::where('id_komoditas', $komoditas->id)
            ->where('kelompok_umur', 'balita')
            ->where('satuan_periode', 'bulanan')
            ->latest('berlaku_mulai')
            ->first();

        $this->assertNotNull($standarBalita);
        $this->assertEquals(3.0, (float) $standarBalita->per_kapita_harian);
        $this->assertEquals('bulanan', $standarBalita->satuan_periode);

        // Jalankan Kalkulasi Kebutuhan untuk Bulan 9 (September = 30 hari)
        $kalkulasiService = app(KalkulasiKebutuhanService::class);
        $kalkulasiService->hitungKebutuhan($this->wilayahTest->id, 2026, 9, $komoditas->id);

        // Cek kebutuhan bulanan balita: 100 orang * 3.0 kg/bln * faktor_hari(1) = 300 kg (BUKAN 100 * 3 * 30 = 9000 kg)
        $kebutuhanBalita = KebutuhanKomoditas::where('id_wilayah', $this->wilayahTest->id)
            ->where('id_komoditas', $komoditas->id)
            ->where('tahun', 2026)
            ->where('bulan', 9)
            ->where('kelompok_umur', 'balita')
            ->first();

        $this->assertNotNull($kebutuhanBalita);
        $this->assertEquals(300.0, (float) $kebutuhanBalita->kebutuhan_bulanan);

        // Cek kebutuhan dewasa: 200 orang * 10.0 kg/bln * 1 = 2000 kg
        $kebutuhanDewasa = KebutuhanKomoditas::where('id_wilayah', $this->wilayahTest->id)
            ->where('id_komoditas', $komoditas->id)
            ->where('tahun', 2026)
            ->where('bulan', 9)
            ->where('kelompok_umur', 'dewasa')
            ->first();

        $this->assertNotNull($kebutuhanDewasa);
        $this->assertEquals(2000.0, (float) $kebutuhanDewasa->kebutuhan_bulanan);
    }

    /**
     * Test Skenario D: Sesi 2 (Potensi Produksi) dengan Kalender Panen Multi-Bulan.
     * Memastikan terbentuk baris ketersediaan_komoditas terpisah untuk setiap bulan panen.
     */
    public function test_sesi_2_kalender_panen_multi_bulan_menghasilkan_baris_per_bulan(): void
    {
        $komoditas = Komoditas::firstOrCreate(
            ['nama' => 'Padi Ciherang Test'],
            ['kategori' => 'pangan_pokok', 'is_active' => true]
        );

        $sesi = SesiSurvei::create([
            'id_petugas' => $this->petugasTest->id,
            'id_wilayah' => $this->wilayahTest->id,
            'tahun' => 2026,
            'tanggal_survei' => '2026-08-10',
            'status' => 'disetujui',
        ]);

        $pProd = Pertanyaan::firstOrCreate(
            ['id_modul' => $this->modulProd->id, 'kode_pertanyaan' => 'PANEN_01'],
            ['teks_pertanyaan' => 'Kalender Panen Bulanan', 'tipe_jawaban' => 'json']
        );

        // Panen di Bulan 3 (Maret) 12.000 KG dan Bulan 8 (Agustus) 15.000 KG
        Jawaban::create([
            'id_sesi' => $sesi->id,
            'id_modul' => $this->modulProd->id,
            'id_pertanyaan' => $pProd->id,
            'nilai_json' => [
                'komoditas' => 'Padi Ciherang Test',
                'satuan' => 'KG',
                'jadwal_panen' => [
                    ['bulan' => 3, 'panen' => true, 'jumlah_produksi' => 12000],
                    ['bulan' => 8, 'panen' => true, 'jumlah_produksi' => 15000],
                    ['bulan' => 5, 'panen' => false, 'jumlah_produksi' => 0],
                ],
            ],
            'sumber' => 'manual',
        ]);

        $syncService = app(SurveySyncService::class);
        $result = $syncService->syncSesi($sesi);

        $this->assertEquals(2, $result['ketersediaan_updated']);

        // Verifikasi baris ketersediaan_komoditas bulan 3
        $panenBulan3 = KetersediaanKomoditas::where('id_wilayah', $this->wilayahTest->id)
            ->where('id_komoditas', $komoditas->id)
            ->where('tahun', 2026)
            ->where('bulan', 3)
            ->first();

        $this->assertNotNull($panenBulan3);
        $this->assertEquals(12000.0, (float) $panenBulan3->jumlah_produksi);

        // Verifikasi baris ketersediaan_komoditas bulan 8
        $panenBulan8 = KetersediaanKomoditas::where('id_wilayah', $this->wilayahTest->id)
            ->where('id_komoditas', $komoditas->id)
            ->where('tahun', 2026)
            ->where('bulan', 8)
            ->first();

        $this->assertNotNull($panenBulan8);
        $this->assertEquals(15000.0, (float) $panenBulan8->jumlah_produksi);

        // Memastikan bulan 5 yang tidak panen TIDAK dibuat
        $panenBulan5 = KetersediaanKomoditas::where('id_wilayah', $this->wilayahTest->id)
            ->where('id_komoditas', $komoditas->id)
            ->where('tahun', 2026)
            ->where('bulan', 5)
            ->first();

        $this->assertNull($panenBulan5);
    }

    /**
     * Test Skenario E: Komoditas yang belum terdaftar di master tabel.
     * Tidak boleh membuat komoditas dummy secara diam-diam, skip row tersebut tanpa error fatal,
     * dan laporkan sebagai data gap.
     */
    public function test_komoditas_tidak_terdaftar_di_master_dilaporkan_sebagai_data_gap(): void
    {
        $sesi = SesiSurvei::create([
            'id_petugas' => $this->petugasTest->id,
            'id_wilayah' => $this->wilayahTest->id,
            'tahun' => 2026,
            'tanggal_survei' => '2026-08-10',
            'status' => 'disetujui',
        ]);

        $pProd = Pertanyaan::firstOrCreate(
            ['id_modul' => $this->modulProd->id, 'kode_pertanyaan' => 'PANEN_02'],
            ['teks_pertanyaan' => 'Kalender Panen Komoditas Asing', 'tipe_jawaban' => 'json']
        );

        Jawaban::create([
            'id_sesi' => $sesi->id,
            'id_modul' => $this->modulProd->id,
            'id_pertanyaan' => $pProd->id,
            'nilai_json' => [
                'komoditas' => 'Komoditas Misterius Non Existent 999',
                'satuan' => 'KG',
                'jadwal_panen' => [
                    ['bulan' => 4, 'panen' => true, 'jumlah_produksi' => 5000],
                ],
            ],
            'sumber' => 'manual',
        ]);

        $syncService = app(SurveySyncService::class);
        $result = $syncService->syncSesi($sesi);

        $this->assertEquals(0, $result['ketersediaan_updated']);
        $this->assertNotEmpty($result['data_gaps']);
        $this->assertStringContainsString('Komoditas Misterius Non Existent 999', $result['data_gaps'][0]['komoditas']);

        // Komoditas tidak boleh ada di master komoditas
        $exists = Komoditas::where('nama', 'Komoditas Misterius Non Existent 999')->exists();
        $this->assertFalse($exists);
    }

    /**
     * Test Skenario F: Endpoint controller sinkronisasi via HTTP request.
     */
    public function test_post_sinkron_survey_via_controller_berhasil(): void
    {
        $user = Pengguna::where('email', 'manajer@kdmp-a.test')->first();
        $this->assertNotNull($user);

        // Buat sesi survei untuk Desa Mekar Jaya (Wilayah ID 1)
        $sesi = SesiSurvei::create([
            'id_petugas' => $user->id,
            'id_wilayah' => 1,
            'tahun' => 2026,
            'tanggal_survei' => '2026-08-25',
            'status' => 'disetujui',
        ]);

        $pertanyaan = Pertanyaan::firstOrCreate(
            ['id_modul' => $this->modulDemo->id, 'kode_pertanyaan' => 'DEMO_HTTP_01'],
            ['teks_pertanyaan' => 'Demografi via HTTP', 'tipe_jawaban' => 'json']
        );

        Jawaban::create([
            'id_sesi' => $sesi->id,
            'id_modul' => $this->modulDemo->id,
            'id_pertanyaan' => $pertanyaan->id,
            'nilai_json' => [
                'balita' => ['laki_laki' => 60, 'perempuan' => 70], // 130
            ],
            'sumber' => 'manual',
        ]);

        $response = $this->actingAs($user)->post(route('survei.sesi.sinkron', $sesi->id));

        $response->assertRedirect();
        $response->assertSessionHas('status');

        $balita = DemografiDesa::where('id_wilayah', 1)
            ->where('tahun', 2026)
            ->where('kelompok_umur', 'balita')
            ->first();

        $this->assertEquals(130, $balita->jumlah_penduduk);
    }

    /**
     * Test Skenario G: Tenant Scoping & Isolation.
     * Sesi survei dari wilayah koperasi lain tidak boleh dapat disinkronkan saat context koperasi berbeda aktif.
     */
    public function test_tenant_isolation_mencegah_sinkronisasi_lintas_koperasi(): void
    {
        $entitasA = Entitas::where('kode_entitas', 'KDMP-A')->first();
        $entitasB = Entitas::where('kode_entitas', 'KDMP-B')->first();

        $this->assertNotNull($entitasA);
        $this->assertNotNull($entitasB);

        // Buat sesi milik Wilayah Koperasi B
        $sesiB = SesiSurvei::create([
            'id_petugas' => $this->petugasTest->id,
            'id_wilayah' => $entitasB->id_wilayah,
            'tahun' => 2026,
            'tanggal_survei' => '2026-08-25',
            'status' => 'disetujui',
        ]);

        $pertanyaan = Pertanyaan::firstOrCreate(
            ['id_modul' => $this->modulDemo->id, 'kode_pertanyaan' => 'DEMO_TENANT_01'],
            ['teks_pertanyaan' => 'Demografi Tenant', 'tipe_jawaban' => 'json']
        );

        Jawaban::create([
            'id_sesi' => $sesiB->id,
            'id_modul' => $this->modulDemo->id,
            'id_pertanyaan' => $pertanyaan->id,
            'nilai_json' => [
                'balita' => ['laki_laki' => 50, 'perempuan' => 50],
            ],
            'sumber' => 'manual',
        ]);

        // Pasang context entitas aktif = Entitas A
        app()->instance('entitas_aktif', $entitasA->id_entitas);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Akses ditolak: Sesi survei wilayah');

        $syncService = app(SurveySyncService::class);
        $syncService->syncSesi($sesiB);
    }
}

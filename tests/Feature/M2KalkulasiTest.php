<?php

namespace Tests\Feature;

use App\Models\Pengguna;
use App\Models\Perencanaan\HasilKalkulasi;
use App\Models\Perencanaan\KebutuhanKomoditas;
use App\Services\Perencanaan\KalkulasiKebutuhanService;
use Tests\TestCase;

class M2KalkulasiTest extends TestCase
{
    public function test_service_kalkulasi_kebutuhan_menghitung_dengan_benar(): void
    {
        $service = app(KalkulasiKebutuhanService::class);
        $result = $service->hitungKebutuhan(1, 2026, 3);

        $this->assertEquals(1, $result['id_wilayah']);
        $this->assertEquals(2026, $result['tahun']);
        $this->assertEquals(3, $result['bulan']);
        $this->assertEquals(31, $result['jumlah_hari']);
        $this->assertGreaterThan(0, $result['total_baris_kebutuhan']);
        $this->assertGreaterThan(0, $result['total_hasil_kalkulasi']);

        // Cek bahwa kebutuhan_komoditas terisi
        $countKebutuhan = KebutuhanKomoditas::where('id_wilayah', 1)
            ->where('tahun', 2026)
            ->where('bulan', 3)
            ->count();
        $this->assertGreaterThan(0, $countKebutuhan);

        // Cek bahwa hasil_kalkulasi terisi dengan status surplus/defisit
        $hasilBeras = HasilKalkulasi::where('id_wilayah', 1)
            ->where('tahun', 2026)
            ->where('bulan', 3)
            ->whereHas('komoditas', fn ($q) => $q->where('nama', 'Beras'))
            ->first();

        $this->assertNotNull($hasilBeras);
        $this->assertEquals('surplus', $hasilBeras->status_surplus_defisit);
        $this->assertGreaterThan(0, (float) $hasilBeras->total_kebutuhan);
        $this->assertEquals(18000.0, (float) $hasilBeras->total_ketersediaan);

        // Cek Gula Pasir defisit
        $hasilGula = HasilKalkulasi::where('id_wilayah', 1)
            ->where('tahun', 2026)
            ->where('bulan', 3)
            ->whereHas('komoditas', fn ($q) => $q->where('nama', 'Gula Pasir'))
            ->first();

        $this->assertNotNull($hasilGula);
        $this->assertEquals('defisit', $hasilGula->status_surplus_defisit);
        $this->assertEquals(0.0, (float) $hasilGula->total_ketersediaan);
    }

    public function test_kalkulasi_bersifat_idempotent_tidak_duplikasi(): void
    {
        $service = app(KalkulasiKebutuhanService::class);

        // Jalankan kalkulasi pertama
        $service->hitungKebutuhan(1, 2026, 3);
        $count1 = KebutuhanKomoditas::where('id_wilayah', 1)->where('tahun', 2026)->where('bulan', 3)->count();
        $countHasil1 = HasilKalkulasi::where('id_wilayah', 1)->where('tahun', 2026)->where('bulan', 3)->count();

        // Jalankan kalkulasi kedua dengan parameter sama
        $service->hitungKebutuhan(1, 2026, 3);
        $count2 = KebutuhanKomoditas::where('id_wilayah', 1)->where('tahun', 2026)->where('bulan', 3)->count();
        $countHasil2 = HasilKalkulasi::where('id_wilayah', 1)->where('tahun', 2026)->where('bulan', 3)->count();

        $this->assertEquals($count1, $count2);
        $this->assertEquals($countHasil1, $countHasil2);
    }

    public function test_halaman_kebutuhan_komoditas_dapat_diakses_oleh_manajer(): void
    {
        $user = Pengguna::where('email', 'manajer@kdmp-a.test')->first();
        $this->assertNotNull($user);

        $response = $this->actingAs($user)->get(route('perencanaan.kebutuhan-komoditas.index', [
            'tahun' => 2026,
            'bulan' => 3,
        ]));

        $response->assertStatus(200);
        $response->assertSee('Kebutuhan Komoditas (Hasil Kalkulasi)');
        $response->assertSee('Beras');
    }

    public function test_halaman_neraca_komoditas_dapat_diakses_dan_menampilkan_status(): void
    {
        $user = Pengguna::where('email', 'manajer@kdmp-a.test')->first();
        $this->assertNotNull($user);

        $response = $this->actingAs($user)->get(route('perencanaan.neraca-komoditas.index', [
            'tahun' => 2026,
            'bulan' => 3,
        ]));

        $response->assertStatus(200);
        $response->assertSee('Surplus Defisit');
        $response->assertSee('SURPLUS');
        $response->assertSee('DEFISIT');
    }

    public function test_post_kalkulasi_berhasil_via_controller(): void
    {
        $user = Pengguna::where('email', 'manajer@kdmp-a.test')->first();

        $response = $this->actingAs($user)->post(route('perencanaan.kebutuhan-komoditas.kalkulasi'), [
            'tahun' => 2026,
            'bulan' => 4, // April 30 hari
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('status');

        // Verifikasi bulan 4 terisi
        $countKebutuhanBulan4 = KebutuhanKomoditas::where('id_wilayah', 1)
            ->where('tahun', 2026)
            ->where('bulan', 4)
            ->count();
        $this->assertGreaterThan(0, $countKebutuhanBulan4);
    }

    public function test_defisit_dapat_memicu_buat_permintaan_pengadaan_dengan_data_terisi(): void
    {
        $user = Pengguna::where('email', 'manajer@kdmp-a.test')->first();
        $hasilDefisit = HasilKalkulasi::where('id_wilayah', 1)
            ->where('status_surplus_defisit', 'defisit')
            ->first();

        $this->assertNotNull($hasilDefisit);

        $response = $this->actingAs($user)->get(route('perencanaan.neraca-komoditas.index', [
            'tahun' => 2026,
            'bulan' => 3,
        ]));

        $response->assertStatus(200);
        $response->assertSee('Buat Permintaan Pengadaan');

        $redirect = $this->actingAs($user)->get(route('perencanaan.permintaan-pengadaan.from-hasil', [
            'hasil' => $hasilDefisit->id_hasil,
        ]));

        $redirect->assertRedirect(route('perencanaan.permintaan-pengadaan.create', [
            'id_hasil' => $hasilDefisit->id_hasil,
        ]));

        $form = $this->actingAs($user)->get(route('perencanaan.permintaan-pengadaan.create', [
            'id_hasil' => $hasilDefisit->id_hasil,
        ]));

        $form->assertStatus(200);
        $form->assertSee('PR - Pengadaan Defisit');
        $form->assertSee($hasilDefisit->komoditas->nama ?? 'Komoditas');
    }

    public function test_dashboard_m2_dapat_diakses_dan_menampilkan_ringkasan_komoditas(): void
    {
        $user = Pengguna::where('email', 'manajer@kdmp-a.test')->first();
        $this->assertNotNull($user);

        // Pastikan ada data kalkulasi
        $service = app(KalkulasiKebutuhanService::class);
        $service->hitungKebutuhan(1, 2026, 3);

        $response = $this->actingAs($user)->get(route('perencanaan.dashboard', [
            'tahun' => 2026,
            'bulan' => 3,
        ]));

        $response->assertStatus(200);
        $response->assertSee('Dashboard Kalkulasi dan Perencanaan');
        $response->assertSee('Distribusi Status Neraca Komoditas');
        $response->assertSee('Surplus');
        $response->assertSee('Defisit');
        $response->assertSee('Sumber Data Survei Lapangan');
        $response->assertSee('Jalankan Kalkulasi');
    }

    public function test_laporan_m2_dapat_diakses_dan_menampilkan_rekapitulasi(): void
    {
        $user = Pengguna::where('email', 'manajer@kdmp-a.test')->first();
        $this->assertNotNull($user);

        $response = $this->actingAs($user)->get(route('perencanaan.laporan.index', [
            'tahun' => 2026,
            'bulan' => 3,
        ]));

        $response->assertStatus(200);
        $response->assertSee('Laporan Rekapitulasi Neraca Komoditas Pangan');
        $response->assertSee('LAPORAN KALKULASI');
        $response->assertSee('Cetak / Ekspor PDF');
    }

    public function test_detail_kebutuhan_komoditas_dapat_diakses(): void
    {
        $user = Pengguna::where('email', 'manajer@kdmp-a.test')->first();
        $this->assertNotNull($user);

        $itemKebutuhan = KebutuhanKomoditas::where('id_wilayah', 1)->first();
        $this->assertNotNull($itemKebutuhan);

        $response = $this->actingAs($user)->get(route('perencanaan.kebutuhan-komoditas.show', [
            'kebutuhan_komodita' => $itemKebutuhan->id,
        ]));

        $response->assertStatus(200);
        $response->assertSee('Rincian Perhitungan Kebutuhan Berdasarkan Kelompok Umur');
        $response->assertSee($itemKebutuhan->komoditas->nama ?? 'Komoditas');
    }

    public function test_detail_neraca_komoditas_dapat_diakses(): void
    {
        $user = Pengguna::where('email', 'manajer@kdmp-a.test')->first();
        $this->assertNotNull($user);

        $hasilNeraca = HasilKalkulasi::where('id_wilayah', 1)->first();
        $this->assertNotNull($hasilNeraca);

        $response = $this->actingAs($user)->get(route('perencanaan.neraca-komoditas.show', [
            'neraca_komodita' => $hasilNeraca->id_hasil,
        ]));

        $response->assertStatus(200);
        $response->assertSee('Total Kebutuhan Konsumsi');
        $response->assertSee('Ketersediaan Produksi Panen');
        $response->assertSee('Selisih Neraca');
        $response->assertSee($hasilNeraca->komoditas->nama ?? 'Komoditas');
    }
}

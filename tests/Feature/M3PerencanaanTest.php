<?php

namespace Tests\Feature;

use App\Models\Pengguna;
use App\Models\Perencanaan\HasilKalkulasi;
use App\Models\Perencanaan\KetersediaanKomoditas;
use App\Models\Perencanaan\PermintaanPengadaan;
use App\Models\Survei\PemenuhanKomoditas;
use App\Models\Survei\SesiSurvei;
use App\Services\Perencanaan\PerbandinganHargaService;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class M3PerencanaanTest extends TestCase
{
    use WithoutMiddleware;

    protected function setUp(): void
    {
        parent::setUp();
        DB::beginTransaction();
    }

    protected function tearDown(): void
    {
        DB::rollBack();
        parent::tearDown();
    }

    public function test_store_pr_menyimpan_header_detail_dan_lokasi_tujuan_valid(): void
    {
        $user = Pengguna::where('email', 'manajer@kdmp-a.test')->firstOrFail();
        $hasil = HasilKalkulasi::create([
            'id_wilayah' => 1,
            'id_komoditas' => 1,
            'tahun' => 2026,
            'bulan' => 10,
            'total_kebutuhan' => 100,
            'total_ketersediaan' => 25,
            'selisih' => -75,
            'status_surplus_defisit' => 'defisit',
            'id_unit_usaha_rekomendasi' => 1,
            'status' => 'disetujui',
        ]);

        $response = $this->actingAs($user)->post(route('perencanaan.permintaan-pengadaan.store'), [
            'id_hasil' => $hasil->id_hasil,
            'id_barang' => 1,
            'id_gudang' => 1,
            'tgl_pengajuan' => '2026-10-30',
            'catatan' => 'Kebutuhan pengadaan test',
        ]);

        $response->assertRedirect(route('perencanaan.permintaan-pengadaan.index'));
        $this->assertDatabaseHas('permintaan_pengadaan', [
            'id_entitas' => 1,
            'id_unit_usaha' => 1,
            'id_gudang' => 1,
            'status' => 'draft',
        ]);

        $pr = PermintaanPengadaan::where('id_entitas', 1)->latest('id_permintaan')->firstOrFail();
        $this->assertSame(1, $pr->detail()->count());
        $this->assertSame(75.0, (float) $pr->detail()->first()->jumlah_diminta);
        $this->assertSame($hasil->id_hasil, $pr->detail()->first()->id_hasil);
    }

    public function test_store_pr_menolak_gudang_milik_entitas_lain(): void
    {
        $user = Pengguna::where('email', 'manajer@kdmp-a.test')->firstOrFail();
        $hasil = HasilKalkulasi::create([
            'id_wilayah' => 1,
            'id_komoditas' => 1,
            'tahun' => 2026,
            'bulan' => 10,
            'total_kebutuhan' => 100,
            'total_ketersediaan' => 25,
            'selisih' => -75,
            'status_surplus_defisit' => 'defisit',
            'id_unit_usaha_rekomendasi' => 1,
            'status' => 'disetujui',
        ]);

        $response = $this->actingAs($user)->post(route('perencanaan.permintaan-pengadaan.store'), [
            'id_hasil' => $hasil->id_hasil,
            'id_barang' => 1,
            'id_gudang' => 2,
            'tgl_pengajuan' => '2026-10-30',
        ]);

        $response->assertNotFound();
        $this->assertSame(0, PermintaanPengadaan::where('id_entitas', 1)->count());
    }

    public function test_calon_supplier_mengambil_potensi_produksi_desa_dan_kosong_dengan_benar(): void
    {
        $service = app(PerbandinganHargaService::class);
        KetersediaanKomoditas::create([
            'id_wilayah' => 1,
            'id_komoditas' => 1,
            'tahun' => 2026,
            'bulan' => 10,
            'jumlah_produksi' => 500,
            'satuan' => 'KG',
        ]);

        $candidates = $service->calonSupplier(1, 2026, 10);
        $this->assertCount(1, $candidates);
        $this->assertSame(500.0, (float) $candidates->first()->jumlah_produksi);
        $this->assertFalse(array_key_exists('stok_aktual', $candidates->first()->getAttributes()));
        $this->assertCount(0, $service->calonSupplier(1, 2026, 11));
    }

    public function test_perbandingan_harga_mengurutkan_desa_dan_mengabaikan_satuan_berbeda(): void
    {
        $petugas = Pengguna::where('email', 'manajer@kdmp-a.test')->firstOrFail();
        $sesiA = SesiSurvei::create([
            'id_petugas' => $petugas->id,
            'id_wilayah' => 1,
            'tahun' => 2026,
            'tanggal_survei' => '2026-10-10',
            'status' => 'disetujui',
        ]);
        $sesiB = SesiSurvei::create([
            'id_petugas' => $petugas->id,
            'id_wilayah' => 2,
            'tahun' => 2026,
            'tanggal_survei' => '2026-10-12',
            'status' => 'disetujui',
        ]);
        $sesiLain = SesiSurvei::create([
            'id_petugas' => $petugas->id,
            'id_wilayah' => 2,
            'tahun' => 2025,
            'tanggal_survei' => '2025-10-12',
            'status' => 'disetujui',
        ]);
        $sesiDraft = SesiSurvei::create([
            'id_petugas' => $petugas->id,
            'id_wilayah' => 1,
            'tahun' => 2026,
            'tanggal_survei' => '2026-10-13',
            'status' => 'draft',
        ]);

        PemenuhanKomoditas::create([
            'id_sesi' => $sesiA->id,
            'id_komoditas' => 1,
            'sumber_pemenuhan' => 'Pasar',
            'nama_tempat' => 'Pasar A',
            'harga' => 12000,
            'satuan_harga' => 'KG',
            'tanggal_survei' => '2026-10-10',
        ]);
        PemenuhanKomoditas::create([
            'id_sesi' => $sesiB->id,
            'id_komoditas' => 1,
            'sumber_pemenuhan' => 'Pasar',
            'nama_tempat' => 'Pasar B',
            'harga' => 10000,
            'satuan_harga' => 'KG',
            'tanggal_survei' => '2026-10-12',
        ]);
        PemenuhanKomoditas::create([
            'id_sesi' => $sesiB->id,
            'id_komoditas' => 1,
            'sumber_pemenuhan' => 'Pasar',
            'nama_tempat' => 'Pasar B',
            'harga' => 9,
            'satuan_harga' => 'LITER',
            'tanggal_survei' => '2026-10-12',
        ]);
        PemenuhanKomoditas::create([
            'id_sesi' => $sesiLain->id,
            'id_komoditas' => 1,
            'sumber_pemenuhan' => 'Pasar',
            'nama_tempat' => 'Pasar Lama',
            'harga' => 1,
            'satuan_harga' => 'KG',
            'tanggal_survei' => '2025-10-12',
        ]);
        PemenuhanKomoditas::create([
            'id_sesi' => $sesiDraft->id,
            'id_komoditas' => 1,
            'sumber_pemenuhan' => 'Pasar',
            'nama_tempat' => 'Pasar Draft',
            'harga' => 1,
            'satuan_harga' => 'KG',
            'tanggal_survei' => '2026-10-13',
        ]);

        $result = app(PerbandinganHargaService::class)->bandingkan(1, 1, 2026, 10);

        $this->assertSame(10000.0, $result['lowest']);
        $this->assertSame(12000.0, $result['highest']);
        $this->assertSame(2000.0, $result['difference']);
        $this->assertSame('KG', $result['satuan']);
        $this->assertSame([2, 1], $result['items']->pluck('id_wilayah_sumber')->all());
        $this->assertSame([1, 2], $result['items']->pluck('rank_harga')->all());
    }

    public function test_perbandingan_harga_mengembalikan_ringkasan_kosong(): void
    {
        $result = app(PerbandinganHargaService::class)->bandingkan(1, 5, 2026, 12);

        $this->assertCount(0, $result['items']);
        $this->assertNull($result['lowest']);
        $this->assertNull($result['highest']);
        $this->assertNull($result['difference']);
        $this->assertNull($result['satuan']);
    }

    public function test_store_pr_menggunakan_harga_survei_valid_dan_menghitung_subtotal(): void
    {
        $user = Pengguna::where('email', 'manajer@kdmp-a.test')->firstOrFail();
        $sesi = SesiSurvei::create([
            'id_petugas' => $user->id,
            'id_wilayah' => 1,
            'tahun' => 2026,
            'tanggal_survei' => '2026-10-10',
            'status' => 'disetujui',
        ]);
        PemenuhanKomoditas::create([
            'id_sesi' => $sesi->id,
            'id_komoditas' => 1,
            'sumber_pemenuhan' => 'Pasar',
            'nama_tempat' => 'Pasar A',
            'harga' => 12000,
            'satuan_harga' => 'KG',
            'tanggal_survei' => '2026-10-10',
        ]);
        $hasil = HasilKalkulasi::create([
            'id_wilayah' => 1,
            'id_komoditas' => 1,
            'tahun' => 2026,
            'bulan' => 10,
            'total_kebutuhan' => 100,
            'total_ketersediaan' => 25,
            'selisih' => -2,
            'status_surplus_defisit' => 'defisit',
            'id_unit_usaha_rekomendasi' => 1,
            'status' => 'disetujui',
        ]);

        $response = $this->actingAs($user)->post(route('perencanaan.permintaan-pengadaan.store'), [
            'id_hasil' => $hasil->id_hasil,
            'id_barang' => 1,
            'id_gudang' => 1,
            'tgl_pengajuan' => '2026-10-30',
        ]);

        $response->assertRedirect(route('perencanaan.permintaan-pengadaan.index'));
        $pr = PermintaanPengadaan::where('id_entitas', 1)->latest('id_permintaan')->firstOrFail();
        $detail = $pr->detail()->firstOrFail();
        $this->assertSame(12000.0, (float) $detail->harga_perkiraan);
        $this->assertSame(24000.0, (float) $detail->subtotal);
        $this->assertSame(24000.0, (float) $pr->total_nilai);
    }

    public function test_index_perencanaan_menampilkan_kebutuhan_defisit_dari_kalkulasi(): void
    {
        $user = Pengguna::where('email', 'manajer@kdmp-a.test')->firstOrFail();
        HasilKalkulasi::create([
            'id_wilayah' => 1,
            'id_komoditas' => 1,
            'tahun' => 2026,
            'bulan' => 7,
            'total_kebutuhan' => 100,
            'total_ketersediaan' => 25,
            'selisih' => -75,
            'status_surplus_defisit' => 'defisit',
            'id_unit_usaha_rekomendasi' => 1,
            'status' => 'disetujui',
        ]);

        $response = $this->actingAs($user)->get(route('perencanaan.permintaan-pengadaan.index'));

        $response->assertOk()
            ->assertSee('Kebutuhan yang Perlu Direncanakan')
            ->assertSee('Buat draft PR')
            ->assertSee('Periode Genap')
            ->assertSee('Periode Ganjil')
            ->assertSee('komoditas');
    }

    public function test_halaman_perbandingan_harga_menampilkan_data_dan_periode_yang_dipilih(): void
    {
        $petugas = Pengguna::where('email', 'manajer@kdmp-a.test')->firstOrFail();
        $sesi = SesiSurvei::create([
            'id_petugas' => $petugas->id,
            'id_wilayah' => 1,
            'tahun' => 2026,
            'tanggal_survei' => '2026-10-10',
            'status' => 'disetujui',
        ]);
        PemenuhanKomoditas::create([
            'id_sesi' => $sesi->id,
            'id_komoditas' => 1,
            'sumber_pemenuhan' => 'Pasar',
            'nama_tempat' => 'Pasar A',
            'harga' => 12000,
            'satuan_harga' => 'KG',
            'tanggal_survei' => '2026-10-10',
        ]);

        $response = $this->actingAs($petugas)->get(route('perencanaan.perbandingan-harga.index', [
            'id_komoditas' => 1,
            'tahun' => 2026,
            'periode' => 'genap',
        ]));

        $response->assertOk()
            ->assertSee('Rp 12.000,00')
            ->assertSee('Genap (Juli–Desember)');
    }
}

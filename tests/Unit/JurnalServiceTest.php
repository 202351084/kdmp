<?php

namespace Tests\Unit;

use App\Exceptions\Finance\AkunTidakDitemukanException;
use App\Exceptions\Finance\JurnalSudahDibalikException;
use App\Exceptions\Finance\JurnalTidakBalanceException;
use App\Exceptions\Finance\PeriodeTutupException;
use App\Models\Akuntansi\JurnalHeader;
use App\Models\Tenant\Entitas;
use App\Models\Tenant\PeriodeAkuntansi;
use App\Services\Finance\JurnalService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Tests\TestCase;

/**
 * JurnalServiceTest â€” Unit test setiap skenario JurnalService.
 *
 * Fokus: postingManual() dan balik() karena tidak membutuhkan setup
 * master_detail_transaksi / master_transaksi (yang kompleks untuk disiapkan di test).
 * posting() dengan template diuji via integration test terpisah.
 *
 * CARA JALANKAN:
 *   php artisan test --filter JurnalServiceTest
 */
class JurnalServiceTest extends TestCase
{
    use DatabaseTruncation;

    private JurnalService $service;
    private Entitas $entitas;
    private int $idKoperasi;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(JurnalService::class);

        // Buat wilayah dulu (required FK dari entitas)
        $sfx = uniqid();
        $idWilayah = \DB::table('wilayah')->insertGetId([
            'tingkat' => 'desa', 'nama' => 'Desa Test ' . $sfx, 'created_at' => now(),
        ]);

        // Buat koperasi dengan semua field wajib
        $this->idKoperasi = \DB::table('entitas')->insertGetId([
            'kode_entitas'   => 'KT-' . $sfx,
            'nama_entitas'   => 'Koperasi Test',
            'id_wilayah'      => $idWilayah,
            'tahun_buku_awal' => 2026,
            'is_active'       => 1,
            'created_at'      => now(),
        ]);
        app()->instance('koperasi_aktif', $this->idKoperasi);
        app()->instance('entitas_aktif', $this->idKoperasi);

        // Insert COA minimal â€” kolom sesuai skema master_coa
        \DB::table('master_coa')->insertOrIgnore([
            [
                'kode_anak'      => '111',
                'nama_rekening'  => 'Kas',
                'kelompok'       => 'Aktiva',
                'posisi_normal'  => 'D',
                'is_transaction' => 'T',
                'level'          => 3,
                'is_kontra'      => 0,
                'urutan_laporan' => 1,
                'is_active'      => 1,
            ],
            [
                'kode_anak'      => '411',
                'nama_rekening'  => 'Pendapatan Penjualan',
                'kelompok'       => 'Pendapatan',
                'posisi_normal'  => 'K',
                'is_transaction' => 'T',
                'level'          => 3,
                'is_kontra'      => 0,
                'urutan_laporan' => 10,
                'is_active'      => 1,
            ],
        ]);

        // Buka periode Agustus 2026
        \DB::table('periode_akuntansi')->insert([
            'id_entitas' => $this->idKoperasi,
            'tahun'       => 2026,
            'bulan'       => 8,
            'status'      => 'OPEN',
        ]);
    }

    // =========================================================================
    // TEST: postingManual() â€” skenario BERHASIL
    // =========================================================================

    /**
     * @test
     * postingManual() dengan Debet = Kredit â†’ jurnal berhasil dibuat.
     */
    public function posting_manual_berhasil_jika_debet_sama_dengan_kredit(): void
    {
        $jurnal = $this->service->postingManual(
            header: [
                'tanggal_jurnal' => '2026-08-15',
                'jenis_jurnal'   => 'MANUAL',
                'keterangan'     => 'Test jurnal balance',
            ],
            baris: [
                ['kode_anak' => '111', 'posisi' => 'D', 'nilai' => 500000],
                ['kode_anak' => '411', 'posisi' => 'K', 'nilai' => 500000],
            ]
        );

        $this->assertInstanceOf(JurnalHeader::class, $jurnal);
        $this->assertEquals('POSTED', $jurnal->status);
        $this->assertEquals(500000, $jurnal->total_debet);
        $this->assertEquals(500000, $jurnal->total_kredit);
        $this->assertEquals($this->idKoperasi, $jurnal->id_entitas);
    }

    // =========================================================================
    // TEST: postingManual() â€” Debet â‰  Kredit â†’ Exception
    // =========================================================================

    /**
     * @test
     * postingManual() dengan Debet â‰  Kredit â†’ JurnalTidakBalanceException dilempar.
     */
    public function posting_manual_gagal_jika_debet_tidak_sama_kredit(): void
    {
        $this->expectException(JurnalTidakBalanceException::class);

        $this->service->postingManual(
            header: [
                'tanggal_jurnal' => '2026-08-15',
                'jenis_jurnal'   => 'MANUAL',
                'keterangan'     => 'Test tidak balance',
            ],
            baris: [
                ['kode_anak' => '111', 'posisi' => 'D', 'nilai' => 500000],
                ['kode_anak' => '411', 'posisi' => 'K', 'nilai' => 300000], // â† beda 200rb
            ]
        );
    }

    // =========================================================================
    // TEST: postingManual() â†’ PeriodeTutupException jika periode CLOSED
    // =========================================================================

    /**
     * @test
     * postingManual() ke periode yang sudah CLOSED â†’ PeriodeTutupException.
     */
    public function posting_manual_gagal_jika_periode_sudah_closed(): void
    {
        // Tutup periode Agustus 2026
        PeriodeAkuntansi::where('id_entitas', $this->idKoperasi)
            ->where('tahun', 2026)
            ->where('bulan', 8)
            ->update(['status' => 'CLOSED']);

        $this->expectException(PeriodeTutupException::class);

        $this->service->postingManual(
            header: [
                'tanggal_jurnal' => '2026-08-15', // â† tanggal di periode CLOSED
                'jenis_jurnal'   => 'MANUAL',
                'keterangan'     => 'Test periode tutup',
            ],
            baris: [
                ['kode_anak' => '111', 'posisi' => 'D', 'nilai' => 100000],
                ['kode_anak' => '411', 'posisi' => 'K', 'nilai' => 100000],
            ]
        );
    }

    // =========================================================================
    // TEST: balik() â€” jurnal yang sudah REVERSED tidak bisa dibalik lagi
    // =========================================================================

    /**
     * @test
     * balik() jurnal yang sudah berstatus REVERSED â†’ JurnalSudahDibalikException.
     */
    public function balik_gagal_jika_jurnal_sudah_pernah_dibalik(): void
    {
        // Buat jurnal POSTED dulu
        $jurnal = $this->service->postingManual(
            header: [
                'tanggal_jurnal' => '2026-08-15',
                'jenis_jurnal'   => 'MANUAL',
                'keterangan'     => 'Jurnal yang akan dibalik',
            ],
            baris: [
                ['kode_anak' => '111', 'posisi' => 'D', 'nilai' => 200000],
                ['kode_anak' => '411', 'posisi' => 'K', 'nilai' => 200000],
            ]
        );

        // Balik pertama kali â†’ berhasil
        $this->service->balik($jurnal->id_jurnal, 'Balik pertama');

        // Balik kedua kali â†’ Exception
        $this->expectException(JurnalSudahDibalikException::class);

        $this->service->balik($jurnal->id_jurnal, 'Coba balik lagi â€” harus ditolak');
    }

    // =========================================================================
    // TEST: balik() â€” jurnal valid bisa dibalik
    // =========================================================================

    /**
     * @test
     * balik() pada jurnal POSTED â†’ membuat jurnal pembalik baru.
     */
    public function balik_berhasil_membuat_jurnal_pembalik(): void
    {
        $jurnal = $this->service->postingManual(
            header: [
                'tanggal_jurnal' => '2026-08-10',
                'jenis_jurnal'   => 'MANUAL',
                'keterangan'     => 'Jurnal untuk dibalik',
            ],
            baris: [
                ['kode_anak' => '111', 'posisi' => 'D', 'nilai' => 150000],
                ['kode_anak' => '411', 'posisi' => 'K', 'nilai' => 150000],
            ]
        );

        $jurnal->fresh(); // reload dari DB

        $jurnalBalik = $this->service->balik($jurnal->id_jurnal, 'Koreksi data');

        $this->assertInstanceOf(JurnalHeader::class, $jurnalBalik);
        $this->assertEquals('PEMBALIK', $jurnalBalik->jenis_jurnal);
        $this->assertEquals($jurnal->id_jurnal, $jurnalBalik->id_jurnal_asal);

        // Jurnal asal harus berubah status ke REVERSED
        $jurnalAsal = JurnalHeader::withoutGlobalScopes()->find($jurnal->id_jurnal);
        $this->assertEquals('REVERSED', $jurnalAsal->status);
    }

    // =========================================================================
    // TEST: Idempoten â€” posting dua kali dengan source_id sama â†’ Exception unik
    // =========================================================================

    /**
     * @test
     * Posting dengan source_type + source_id yang sama dua kali â†’ UniqueConstraintViolationException
     * (diproteksi oleh unique constraint 'jurnal_idempoten' di tabel jurnal_header).
     */
    public function posting_dua_kali_dengan_source_sama_ditolak(): void
    {
        $params = [
            'tanggal_jurnal' => '2026-08-20',
            'jenis_jurnal'   => 'MANUAL',
            'keterangan'     => 'Idempoten test',
            'source_type'    => 'App\\Models\\Penjualan',
            'source_id'      => 999,
        ];
        $baris = [
            ['kode_anak' => '111', 'posisi' => 'D', 'nilai' => 75000],
            ['kode_anak' => '411', 'posisi' => 'K', 'nilai' => 75000],
        ];

        // Posting pertama â†’ berhasil
        $first = $this->service->postingManual($params, $baris);

        // Posting kedua dengan source_id sama â†’ aman dan idempoten (mengembalikan jurnal yang sama)
        $second = $this->service->postingManual($params, $baris);
        $this->assertEquals($first->id_jurnal, $second->id_jurnal);
    }

    // =========================================================================
    // TEST: Defense in depth — isolasi entitas pada jurnal pembalik & kas/bank
    // =========================================================================

    /** Buat entitas kedua (Desa B) untuk skenario lintas tenant. */
    private function buatEntitasLain(): int
    {
        $sfx = uniqid();
        $idWilayah = \DB::table('wilayah')->insertGetId([
            'tingkat' => 'desa', 'nama' => 'Desa Lain ' . $sfx, 'created_at' => now(),
        ]);

        return \DB::table('entitas')->insertGetId([
            'kode_entitas'    => 'KL-' . $sfx,
            'nama_entitas'    => 'Koperasi Lain',
            'id_wilayah'      => $idWilayah,
            'tahun_buku_awal' => 2026,
            'is_active'       => 1,
            'created_at'      => now(),
        ]);
    }

    private function postingJurnalContoh(): JurnalHeader
    {
        return $this->service->postingManual(
            header: [
                'tanggal_jurnal' => '2026-08-12',
                'jenis_jurnal'   => 'MANUAL',
                'keterangan'     => 'Jurnal milik Desa A',
            ],
            baris: [
                ['kode_anak' => '111', 'posisi' => 'D', 'nilai' => 50000],
                ['kode_anak' => '411', 'posisi' => 'K', 'nilai' => 50000],
            ]
        );
    }

    /**
     * @test
     * User Desa B tidak boleh membalik jurnal milik Desa A (ditolak di PHP).
     */
    public function balik_ditolak_jika_jurnal_milik_entitas_lain(): void
    {
        $jurnal = $this->postingJurnalContoh();
        $idDesaB = $this->buatEntitasLain();

        // Pindah konteks ke Desa B
        app()->instance('entitas_aktif', $idDesaB);
        app()->instance('koperasi_aktif', $idDesaB);

        try {
            $this->service->balik($jurnal->id_jurnal, 'Coba balik jurnal desa lain');
            $this->fail('Seharusnya ModelNotFoundException.');
        } catch (ModelNotFoundException) {
            // sesuai harapan
        }

        $this->assertEquals(
            'POSTED',
            JurnalHeader::withoutGlobalScopes()->find($jurnal->id_jurnal)->status,
            'Jurnal Desa A tidak boleh berubah.'
        );
    }

    /**
     * @test
     * Lapisan terakhir: SP sendiri menolak bila p_id_entitas bukan pemilik jurnal,
     * walaupun pemanggil melewati validasi PHP.
     */
    public function sp_pembalik_menolak_entitas_yang_tidak_cocok(): void
    {
        $jurnal = $this->postingJurnalContoh();
        $idDesaB = $this->buatEntitasLain();

        try {
            \DB::select('CALL sp_jurnal_pembalik(?, ?, ?, ?)', [
                $jurnal->id_jurnal, $idDesaB, 1, 'Panggilan langsung ke SP',
            ]);
            $this->fail('Seharusnya SP melempar error.');
        } catch (QueryException $e) {
            $this->assertStringContainsString('Jurnal tidak ditemukan', $e->getMessage());
        }

        $this->assertEquals('POSTED', JurnalHeader::withoutGlobalScopes()->find($jurnal->id_jurnal)->status);
        $this->assertEquals(0, JurnalHeader::withoutGlobalScopes()->where('id_jurnal_asal', $jurnal->id_jurnal)->count());
    }

    /**
     * @test
     * posting() menolak id_kas_bank milik entitas lain, walaupun ID-nya ada.
     */
    public function posting_menolak_kas_bank_milik_entitas_lain(): void
    {
        $idDesaB = $this->buatEntitasLain();

        $idKasDesaB = \DB::table('master_kas_bank')->insertGetId([
            'id_entitas' => $idDesaB,
            'nama'       => 'Kas Desa B',
            'jenis'      => 'kas',
            'kode_akun'  => '111',
            'is_active'  => 1,
        ]);

        \DB::table('master_transaksi')->insertOrIgnore([
            'kode_transaksi' => 'TST', 'nama_transaksi' => 'Test Kas', 'modul' => 'test', 'is_active' => 1,
        ]);

        \DB::table('master_detail_transaksi')->insert([
            ['kode_transaksi' => 'TST', 'urutan' => 1, 'posisi' => 'D', 'akun_dinamis' => 'KAS_BANK', 'kode_anak' => null, 'sumber_variabel' => 'nilai'],
            ['kode_transaksi' => 'TST', 'urutan' => 2, 'posisi' => 'K', 'akun_dinamis' => null, 'kode_anak' => '411', 'sumber_variabel' => 'nilai'],
        ]);

        $this->expectException(AkunTidakDitemukanException::class);

        // Konteks tetap Desa A, tapi menyelipkan kas milik Desa B
        $this->service->posting('TST', [
            'tanggal_jurnal' => '2026-08-15',
            'nilai'          => 10000,
            'id_kas_bank'    => $idKasDesaB,
        ], 'Test', 1);
    }
}



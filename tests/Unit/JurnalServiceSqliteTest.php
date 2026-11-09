<?php

namespace Tests\Unit;

use App\Services\Finance\JurnalService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class JurnalServiceSqliteTest extends TestCase
{
    private string $originalConnection;

    protected function setUp(): void
    {
        parent::setUp();

        $this->originalConnection = config('database.default');
        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'database.connections.sqlite.foreign_key_constraints' => false,
        ]);
        DB::purge('sqlite');

        Schema::create('jurnal_header', function (Blueprint $table) {
            $table->increments('id_jurnal');
            $table->unsignedBigInteger('id_entitas');
            $table->string('no_jurnal');
            $table->string('nomor_nota')->nullable();
            $table->date('tanggal_jurnal');
            $table->unsignedInteger('periode_tahun');
            $table->unsignedInteger('periode_bulan');
            $table->string('kode_transaksi')->nullable();
            $table->string('jenis_jurnal');
            $table->string('source_type')->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->text('keterangan')->nullable();
            $table->decimal('total_debet', 18, 2)->default(0);
            $table->decimal('total_kredit', 18, 2)->default(0);
            $table->string('status');
            $table->unsignedBigInteger('id_jurnal_asal')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('posted_by')->nullable();
            $table->dateTime('posted_at')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->unique(['id_entitas', 'no_jurnal']);
        });

        Schema::create('jurnal_detail', function (Blueprint $table) {
            $table->increments('id_detail');
            $table->unsignedBigInteger('id_jurnal');
            $table->unsignedInteger('urutan');
            $table->string('kode_anak');
            $table->decimal('debet', 18, 2)->default(0);
            $table->decimal('kredit', 18, 2)->default(0);
            $table->string('keterangan')->nullable();
            $table->unsignedBigInteger('id_pihak')->nullable();
        });

        Schema::create('periode_akuntansi', function (Blueprint $table) {
            $table->unsignedBigInteger('id_entitas');
            $table->unsignedInteger('tahun');
            $table->unsignedInteger('bulan');
            $table->string('status');
        });

        Schema::create('penomoran_jurnal', function (Blueprint $table) {
            $table->unsignedBigInteger('id_entitas');
            $table->string('prefix');
            $table->unsignedInteger('nomor_terakhir')->default(0);
            $table->primary(['id_entitas', 'prefix']);
        });

        Schema::create('buku_besar_periode', function (Blueprint $table) {
            $table->unsignedBigInteger('id_entitas');
            $table->unsignedInteger('periode_tahun');
            $table->unsignedInteger('periode_bulan');
            $table->string('kode_anak');
            $table->decimal('saldo_awal_debet', 18, 2)->default(0);
            $table->decimal('saldo_awal_kredit', 18, 2)->default(0);
            $table->decimal('mutasi_debet', 18, 2)->default(0);
            $table->decimal('mutasi_kredit', 18, 2)->default(0);
            $table->decimal('saldo_akhir_debet', 18, 2)->default(0);
            $table->decimal('saldo_akhir_kredit', 18, 2)->default(0);
            $table->dateTime('dihitung_pada')->nullable();
            $table->primary(['id_entitas', 'periode_tahun', 'periode_bulan', 'kode_anak']);
        });

        DB::table('periode_akuntansi')->insert([
            'id_entitas' => 1,
            'tahun' => 2026,
            'bulan' => 11,
            'status' => 'OPEN',
        ]);
        app()->instance('entitas_aktif', 1);
    }

    protected function tearDown(): void
    {
        DB::purge('sqlite');
        config(['database.default' => $this->originalConnection]);

        parent::tearDown();
    }

    public function test_sqlite_posts_journal_with_sequential_numbers_and_ledger_rows(): void
    {
        $service = app(JurnalService::class);
        $header = [
            'tanggal_jurnal' => '2026-11-02',
            'jenis_jurnal' => 'MANUAL',
            'keterangan' => 'SQLite journal counter regression',
        ];
        $lines = [
            ['kode_anak' => '111', 'posisi' => 'D', 'nilai' => 100],
            ['kode_anak' => '411', 'posisi' => 'K', 'nilai' => 100],
        ];

        $first = $service->postingManual($header, $lines);
        $second = $service->postingManual($header, $lines);

        $this->assertSame('JRN-2026-11-0001', $first->no_jurnal);
        $this->assertSame('JRN-2026-11-0002', $second->no_jurnal);
        $this->assertSame(4, DB::table('jurnal_detail')->count());
        $this->assertSame(2, DB::table('buku_besar_periode')->count());
        $this->assertSame(2, (int) DB::table('penomoran_jurnal')->value('nomor_terakhir'));
    }
}

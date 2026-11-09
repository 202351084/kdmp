<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $existingTables = array_filter([
            'stok_batch',
            'penerimaan_batch_detail',
            'kartu_stok_batch',
            'opname_batch_detail',
        ], fn (string $table): bool => Schema::hasTable($table));

        if (
            $existingTables !== []
            || Schema::hasColumn('barang_per_koperasi', 'lacak_batch')
            || Schema::hasColumn('kerusakan_barang', 'id_batch')
        ) {
            throw new RuntimeException(
                'Migration dibatalkan: sebagian struktur pelacakan batch sudah tersedia. '
                .'Periksa struktur database sebelum melanjutkan.'
            );
        }

        Schema::table('barang_per_koperasi', function (Blueprint $table): void {
            $table->boolean('lacak_batch')
                ->default(false)
                ->after('is_dijual');
        });

        Schema::create('stok_batch', function (Blueprint $table): void {
            $table->id('id_batch');
            $table->foreignId('id_gudang')
                ->constrained('gudang', 'id_gudang')
                ->restrictOnDelete();
            $table->foreignId('id_barang')
                ->constrained('master_barang', 'id_barang')
                ->restrictOnDelete();
            $table->string('kode_batch', 100);
            $table->date('tanggal_kedaluwarsa')->nullable();
            $table->decimal('qty_on_hand', 18, 4)->default(0);
            $table->boolean('is_stok_awal')->default(false);
            $table->timestamps();

            $table->index(
                ['id_gudang', 'id_barang', 'tanggal_kedaluwarsa'],
                'stok_batch_fefo_lookup',
            );
            $table->index(
                ['id_gudang', 'id_barang', 'kode_batch', 'tanggal_kedaluwarsa'],
                'stok_batch_identity_lookup',
            );
        });

        Schema::create('penerimaan_batch_detail', function (Blueprint $table): void {
            $table->id('id_penerimaan_batch');
            $table->foreignId('id_detail')
                ->constrained('penerimaan_barang_detail', 'id_detail')
                ->restrictOnDelete();
            $table->foreignId('id_batch')
                ->constrained('stok_batch', 'id_batch')
                ->restrictOnDelete();
            $table->decimal('qty_dasar', 18, 4);
            $table->decimal('qty_layak', 18, 4);
            $table->decimal('qty_tidak_layak', 18, 4);
            $table->text('alasan_tidak_layak')->nullable();
            $table->timestamps();

            $table->unique(
                ['id_detail', 'id_batch'],
                'penerimaan_batch_detail_unique',
            );
        });

        Schema::create('kartu_stok_batch', function (Blueprint $table): void {
            $table->id('id_kartu_batch');
            $table->foreignId('id_kartu')
                ->constrained('kartu_stok', 'id_kartu')
                ->restrictOnDelete();
            $table->foreignId('id_batch')
                ->constrained('stok_batch', 'id_batch')
                ->restrictOnDelete();
            $table->decimal('qty_masuk', 18, 4)->default(0);
            $table->decimal('qty_keluar', 18, 4)->default(0);
            $table->decimal('saldo_batch', 18, 4);
            $table->timestamps();

            $table->unique(
                ['id_kartu', 'id_batch'],
                'kartu_stok_batch_unique',
            );
            $table->index(['id_batch', 'id_kartu'], 'kartu_stok_batch_history');
        });

        Schema::create('opname_batch_detail', function (Blueprint $table): void {
            $table->id('id_opname_batch');
            $table->foreignId('id_detail')
                ->constrained('opname_detail', 'id_detail')
                ->restrictOnDelete();
            $table->foreignId('id_batch')
                ->constrained('stok_batch', 'id_batch')
                ->restrictOnDelete();
            $table->decimal('qty_sistem', 18, 4);
            $table->decimal('qty_fisik', 18, 4);
            $table->decimal('selisih', 18, 4);
            $table->timestamps();

            $table->unique(
                ['id_detail', 'id_batch'],
                'opname_batch_detail_unique',
            );
        });

        DB::table('stok')
            ->where('qty_on_hand', '>', 0)
            ->orderBy('id_gudang')
            ->orderBy('id_barang')
            ->cursor()
            ->each(function (object $stok): void {
                DB::table('stok_batch')->insert([
                    'id_gudang' => $stok->id_gudang,
                    'id_barang' => $stok->id_barang,
                    'kode_batch' => "AWAL-{$stok->id_gudang}-{$stok->id_barang}",
                    'tanggal_kedaluwarsa' => null,
                    'qty_on_hand' => $stok->qty_on_hand,
                    'is_stok_awal' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            });

        Schema::table('kerusakan_barang', function (Blueprint $table): void {
            $table->foreignId('id_batch')
                ->nullable()
                ->after('id_barang')
                ->constrained('stok_batch', 'id_batch')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        throw new RuntimeException(
            'Migration pelacakan batch tidak boleh di-rollback otomatis karena dapat menghapus riwayat batch.'
        );
    }
};

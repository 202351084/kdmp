<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('retur_konsinyasi', function (Blueprint $table): void {
            $table->id('id_retur_konsinyasi');
            $table->string('kode_retur', 30)->unique();
            $table->foreignId('id_stok_konsinyasi')
                ->constrained('stok_konsinyasi', 'id_stok_konsinyasi');
            $table->foreignId('id_entitas_pemilik')->nullable()
                ->constrained('entitas', 'id_entitas');
            $table->foreignId('id_entitas_pencatat')
                ->constrained('entitas', 'id_entitas');
            $table->foreignId('id_pengguna')->nullable()
                ->constrained('pengguna', 'id');
            $table->date('tanggal');
            $table->decimal('qty_dikembalikan', 18, 4);
            $table->text('catatan')->nullable();
            $table->timestamps();

            $table->index(['id_entitas_pemilik', 'tanggal'], 'retur_kon_pemilik_tanggal');
            $table->index(['id_entitas_pencatat', 'tanggal'], 'retur_kon_pencatat_tanggal');
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('retur_konsinyasi') && DB::table('retur_konsinyasi')->exists()) {
            throw new RuntimeException('Riwayat retur konsinyasi masih ada; data harus ditangani sebelum rollback.');
        }

        Schema::dropIfExists('retur_konsinyasi');
    }
};

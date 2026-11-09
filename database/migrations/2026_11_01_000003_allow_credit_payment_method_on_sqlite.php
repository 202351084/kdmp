<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            return;
        }

        Schema::table('penjualan', function (Blueprint $table) {
            $table->enum('metode_bayar', ['tunai', 'transfer', 'kredit'])->change();
        });
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            return;
        }

        if (DB::table('penjualan')->where('metode_bayar', 'kredit')->exists()) {
            throw new RuntimeException('Metode pembayaran kredit masih digunakan dan tidak dapat dihapus dari batasan tabel.');
        }

        Schema::table('penjualan', function (Blueprint $table) {
            $table->enum('metode_bayar', ['tunai', 'transfer'])->change();
        });
    }
};

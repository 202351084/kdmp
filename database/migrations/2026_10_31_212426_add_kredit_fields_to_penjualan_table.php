<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('penjualan', function (Blueprint $table) {
            if (!Schema::hasColumn('penjualan', 'tgl_jatuh_tempo')) {
                $table->date('tgl_jatuh_tempo')->nullable()->after('tanggal_transaksi');
            }
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE penjualan MODIFY COLUMN metode_bayar ENUM('tunai', 'transfer', 'kredit') NOT NULL DEFAULT 'tunai'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('penjualan', function (Blueprint $table) {
            if (Schema::hasColumn('penjualan', 'tgl_jatuh_tempo')) {
                $table->dropColumn('tgl_jatuh_tempo');
            }
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE penjualan MODIFY COLUMN metode_bayar ENUM('tunai', 'transfer') NOT NULL DEFAULT 'tunai'");
        }
    }
};

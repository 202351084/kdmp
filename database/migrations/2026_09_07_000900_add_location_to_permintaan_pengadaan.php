<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        Schema::table('permintaan_pengadaan', function (Blueprint $table) {
            $table->foreignId('id_unit_usaha')->nullable()
                ->after('id_pihak')
                ->constrained('master_unit_usaha', 'id_unit_usaha');
            $table->foreignId('id_gudang')->nullable()
                ->after('id_unit_usaha')
                ->constrained('gudang', 'id_gudang');
        });
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        Schema::table('permintaan_pengadaan', function (Blueprint $table) {
            $table->dropForeign(['id_unit_usaha']);
            $table->dropForeign(['id_gudang']);
            $table->dropColumn(['id_unit_usaha', 'id_gudang']);
        });
    }
};
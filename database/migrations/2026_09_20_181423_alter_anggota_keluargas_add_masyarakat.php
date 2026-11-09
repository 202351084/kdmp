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
        if (DB::getDriverName() === 'sqlite') {
            return;
        }
        Schema::table('anggota_keluargas', function (Blueprint $table) {
            $table->dropForeign(['id_narasumber']);
            $table->dropColumn('id_narasumber');
            $table->foreignId('id_masyarakat')->after('id')->constrained('masyarakat_desas')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        Schema::table('anggota_keluargas', function (Blueprint $table) {
            $table->dropForeign(['id_masyarakat']);
            $table->dropColumn('id_masyarakat');
            $table->foreignId('id_narasumber')->after('id')->constrained('narasumber_sesi', 'id_narasumber')->cascadeOnDelete();
        });
    }
};

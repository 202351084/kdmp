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
        Schema::table('konsumsi_keluargas', function (Blueprint $table) {
            $table->dropForeign(['id_komoditas']);
            $table->dropColumn('id_komoditas');
            $table->string('nama_komoditas')->after('id_masyarakat');
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

        Schema::table('konsumsi_keluargas', function (Blueprint $table) {
            $table->dropColumn('nama_komoditas');
            $table->foreignId('id_komoditas')->constrained('komoditas')->onDelete('cascade');
        });
    }
};

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
        Schema::table('rt_desas', function (Blueprint $table) {
            $table->unsignedBigInteger('id_sesi')->nullable()->after('id');
            $table->string('nama_ketua_rt')->nullable()->after('nama_rt');
            $table->string('no_hp', 20)->nullable()->after('nama_ketua_rt');

            $table->foreign('id_sesi')->references('id')->on('sesi_survei')->onDelete('set null');
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

        Schema::table('rt_desas', function (Blueprint $table) {
            $table->dropForeign(['id_sesi']);
            $table->dropColumn(['id_sesi', 'nama_ketua_rt', 'no_hp']);
        });
    }
};

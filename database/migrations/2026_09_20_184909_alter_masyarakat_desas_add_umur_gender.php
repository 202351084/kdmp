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
        Schema::table('masyarakat_desas', function (Blueprint $table) {
            $table->integer('umur')->nullable()->after('nomor_hp');
            $table->enum('jenis_kelamin', ['Laki-laki', 'Perempuan'])->nullable()->after('umur');
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

        Schema::table('masyarakat_desas', function (Blueprint $table) {
            $table->dropColumn(['umur', 'jenis_kelamin']);
        });
    }
};

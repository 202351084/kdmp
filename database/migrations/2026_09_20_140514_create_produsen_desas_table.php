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
        Schema::create('produsen_desas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_wilayah')->constrained('wilayah')->cascadeOnDelete();
            $table->enum('kategori', ['Kelompok Tani', 'Ekraf']);
            $table->string('nama_anggota');
            $table->timestamps();
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

        Schema::dropIfExists('produsen_desas');
    }
};

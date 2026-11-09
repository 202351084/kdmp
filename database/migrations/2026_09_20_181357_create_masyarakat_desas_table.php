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
        Schema::create('masyarakat_desas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_sesi')->constrained('sesi_survei')->cascadeOnDelete();
            $table->foreignId('id_wilayah')->constrained('wilayah')->cascadeOnDelete();
            $table->string('nama_kepala_keluarga');
            $table->string('nomor_hp')->nullable();
            
            // Lokasi
            $table->string('provinsi')->nullable();
            $table->string('kabupaten')->nullable();
            $table->string('kecamatan')->nullable();
            $table->string('desa')->nullable();
            $table->string('dusun')->nullable();
            $table->string('rt')->nullable();
            $table->string('rw')->nullable();
            $table->string('nama_jalan')->nullable();
            $table->string('nomor_rumah')->nullable();
            $table->text('detail_alamat')->nullable();
            
            // Koordinat (opsional, karena pengguna bisa lanjut tanpa lokasi)
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            
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

        Schema::dropIfExists('masyarakat_desas');
    }
};

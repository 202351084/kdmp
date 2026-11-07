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

        // Tambah id_sesi ke produsen_desas supaya produsen bisa terikat ke sesi survei aktif
        Schema::table('produsen_desas', function (Blueprint $table) {
            $table->foreignId('id_sesi')->nullable()->after('id_wilayah')->constrained('sesi_survei')->nullOnDelete();
        });

        // Tabel produksi: setiap baris = satu komoditas yang dilaporkan oleh satu produsen
        Schema::create('produksi_produsen', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_sesi')->constrained('sesi_survei')->cascadeOnDelete();
            $table->foreignId('id_wilayah')->constrained('wilayah')->cascadeOnDelete();
            // Nama responden yang mengisi (tidak harus cocok ke master data)
            $table->string('nama_responden');
            // Kategori & sub-kategori diisi saat simpan agar data mandiri
            $table->string('kategori');          // Kelompok Tani / Ekraf
            $table->string('sub_kategori')->nullable(); // Pertanian, Perkebunan, Pangan, dll
            // Data komoditas
            $table->string('nama_komoditas');
            $table->decimal('jumlah_produksi', 12, 2)->nullable();
            $table->string('satuan', 30)->nullable();   // kg, ton, liter, buah, dll
            // Periode produksi
            $table->tinyInteger('bulan_produksi');      // 1–12
            $table->smallInteger('tahun_produksi');
            $table->text('keterangan')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        Schema::dropIfExists('produksi_produsen');
        Schema::table('produsen_desas', function (Blueprint $table) {
            $table->dropForeignIdFor(\App\Models\Survei\SesiSurvei::class, 'id_sesi');
            $table->dropColumn('id_sesi');
        });
    }
};

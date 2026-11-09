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
        Schema::create('konsumsi_keluargas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_masyarakat')->constrained('masyarakat_desas')->onDelete('cascade');
            $table->foreignId('id_komoditas')->constrained('komoditas')->onDelete('cascade');
            $table->double('jumlah')->default(0);
            $table->string('satuan', 50)->default('kg');
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

        Schema::dropIfExists('konsumsi_keluargas');
    }
};

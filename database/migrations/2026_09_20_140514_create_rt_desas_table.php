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
        Schema::create('rt_desas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_wilayah')->constrained('wilayah')->cascadeOnDelete();
            $table->string('nama_rt');
            $table->integer('total_kk_baseline')->default(0);
            $table->integer('total_jiwa_baseline')->default(0);
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

        Schema::dropIfExists('rt_desas');
    }
};

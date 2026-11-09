<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('standar_kebutuhan_komoditas', function (Blueprint $table) {
            $table->enum('satuan_periode', ['harian', 'bulanan'])
                ->default('harian')
                ->after('satuan');
        });
    }

    public function down(): void
    {
        Schema::table('standar_kebutuhan_komoditas', function (Blueprint $table) {
            $table->dropColumn('satuan_periode');
        });
    }
};

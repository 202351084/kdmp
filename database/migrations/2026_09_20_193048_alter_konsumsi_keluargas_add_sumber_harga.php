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
            $table->string('sumber_pemenuhan', 100)->nullable()->after('satuan');
            $table->double('harga')->nullable()->after('sumber_pemenuhan');
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
            $table->dropColumn(['sumber_pemenuhan', 'harga']);
        });
    }
};

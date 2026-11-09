<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pelunasan', function (Blueprint $table) {
            $table->string('transfer_pengirim_nama', 150)->nullable();
            $table->string('transfer_pengirim_bank', 100)->nullable();
            $table->string('transfer_pengirim_rekening', 50)->nullable();
            $table->string('transfer_no_referensi', 100)->nullable();
            $table->string('transfer_penerima_nama', 100)->nullable();
            $table->string('transfer_penerima_rekening', 50)->nullable();
            $table->timestamp('transfer_dikonfirmasi_pada')->nullable();
            $table->unsignedBigInteger('transfer_dikonfirmasi_oleh')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('pelunasan', function (Blueprint $table) {
            $table->dropColumn([
                'transfer_pengirim_nama',
                'transfer_pengirim_bank',
                'transfer_pengirim_rekening',
                'transfer_no_referensi',
                'transfer_penerima_nama',
                'transfer_penerima_rekening',
                'transfer_dikonfirmasi_pada',
                'transfer_dikonfirmasi_oleh',
            ]);
        });
    }
};

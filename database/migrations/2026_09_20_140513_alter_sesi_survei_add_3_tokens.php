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

        Schema::table('sesi_survei', function (Blueprint $table) {
            $table->dropColumn('token_publik');
            $table->string('token_rt', 64)->nullable()->unique()->after('status');
            $table->string('token_masyarakat', 64)->nullable()->unique()->after('token_rt');
            $table->string('token_produsen', 64)->nullable()->unique()->after('token_masyarakat');
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

        Schema::table('sesi_survei', function (Blueprint $table) {
            $table->dropColumn(['token_rt', 'token_masyarakat', 'token_produsen']);
            $table->string('token_publik', 64)->nullable()->unique()->after('status');
        });
    }
};

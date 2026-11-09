<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $indexes = Schema::getIndexes('stok_konsinyasi');
        $primaryExists = collect($indexes)->contains(
            fn ($index) => strtolower($index['name'] ?? '') === 'primary' || !empty($index['primary'])
        );

        if ($primaryExists) {
            return;
        }

        // Langkah 1: Jadikan kolom ID sebagai primary key.
        DB::statement(
            'ALTER TABLE stok_konsinyasi
             ADD PRIMARY KEY (id_stok_konsinyasi)'
        );

        // Langkah 2: Aktifkan AUTO_INCREMENT.
        DB::statement(
            'ALTER TABLE stok_konsinyasi
             MODIFY id_stok_konsinyasi
             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT'
        );
    }

    public function down(): void
    {
        // Lepaskan AUTO_INCREMENT terlebih dahulu.
        DB::statement(
            'ALTER TABLE stok_konsinyasi
             MODIFY id_stok_konsinyasi
             BIGINT UNSIGNED NOT NULL'
        );

        // Kemudian hapus primary key.
        DB::statement(
            'ALTER TABLE stok_konsinyasi
             DROP PRIMARY KEY'
        );
    }
};
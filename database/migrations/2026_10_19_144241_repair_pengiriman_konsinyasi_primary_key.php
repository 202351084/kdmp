
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $table = 'pengiriman_konsinyasi';

        $indexes = Schema::getIndexes($table);

        $primaryExists = collect($indexes)->contains(
            fn ($index) => strtolower($index['name'] ?? '') === 'primary' || !empty($index['primary'])
        );

        if ($primaryExists) {
            return;
        }

        $nullIds = DB::table($table)
            ->whereNull('id_kiriman')
            ->exists();

        $duplicateIds = DB::table($table)
            ->select('id_kiriman')
            ->groupBy('id_kiriman')
            ->havingRaw('COUNT(*) > 1')
            ->exists();

        $duplicateCodes = DB::table($table)
            ->select('kode_kiriman')
            ->groupBy('kode_kiriman')
            ->havingRaw('COUNT(*) > 1')
            ->exists();

        if ($nullIds || $duplicateIds || $duplicateCodes) {
            throw new RuntimeException(
                'Ada ID kosong atau ID/kode kiriman duplikat. Perbaikan dibatalkan.'
            );
        }

        // Jadikan ID sebagai Primary Key terlebih dahulu.
        DB::statement(
            'ALTER TABLE `pengiriman_konsinyasi`
             ADD PRIMARY KEY (`id_kiriman`)'
        );

        // Baru aktifkan AUTO_INCREMENT.
        DB::statement(
            'ALTER TABLE `pengiriman_konsinyasi`
             MODIFY `id_kiriman` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT'
        );

        // Pertahankan aturan agar kode kiriman tidak boleh duplikat.
        $kodeUniqueExists = collect($indexes)->contains(
            fn ($index) =>
                ($index['name'] ?? '') === 'pengiriman_konsinyasi_kode_kiriman_unique'
        );

        if (!$kodeUniqueExists) {
            DB::statement(
                'ALTER TABLE `pengiriman_konsinyasi`
                 ADD UNIQUE KEY `pengiriman_konsinyasi_kode_kiriman_unique`
                 (`kode_kiriman`)'
            );
        }
    }

    public function down(): void
    {
        // Tidak menghapus indeks atau mengubah ID saat rollback.
    }
};

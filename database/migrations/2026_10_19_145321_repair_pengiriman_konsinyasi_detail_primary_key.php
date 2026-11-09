
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $table = 'pengiriman_konsinyasi_detail';

        $indexes = Schema::getIndexes($table);

        $nullIds = DB::table($table)
            ->whereNull('id_detail')
            ->exists();

        $duplicateIds = DB::table($table)
            ->select('id_detail')
            ->groupBy('id_detail')
            ->havingRaw('COUNT(*) > 1')
            ->exists();

        if ($nullIds || $duplicateIds) {
            throw new RuntimeException(
                'Ada id_detail kosong atau duplikat. Perbaikan dibatalkan.'
            );
        }

        $primary = collect($indexes)->first(
            fn ($index) => strtolower($index['name'] ?? '') === 'primary' || !empty($index['primary'])
        );

        if ($primary) {
            return;
        }

        DB::statement(
            'ALTER TABLE `pengiriman_konsinyasi_detail`
             ADD PRIMARY KEY (`id_detail`)'
        );

        // Setelah menjadi key, aktifkan AUTO_INCREMENT.
        DB::statement(
            'ALTER TABLE `pengiriman_konsinyasi_detail`
             MODIFY `id_detail` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT'
        );
    }

    public function down(): void
    {
        // Sengaja tidak menghapus Primary Key atau AUTO_INCREMENT
        // agar rollback tidak merusak data yang sudah tersimpan.
    }
};

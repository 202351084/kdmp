
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $columns = Schema::getColumns('opname_detail');
        $indexes = Schema::getIndexes('opname_detail');

        $idColumn = collect($columns)->firstWhere('name', 'id_detail');

        if (!$idColumn) {
            throw new RuntimeException(
                'Kolom id_detail tidak ditemukan pada tabel opname_detail.'
            );
        }

        $primaryExists = collect($indexes)->contains(
            fn ($index) => strtolower($index['name'] ?? '') === 'primary' || !empty($index['primary'])
        );

        if ($primaryExists) {
            return;
        }

        $nullIds = DB::table('opname_detail')
            ->whereNull('id_detail')
            ->exists();

        $duplicateIds = DB::table('opname_detail')
            ->select('id_detail')
            ->groupBy('id_detail')
            ->havingRaw('COUNT(*) > 1')
            ->exists();

        if ($nullIds || $duplicateIds) {
            throw new RuntimeException(
                'Terdapat ID kosong atau duplikat. Perbaikan dibatalkan.'
            );
        }

        DB::statement(
            'ALTER TABLE `opname_detail`
            ADD PRIMARY KEY (`id_detail`)'
        );

        DB::statement(
            'ALTER TABLE `opname_detail`
            MODIFY `id_detail` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT'
        );
    }

    public function down(): void
    {
        // Tidak menghapus PRIMARY KEY atau AUTO_INCREMENT
        // agar rollback tidak merusak struktur tabel.
    }
};

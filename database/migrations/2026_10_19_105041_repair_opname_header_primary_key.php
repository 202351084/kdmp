
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $tableName = 'opname_header';

        // Pastikan tabel tersedia.
        if (!Schema::hasTable($tableName)) {
            throw new RuntimeException("Tabel {$tableName} tidak ditemukan.");
        }

        // Periksa struktur id_opname.
        $columns = Schema::getColumns($tableName);
        $idColumn = collect($columns)->firstWhere('name', 'id_opname');

        if (!$idColumn) {
            throw new RuntimeException("Kolom id_opname tidak ditemukan.");
        }

        // Pastikan tidak ada ID kosong atau duplikat.
        $invalidRows = DB::table($tableName)
            ->whereNull('id_opname')
            ->count();

        $duplicateIds = DB::table($tableName)
            ->select('id_opname')
            ->groupBy('id_opname')
            ->havingRaw('COUNT(*) > 1')
            ->count();

        if ($invalidRows > 0 || $duplicateIds > 0) {
            throw new RuntimeException(
                'Perbaikan dibatalkan: terdapat id_opname kosong atau duplikat.'
            );
        }

        // Periksa indeks yang sudah ada.
        $indexes = Schema::getIndexes($tableName);

        $hasPrimary = collect($indexes)->contains(
            fn ($index) => strtolower($index['name'] ?? '') === 'primary' || !empty($index['primary'])
        );

        $hasUniqueCode = collect($indexes)->contains(
            fn ($index) =>
                ($index['unique'] ?? false) === true
                && ($index['columns'] ?? []) === ['id_entitas', 'kode_opname']
        );

        // Tambahkan primary key dan AUTO_INCREMENT jika belum benar.
        $needsAutoIncrement = empty($idColumn['auto_increment']);

        if (!$hasPrimary || $needsAutoIncrement) {
            Schema::table($tableName, function (Blueprint $table) use ($hasPrimary) {
                if (!$hasPrimary) {
                    $table->primary('id_opname');
                }
            });

            if ($needsAutoIncrement) {
                DB::statement(
                    'ALTER TABLE `opname_header` MODIFY `id_opname` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT'
                );
            }
        }

        // Pertahankan aturan kode opname unik per entitas.
        if (!$hasUniqueCode) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->unique(
                    ['id_entitas', 'kode_opname'],
                    'opname_header_entitas_kode_unique'
                );
            });
        }
    }

    public function down(): void
    {
        // Sengaja tidak menghapus primary key atau indeks.
        // Pembatalan otomatis dapat merusak struktur yang sudah diperbaiki.
    }
};

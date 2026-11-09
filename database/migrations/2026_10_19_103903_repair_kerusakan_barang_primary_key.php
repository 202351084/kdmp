
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $tableName = 'kerusakan_barang';

        // Pastikan tabelnya memang ada.
        if (!Schema::hasTable($tableName)) {
            throw new \RuntimeException(
                'Tabel kerusakan_barang tidak ditemukan.'
            );
        }

        // Pastikan kolom ID tersedia.
        if (!Schema::hasColumn($tableName, 'id_kerusakan')) {
            throw new \RuntimeException(
                'Kolom id_kerusakan tidak ditemukan.'
            );
        }

        // Pastikan tidak ada ID duplikat sebelum membuat primary key.
        $duplicates = DB::table($tableName)
            ->select('id_kerusakan')
            ->groupBy('id_kerusakan')
            ->havingRaw('COUNT(*) > 1')
            ->exists();

        if ($duplicates) {
            throw new \RuntimeException(
                'Terdapat id_kerusakan duplikat. Perbaikan dibatalkan.'
            );
        }

        // Pulihkan PRIMARY KEY jika belum ada.
        $indexes = Schema::getIndexes($tableName);

        $hasPrimaryKey = collect($indexes)->contains(
            fn ($index) => ($index['primary'] ?? false) === true
        );

        if (!$hasPrimaryKey) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->primary('id_kerusakan');
            });
        }

        // Pulihkan AUTO_INCREMENT jika belum aktif.
        $columns = Schema::getColumns($tableName);

        $idColumn = collect($columns)->firstWhere(
            'name',
            'id_kerusakan'
        );

        if (
            $idColumn &&
            stripos($idColumn['auto_increment'] ?? '', 'auto_increment') === false
        ) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->unsignedBigInteger('id_kerusakan')
                    ->autoIncrement()
                    ->change();
            });
        }
    }

    public function down(): void
    {
        // Tidak menghapus PRIMARY KEY atau AUTO_INCREMENT
        // agar rollback tidak merusak struktur tabel.
    }
};

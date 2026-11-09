
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tableName = 'kartu_stok';

        $idColumn = collect(Schema::getColumns($tableName))
            ->firstWhere('name', 'id_kartu');

        if ($idColumn === null) {
            throw new RuntimeException(
                'Kolom id_kartu tidak ditemukan pada tabel kartu_stok.'
            );
        }

        // Pulihkan PRIMARY KEY jika belum ada.
        if (!Schema::hasIndex($tableName, ['id_kartu'], 'primary')) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->primary('id_kartu');
            });
        }

        // Pulihkan AUTO_INCREMENT jika belum aktif.
        if (!$idColumn['auto_increment']) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->unsignedBigInteger('id_kartu')
                    ->autoIncrement()
                    ->change();
            });
        }
    }

    public function down(): void
    {
        // Struktur primary key dan auto_increment dipertahankan.
    }
};


<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tableName = 'kartu_konsinyasi';

        $idColumn = collect(Schema::getColumns($tableName))
            ->firstWhere('name', 'id_kartu_kons');

        if ($idColumn === null) {
            throw new RuntimeException(
                'Kolom id_kartu_kons tidak ditemukan pada tabel kartu_konsinyasi.'
            );
        }

        // Pulihkan PRIMARY KEY jika belum ada.
        if (!Schema::hasIndex($tableName, ['id_kartu_kons'], 'primary')) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->primary('id_kartu_kons');
            });
        }

        // Pulihkan AUTO_INCREMENT jika belum aktif.
        if (!$idColumn['auto_increment']) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->unsignedBigInteger('id_kartu_kons')
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

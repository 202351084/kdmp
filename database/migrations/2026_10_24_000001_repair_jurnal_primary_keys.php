
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Pastikan ID yang ada tidak duplikat.
        $headerDuplikat = DB::selectOne("
            SELECT COUNT(*) AS total
            FROM (
                SELECT id_jurnal
                FROM jurnal_header
                GROUP BY id_jurnal
                HAVING COUNT(*) > 1
            ) AS duplikat
        ")->total;

        $detailDuplikat = DB::selectOne("
            SELECT COUNT(*) AS total
            FROM (
                SELECT id_detail
                FROM jurnal_detail
                GROUP BY id_detail
                HAVING COUNT(*) > 1
            ) AS duplikat
        ")->total;

        if ($headerDuplikat > 0 || $detailDuplikat > 0) {
            throw new RuntimeException(
                'Perbaikan dibatalkan: ditemukan ID jurnal duplikat.'
            );
        }

        // Pastikan setiap detail memiliki header jurnal.
        $detailTanpaHeader = DB::table('jurnal_detail as d')
            ->leftJoin(
                'jurnal_header as h',
                'd.id_jurnal',
                '=',
                'h.id_jurnal'
            )
            ->whereNull('h.id_jurnal')
            ->count();

        if ($detailTanpaHeader > 0) {
            throw new RuntimeException(
                'Perbaikan dibatalkan: ada detail jurnal tanpa header.'
            );
        }

        // Pastikan primary key belum ada.
        $pkHeader = DB::selectOne("
            SELECT COUNT(*) AS total
            FROM information_schema.TABLE_CONSTRAINTS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'jurnal_header'
              AND CONSTRAINT_TYPE = 'PRIMARY KEY'
        ")->total;

        $pkDetail = DB::selectOne("
            SELECT COUNT(*) AS total
            FROM information_schema.TABLE_CONSTRAINTS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'jurnal_detail'
              AND CONSTRAINT_TYPE = 'PRIMARY KEY'
        ")->total;

        if ($pkHeader > 0 || $pkDetail > 0) {
            return;
        }

        // Pulihkan primary key dan AUTO_INCREMENT.
        DB::statement("
            ALTER TABLE jurnal_header
            MODIFY id_jurnal BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            ADD PRIMARY KEY (id_jurnal)
        ");

        DB::statement("
            ALTER TABLE jurnal_detail
            MODIFY id_detail BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            ADD PRIMARY KEY (id_detail)
        ");

        // Pulihkan foreign key detail -> header jika belum ada.
        $fkAda = DB::selectOne("
            SELECT COUNT(*) AS total
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'jurnal_detail'
              AND COLUMN_NAME = 'id_jurnal'
              AND REFERENCED_TABLE_NAME = 'jurnal_header'
              AND REFERENCED_COLUMN_NAME = 'id_jurnal'
        ")->total;

        if ($fkAda == 0) {
            DB::statement("
                ALTER TABLE jurnal_detail
                ADD CONSTRAINT jurnal_detail_id_jurnal_foreign
                FOREIGN KEY (id_jurnal)
                REFERENCES jurnal_header (id_jurnal)
            ");
        }
    }

    public function down(): void
    {
        throw new RuntimeException(
            'Migration perbaikan jurnal tidak boleh di-rollback otomatis.'
        );
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->assertColumnType('entitas', 'id_entitas');
        $this->assertColumnType('master_barang', 'id_barang');
        $this->assertColumnType('gudang', 'id_gudang');
        $this->assertColumnType('stok', 'id_gudang');
        $this->assertColumnType('stok', 'id_barang');
        $this->assertColumnType('barang_per_koperasi', 'id_entitas');
        $this->assertColumnType('barang_per_koperasi', 'id_barang');

        $this->assertUniqueValues('entitas', ['id_entitas']);
        $this->assertUniqueValues('master_barang', ['id_barang']);
        $this->assertUniqueValues('gudang', ['id_gudang']);
        $this->assertUniqueValues('stok', ['id_gudang', 'id_barang']);
        $this->assertUniqueValues('barang_per_koperasi', ['id_entitas', 'id_barang']);

        $this->assertNoOrphans('stok', 'id_gudang', 'gudang', 'id_gudang');
        $this->assertNoOrphans('stok', 'id_barang', 'master_barang', 'id_barang');
        $this->assertNoOrphans('barang_per_koperasi', 'id_entitas', 'entitas', 'id_entitas');
        $this->assertNoOrphans('barang_per_koperasi', 'id_barang', 'master_barang', 'id_barang');

        $this->ensurePrimaryKey('entitas', ['id_entitas'], true);
        $this->ensurePrimaryKey('master_barang', ['id_barang'], true);
        $this->ensurePrimaryKey('gudang', ['id_gudang'], true);
        $this->ensurePrimaryKey('stok', ['id_gudang', 'id_barang']);
        $this->ensurePrimaryKey('barang_per_koperasi', ['id_entitas', 'id_barang']);

        $this->ensureForeignKey('stok', 'id_gudang', 'gudang', 'id_gudang');
        $this->ensureForeignKey('stok', 'id_barang', 'master_barang', 'id_barang');
        $this->ensureForeignKey('barang_per_koperasi', 'id_entitas', 'entitas', 'id_entitas');
        $this->ensureForeignKey('barang_per_koperasi', 'id_barang', 'master_barang', 'id_barang');
    }

    public function down(): void
    {
        throw new RuntimeException(
            'Migration perbaikan key Gudang tidak boleh di-rollback otomatis.'
        );
    }

    private function assertColumnType(string $table, string $column): void
    {
        $definition = collect(Schema::getColumns($table))
            ->firstWhere('name', $column);

        if (
            $definition === null
            || $definition['nullable']
            || ! str_contains($definition['type'], 'bigint')
            || ! str_contains($definition['type'], 'unsigned')
        ) {
            throw new RuntimeException(
                "Perbaikan dibatalkan: {$table}.{$column} harus berupa BIGINT UNSIGNED NOT NULL."
            );
        }
    }

    /**
     * @param  list<string>  $columns
     */
    private function assertUniqueValues(string $table, array $columns): void
    {
        $query = DB::table($table)->select($columns)->selectRaw('COUNT(*) AS jumlah');

        foreach ($columns as $column) {
            $query->groupBy($column);
        }

        if ($query->havingRaw('COUNT(*) > 1')->exists()) {
            throw new RuntimeException(
                "Perbaikan dibatalkan: ditemukan nilai duplikat pada {$table}."
            );
        }

        $nullKey = DB::table($table)
            ->where(function ($query) use ($columns) {
                foreach ($columns as $column) {
                    $query->orWhereNull($column);
                }
            })
            ->exists();

        if ($nullKey) {
            throw new RuntimeException(
                "Perbaikan dibatalkan: ditemukan key NULL pada {$table}."
            );
        }
    }

    private function assertNoOrphans(
        string $childTable,
        string $childColumn,
        string $parentTable,
        string $parentColumn,
    ): void {
        $orphanExists = DB::table($childTable.' as child')
            ->leftJoin(
                $parentTable.' as parent',
                'child.'.$childColumn,
                '=',
                'parent.'.$parentColumn,
            )
            ->whereNull('parent.'.$parentColumn)
            ->exists();

        if ($orphanExists) {
            throw new RuntimeException(
                "Perbaikan dibatalkan: {$childTable}.{$childColumn} memiliki referensi tanpa pasangan pada {$parentTable}."
            );
        }
    }

    /**
     * @param  list<string>  $columns
     */
    private function ensurePrimaryKey(string $table, array $columns, bool $autoIncrement = false): void
    {
        $existingColumns = DB::table('information_schema.STATISTICS')
            ->whereRaw('TABLE_SCHEMA = DATABASE()')
            ->where('TABLE_NAME', $table)
            ->where('INDEX_NAME', 'PRIMARY')
            ->orderBy('SEQ_IN_INDEX')
            ->pluck('COLUMN_NAME')
            ->all();

        if ($existingColumns !== [] && $existingColumns !== $columns) {
            throw new RuntimeException(
                "Perbaikan dibatalkan: primary key {$table} tidak sesuai dengan struktur yang diharapkan."
            );
        }

        $column = $columns[0];
        $columnDefinition = collect(Schema::getColumns($table))
            ->firstWhere('name', $column);
        $needsAutoIncrement = $autoIncrement && ! $columnDefinition['auto_increment'];

        if ($existingColumns === [] && $autoIncrement) {
            DB::statement(
                "ALTER TABLE `{$table}` MODIFY `{$column}` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, ADD PRIMARY KEY (`{$column}`)"
            );

            return;
        }

        if ($existingColumns === []) {
            DB::statement(
                'ALTER TABLE `'.$table.'` ADD PRIMARY KEY (`'.implode('`, `', $columns).'`)'
            );

            return;
        }

        if ($needsAutoIncrement) {
            DB::statement(
                "ALTER TABLE `{$table}` MODIFY `{$column}` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT"
            );
        }
    }

    private function ensureForeignKey(
        string $table,
        string $column,
        string $parentTable,
        string $parentColumn,
    ): void {
        $matchingForeignKey = DB::table('information_schema.KEY_COLUMN_USAGE')
            ->whereRaw('TABLE_SCHEMA = DATABASE()')
            ->where('TABLE_NAME', $table)
            ->where('COLUMN_NAME', $column)
            ->where('REFERENCED_TABLE_NAME', $parentTable)
            ->where('REFERENCED_COLUMN_NAME', $parentColumn)
            ->exists();

        if ($matchingForeignKey) {
            return;
        }

        $conflictingForeignKey = DB::table('information_schema.KEY_COLUMN_USAGE')
            ->whereRaw('TABLE_SCHEMA = DATABASE()')
            ->where('TABLE_NAME', $table)
            ->where('COLUMN_NAME', $column)
            ->whereNotNull('REFERENCED_TABLE_NAME')
            ->exists();

        if ($conflictingForeignKey) {
            throw new RuntimeException(
                "Perbaikan dibatalkan: foreign key {$table}.{$column} sudah mengarah ke tabel lain."
            );
        }

        $constraint = "{$table}_{$column}_foreign";
        DB::statement(
            "ALTER TABLE `{$table}` ADD CONSTRAINT `{$constraint}` FOREIGN KEY (`{$column}`) REFERENCES `{$parentTable}` (`{$parentColumn}`)"
        );
    }
};

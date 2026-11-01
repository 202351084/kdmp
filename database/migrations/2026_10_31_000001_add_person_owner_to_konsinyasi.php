<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->ensurePartyPrimaryKey();

        $this->addMissingColumns('pengiriman_konsinyasi', [
            'jenis_pemilik' => fn (Blueprint $table) => $table->string('jenis_pemilik', 20)->default('entitas')->after('id_penawaran_barter'),
            'id_pihak_pemilik' => fn (Blueprint $table) => $table->foreignId('id_pihak_pemilik')->nullable()->after('id_entitas_pemilik'),
            'alamat_asal' => fn (Blueprint $table) => $table->text('alamat_asal')->nullable()->after('id_gudang_asal'),
        ]);
        $this->addMissingColumns('stok_konsinyasi', [
            'id_pihak_pemilik' => fn (Blueprint $table) => $table->foreignId('id_pihak_pemilik')->nullable()->after('id_entitas_pemilik'),
        ]);

        $this->setNullable('pengiriman_konsinyasi', 'id_entitas_pemilik');
        $this->setNullable('pengiriman_konsinyasi', 'id_gudang_asal');
        $this->setNullable('stok_konsinyasi', 'id_entitas_pemilik');
        $this->addPartyForeignKey('pengiriman_konsinyasi');
        $this->addPartyForeignKey('stok_konsinyasi');
    }

    public function down(): void
    {
        if (
            DB::table('pengiriman_konsinyasi')->where('jenis_pemilik', 'pihak')->exists()
            || DB::table('stok_konsinyasi')->whereNotNull('id_pihak_pemilik')->exists()
        ) {
            throw new RuntimeException('Pengiriman dengan pemilik perorangan masih ada; data harus ditangani sebelum rollback.');
        }

        if (
            DB::table('pengiriman_konsinyasi')->whereNull('id_entitas_pemilik')->exists()
            || DB::table('pengiriman_konsinyasi')->whereNull('id_gudang_asal')->exists()
            || DB::table('stok_konsinyasi')->whereNull('id_entitas_pemilik')->exists()
        ) {
            throw new RuntimeException('Data pemilik atau gudang asal kosong; skema tidak dapat dikembalikan dengan aman.');
        }

        Schema::table('stok_konsinyasi', function (Blueprint $table): void {
            $table->dropForeign(['id_pihak_pemilik']);
            $table->dropColumn('id_pihak_pemilik');
            $table->foreignId('id_entitas_pemilik')->nullable(false)->change();
        });

        Schema::table('pengiriman_konsinyasi', function (Blueprint $table): void {
            $table->dropForeign(['id_pihak_pemilik']);
            $table->dropColumn(['jenis_pemilik', 'id_pihak_pemilik', 'alamat_asal']);
            $table->foreignId('id_entitas_pemilik')->nullable(false)->change();
            $table->foreignId('id_gudang_asal')->nullable(false)->change();
        });
    }

    private function ensurePartyPrimaryKey(): void
    {
        $hasPrimaryKey = DB::table('information_schema.statistics')
            ->where('TABLE_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', 'master_pihak')
            ->where('INDEX_NAME', 'PRIMARY')
            ->exists();

        if ($hasPrimaryKey) {
            return;
        }

        $hasNullIds = DB::table('master_pihak')->whereNull('id_pihak')->exists();
        $hasDuplicateIds = DB::table('master_pihak')
            ->select('id_pihak')
            ->groupBy('id_pihak')
            ->havingRaw('COUNT(*) > 1')
            ->exists();

        if ($hasNullIds || $hasDuplicateIds) {
            throw new RuntimeException('master_pihak.id_pihak harus unik dan tidak kosong sebelum primary key dibuat.');
        }

        Schema::table('master_pihak', function (Blueprint $table): void {
            $table->primary('id_pihak');
        });
    }

    private function addMissingColumns(string $tableName, array $columns): void
    {
        foreach ($columns as $columnName => $definition) {
            if (Schema::hasColumn($tableName, $columnName)) {
                continue;
            }

            Schema::table($tableName, $definition);
        }
    }

    private function setNullable(string $tableName, string $columnName): void
    {
        $nullable = DB::table('information_schema.columns')
            ->where('TABLE_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', $tableName)
            ->where('COLUMN_NAME', $columnName)
            ->value('IS_NULLABLE');

        if ($nullable === 'YES') {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) use ($columnName): void {
            $table->foreignId($columnName)->nullable()->change();
        });
    }

    private function addPartyForeignKey(string $tableName): void
    {
        $hasForeignKey = DB::table('information_schema.key_column_usage')
            ->where('TABLE_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', $tableName)
            ->where('COLUMN_NAME', 'id_pihak_pemilik')
            ->where('REFERENCED_TABLE_NAME', 'master_pihak')
            ->where('REFERENCED_COLUMN_NAME', 'id_pihak')
            ->exists();

        if ($hasForeignKey) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table): void {
            $table->foreign('id_pihak_pemilik')
                ->references('id_pihak')
                ->on('master_pihak');
        });
    }
};

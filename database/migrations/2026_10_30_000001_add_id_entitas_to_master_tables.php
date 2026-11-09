<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Tambah kolom id_entitas pada tabel komoditas
        if (Schema::hasTable('komoditas') && ! Schema::hasColumn('komoditas', 'id_entitas')) {
            Schema::table('komoditas', function (Blueprint $table) {
                $table->unsignedBigInteger('id_entitas')->nullable()->after('id');
                $table->index('id_entitas', 'komoditas_id_entitas_idx');
            });
        }

        // 2. Tambah kolom id_entitas pada tabel satuan & sesuaikan unique index kode_satuan per entitas
        if (Schema::hasTable('satuan') && ! Schema::hasColumn('satuan', 'id_entitas')) {
            try {
                Schema::table('satuan', function (Blueprint $table) {
                    $table->dropUnique(['kode_satuan']);
                });
            } catch (\Throwable $e) {
                // Abaikan jika index unique lama tidak ada
            }

            Schema::table('satuan', function (Blueprint $table) {
                $table->unsignedBigInteger('id_entitas')->nullable()->after('id');
                $table->index('id_entitas', 'satuan_id_entitas_idx');
                $table->unique(['id_entitas', 'kode_satuan'], 'satuan_entitas_kode_unique');
            });
        }

        // 3. Tambah kolom id_entitas pada tabel master_barang & sesuaikan unique index kode_barang per entitas
        if (Schema::hasTable('master_barang') && ! Schema::hasColumn('master_barang', 'id_entitas')) {
            try {
                Schema::table('master_barang', function (Blueprint $table) {
                    $table->dropUnique(['kode_barang']);
                });
            } catch (\Throwable $e) {
                // Abaikan jika index unique lama tidak ada
            }

            Schema::table('master_barang', function (Blueprint $table) {
                $table->unsignedBigInteger('id_entitas')->nullable()->after('id_barang');
                $table->index('id_entitas', 'master_barang_id_entitas_idx');
                $table->unique(['id_entitas', 'kode_barang'], 'master_barang_entitas_kode_unique');
            });
        }

        // 4. Isolasi data seeder awal per entitas (KDMP-A dan KDMP-B)
        if (Schema::hasTable('entitas')) {
            $entitasA = DB::table('entitas')->where('kode_entitas', 'KDMP-A')->value('id_entitas');
            $entitasB = DB::table('entitas')->where('kode_entitas', 'KDMP-B')->value('id_entitas');

            if ($entitasA) {
                if (Schema::hasTable('satuan') && Schema::hasColumn('satuan', 'id_entitas')) {
                    DB::table('satuan')->whereNull('id_entitas')->update(['id_entitas' => $entitasA]);
                }
                if (Schema::hasTable('komoditas') && Schema::hasColumn('komoditas', 'id_entitas')) {
                    DB::table('komoditas')->whereNull('id_entitas')->update(['id_entitas' => $entitasA]);
                }
                if (Schema::hasTable('master_barang') && Schema::hasColumn('master_barang', 'id_entitas')) {
                    DB::table('master_barang')->whereNull('id_entitas')->update(['id_entitas' => $entitasA]);
                }

                // Pastikan semua barang KDMP-A terdaftar di barang_per_koperasi milik KDMP-A
                if (Schema::hasTable('master_barang') && Schema::hasTable('barang_per_koperasi')) {
                    $barangA = DB::table('master_barang')->where('id_entitas', $entitasA)->pluck('id_barang');
                    foreach ($barangA as $idBarang) {
                        DB::table('barang_per_koperasi')->updateOrInsert(
                            ['id_entitas' => $entitasA, 'id_barang' => $idBarang],
                            [
                                'stok_minimum' => 10,
                                'stok_maksimum' => 1000,
                                'harga_jual_standar' => 13000,
                                'is_dijual' => true,
                            ]
                        );
                    }
                }
            }

            // Sediakan salinan master satuan & komoditas terpisah untuk KDMP-B jika belum ada
            if ($entitasB && $entitasA) {
                if (Schema::hasTable('satuan') && Schema::hasColumn('satuan', 'id_entitas')) {
                    $satuanA = DB::table('satuan')->where('id_entitas', $entitasA)->get();
                    foreach ($satuanA as $s) {
                        $exists = DB::table('satuan')
                            ->where('id_entitas', $entitasB)
                            ->where('kode_satuan', $s->kode_satuan)
                            ->exists();
                        if (! $exists) {
                            DB::table('satuan')->insert([
                                'id_entitas' => $entitasB,
                                'kode_satuan' => $s->kode_satuan,
                                'alias_json' => $s->alias_json,
                                'is_active' => $s->is_active,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                        }
                    }
                }

                if (Schema::hasTable('komoditas') && Schema::hasColumn('komoditas', 'id_entitas')) {
                    $komoditasA = DB::table('komoditas')->where('id_entitas', $entitasA)->get();
                    foreach ($komoditasA as $k) {
                        $exists = DB::table('komoditas')
                            ->where('id_entitas', $entitasB)
                            ->where('nama', $k->nama)
                            ->exists();
                        if (! $exists) {
                            DB::table('komoditas')->insert([
                                'id_entitas' => $entitasB,
                                'kategori' => $k->kategori,
                                'nama' => $k->nama,
                                'alias_json' => $k->alias_json,
                                'is_active' => $k->is_active,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                        }
                    }
                }
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('master_barang') && Schema::hasColumn('master_barang', 'id_entitas')) {
            Schema::table('master_barang', function (Blueprint $table) {
                try {
                    $table->dropUnique('master_barang_entitas_kode_unique');
                    $table->dropIndex('master_barang_id_entitas_idx');
                } catch (\Throwable $e) {
                }
                $table->dropColumn('id_entitas');
            });
        }

        if (Schema::hasTable('satuan') && Schema::hasColumn('satuan', 'id_entitas')) {
            Schema::table('satuan', function (Blueprint $table) {
                try {
                    $table->dropUnique('satuan_entitas_kode_unique');
                    $table->dropIndex('satuan_id_entitas_idx');
                } catch (\Throwable $e) {
                }
                $table->dropColumn('id_entitas');
            });
        }

        if (Schema::hasTable('komoditas') && Schema::hasColumn('komoditas', 'id_entitas')) {
            Schema::table('komoditas', function (Blueprint $table) {
                try {
                    $table->dropIndex('komoditas_id_entitas_idx');
                } catch (\Throwable $e) {
                }
                $table->dropColumn('id_entitas');
            });
        }
    }
};

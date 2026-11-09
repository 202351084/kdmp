<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class StoredProcedureSeeder extends Seeder
{
    /**
     * Daftarkan ulang Stored Procedure ke database MySQL.
     * Berguna jika database diimpor dari dump SQL tanpa routine/SP.
     */
    public function run(): void
    {
        $this->command->info('Mendaftarkan Stored Procedures...');

        // 1. sp_post_jurnal & sp_jurnal_pembalik
        $migrationSpJurnal = require database_path('migrations/2026_10_11_000001_revise_sp_jurnal_tanpa_transaksi.php');
        $migrationSpJurnal->up();

        // 1b. sp_jurnal_pembalik versi terbaru (validasi id_entitas, 4 parameter)
        //     WAJIB setelah langkah 1 karena menimpa versi 3 parameter.
        $migrationSpPembalik = require database_path('migrations/2026_10_24_000001_sp_jurnal_pembalik_validasi_entitas.php');
        $migrationSpPembalik->up();
        $this->command->info('  [OK] sp_post_jurnal & sp_jurnal_pembalik berhasil dibuat.');

        // 2. sp_tutup_tahun
        $migrationSpTutup = require database_path('migrations/2026_10_04_000002_revise_sp_tutup_tahun_non_koperasi.php');
        $migrationSpTutup->up();
        $this->command->info('  [OK] sp_tutup_tahun berhasil dibuat.');

        $this->command->info('Semua Stored Procedure berhasil didaftarkan ke MySQL!');
    }
}

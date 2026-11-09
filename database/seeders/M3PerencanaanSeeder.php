<?php

namespace Database\Seeders;

use App\Models\Master\Barang;
use App\Models\Master\Komoditas;
use App\Models\Master\Satuan;
use App\Models\Master\UnitUsaha;
use App\Models\Tenant\Entitas;
use App\Models\Tenant\Wilayah;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class M3PerencanaanSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Pastikan Master Barang untuk setiap Komoditas Pokok
        $unitUsahaSembako = DB::table('master_unit_usaha')->where('kode_unit_usaha', 'SEMBAKO')->value('id_unit_usaha') ?? 1;
        $satuanKg = Satuan::where('kode_satuan', 'KG')->value('id') ?? 1;
        $satuanLtr = Satuan::where('kode_satuan', 'LTR')->value('id') ?? 2;

        $daftarBarang = [
            [
                'kode_barang' => 'BRG-BERAS-01',
                'nama_barang' => 'Beras IR-64 Medium',
                'komoditas' => 'Beras',
                'satuan' => $satuanKg,
            ],
            [
                'kode_barang' => 'BRG-GULA-01',
                'nama_barang' => 'Gula Pasir Kristal Putih',
                'komoditas' => 'Gula Pasir',
                'satuan' => $satuanKg,
            ],
            [
                'kode_barang' => 'BRG-MYK-01',
                'nama_barang' => 'Minyak Goreng Sawit 1L',
                'komoditas' => 'Minyak Goreng',
                'satuan' => $satuanLtr,
            ],
            [
                'kode_barang' => 'BRG-TLR-01',
                'nama_barang' => 'Telur Ayam Ras Segar',
                'komoditas' => 'Telur Ayam',
                'satuan' => $satuanKg,
            ],
            [
                'kode_barang' => 'BRG-SAPI-01',
                'nama_barang' => 'Daging Sapi Lokal Pilihan',
                'komoditas' => 'Daging Sapi',
                'satuan' => $satuanKg,
            ],
        ];

        foreach ($daftarBarang as $b) {
            $komoditas = Komoditas::where('nama', $b['komoditas'])->first();
            if ($komoditas) {
                Barang::updateOrCreate(
                    ['kode_barang' => $b['kode_barang']],
                    [
                        'id_komoditas' => $komoditas->id,
                        'id_unit_usaha' => $unitUsahaSembako,
                        'nama_barang' => $b['nama_barang'],
                        'id_satuan_dasar' => $b['satuan'],
                        'is_active' => true,
                    ]
                );
            }
        }

        // 2. Data Master Kelompok Tani sebagai Supplier di master_pihak
        $koperasiMekar = Entitas::where('kode_entitas', 'KDMP-A')->first();
        $koperasiSukamaju = Entitas::where('kode_entitas', 'KDMP-B')->first();
        $wilayahMekar = Wilayah::where('nama', 'Desa Mekar Jaya')->first();
        $wilayahSukamaju = Wilayah::where('nama', 'Desa Sukamaju')->first();

        if ($koperasiMekar && $wilayahMekar) {
            DB::table('master_pihak')->updateOrInsert(
                [
                    'id_entitas' => $koperasiMekar->id_entitas,
                    'nama' => 'Kelompok Tani Makmur Subur',
                ],
                [
                    'jenis_pihak' => 'petani',
                    'is_anggota' => true,
                    'no_anggota' => 'POKTAN-MJ-001',
                    'alamat' => 'Dusun Mekar Sari RT 02/03, Desa Mekar Jaya',
                    'no_hp' => '0812-3456-7890',
                    'id_wilayah' => $wilayahMekar->id,
                    'kualitas_rating' => 4.80,
                    'estimasi_pengiriman' => '1 hari',
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );

            // Poktan mitra dari desa tetangga yang siap memasok ke Mekar Jaya
            if ($wilayahSukamaju) {
                DB::table('master_pihak')->updateOrInsert(
                    [
                        'id_entitas' => $koperasiMekar->id_entitas,
                        'nama' => 'Kelompok Tani Sri Rejeki (Mitra Sukamaju)',
                    ],
                    [
                        'jenis_pihak' => 'petani',
                        'is_anggota' => false,
                        'no_anggota' => null,
                        'alamat' => 'Dusun Krajan RT 01/01, Desa Sukamaju',
                        'no_hp' => '0813-8899-7711',
                        'id_wilayah' => $wilayahSukamaju->id,
                        'kualitas_rating' => 4.65,
                        'estimasi_pengiriman' => '1-2 hari',
                        'is_active' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }
        }

        if ($koperasiSukamaju && $wilayahSukamaju) {
            DB::table('master_pihak')->updateOrInsert(
                [
                    'id_entitas' => $koperasiSukamaju->id_entitas,
                    'nama' => 'Kelompok Tani Sri Rejeki',
                ],
                [
                    'jenis_pihak' => 'petani',
                    'is_anggota' => true,
                    'no_anggota' => 'POKTAN-SM-001',
                    'alamat' => 'Dusun Krajan RT 01/01, Desa Sukamaju',
                    'no_hp' => '0813-8899-7711',
                    'id_wilayah' => $wilayahSukamaju->id,
                    'kualitas_rating' => 4.70,
                    'estimasi_pengiriman' => '1 hari',
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        // 3. Rekomendasi Alternatif KBR (perbandingan_harga)
        if ($koperasiMekar && $wilayahSukamaju) {
            $komoditasGula = Komoditas::where('nama', 'Gula Pasir')->first();
            $komoditasMinyak = Komoditas::where('nama', 'Minyak Goreng')->first();

            if ($komoditasGula) {
                DB::table('perbandingan_harga')->updateOrInsert(
                    [
                        'id_entitas' => $koperasiMekar->id_entitas,
                        'id_komoditas' => $komoditasGula->id,
                        'id_wilayah_sumber' => $wilayahSukamaju->id,
                        'bulan' => 3,
                        'tahun' => 2026,
                    ],
                    [
                        'harga_ditawarkan' => 14500.00,
                        'jumlah_tersedia' => 2500.0000,
                        'jarak_ke_gudang' => 4.20,
                        'estimasi_ongkir' => 500.00,
                        'harga_efektif' => 15000.00,
                        'rank_harga' => 1,
                        'dipilih' => true,
                        'created_at' => now(),
                    ]
                );
            }

            if ($komoditasMinyak) {
                DB::table('perbandingan_harga')->updateOrInsert(
                    [
                        'id_entitas' => $koperasiMekar->id_entitas,
                        'id_komoditas' => $komoditasMinyak->id,
                        'id_wilayah_sumber' => $wilayahSukamaju->id,
                        'bulan' => 3,
                        'tahun' => 2026,
                    ],
                    [
                        'harga_ditawarkan' => 15500.00,
                        'jumlah_tersedia' => 1800.0000,
                        'jarak_ke_gudang' => 4.20,
                        'estimasi_ongkir' => 500.00,
                        'harga_efektif' => 16000.00,
                        'rank_harga' => 1,
                        'dipilih' => true,
                        'created_at' => now(),
                    ]
                );
            }
        }
    }
}

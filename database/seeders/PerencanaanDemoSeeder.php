<?php

namespace Database\Seeders;

use App\Models\Master\Barang;
use App\Models\Master\Komoditas;
use App\Models\Tenant\Entitas;
use App\Models\Tenant\Wilayah;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PerencanaanDemoSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $unitUsaha = (int) DB::table('master_unit_usaha')
            ->where('kode_unit_usaha', 'SEMBAKO')
            ->value('id_unit_usaha');
        $satuanKg = (int) DB::table('satuan')->where('kode_satuan', 'KG')->value('id');
        $wilayah = Wilayah::whereIn('nama', ['Desa Mekar Jaya', 'Desa Sukamaju'])->get()->keyBy('nama');
        $entitas = Entitas::whereIn('kode_entitas', ['KDMP-A', 'KDMP-B'])->get()->keyBy('kode_entitas');

        $komoditasData = [
            ['nama' => 'Beras', 'kategori' => 'pertanian', 'kode' => 'BRG-DEMO-BERAS', 'produksi' => [18000, 15000], 'kebutuhan' => 15000, 'harga' => 13500],
            ['nama' => 'Gula Pasir', 'kategori' => 'pertanian', 'kode' => 'BRG-DEMO-GULA', 'produksi' => [0, 250], 'kebutuhan' => 1500, 'harga' => 16000],
            ['nama' => 'Jagung', 'kategori' => 'pertanian', 'kode' => 'BRG-DEMO-JAGUNG', 'produksi' => [12000, 9000], 'kebutuhan' => 10000, 'harga' => 6500],
            ['nama' => 'Cabai Merah', 'kategori' => 'pertanian', 'kode' => 'BRG-DEMO-CABAI', 'produksi' => [1400, 850], 'kebutuhan' => 900, 'harga' => 32000],
            ['nama' => 'Kopi', 'kategori' => 'perkebunan', 'kode' => 'BRG-DEMO-KOPI', 'produksi' => [300, 180], 'kebutuhan' => 500, 'harga' => 42000],
            ['nama' => 'Minyak Goreng', 'kategori' => 'perkebunan', 'kode' => 'BRG-DEMO-MINYAK', 'produksi' => [300, 450], 'kebutuhan' => 1300, 'harga' => 15500],
            ['nama' => 'Telur Ayam', 'kategori' => 'peternakan', 'kode' => 'BRG-DEMO-TELUR', 'produksi' => [3200, 2400], 'kebutuhan' => 2700, 'harga' => 28000],
            ['nama' => 'Daging Sapi', 'kategori' => 'peternakan', 'kode' => 'BRG-DEMO-SAPI', 'produksi' => [450, 700], 'kebutuhan' => 1000, 'harga' => 125000],
            ['nama' => 'Kambing', 'kategori' => 'peternakan', 'kode' => 'BRG-DEMO-KAMBING', 'produksi' => [60, 110], 'kebutuhan' => 120, 'harga' => 95000],
        ];

        $komoditas = [];
        foreach ($komoditasData as $data) {
            $item = Komoditas::updateOrCreate(
                ['nama' => $data['nama']],
                ['kategori' => $data['kategori'], 'is_active' => true]
            );
            $komoditas[$data['nama']] = $item;

            Barang::updateOrCreate(
                ['kode_barang' => $data['kode']],
                [
                    'id_komoditas' => $item->id,
                    'id_unit_usaha' => $unitUsaha,
                    'nama_barang' => $data['nama'].' Lokal',
                    'id_satuan_dasar' => $satuanKg,
                    'is_active' => true,
                ]
            );
        }

        foreach ($wilayah->values() as $index => $desa) {
            for ($bulan = 1; $bulan <= 12; $bulan++) {
                foreach ($komoditasData as $data) {
                    $item = $komoditas[$data['nama']];
                    $produksi = (float) $data['produksi'][$index];
                    DB::table('ketersediaan_komoditas')->updateOrInsert(
                        ['id_wilayah' => $desa->id, 'id_komoditas' => $item->id, 'tahun' => 2026, 'bulan' => $bulan],
                        ['id_sesi_survei' => null, 'jumlah_produksi' => $produksi, 'satuan' => 'KG', 'created_at' => $now]
                    );

                    $harga = $data['harga'] + ($index * 500) + (($bulan - 1) * 100);
                    foreach ($entitas as $entitasItem) {
                        DB::table('perbandingan_harga')->updateOrInsert(
                            [
                                'id_entitas' => $entitasItem->id_entitas,
                                'id_komoditas' => $item->id,
                                'id_wilayah_sumber' => $desa->id,
                                'bulan' => $bulan,
                                'tahun' => 2026,
                            ],
                            [
                                'harga_ditawarkan' => $harga,
                                'jumlah_tersedia' => $produksi,
                                'jarak_ke_gudang' => $index === 0 ? 0 : 4.2,
                                'estimasi_ongkir' => $index === 0 ? 0 : 500,
                                'harga_efektif' => $harga + ($index === 0 ? 0 : 500),
                                'rank_harga' => null,
                                'dipilih' => false,
                                'created_at' => $now,
                            ]
                        );
                    }

                    $kebutuhan = (float) $data['kebutuhan'];
                    $selisih = $produksi - $kebutuhan;
                    DB::table('hasil_kalkulasi')->updateOrInsert(
                        ['id_wilayah' => $desa->id, 'id_komoditas' => $item->id, 'tahun' => 2026, 'bulan' => $bulan, 'versi' => 1],
                        [
                            'total_kebutuhan' => $kebutuhan,
                            'total_ketersediaan' => $produksi,
                            'selisih' => $selisih,
                            'status_surplus_defisit' => $selisih > 0 ? 'surplus' : ($selisih < 0 ? 'defisit' : 'seimbang'),
                            'persentase_kecukupan' => $kebutuhan > 0 ? ($produksi / $kebutuhan) * 100 : 0,
                            'id_unit_usaha_rekomendasi' => $unitUsaha,
                            'alasan_rekomendasi' => $selisih < 0 ? 'Kebutuhan melebihi produksi lokal; perlu PR ke gudang.' : 'Produksi lokal mencukupi kebutuhan desa.',
                            'prioritas' => $selisih < 0 ? 1 : 3,
                            'status' => 'disetujui',
                            'created_at' => $now,
                        ]
                    );
                }
            }
        }

        foreach ($entitas as $entitasItem) {
            DB::table('gudang')->updateOrInsert(
                ['id_entitas' => $entitasItem->id_entitas, 'kode_gudang' => 'DEMO-PERENCANAAN'],
                [
                    'nama_gudang' => 'Gudang Demo Komoditas',
                    'alamat' => 'Gudang tujuan PR data dummy',
                    'is_active' => true,
                ]
            );
        }
    }
}

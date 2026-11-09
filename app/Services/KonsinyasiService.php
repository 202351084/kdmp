<?php

namespace App\Services;

use App\Services\Finance\JurnalService;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Proses inti konsinyasi antar desa.
 * Barang tetap menjadi aset pemilik sampai terjual di desa penerima.
 */
class KonsinyasiService
{
    public function __construct(
        private JurnalService $jurnal,
        private StokService $stok,
    ) {}

    /**
     * Mem-posting pengiriman titipan: stok normal pemilik berkurang,
     * stok konsinyasi penerima bertambah, dan hanya pemilik mendapat jurnal KTK.
     */
    public function kirim(int $idKiriman): bool
    {
        // Sejak revisi 2026-10-11 sp_post_jurnal tidak lagi COMMIT sendiri, sehingga
        // seluruh proses (stok keluar, stok konsinyasi, jurnal KTK, status kiriman)
        // dibungkus SATU transaksi: berhasil semua atau batal semua.
        return DB::transaction(function () use ($idKiriman): bool {
            $persiapan = DB::transaction(function () use ($idKiriman): array|false {
            $kiriman = DB::table('pengiriman_konsinyasi')
                ->where('id_kiriman', $idKiriman)
                ->lockForUpdate()
                ->first();

            if (! $kiriman) {
                throw new RuntimeException("Pengiriman {$idKiriman} tidak ditemukan.");
            }

            if ($kiriman->status_posting === 'T') {
                throw new RuntimeException('Pengiriman ini sudah diposting.');
            }

            if ($kiriman->id_entitas_pemilik === $kiriman->id_entitas_penerima) {
                throw new RuntimeException('Pemilik dan penerima harus merupakan koperasi yang berbeda.');
            }

            $detail = DB::table('pengiriman_konsinyasi_detail as d')
                ->join('master_barang as b', 'b.id_barang', '=', 'd.id_barang')
                ->join('master_unit_usaha as u', 'u.id_unit_usaha', '=', 'b.id_unit_usaha')
                ->where('d.id_kiriman', $idKiriman)
                ->select('d.*', 'u.kode_unit_usaha')
                ->get();

            if ($detail->isEmpty()) {
                throw new RuntimeException('Pengiriman tanpa detail barang.');
            }

            $kodeUnit = $detail->first()->kode_unit_usaha;
            $totalHpp = '0';
            $totalTitip = '0';
            $kekurangan = [];

            foreach ($detail as $item) {
                $mutasiStokAda = DB::table('kartu_stok')
                    ->where('ref_tipe', 'KIRIM_KONSINYASI')
                    ->where('ref_id', $idKiriman)
                    ->where('id_barang', $item->id_barang)
                    ->where('jenis_mutasi', 'OUT')
                    ->exists();

                if ($mutasiStokAda) {
                    continue;
                }

                $tersedia = $this->stok->tersedia((int) $kiriman->id_gudang_asal, (int) $item->id_barang);
                if (bccomp($tersedia, (string) $item->qty_dasar, 4) < 0) {
                    $kekurangan[] = "barang {$item->id_barang}: tersedia {$tersedia}, diminta {$item->qty_dasar}";
                }
            }

            if ($kekurangan !== []) {
                DB::table('pengiriman_konsinyasi')
                    ->where('id_kiriman', $idKiriman)
                    ->update([
                        'status' => 'menunggu_stok',
                        'catatan_pengiriman' => 'Menunggu stok: '.implode('; ', $kekurangan),
                        'updated_at' => now(),
                    ]);

                return false;
            }

            foreach ($detail as $item) {
                if ($item->kode_unit_usaha !== $kodeUnit) {
                    throw new RuntimeException('Semua barang dalam satu pengiriman harus berasal dari unit usaha yang sama.');
                }

                if (bccomp((string) $item->qty_dasar, '0', 4) <= 0) {
                    throw new RuntimeException('Qty pengiriman harus lebih besar dari nol.');
                }

                if (bccomp((string) $item->harga_titip_satuan, '0', 2) < 0) {
                    throw new RuntimeException('Harga titip tidak boleh negatif.');
                }

                $kartuStok = DB::table('kartu_stok')
                    ->where('ref_tipe', 'KIRIM_KONSINYASI')
                    ->where('ref_id', $idKiriman)
                    ->where('id_barang', $item->id_barang)
                    ->where('jenis_mutasi', 'OUT')
                    ->first();

                if ($kartuStok) {
                    // Retry setelah kegagalan jurnal: mutasi stok sudah tersimpan.
                    $hpp = (string) $kartuStok->harga_satuan;
                    $nilaiHpp = (string) $kartuStok->nilai_mutasi;
                } else {
                    $hpp = $this->stok->hppSaatIni((int) $kiriman->id_gudang_asal, (int) $item->id_barang);
                    $kartuStok = $this->stok->keluar(
                        entitasId: (int) $kiriman->id_entitas_pemilik,
                        gudangId: (int) $kiriman->id_gudang_asal,
                        barangId: (int) $item->id_barang,
                        qty: (string) $item->qty_dasar,
                        refTipe: 'KIRIM_KONSINYASI',
                        refId: $idKiriman,
                        tanggal: (string) $kiriman->tgl_kirim,
                    );
                    $nilaiHpp = (string) $kartuStok->nilai_mutasi;
                }

                $nilaiTitip = bcmul((string) $item->qty_dasar, (string) $item->harga_titip_satuan, 2);
                $totalHpp = bcadd($totalHpp, $nilaiHpp, 2);
                $totalTitip = bcadd($totalTitip, $nilaiTitip, 2);

                DB::table('pengiriman_konsinyasi_detail')
                    ->where('id_detail', $item->id_detail)
                    ->update([
                        'hpp_pemilik' => $hpp,
                        'total_nilai_titip' => $nilaiTitip,
                        'total_hpp' => $nilaiHpp,
                    ]);

                $stokKonsinyasi = DB::table('stok_konsinyasi')
                    ->where('id_kiriman', $idKiriman)
                    ->where('id_barang', $item->id_barang)
                    ->first();

                if (! $stokKonsinyasi) {
                    $stokKonsinyasiId = DB::table('stok_konsinyasi')->insertGetId([
                        'id_kiriman' => $idKiriman,
                        'id_entitas_pemilik' => $kiriman->id_entitas_pemilik,
                        'id_entitas_penerima' => $kiriman->id_entitas_penerima,
                        'id_gudang_penerima' => $kiriman->id_gudang_tujuan,
                        'id_barang' => $item->id_barang,
                        'qty_titip' => $item->qty_dasar,
                        'qty_sisa' => $item->qty_dasar,
                        'harga_titip_satuan' => $item->harga_titip_satuan,
                        'harga_jual_satuan' => $item->harga_jual_saran,
                        'hpp_pemilik' => $hpp,
                        'status' => 'aktif',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                } else {
                    $stokKonsinyasiId = (int) $stokKonsinyasi->id_stok_konsinyasi;
                }

                $kartuKonsinyasiAda = DB::table('kartu_konsinyasi')
                    ->where('ref_tipe', 'KIRIM_KONSINYASI')
                    ->where('ref_id', $idKiriman)
                    ->where('id_stok_konsinyasi', $stokKonsinyasiId)
                    ->where('jenis_mutasi', 'TITIP')
                    ->exists();

                if (! $kartuKonsinyasiAda) {
                    DB::table('kartu_konsinyasi')->insert([
                        'id_stok_konsinyasi' => $stokKonsinyasiId,
                        'tanggal' => $kiriman->tgl_kirim,
                        'jenis_mutasi' => 'TITIP',
                        'ref_tipe' => 'KIRIM_KONSINYASI',
                        'ref_id' => $idKiriman,
                        'qty' => $item->qty_dasar,
                        'harga_titip_satuan' => $item->harga_titip_satuan,
                        'harga_jual_satuan' => $item->harga_jual_saran,
                        'saldo_qty' => $item->qty_dasar,
                        'created_at' => now(),
                    ]);
                }
            }

            return [
                'kiriman' => $kiriman,
                'kode_unit' => $kodeUnit,
                'total_hpp' => $totalHpp,
                'total_titip' => $totalTitip,
            ];
        });

        if ($persiapan === false) {
            return false;
        }

        $kiriman = $persiapan['kiriman'];
        $jurnal = $this->jurnal->posting(
            kodeTransaksi: 'KTK',
            payload: [
                'tanggal_jurnal' => (string) $kiriman->tgl_kirim,
                'total_hpp' => $persiapan['total_hpp'],
                'kode_unit' => $persiapan['kode_unit'],
                'nomor_nota' => $kiriman->kode_kiriman,
            ],
            sourceType: 'pengiriman_konsinyasi',
            sourceId: $idKiriman,
            keterangan: "Kirim titipan {$kiriman->kode_kiriman}",
            entitasId: (int) $kiriman->id_entitas_pemilik,
        );

        DB::table('pengiriman_konsinyasi')
            ->where('id_kiriman', $idKiriman)
            ->update([
                'status' => 'dikirim',
                'status_posting' => 'T',
                'total_nilai_titip' => $persiapan['total_titip'],
                'total_hpp_pemilik' => $persiapan['total_hpp'],
                'id_jurnal_kirim' => $jurnal->id_jurnal,
                'updated_at' => now(),
            ]);

            return true;
        }, 3);
    }
}


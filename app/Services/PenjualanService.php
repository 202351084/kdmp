<?php

namespace App\Services;

use App\Models\Master\Gudang;
use App\Models\Master\Pihak;
use App\Models\Keuangan\Piutang;
use App\Models\Penjualan\DetailPenjualan;
use App\Models\Penjualan\Penjualan;
use App\Models\Penjualan\ReturPenjualan;
use App\Models\Penjualan\ReturPenjualanDetail;
use App\Services\Finance\JurnalService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use RuntimeException;

class PenjualanService
{
    public const DEMO_STOCK_PER_ITEM = 100.0;

    public static function availableDemoStock(int $idEntitas, int $idBarang): float
    {
        $sold = (float) DB::table('detail_penjualan as detail')
            ->join('penjualan as sale', 'sale.id_penjualan', '=', 'detail.id_penjualan')
            ->where('sale.id_entitas', $idEntitas)
            ->where('sale.status', 'selesai')
            ->where('detail.id_barang', $idBarang)
            ->sum('detail.qty_dasar');

        $returned = (float) DB::table('retur_penjualan_detail as detail')
            ->join('retur_penjualan as retur', 'retur.id_retur', '=', 'detail.id_retur')
            ->join('penjualan as sale', 'sale.id_penjualan', '=', 'retur.id_penjualan')
            ->where('sale.id_entitas', $idEntitas)
            ->where('sale.status', 'selesai')
            ->where('retur.status', 'selesai')
            ->where('detail.id_barang', $idBarang)
            ->sum('detail.qty_dasar');

        return max(0, self::DEMO_STOCK_PER_ITEM - $sold + $returned);
    }

    public static function createSale(
        int $idEntitas,
        ?int $idPihak,
        int $idUnitUsaha,
        string $metodeBayar,
        array $items,
        ?int $idKasBank = null,
        array $transferDetails = [],
        ?string $tglJatuhTempo = null,
        ?string $namaPembeli = null,
    ): Penjualan {
        if (! in_array($metodeBayar, ['tunai', 'transfer', 'kredit'], true)) {
            throw new RuntimeException('Metode pembayaran tidak valid.');
        }

        if ($metodeBayar === 'transfer' && (
            $idKasBank === null
            || blank($transferDetails['pengirim_nama'] ?? null)
            || blank($transferDetails['pengirim_bank'] ?? null)
            || blank($transferDetails['pengirim_rekening'] ?? null)
            || blank($transferDetails['no_referensi'] ?? null)
            || ($transferDetails['dikonfirmasi'] ?? false) !== true
        )) {
            throw new RuntimeException('Lengkapi rekening tujuan dan data transfer, lalu konfirmasi dana telah diterima.');
        }

        if ($metodeBayar === 'kredit' && (
            blank($tglJatuhTempo)
            || Carbon::parse($tglJatuhTempo)->startOfDay()->lt(today())
        )) {
            throw new RuntimeException('Tanggal jatuh tempo kredit wajib diisi dan tidak boleh sebelum hari ini.');
        }

        if ($items === []) {
            throw new RuntimeException('Keranjang penjualan masih kosong.');
        }

        return DB::transaction(function () use ($idEntitas, $idPihak, $idUnitUsaha, $metodeBayar, $items, $idKasBank, $transferDetails, $tglJatuhTempo, $namaPembeli) {
            if ($idPihak === null && filled($namaPembeli)) {
                $pembeli = Pihak::query()->firstOrCreate(
                    [
                        'id_entitas' => $idEntitas,
                        'jenis_pihak' => 'entitas_lain',
                        'nama' => 'Pembeli Umum',
                    ],
                    ['is_anggota' => false, 'is_active' => true],
                );
                if (! $pembeli->is_active) {
                    $pembeli->update(['is_active' => true]);
                }
            } else {
                $pembeli = Pihak::query()
                    ->where('id_pihak', $idPihak)
                    ->where('is_active', true)
                    ->firstOrFail();
            }

            $kasBank = null;
            if ($metodeBayar !== 'kredit') {
                $kasBank = DB::table('master_kas_bank')
                    ->where('id_entitas', $idEntitas)
                    ->where('jenis', $metodeBayar === 'tunai' ? 'kas' : 'bank')
                    ->where('is_active', true)
                    ->when($metodeBayar === 'transfer', fn ($query) => $query->whereNotNull('no_rekening')->where('no_rekening', '<>', ''))
                    ->when($metodeBayar === 'transfer', fn ($query) => $query->where('id_kas_bank', $idKasBank))
                    ->orderByDesc('is_default')
                    ->orderBy('id_kas_bank')
                    ->first();
            }

            if ($metodeBayar !== 'kredit' && ! $kasBank) {
                throw new RuntimeException(
                    $metodeBayar === 'transfer'
                        ? 'Rekening bank tujuan tidak aktif atau bukan milik entitas ini.'
                        : 'Rekening kas aktif untuk entitas ini belum dikonfigurasi.',
                );
            }

            $idGudang = Gudang::query()
                ->where('id_entitas', $idEntitas)
                ->where('is_active', true)
                ->orderBy('id_gudang')
                ->value('id_gudang')
                ?? Gudang::value('id_gudang')
                ?? 1;

            $itemsByBarang = collect($items)
                ->groupBy('id_barang')
                ->map(fn ($rows) => (float) $rows->sum('qty_dasar'));

            $products = DB::table('master_barang as barang')
                ->join('barang_per_koperasi as setting', function ($join) use ($idEntitas) {
                    $join->on('setting.id_barang', '=', 'barang.id_barang')
                        ->where('setting.id_entitas', $idEntitas)
                        ->where('setting.is_dijual', true);
                })
                ->where('barang.is_active', true)
                ->where('barang.id_unit_usaha', $idUnitUsaha)
                ->whereIn('barang.id_barang', $itemsByBarang->keys())
                ->select([
                    'barang.id_barang',
                    'barang.id_satuan_dasar',
                    'setting.harga_jual_standar',
                ])
                ->lockForUpdate()
                ->get()
                ->keyBy('id_barang');

            if ($products->count() !== $itemsByBarang->count()) {
                throw new RuntimeException('Barang tidak aktif, bukan milik unit usaha yang dipilih, atau tidak tersedia untuk dijual.');
            }

            $total = 0.0;
            foreach ($itemsByBarang as $idBarang => $quantity) {
                if ($quantity <= 0) {
                    throw new RuntimeException('Jumlah barang harus lebih besar dari nol.');
                }

                if ($quantity > self::availableDemoStock($idEntitas, (int) $idBarang)) {
                    throw new RuntimeException("Stok demo {$products->get($idBarang)->id_barang} tidak mencukupi.");
                }

                $total += $quantity * (float) $products->get($idBarang)->harga_jual_standar;
            }

            $sale = Penjualan::create([
                'id_entitas' => $idEntitas,
                'kode_penjualan' => self::generateSaleCode($idEntitas),
                'id_pihak' => $pembeli->id_pihak,
                'nama_pembeli' => filled($namaPembeli) ? trim($namaPembeli) : $pembeli->nama,
                'id_unit_usaha' => $idUnitUsaha,
                'id_gudang' => $idGudang,
                'tanggal_transaksi' => now()->toDateString(),
                'tgl_jatuh_tempo' => $metodeBayar === 'kredit' ? $tglJatuhTempo : null,
                'is_pembeli_anggota' => $pembeli->is_anggota,
                'ada_baris_konsinyasi' => false,
                'id_kas_bank' => $kasBank?->id_kas_bank,
                'metode_bayar' => $metodeBayar,
                'transfer_pengirim_nama' => $metodeBayar === 'transfer' ? trim($transferDetails['pengirim_nama']) : null,
                'transfer_pengirim_bank' => $metodeBayar === 'transfer' ? trim($transferDetails['pengirim_bank']) : null,
                'transfer_pengirim_rekening' => $metodeBayar === 'transfer' ? trim($transferDetails['pengirim_rekening']) : null,
                'transfer_no_referensi' => $metodeBayar === 'transfer' ? trim($transferDetails['no_referensi']) : null,
                'transfer_penerima_nama' => $metodeBayar === 'transfer' ? $kasBank->nama : null,
                'transfer_penerima_rekening' => $metodeBayar === 'transfer' ? $kasBank->no_rekening : null,
                'transfer_dikonfirmasi_pada' => $metodeBayar === 'transfer' ? now() : null,
                'transfer_dikonfirmasi_oleh' => $metodeBayar === 'transfer' ? auth()->id() : null,
                'total_bruto' => $total,
                'diskon' => 0,
                'total_bayar' => $total,
                'total_hpp' => 0,
                'status' => 'selesai',
                'status_posting' => 'F',
                'id_jurnal' => null,
                'created_by' => auth()->id(),
            ]);

            $totalHpp = 0.0;
            foreach ($itemsByBarang as $idBarang => $quantity) {
                $product = $products->get($idBarang);
                $price = (float) $product->harga_jual_standar;

                $hpp = (float) (DB::table('stok')
                    ->where('id_gudang', $idGudang)
                    ->where('id_barang', (int) $idBarang)
                    ->value('hpp_rata2') ?? 0);

                if ($hpp <= 0) {
                    $hpp = round($price * 0.7, 2);
                }

                $lineHpp = $quantity * $hpp;
                $totalHpp += $lineHpp;

                DetailPenjualan::create([
                    'id_penjualan' => $sale->id_penjualan,
                    'id_barang' => (int) $idBarang,
                    'id_stok_konsinyasi' => null,
                    'id_satuan_input' => (int) $product->id_satuan_dasar,
                    'qty_input' => $quantity,
                    'faktor_konversi' => 1,
                    'qty_dasar' => $quantity,
                    'harga_satuan' => $price,
                    'subtotal' => $quantity * $price,
                    'hpp_satuan_dasar' => $hpp,
                    'total_hpp' => $lineHpp,
                    'harga_titip_satuan' => 0,
                ]);
            }

            $sale->update(['total_hpp' => $totalHpp]);

            // Realtime Auto-Posting Jurnal Akuntansi (Opsi A)
            $kodeTransaksi = match ($metodeBayar) {
                'transfer' => 'JTFW',
                'kredit' => 'JKW',
                default => 'JTW',
            };
            $unitUsaha = DB::table('master_unit_usaha')->where('id_unit_usaha', $idUnitUsaha)->first();

            $idJurnal = null;
            $statusPosting = 'F';

            try {
                if (class_exists(JurnalService::class)) {
                    $jurnal = app(JurnalService::class)->posting(
                        $kodeTransaksi,
                        [
                            'tanggal_jurnal' => $sale->tanggal_transaksi,
                            'nomor_nota' => $sale->kode_penjualan,
                            'total_bayar' => (float) $sale->total_bayar,
                            'total_hpp' => (float) $totalHpp,
                            'id_kas_bank' => $sale->id_kas_bank,
                            'kode_unit' => $unitUsaha?->kode_unit_usaha,
                            'is_anggota' => (bool) $pembeli->is_anggota,
                            'id_pihak' => $pembeli->id_pihak,
                        ],
                        Penjualan::class,
                        (int) $sale->id_penjualan,
                        "Penjualan {$sale->kode_penjualan} ({$pembeli->nama})",
                        $idEntitas,
                    );
                    $idJurnal = $jurnal?->id_jurnal;
                    $statusPosting = $idJurnal ? 'T' : 'F';
                }
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning("Posting jurnal penjualan {$sale->kode_penjualan} dilewati: " . $e->getMessage());
                $idJurnal = null;
                $statusPosting = 'F';
            }

            $sale->update([
                'status_posting' => $statusPosting,
                'id_jurnal' => $idJurnal,
            ]);

            if ($metodeBayar === 'kredit') {
                Piutang::updateOrCreate(
                    ['sumber_tipe' => 'PENJUALAN', 'sumber_id' => $sale->id_penjualan],
                    [
                        'id_entitas' => $idEntitas,
                        'id_pihak' => $pembeli->id_pihak,
                        'kode_akun' => '1132',
                        'tanggal' => $sale->tanggal_transaksi,
                        'tgl_jatuh_tempo' => $tglJatuhTempo,
                        'nilai_awal' => $sale->total_bayar,
                        'nilai_terbayar' => 0,
                        'status' => 'belum_lunas',
                    ],
                );
            }

            return $sale->load(['pihak', 'detail.barang.satuanDasar']);
        });
    }

    public static function createReturn(
        Penjualan $sale,
        array $items,
        string $jenisPenyelesaian,
        string $alasan,
    ): ReturPenjualan {
        if ($sale->status !== 'selesai') {
            throw new RuntimeException('Retur hanya bisa dibuat untuk transaksi penjualan yang selesai.');
        }

        if (! in_array($jenisPenyelesaian, ['uang', 'potong_piutang', 'ganti_barang'], true)) {
            throw new RuntimeException('Catatan penyelesaian retur tidak valid.');
        }

        if ($items === []) {
            throw new RuntimeException('Isi jumlah minimal satu barang yang akan diretur.');
        }

        return DB::transaction(function () use ($sale, $items, $jenisPenyelesaian, $alasan) {
            $sale = Penjualan::with(['detail', 'returs.detail'])
                ->whereKey($sale->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $returnedByBarang = $sale->returs
                ->flatMap(fn ($retur) => $retur->detail)
                ->groupBy('id_barang')
                ->map(fn ($details) => (float) $details->sum('qty_dasar'));
            $requestedByBarang = collect($items)
                ->groupBy('id_barang')
                ->map(fn ($rows) => (float) $rows->sum('qty_dasar'));

            foreach ($requestedByBarang as $idBarang => $quantity) {
                $sold = (float) $sale->detail->where('id_barang', (int) $idBarang)->sum('qty_dasar');
                $returned = (float) $returnedByBarang->get($idBarang, 0);

                if ($sold <= 0 || $returned + $quantity > $sold + 0.0000001) {
                    throw new RuntimeException('Jumlah retur melebihi sisa barang pada transaksi ini.');
                }
            }

            $itemsByBarang = $sale->detail->keyBy('id_barang');
            $totalValue = 0.0;
            $totalHpp = 0.0;
            foreach ($requestedByBarang as $idBarang => $quantity) {
                $line = $itemsByBarang->get($idBarang);
                $totalValue += $quantity * (float) $line->harga_satuan;
                $totalHpp += $quantity * (float) $line->hpp_satuan_dasar;
            }

            $return = ReturPenjualan::create([
                'id_entitas' => $sale->id_entitas,
                'kode_retur' => self::generateReturnCode($sale->id_entitas),
                'id_penjualan' => $sale->id_penjualan,
                'tgl_retur' => now()->toDateString(),
                'jenis_penyelesaian' => $jenisPenyelesaian,
                'total_nilai' => $totalValue,
                'total_hpp' => $totalHpp,
                'alasan' => $alasan,
                'status' => 'diajukan',
                'status_posting' => 'F',
                'id_jurnal' => null,
            ]);

            foreach ($requestedByBarang as $idBarang => $quantity) {
                $line = $itemsByBarang->get($idBarang);
                $price = (float) $line->harga_satuan;
                $hpp = (float) $line->hpp_satuan_dasar;
                ReturPenjualanDetail::create([
                    'id_retur' => $return->id_retur,
                    'id_barang' => (int) $idBarang,
                    'qty_dasar' => $quantity,
                    'harga_satuan' => $price,
                    'nilai' => $quantity * $price,
                    'hpp_satuan' => $hpp,
                    'total_hpp' => $quantity * $hpp,
                ]);
            }

            return $return->load(['penjualan', 'detail.barang']);
        });
    }

    public static function completeReturn(ReturPenjualan $return): ReturPenjualan
    {
        if ($return->status !== 'diajukan') {
            throw new RuntimeException('Hanya retur yang diajukan yang dapat diselesaikan.');
        }

        return DB::transaction(function () use ($return) {
            $return->loadMissing(['penjualan.unitUsaha', 'penjualan.pihak']);
            $sale = $return->penjualan;

            $jurnal = null;
            if ($return->jenis_penyelesaian === 'uang') {
                $unitUsaha = $sale->unitUsaha ?? DB::table('master_unit_usaha')->where('id_unit_usaha', $sale->id_unit_usaha)->first();

                try {
                    if (class_exists(JurnalService::class)) {
                        $jurnal = app(JurnalService::class)->posting(
                            'RJU',
                            [
                                'tanggal_jurnal' => $return->tgl_retur,
                                'nomor_nota' => $return->kode_retur,
                                'total_nilai' => (float) $return->total_nilai,
                                'total_hpp' => (float) $return->total_hpp,
                                'kode_unit' => $unitUsaha?->kode_unit_usaha,
                                'id_pihak' => $sale->id_pihak,
                            ],
                            ReturPenjualan::class,
                            (int) $return->id_retur,
                            "Retur Penjualan {$return->kode_retur} untuk {$sale->kode_penjualan}",
                            $return->id_entitas,
                        );
                    }
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::warning("Posting jurnal retur penjualan {$return->kode_retur} dilewati: " . $e->getMessage());
                    $jurnal = null;
                }
            }

            $return->update([
                'status' => 'selesai',
                'status_posting' => $jurnal ? 'T' : 'F',
                'id_jurnal' => $jurnal?->id_jurnal,
            ]);

            return $return->fresh();
        });
    }

    private static function generateSaleCode(int $idEntitas): string
    {
        $period = now()->format('Ym');
        $last = Penjualan::where('id_entitas', $idEntitas)
            ->where('kode_penjualan', 'like', "JUL-{$period}-%")
            ->orderByDesc('id_penjualan')
            ->value('kode_penjualan');
        $sequence = $last && preg_match('/(\d+)$/', $last, $matches) ? (int) $matches[1] + 1 : 1;
        return sprintf('JUL-%s-%04d', $period, $sequence);
    }

    private static function generateReturnCode(int $idEntitas): string
    {
        $period = now()->format('Ym');
        $last = ReturPenjualan::where('id_entitas', $idEntitas)
            ->where('kode_retur', 'like', "RJU-{$period}-%")
            ->orderByDesc('id_retur')
            ->value('kode_retur');
        $sequence = $last && preg_match('/(\d+)$/', $last, $matches) ? (int) $matches[1] + 1 : 1;
        return sprintf('RJU-%s-%04d', $period, $sequence);
    }
}

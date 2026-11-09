<?php

namespace App\Services;

use App\Models\Gudang\KartuStok;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class StokBatchService
{
    public function pelacakanAktif(int $entitasId, int $barangId): bool
    {
        if (! Schema::hasColumn('barang_per_koperasi', 'lacak_batch')) {
            return false;
        }

        return (bool) DB::table('barang_per_koperasi')
            ->where('id_entitas', $entitasId)
            ->where('id_barang', $barangId)
            ->value('lacak_batch');
    }

    public function sediakanSaldoAwal(int $entitasId, int $barangId): void
    {
        if (! Schema::hasTable('stok_batch')) {
            throw new RuntimeException('Struktur batch belum tersedia. Migration belum dijalankan.');
        }

        $stokBarang = DB::table('stok')
            ->join('gudang', 'gudang.id_gudang', '=', 'stok.id_gudang')
            ->where('gudang.id_entitas', $entitasId)
            ->where('stok.id_barang', $barangId)
            ->select('stok.id_gudang', 'stok.qty_on_hand')
            ->lockForUpdate()
            ->get();

        foreach ($stokBarang as $stok) {
            $batchLain = DB::table('stok_batch')
                ->where('id_gudang', $stok->id_gudang)
                ->where('id_barang', $barangId)
                ->where('is_stok_awal', false)
                ->sum('qty_on_hand');
            $qtySaldoAwal = bcsub((string) $stok->qty_on_hand, (string) $batchLain, 4);

            if (bccomp($qtySaldoAwal, '0', 4) < 0) {
                throw new RuntimeException(
                    "Total batch barang {$barangId} melebihi saldo stok gudang {$stok->id_gudang}; pelacakan tidak diaktifkan."
                );
            }

            $batchAwal = DB::table('stok_batch')
                ->where('id_gudang', $stok->id_gudang)
                ->where('id_barang', $barangId)
                ->where('is_stok_awal', true)
                ->lockForUpdate()
                ->first();

            if ($batchAwal) {
                DB::table('stok_batch')
                    ->where('id_batch', $batchAwal->id_batch)
                    ->update(['qty_on_hand' => $qtySaldoAwal, 'updated_at' => now()]);
            } elseif (bccomp($qtySaldoAwal, '0', 4) > 0) {
                DB::table('stok_batch')->insert([
                    'id_gudang' => $stok->id_gudang,
                    'id_barang' => $barangId,
                    'kode_batch' => "AWAL-{$stok->id_gudang}-{$barangId}",
                    'tanggal_kedaluwarsa' => null,
                    'qty_on_hand' => $qtySaldoAwal,
                    'is_stok_awal' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function catatPenerimaan(
        int $detailId,
        int $gudangId,
        int $barangId,
        array $batches,
        ?KartuStok $kartuStok,
    ): void {
        foreach (array_values($batches) as $index => $batchInput) {
            $qtyDasar = (string) $batchInput['qty_dasar'];
            $qtyLayak = (string) $batchInput['qty_layak'];
            $qtyTidakLayak = (string) $batchInput['qty_tidak_layak'];
            $kodeSupplier = trim((string) ($batchInput['kode_batch'] ?? ''));
            $kodeBatch = $kodeSupplier !== ''
                ? $kodeSupplier
                : "INT-{$detailId}-".($index + 1);
            $batchId = DB::table('stok_batch')->insertGetId([
                'id_gudang' => $gudangId,
                'id_barang' => $barangId,
                'kode_batch' => $kodeBatch,
                'tanggal_kedaluwarsa' => $batchInput['tanggal_kedaluwarsa'] ?: null,
                'qty_on_hand' => $qtyLayak,
                'is_stok_awal' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('penerimaan_batch_detail')->insert([
                'id_detail' => $detailId,
                'id_batch' => $batchId,
                'qty_dasar' => $qtyDasar,
                'qty_layak' => $qtyLayak,
                'qty_tidak_layak' => $qtyTidakLayak,
                'alasan_tidak_layak' => $batchInput['alasan_tidak_layak'] ?: null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            if ($kartuStok !== null && bccomp($qtyLayak, '0', 4) > 0) {
                DB::table('kartu_stok_batch')->insert([
                    'id_kartu' => $kartuStok->id_kartu,
                    'id_batch' => $batchId,
                    'qty_masuk' => $qtyLayak,
                    'qty_keluar' => '0',
                    'saldo_batch' => $qtyLayak,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function catatPenerimaanTanpaRincian(
        int $gudangId,
        int $barangId,
        string $qty,
        int $idKartu,
        int $refId,
    ): void {
        $batchId = DB::table('stok_batch')->insertGetId([
            'id_gudang' => $gudangId,
            'id_barang' => $barangId,
            'kode_batch' => "TIDAK-DIKETAHUI-RETUR-{$refId}",
            'tanggal_kedaluwarsa' => null,
            'qty_on_hand' => $qty,
            'is_stok_awal' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('kartu_stok_batch')->insert([
            'id_kartu' => $idKartu,
            'id_batch' => $batchId,
            'qty_masuk' => $qty,
            'qty_keluar' => '0',
            'saldo_batch' => $qty,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * @return array<int, array{id_batch: int, qty: string}>
     */
    public function alokasiKeluar(
        int $gudangId,
        int $barangId,
        string $qty,
        ?int $idBatch = null,
        bool $izinkanBatchKedaluwarsa = false,
    ): array {
        $query = DB::table('stok_batch')
            ->where('id_gudang', $gudangId)
            ->where('id_barang', $barangId)
            ->where('qty_on_hand', '>', 0)
            ->lockForUpdate();

        if ($idBatch !== null) {
            $query->where('id_batch', $idBatch);
        } else {
            $query->orderByRaw('tanggal_kedaluwarsa IS NULL')
                ->orderBy('tanggal_kedaluwarsa')
                ->orderBy('id_batch');
        }

        $batches = $query->get();
        $sisa = $qty;
        $alokasi = [];
        $qtyKedaluwarsa = '0';

        foreach ($batches as $batch) {
            $sudahKedaluwarsa = $batch->tanggal_kedaluwarsa !== null
                && $batch->tanggal_kedaluwarsa < now()->toDateString();

            if ($sudahKedaluwarsa && ! $izinkanBatchKedaluwarsa) {
                $qtyKedaluwarsa = bcadd($qtyKedaluwarsa, (string) $batch->qty_on_hand, 4);
                continue;
            }

            $qtyDiambil = bccomp((string) $batch->qty_on_hand, $sisa, 4) < 0
                ? (string) $batch->qty_on_hand
                : $sisa;

            if (bccomp($qtyDiambil, '0', 4) > 0) {
                $alokasi[] = [
                    'id_batch' => (int) $batch->id_batch,
                    'qty' => $qtyDiambil,
                ];
                $sisa = bcsub($sisa, $qtyDiambil, 4);
            }

            if (bccomp($sisa, '0', 4) <= 0) {
                break;
            }
        }

        if (bccomp($sisa, '0', 4) > 0) {
            if ($idBatch !== null && $batches->isEmpty()) {
                throw new RuntimeException('Batch tidak ditemukan untuk barang dan gudang yang dipilih.');
            }

            $pesan = bccomp($qtyKedaluwarsa, '0', 4) > 0
                ? 'Jumlah batch yang belum kedaluwarsa tidak mencukupi. Batch kedaluwarsa harus dicatat melalui Kerusakan.'
                : 'Stok batch tidak mencukupi untuk transaksi ini.';
            throw new RuntimeException($pesan);
        }

        return $alokasi;
    }

    /**
     * @param array<int, array{id_batch: int, qty: string}> $alokasi
     */
    public function catatPengeluaran(KartuStok $kartuStok, array $alokasi): void
    {
        foreach ($alokasi as $item) {
            $batch = DB::table('stok_batch')
                ->where('id_batch', $item['id_batch'])
                ->lockForUpdate()
                ->first();

            if (! $batch || bccomp((string) $batch->qty_on_hand, $item['qty'], 4) < 0) {
                throw new RuntimeException('Saldo batch berubah saat transaksi diproses. Muat ulang dan coba kembali.');
            }

            $saldoBaru = bcsub((string) $batch->qty_on_hand, $item['qty'], 4);
            DB::table('stok_batch')
                ->where('id_batch', $item['id_batch'])
                ->update([
                    'qty_on_hand' => $saldoBaru,
                    'updated_at' => now(),
                ]);

            DB::table('kartu_stok_batch')->insert([
                'id_kartu' => $kartuStok->id_kartu,
                'id_batch' => $item['id_batch'],
                'qty_masuk' => '0',
                'qty_keluar' => $item['qty'],
                'saldo_batch' => $saldoBaru,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function catatPenyesuaianOpname(
        int $detailId,
        int $gudangId,
        int $barangId,
        string $qtySistem,
        string $qtyFisik,
        ?int $idKartu,
    ): void {
        $entitasId = (int) DB::table('gudang')
            ->where('id_gudang', $gudangId)
            ->value('id_entitas');

        if (! $this->pelacakanAktif($entitasId, $barangId) || $idKartu === null) {
            return;
        }

        $selisih = bcsub($qtyFisik, $qtySistem, 4);
        if (bccomp($selisih, '0', 4) === 0) {
            return;
        }

        if (bccomp($selisih, '0', 4) > 0) {
            $batchId = DB::table('stok_batch')->insertGetId([
                'id_gudang' => $gudangId,
                'id_barang' => $barangId,
                'kode_batch' => "OPN-{$detailId}",
                'tanggal_kedaluwarsa' => null,
                'qty_on_hand' => $selisih,
                'is_stok_awal' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('opname_batch_detail')->insert([
                'id_detail' => $detailId,
                'id_batch' => $batchId,
                'qty_sistem' => '0',
                'qty_fisik' => $selisih,
                'selisih' => $selisih,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            DB::table('kartu_stok_batch')->insert([
                'id_kartu' => $idKartu,
                'id_batch' => $batchId,
                'qty_masuk' => $selisih,
                'qty_keluar' => '0',
                'saldo_batch' => $selisih,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return;
        }

        $qtyKurang = bcsub('0', $selisih, 4);
        $alokasi = $this->alokasiKeluar(
            gudangId: $gudangId,
            barangId: $barangId,
            qty: $qtyKurang,
            izinkanBatchKedaluwarsa: true,
        );

        foreach ($alokasi as $item) {
            $batch = DB::table('stok_batch')
                ->where('id_batch', $item['id_batch'])
                ->lockForUpdate()
                ->first();
            $saldoBaru = bcsub((string) $batch->qty_on_hand, $item['qty'], 4);

            DB::table('opname_batch_detail')->insert([
                'id_detail' => $detailId,
                'id_batch' => $item['id_batch'],
                'qty_sistem' => $batch->qty_on_hand,
                'qty_fisik' => $saldoBaru,
                'selisih' => bcsub($saldoBaru, (string) $batch->qty_on_hand, 4),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $kartuStok = KartuStok::query()->findOrFail($idKartu);
        $this->catatPengeluaran($kartuStok, $alokasi);
    }

    public function jumlahBatchMendekatiKedaluwarsa(?int $entitasId = null): int
    {
        if (! Schema::hasTable('stok_batch')) {
            return 0;
        }

        return DB::table('stok_batch')
            ->join('gudang', 'gudang.id_gudang', '=', 'stok_batch.id_gudang')
            ->where('stok_batch.qty_on_hand', '>', 0)
            ->whereBetween('stok_batch.tanggal_kedaluwarsa', [
                now()->toDateString(),
                now()->addDays(30)->toDateString(),
            ])
            ->when(
                $entitasId !== null,
                fn ($query) => $query->where('gudang.id_entitas', $entitasId),
            )
            ->count();
    }
}

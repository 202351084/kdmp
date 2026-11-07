<?php

namespace App\Services;

use App\Models\Pembelian\Pembelian;
use App\Models\Pembelian\DetailPembelian;
use App\Models\Pembelian\ReturPembelian;
use App\Models\Pembelian\ReturPembelianDetail;
use App\Models\Perencanaan\PermintaanPengadaan;
use App\Models\Master\Barang;
use App\Models\Master\KasBank;
use App\Models\Keuangan\Hutang;
use App\Services\Finance\JurnalService;
use Illuminate\Support\Facades\DB;

class PembelianService
{
    /**
     * Create Purchase Order (PO) from approved PR.
     *
     * @param PermintaanPengadaan $pr Approved PR
     * @param int $idPihak Supplier ID
     * @param string $jenisPembayaran 'tunai', 'transfer', or 'kredit'
     * @param string|null $tglJatuhTempo For kredit
     * @return Pembelian
     * @throws \Exception
     */
    public static function createFromPR(
        PermintaanPengadaan $pr,
        int $idPihak,
        string $jenisPembayaran,
        ?string $tglJatuhTempo = null,
        ?int $idKasBank = null,
    ): Pembelian {
        if ($pr->status !== 'disetujui') {
            throw new \Exception('PR harus berstatus "disetujui" sebelum dibuat PO.');
        }

        if (!$pr->id_unit_usaha || !$pr->id_gudang) {
            throw new \Exception('PR harus memiliki unit usaha dan gudang tujuan.');
        }

        if ($jenisPembayaran === 'kredit' && !$tglJatuhTempo) {
            throw new \Exception('Jatuh tempo harus diisi untuk pembayaran kredit.');
        }

        DB::beginTransaction();

        try {
            $kodePembelian = self::generatePurchaseCode(
                $pr->id_entitas,
                $pr->tgl_pengajuan->toDateString(),
            );

            $totalPembelian = 0;

            foreach ($pr->detail as $item) {
                $totalPembelian += $item->subtotal ?? 0;
            }

            $pembelian = Pembelian::create([
                'id_entitas' => $pr->id_entitas,
                'kode_pembelian' => $kodePembelian,
                'id_permintaan' => $pr->id_permintaan,
                'id_pihak' => $idPihak,
                'id_unit_usaha' => $pr->id_unit_usaha,
                'id_gudang' => $pr->id_gudang,
                'tanggal_transaksi' => now()->toDateString(),
                'jenis_pembayaran' => $jenisPembayaran,
                'id_kas_bank' => $idKasBank,
                'tgl_jatuh_tempo' => $tglJatuhTempo,
                'total_pembelian' => $totalPembelian,
                'status' => 'draft',
                'status_posting' => 'F',
                'created_by' => auth()->id(),
            ]);

            foreach ($pr->detail as $prDetail) {
                $faktorKonversi = self::conversionFactor(
                    (int) $prDetail->id_barang,
                    (int) $prDetail->barang->id_satuan_dasar,
                );

                DetailPembelian::create([
                    'id_pembelian' => $pembelian->id_pembelian,
                    'id_barang' => $prDetail->id_barang,
                    'id_satuan_input' => $prDetail->barang->id_satuan_dasar,
                    'qty_input' => $prDetail->jumlah_diminta,
                    'faktor_konversi' => $faktorKonversi,
                    'qty_dasar' => $prDetail->jumlah_diminta * $faktorKonversi,
                    'harga_satuan_input' => $prDetail->harga_perkiraan ?? 0,
                    'subtotal' => $prDetail->subtotal ?? 0,
                ]);
            }

            DB::commit();

            return $pembelian;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Create quick purchase.
     *
    * Payment terms are recorded locally without Finance posting.
     *
     * @param int $idEntitas
     * @param int $idPihak Farmer ID
     * @param int $idUnitUsaha
     * @param int $idGudang
     * @param array $items
    * @param string $jenisPembayaran 'tunai', 'transfer', or 'kredit'
    * @param string|null $tglJatuhTempo
     * @return Pembelian
     * @throws \Exception
     */
    public static function createQuickPurchase(
        int $idEntitas,
        int $idPihak,
        int $idUnitUsaha,
        int $idGudang,
        array $items,
        string $jenisPembayaran,
        ?string $tglJatuhTempo = null,
        ?int $idKasBank = null,
        string $sumberPembelian = 'warga_lokal',
    ): Pembelian {
        if (! in_array($sumberPembelian, ['desa_lain', 'warga_lokal', 'pengambilan_konsinyasi'], true)) {
            throw new \Exception('Sumber pembelian tidak valid.');
        }

        if (!in_array($jenisPembayaran, ['tunai', 'transfer', 'kredit'], true)) {
            throw new \Exception('Jenis pembayaran pembelian tidak valid.');
        }

        if ($sumberPembelian === 'pengambilan_konsinyasi' && $jenisPembayaran !== 'kredit') {
            throw new \Exception('Pengambilan konsinyasi dicatat sebagai hutang, tanpa pembayaran kas atau bank.');
        }

        if ($sumberPembelian === 'pengambilan_konsinyasi' && $idKasBank !== null) {
            throw new \Exception('Pengambilan konsinyasi tidak menggunakan kas atau rekening bank.');
        }

        if ($jenisPembayaran === 'kredit'
            && (! $tglJatuhTempo || \Carbon\Carbon::parse($tglJatuhTempo)->startOfDay()->lt(today()))) {
            throw new \Exception('Tanggal jatuh tempo wajib diisi dan tidak boleh sebelum hari ini untuk pembayaran kredit.');
        }

        if (empty($items)) {
            throw new \Exception('Minimal satu item harus ditambahkan.');
        }

        if ($sumberPembelian === 'pengambilan_konsinyasi'
            && collect($items)->contains(fn (array $item) => (float) ($item['harga_satuan'] ?? 0) <= 0)) {
            throw new \Exception('Harga yang ditetapkan desa pengirim harus lebih besar dari nol.');
        }

        DB::beginTransaction();

        try {
            $kodePembelian = self::generatePurchaseCode(
                $idEntitas,
                now()->toDateString(),
            );

            $totalPembelian = 0;

            foreach ($items as $item) {
                $totalPembelian +=
                    ($item['qty_dasar'] ?? 0)
                    * ($item['harga_satuan'] ?? 0);
            }

            $pembelian = Pembelian::create([
                'id_entitas' => $idEntitas,
                'kode_pembelian' => $kodePembelian,
                'id_permintaan' => null,
                'sumber_pembelian' => $sumberPembelian,
                'id_pihak' => $idPihak,
                'id_unit_usaha' => $idUnitUsaha,
                'id_gudang' => $idGudang,
                'tanggal_transaksi' => now()->toDateString(),
                'jenis_pembayaran' => $jenisPembayaran,
                'id_kas_bank' => $idKasBank,
                'tgl_jatuh_tempo' => $tglJatuhTempo,
                'total_pembelian' => $totalPembelian,
                'status' => 'draft',
                'status_posting' => 'F',
                'created_by' => auth()->id(),
            ]);

            foreach ($items as $item) {
                $faktorKonversi = self::kilogramConversionFactor(
                    (int) $item['id_barang'],
                    (int) $item['id_satuan'],
                );

                DetailPembelian::create([
                    'id_pembelian' => $pembelian->id_pembelian,
                    'id_barang' => $item['id_barang'],
                    'id_satuan_input' => $item['id_satuan'],
                    'qty_input' => $item['qty_dasar'],
                    'faktor_konversi' => $faktorKonversi,
                    'qty_dasar' => $item['qty_dasar'] * $faktorKonversi,
                    'harga_satuan_input' => $item['harga_satuan'],
                    'subtotal' =>
                        $item['qty_dasar'] * $item['harga_satuan'],
                ]);
            }

            DB::commit();

            return $pembelian;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /** Approve a purchase and automatically post to journal and hutang (Option A). */
    public static function approvePembelian(Pembelian $pembelian): Pembelian
    {
        if ($pembelian->status !== 'draft') {
            throw new \Exception('Hanya pembelian draft yang bisa disetujui.');
        }

        DB::beginTransaction();

        try {
            $pembelian->loadMissing(['unitUsaha', 'pihak']);

            $isConsignmentIntake = $pembelian->sumber_pembelian === 'pengambilan_konsinyasi';
            $isPetani = $pembelian->pihak?->jenis_pihak === 'petani';
            $transactionCode = $isConsignmentIntake ? 'KPN' : match (true) {
                $isPetani && $pembelian->jenis_pembayaran === 'tunai' => 'BPT',
                $pembelian->jenis_pembayaran === 'tunai' => 'BTU',
                $pembelian->jenis_pembayaran === 'transfer' => 'BTF',
                $pembelian->jenis_pembayaran === 'kredit' => 'BKR',
                default => throw new \Exception('Jenis pembayaran pembelian tidak valid.'),
            };

            $idKasBank = $isConsignmentIntake ? null : ($pembelian->id_kas_bank
                ?? DB::table('master_kas_bank')
                    ->where('id_entitas', $pembelian->id_entitas)
                    ->where('jenis', $pembelian->jenis_pembayaran === 'tunai' ? 'kas' : 'bank')
                    ->where('is_active', true)
                    ->value('id_kas_bank')
                ?? DB::table('master_kas_bank')->where('is_active', true)->value('id_kas_bank'));

            $idJurnal = null;
            $statusPosting = 'F';

            try {
                if ($isConsignmentIntake && ! class_exists(JurnalService::class)) {
                    throw new \RuntimeException('Layanan jurnal tidak tersedia untuk mem-posting hutang konsinyasi.');
                }

                if (class_exists(JurnalService::class)) {
                    $jurnal = app(JurnalService::class)->posting(
                        $transactionCode,
                        [
                            'tanggal_jurnal' => $pembelian->tanggal_transaksi,
                            'nomor_nota' => $pembelian->kode_pembelian,
                            'total_pembelian' => (float) $pembelian->total_pembelian,
                            'id_kas_bank' => $idKasBank,
                            'kode_unit' => $pembelian->unitUsaha?->kode_unit_usaha,
                            'id_pihak' => $pembelian->id_pihak,
                        ],
                        Pembelian::class,
                        (int) $pembelian->id_pembelian,
                        "Pembelian {$pembelian->kode_pembelian} (" . ($pembelian->pihak?->nama ?? 'Supplier') . ")",
                        $pembelian->id_entitas,
                    );
                    $idJurnal = $jurnal?->id_jurnal;
                    $statusPosting = $idJurnal ? 'T' : 'F';
                }
            } catch (\Throwable $e) {
                if ($isConsignmentIntake) {
                    throw $e;
                }

                $idJurnal = null;
                $statusPosting = 'F';
            }

            $pembelian->update([
                'status' => 'disetujui',
                'status_posting' => $statusPosting,
                'id_jurnal' => $idJurnal,
                'id_kas_bank' => $isConsignmentIntake ? null : $idKasBank,
            ]);

            if ($pembelian->jenis_pembayaran === 'kredit') {
                Hutang::updateOrCreate(
                    ['sumber_tipe' => 'PEMBELIAN', 'sumber_id' => $pembelian->id_pembelian],
                    [
                        'id_entitas' => $pembelian->id_entitas,
                        'id_pihak' => $pembelian->id_pihak,
                        'kode_akun' => $isConsignmentIntake ? '2117' : '2111',
                        'tanggal' => $pembelian->tanggal_transaksi,
                        'tgl_jatuh_tempo' => $pembelian->tgl_jatuh_tempo,
                        'nilai_awal' => $pembelian->total_pembelian,
                        'nilai_terbayar' => 0,
                        'status' => 'belum_lunas',
                    ],
                );
            }

            DB::commit();

            return $pembelian->fresh();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public static function updatePurchase(Pembelian $pembelian, array $data): Pembelian
    {
        if ($pembelian->status !== 'draft') {
            throw new \Exception('Hanya pembelian draft yang dapat diubah.');
        }

        if ($pembelian->sumber_pembelian === 'pengambilan_konsinyasi'
            && ($data['jenis_pembayaran'] !== 'kredit'
                || ! empty($data['id_kas_bank'])
                || collect($data['items'])->contains(fn (array $item) => (float) ($item['harga_satuan'] ?? 0) <= 0))) {
            throw new \Exception('Pengambilan konsinyasi harus dicatat sebagai hutang tanpa kas/bank, dengan harga satuan dari desa pengirim.');
        }

        DB::beginTransaction();

        try {
            $totalPembelian = 0;
            foreach ($data['items'] as $item) {
                $totalPembelian += (float) ($item['qty_dasar'] ?? 0) * (float) ($item['harga_satuan'] ?? 0);
            }

            $updateData = [
                'id_pihak' => $data['id_pihak'],
                'jenis_pembayaran' => $data['jenis_pembayaran'],
                'id_kas_bank' => in_array($data['jenis_pembayaran'], ['tunai', 'transfer'], true)
                    ? ($data['id_kas_bank'] ?? $pembelian->id_kas_bank)
                    : null,
                'tgl_jatuh_tempo' => $data['jenis_pembayaran'] === 'kredit'
                    ? ($data['tgl_jatuh_tempo'] ?? null)
                    : null,
                'total_pembelian' => $totalPembelian,
            ];

            if (!empty($data['id_unit_usaha'])) {
                $updateData['id_unit_usaha'] = $data['id_unit_usaha'];
            }
            if (!empty($data['id_gudang'])) {
                $updateData['id_gudang'] = $data['id_gudang'];
            }

            $pembelian->update($updateData);

            // Recreate detail pembelian
            $pembelian->detail()->delete();

            foreach ($data['items'] as $item) {
                $faktorKonversi = self::kilogramConversionFactor(
                    (int) $item['id_barang'],
                    (int) $item['id_satuan'],
                );

                DetailPembelian::create([
                    'id_pembelian' => $pembelian->id_pembelian,
                    'id_barang' => $item['id_barang'],
                    'id_satuan_input' => $item['id_satuan'],
                    'qty_input' => $item['qty_dasar'],
                    'faktor_konversi' => $faktorKonversi,
                    'qty_dasar' => (float) $item['qty_dasar'] * $faktorKonversi,
                    'harga_satuan_input' => $item['harga_satuan'],
                    'subtotal' => (float) $item['qty_dasar'] * (float) $item['harga_satuan'],
                ]);
            }

            DB::commit();

            return $pembelian->fresh(['detail.barang', 'pihak', 'unitUsaha', 'gudang', 'kasBank']);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public static function cancelPurchase(Pembelian $pembelian): Pembelian
    {
        if (!in_array($pembelian->status, ['draft', 'disetujui'], true)) {
            throw new \Exception('Hanya pembelian draft atau disetujui yang bisa dibatalkan.');
        }

        if ($pembelian->returs()->exists()) {
            throw new \Exception('Pembelian yang sudah memiliki catatan retur tidak bisa dibatalkan.');
        }

        DB::beginTransaction();

        try {
            $pembelian->update(['status' => 'dibatalkan']);

            if ($pembelian->id_jurnal) {
                try {
                    app(JurnalService::class)->balik((int) $pembelian->id_jurnal, "Pembatalan pembelian {$pembelian->kode_pembelian}");
                } catch (\Exception $ignored) {}
            }

            if ($pembelian->jenis_pembayaran === 'kredit') {
                Hutang::where('sumber_tipe', 'PEMBELIAN')
                    ->where('sumber_id', $pembelian->id_pembelian)
                    ->delete();
            }

            DB::commit();

            return $pembelian->fresh();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    private static function conversionFactor(int $idBarang, int $idSatuan): float
    {
        $factor = DB::table('konversi_satuan')
            ->where('id_barang', $idBarang)
            ->where('id_satuan', $idSatuan)
            ->value('faktor_ke_dasar');

        if ($factor === null || (float) $factor <= 0) {
            $idSatuanDasar = DB::table('master_barang')
                ->where('id_barang', $idBarang)
                ->value('id_satuan_dasar');

            if ((int) $idSatuanDasar === $idSatuan) {
                return 1.0;
            }

            throw new \Exception('Konversi satuan untuk barang dan satuan yang dipilih tidak ditemukan. Pilih satuan yang tersedia untuk barang tersebut.');
        }

        return (float) $factor;
    }

    private static function kilogramConversionFactor(int $idBarang, int $idSatuan): float
    {
        $isKilogram = DB::table('satuan')
            ->where('id', $idSatuan)
            ->whereRaw('UPPER(kode_satuan) = ?', ['KG'])
            ->exists();

        if (! $isKilogram) {
            throw new \Exception('Satuan pembelian harus KG.');
        }

        return self::conversionFactor($idBarang, $idSatuan);
    }

    /**
     * Create purchase return.
     *
     * @param Pembelian $pembelian
     * @param array $items
     * @param string $jenisPenyelesaian
     * @param string $alasan
     * @return ReturPembelian
     * @throws \Exception
     */
    public static function createRetur(
        Pembelian $pembelian,
        array $items,
        string $jenisPenyelesaian,
        string $alasan,
    ): ReturPembelian {
        if (!in_array($pembelian->status, [
            'disetujui',
            'diterima',
            'selesai',
        ], true)) {
            throw new \Exception(
                'Hanya pembelian yang sudah disetujui atau diterima bisa di-retur.'
            );
        }

        if (empty($items)) {
            throw new \Exception('Minimal satu item harus di-retur.');
        }

        if (!in_array($jenisPenyelesaian, [
            'uang',
            'potong_hutang',
            'ganti_barang',
        ], true)) {
            throw new \Exception('Jenis penyelesaian tidak valid.');
        }

        DB::beginTransaction();

        try {
            $pembelian = Pembelian::with(['detail', 'returs.detail'])
                ->whereKey($pembelian->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $idSatuanKg = DB::table('satuan')
                ->whereRaw('UPPER(kode_satuan) = ?', ['KG'])
                ->value('id');

            if (! $idSatuanKg) {
                throw new \Exception('Master satuan KG belum tersedia.');
            }

            $items = array_map(function (array $item) use ($idSatuanKg) {
                $factor = self::conversionFactor(
                    (int) $item['id_barang'],
                    (int) $idSatuanKg,
                );
                $item['qty_dasar'] = (float) $item['qty_dasar'] * $factor;

                return $item;
            }, $items);

            $returnedByBarang = $pembelian->returs
                ->flatMap(fn ($retur) => $retur->detail)
                ->groupBy('id_barang')
                ->map(fn ($details) => (float) $details->sum('qty_dasar'));
            $requestedByBarang = collect($items)
                ->groupBy('id_barang')
                ->map(fn ($rows) => (float) $rows->sum('qty_dasar'));

            foreach ($requestedByBarang as $idBarang => $qtyDiminta) {
                $qtyDibeli = (float) $pembelian->detail
                    ->where('id_barang', (int) $idBarang)
                    ->sum('qty_dasar');
                $qtySudahDiretur = (float) $returnedByBarang->get($idBarang, 0);

                if ($qtyDibeli <= 0) {
                    throw new \Exception("Barang {$idBarang} tidak ditemukan pada pembelian.");
                }

                if ($qtySudahDiretur + $qtyDiminta > $qtyDibeli + 0.0000001) {
                    throw new \Exception(
                        "Jumlah retur barang {$idBarang} melebihi sisa kuantitas pembelian."
                    );
                }
            }

            $kodeRetur = self::generateReturnCode(
                $pembelian->id_entitas,
                now()->toDateString(),
            );

            $totalNilai = 0;
            $itemsWithHpp = [];

            foreach ($items as $item) {
                $detailPembelian = $pembelian->detail
                    ->where('id_barang', (int) $item['id_barang']);

                if ($detailPembelian->isEmpty()) {
                    throw new \Exception(
                        "Barang {$item['id_barang']} tidak ditemukan pada pembelian."
                    );
                }

                $qtyRetur = (float) $item['qty_dasar'];
                $qtyDibeli = (float) $detailPembelian->sum('qty_dasar');
                $nilaiBeli = $detailPembelian->sum(function ($detail) {
                    $faktorKonversi = (float) $detail->faktor_konversi;
                    $hargaSatuanDasar = $faktorKonversi > 0
                        ? (float) $detail->harga_satuan_input / $faktorKonversi
                        : (float) $detail->harga_satuan_input;

                    return (float) $detail->qty_dasar * $hargaSatuanDasar;
                });
                $hargaBeliDasar = $qtyDibeli > 0 ? $nilaiBeli / $qtyDibeli : 0;

                $item['hpp_rata2'] = $hargaBeliDasar;
                $itemsWithHpp[] = $item;
                $totalNilai +=
                    $qtyRetur * $hargaBeliDasar;
            }

            $retur = ReturPembelian::create([
                'id_entitas' => $pembelian->id_entitas,
                'kode_retur' => $kodeRetur,
                'id_pembelian' => $pembelian->id_pembelian,
                'tgl_retur' => now()->toDateString(),
                'jenis_penyelesaian' => $jenisPenyelesaian,
                'total_nilai' => $totalNilai,
                'alasan' => $alasan,
                'status' => 'diajukan',
                'status_posting' => 'F',
            ]);

            foreach ($itemsWithHpp as $item) {
                ReturPembelianDetail::create([
                    'id_retur' => $retur->id_retur,
                    'id_barang' => $item['id_barang'],
                    'qty_dasar' => $item['qty_dasar'],
                    'hpp_rata2' => $item['hpp_rata2'],
                    'nilai' =>
                        $item['qty_dasar'] * $item['hpp_rata2'],
                ]);
            }

            DB::commit();

            return $retur;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /** Mark a return complete and post relevant journals & hutang reduction (Option A). */
    public static function approveRetur(
        ReturPembelian $retur,
        ?int $idKasBank = null,
    ): ReturPembelian {
        if ($retur->status !== 'diajukan') {
            throw new \Exception('Hanya retur yang diajukan yang dapat diproses.');
        }

        DB::beginTransaction();

        try {
            $retur->loadMissing(['pembelian.unitUsaha', 'pembelian.pihak']);
            $pembelian = $retur->pembelian;

            if (!$pembelian) {
                throw new \Exception('Pembelian untuk retur tidak ditemukan.');
            }

            $isConsignmentIntake = $pembelian->sumber_pembelian === 'pengambilan_konsinyasi';
            $transactionCode = match ($retur->jenis_penyelesaian) {
                'uang' => 'RBU',
                'potong_hutang' => $isConsignmentIntake ? 'RBK' : 'RBH',
                'ganti_barang' => null,
                default => throw new \Exception('Jenis penyelesaian tidak dikenal.'),
            };

            $jurnal = null;

            if ($retur->jenis_penyelesaian === 'ganti_barang') {
                foreach ($retur->detail as $detail) {
                    StokService::keluarBarang(
                        $pembelian->id_entitas,
                        $pembelian->id_gudang,
                        $detail->id_barang,
                        (float) $detail->qty_dasar,
                        (int) $retur->id_retur,
                    );
                    StokService::masukBarang(
                        $pembelian->id_entitas,
                        $pembelian->id_gudang,
                        $detail->id_barang,
                        (float) $detail->qty_dasar,
                        (float) $detail->hpp_rata2,
                        (int) $retur->id_retur,
                        'RETUR_PEMBELIAN_GANTI',
                    );
                }
            } elseif ($transactionCode) {
                $kasBankId = $idKasBank;
                if ($retur->jenis_penyelesaian === 'uang' && !$kasBankId) {
                    $kasBankId = KasBank::where('id_entitas', $pembelian->id_entitas)
                        ->where('is_active', true)
                        ->value('id_kas_bank');
                }

                if ($kasBankId || $retur->jenis_penyelesaian === 'potong_hutang') {
                    $payload = [
                        'tanggal_jurnal' => $retur->tgl_retur,
                        'nomor_nota' => $retur->kode_retur,
                        'total_nilai' => (float) $retur->total_nilai,
                        'id_kas_bank' => $kasBankId,
                        'kode_unit' => $pembelian->unitUsaha?->kode_unit_usaha,
                        'id_pihak' => $pembelian->id_pihak,
                    ];

                    $jurnal = app(JurnalService::class)->posting(
                        $transactionCode,
                        $payload,
                        ReturPembelian::class,
                        (int) $retur->id_retur,
                        "Retur {$retur->jenis_penyelesaian} untuk {$pembelian->kode_pembelian}",
                        $pembelian->id_entitas,
                    );
                }
            }

            $retur->update([
                'status' => 'selesai',
                'status_posting' => $jurnal ? 'T' : 'F',
                'id_jurnal' => $jurnal?->id_jurnal,
            ]);

            if ($retur->jenis_penyelesaian === 'potong_hutang') {
                $hutang = Hutang::where('sumber_tipe', 'PEMBELIAN')
                    ->where('sumber_id', $pembelian->id_pembelian)
                    ->first();

                if ($hutang) {
                    $nilaiAwal = max(0, (float) $hutang->nilai_awal - (float) $retur->total_nilai);
                    $terbayar = (float) $hutang->nilai_terbayar;
                    $hutang->update([
                        'nilai_awal' => $nilaiAwal,
                        'status' => $terbayar >= $nilaiAwal
                            ? 'lunas'
                            : ($terbayar > 0 ? 'sebagian' : 'belum_lunas'),
                    ]);
                }
            }

            DB::commit();

            return $retur->fresh();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    private static function generatePurchaseCode(
        int $idEntitas,
        string $tanggal
    ): string {
        $tahun = date('Y', strtotime($tanggal));
        $bulan = date('m', strtotime($tanggal));

        $last = Pembelian::where('id_entitas', $idEntitas)
            ->whereYear('created_at', $tahun)
            ->whereMonth('created_at', $bulan)
            ->orderByDesc('id_pembelian')
            ->first();

        $seq = 1;

        if (
            $last
            && preg_match(
                '/(\d+)$/',
                $last->kode_pembelian,
                $matches
            )
        ) {
            $seq = (int) $matches[1] + 1;
        }

        return sprintf(
            'PO/%04d%02d/%04d',
            $tahun,
            $bulan,
            $seq
        );
    }

    private static function generateReturnCode(
        int $idEntitas,
        string $tanggal
    ): string {
        $tahun = date('Y', strtotime($tanggal));
        $bulan = date('m', strtotime($tanggal));

        $last = ReturPembelian::where('id_entitas', $idEntitas)
            ->whereYear('created_at', $tahun)
            ->whereMonth('created_at', $bulan)
            ->orderByDesc('id_retur')
            ->first();

        $seq = 1;

        if (
            $last
            && preg_match(
                '/(\d+)$/',
                $last->kode_retur,
                $matches
            )
        ) {
            $seq = (int) $matches[1] + 1;
        }

        return sprintf(
            'RET/%04d%02d/%04d',
            $tahun,
            $bulan,
            $seq
        );
    }
}

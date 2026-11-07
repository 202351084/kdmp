<?php

namespace App\Services\Finance;

use App\Exceptions\Finance\AkunTidakDitemukanException;
use App\Exceptions\Finance\JurnalSudahDibalikException;
use App\Exceptions\Finance\JurnalTidakBalanceException;
use App\Exceptions\Finance\PeriodeTutupException;
use App\Models\Akuntansi\JurnalDetail;
use App\Models\Akuntansi\JurnalHeader;
use App\Models\Akuntansi\MasterDetailTransaksi;
use App\Models\Master\KasBank;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * JurnalService Ã¢â‚¬â€ Satu-satunya pintu resmi untuk menulis ke jurnal_header,
 * jurnal_detail, dan buku_besar_periode.
 *
 * ATURAN: Tidak ada kode lain yang boleh INSERT langsung ke tabel-tabel
 * tersebut. Semua harus lewat service ini.
 *
 * Tiga cara jurnal bisa dibuat:
 *   1. posting()        Ã¢â‚¬â€ dari template (JTW, BTU, TPI, dll)
 *   2. postingManual()  Ã¢â‚¬â€ baris bebas (KSM, KSK, MTK, penyesuaian)
 *   3. balik()          Ã¢â‚¬â€ membuat jurnal pembalik dari jurnal yang sudah POSTED
 *
 * Satu method tambahan untuk maintenance:
 *   4. bangunBukuBesar() Ã¢â‚¬â€ rebuild ringkasan saldo dari jurnal (idempoten)
 *
 * ATURAN TRANSAKSI (revisi 2026-10-11):
 *   Stored procedure TIDAK lagi membuka/menutup transaksi sendiri. Setiap
 *   method penulis di service ini membungkus pekerjaannya dengan DB::transaction():
 *     - Pemanggil SUDAH punya transaksi  -> service ikut transaksi itu
 *       (SAVEPOINT). Data pemanggil + jurnal = satu kesatuan.
 *     - Pemanggil BELUM punya transaksi -> service membuat transaksi sendiri,
 *       sehingga jurnal tetap atomik (header, detail, buku besar, nomor).
 *   Jika pemanggil juga menulis data lain (kas, stok, pelunasan), bungkus
 *   semuanya dalam satu DB::transaction agar ikut batal bersama jurnal.
 */
class JurnalService
{
    // =========================================================================
    // METHOD 1: posting() Ã¢â‚¬â€ Jurnal dari Template
    // =========================================================================

    /**
     * Posting jurnal otomatis berdasarkan template master_detail_transaksi.
     *
     * Alur kerja:
     *   1. Ambil template baris dari master_detail_transaksi
     *   2. Resolve setiap akun dinamis (KAS_BANK, PERSEDIAAN_UNIT, dll)
     *   3. Hitung nilai tiap baris dari $payload
     *   4. Validasi: total Debit harus = total Kredit
     *   5. Validasi: periode akuntansi harus masih OPEN
     *   6. Generate nomor jurnal
     *   7. CALL sp_post_jurnal (MySQL: INSERT + UPDATE dalam 1 transaksi)
     *   8. Return JurnalHeader yang baru dibuat
     *
     * @param  string  $kodeTransaksi  Kode dari master_transaksi (mis: 'JTW')
     * @param  array   $payload        Nilai variabel yang dibutuhkan template + info akun dinamis
     * @param  string  $sourceType     Nama class model sumber (mis: Penjualan::class)
     * @param  int     $sourceId       ID record sumber
     * @param  string  $keterangan     Deskripsi manusiawi
     *
     * @throws AkunTidakDitemukanException   jika akun dinamis gagal di-resolve
     * @throws JurnalTidakBalanceException   jika Debit Ã¢â€°Â  Kredit setelah resolve
     * @throws PeriodeTutupException         jika periode sudah ditutup
     */
    public function posting(
        string $kodeTransaksi,
        array  $payload,
        string $sourceType,
        int    $sourceId,
        string $keterangan = '',
        ?int   $entitasId = null,
    ): JurnalHeader {
        // --- LANGKAH 1: Ambil template dari database ---
        // MasterDetailTransaksi tidak pakai BelongsToEntitas karena tabel
        // ini global (tidak per-koperasi) Ã¢â‚¬â€ semua desa pakai template sama.
        $template = MasterDetailTransaksi::where('kode_transaksi', $kodeTransaksi)
            ->orderBy('urutan')
            ->get();

        if ($template->isEmpty()) {
            throw new \InvalidArgumentException(
                "Kode transaksi [{$kodeTransaksi}] tidak ditemukan di master_detail_transaksi."
            );
        }

        // --- LANGKAH 2 & 3: Resolve akun dinamis + hitung nilai ---
        $baris = $this->resolveBarisJurnal($template, $payload);

        // --- LANGKAH 4: Validasi balance Debit = Kredit ---
        $totalDebit  = array_sum(array_column($baris, 'debet'));
        $totalKredit = array_sum(array_column($baris, 'kredit'));

        if (abs($totalDebit - $totalKredit) > 0.001) {
            // Toleransi 0.001 untuk menghindari floating-point imprecision
            throw new JurnalTidakBalanceException($totalDebit, $totalKredit);
        }

        // --- LANGKAH 5: Validasi periode masih OPEN ---
        $tanggal = $payload['tanggal_jurnal'] ?? now()->toDateString();
        [$tahun, $bulan] = $this->parsePeriode($tanggal);
        $this->pastikanPeriodeTerbuka($tahun, $bulan, $entitasId);

        // --- LANGKAH 6 & 7: Nomor jurnal + panggil SP (dalam 1 transaksi) ---
        return $this->tulisJurnal($tahun, $bulan, [
            'nomor_nota'     => $payload['nomor_nota'] ?? null,
            'tanggal_jurnal' => $tanggal,
            'periode_tahun'  => $tahun,
            'periode_bulan'  => $bulan,
            'kode_transaksi' => $kodeTransaksi,
            'jenis_jurnal'   => 'OTOMATIS',
            'source_type'    => $sourceType,
            'source_id'      => $sourceId,
            'keterangan'     => $keterangan,
        ], $baris, $sourceType, $sourceId, 'OTOMATIS', $entitasId);
    }

    // =========================================================================
    // METHOD 2: postingManual() Ã¢â‚¬â€ Jurnal Bebas (tanpa template)
    // =========================================================================

    /**
     * Posting jurnal manual Ã¢â‚¬â€ pengguna tentukan sendiri setiap baris.
     * Dipakai untuk: KSM (kas masuk lain), KSK (kas keluar lain),
     * MTK (mutasi antar kas), dan jurnal penyesuaian.
     *
     * @param  array  $header  Data header jurnal
     *                         Wajib: tanggal_jurnal, jenis_jurnal, keterangan
     *                         Opsional: kode_transaksi, nomor_nota
     * @param  array  $baris   Array baris jurnal
     *                         Setiap baris: ['kode_anak', 'posisi' (D/K), 'nilai', 'id_pihak']
     *
     * @throws JurnalTidakBalanceException
     * @throws PeriodeTutupException
     */
    public function postingManual(array $header, array $baris): JurnalHeader
    {
        // Ubah format ['posisi' => 'D', 'nilai' => 100000] menjadi
        // format internal ['debet' => 100000, 'kredit' => 0]
        $barisInternal = array_map(function (array $b, int $idx): array {
            return [
                'urutan'    => $idx + 1,
                'kode_anak' => $b['kode_anak'],
                'debet'     => $b['posisi'] === 'D' ? (float) $b['nilai'] : 0.0,
                'kredit'    => $b['posisi'] === 'K' ? (float) $b['nilai'] : 0.0,
                'id_pihak'  => $b['id_pihak'] ?? null,
                'keterangan'=> $b['keterangan'] ?? null,
            ];
        }, $baris, array_keys($baris));

        // Validasi balance
        $totalDebit  = array_sum(array_column($barisInternal, 'debet'));
        $totalKredit = array_sum(array_column($barisInternal, 'kredit'));

        if (abs($totalDebit - $totalKredit) > 0.001) {
            throw new JurnalTidakBalanceException($totalDebit, $totalKredit);
        }

        // Validasi periode
        $tanggal = $header['tanggal_jurnal'];
        [$tahun, $bulan] = $this->parsePeriode($tanggal);
        $this->pastikanPeriodeTerbuka($tahun, $bulan);

        return $this->tulisJurnal($tahun, $bulan, [
            'nomor_nota'     => $header['nomor_nota'] ?? null,
            'tanggal_jurnal' => $tanggal,
            'periode_tahun'  => $tahun,
            'periode_bulan'  => $bulan,
            'kode_transaksi' => $header['kode_transaksi'] ?? null,
            'jenis_jurnal'   => $header['jenis_jurnal'],  // MANUAL / PENYESUAIAN
            'source_type'    => $header['source_type'] ?? null,
            'source_id'      => $header['source_id'] ?? null,
            'keterangan'     => $header['keterangan'],
        ], $barisInternal, $header['source_type'] ?? null, isset($header['source_id']) ? (int) $header['source_id'] : null, $header['jenis_jurnal']);
    }

    // =========================================================================
    // METHOD 3: balik() Ã¢â‚¬â€ Jurnal Pembalik
    // =========================================================================

    /**
     * Membuat jurnal pembalik dari jurnal yang sudah POSTED.
     * Setiap baris debet menjadi kredit dan sebaliknya.
     * Jurnal asli ditandai REVERSED.
     *
     * @throws JurnalSudahDibalikException  jika jurnal sudah pernah dibalik
     * @throws \InvalidArgumentException    jika jurnal bukan berstatus POSTED
     */
    public function balik(int $idJurnal, string $alasan): JurnalHeader
    {
        return DB::transaction(function () use ($idJurnal, $alasan) {
            // 1. Validasi awal di level PHP. lockForUpdate: dua user yang
            //    menekan "Balik" bersamaan akan antre, yang kedua pasti gagal.
            $jurnalAsal = JurnalHeader::lockForUpdate()->findOrFail($idJurnal);

            $sudahDibalik = JurnalHeader::where('id_jurnal_asal', $idJurnal)->first();
            if ($sudahDibalik) {
                throw new JurnalSudahDibalikException($idJurnal, $sudahDibalik->id_jurnal);
            }

            if ($jurnalAsal->status !== 'POSTED') {
                throw new \InvalidArgumentException(
                    "Jurnal #{$idJurnal} berstatus [{$jurnalAsal->status}], bukan POSTED."
                );
            }

            $tanggalPembalik = now()->toDateString();
            [$tahun, $bulan] = $this->parsePeriode($tanggalPembalik);
            $this->pastikanPeriodeTerbuka($tahun, $bulan);

            // 2. Eksekusi Stored Procedure (ikut transaksi ini, tanpa COMMIT sendiri)
            $result = DB::select('CALL sp_jurnal_pembalik(?, ?, ?)', [
                $idJurnal,
                Auth::id() ?? 1,
                $alasan,
            ]);

            return JurnalHeader::findOrFail($result[0]->id_jurnal);
        }, 3);
    }

    // =========================================================================
    // METHOD 4: bangunBukuBesar() Ã¢â‚¬â€ Rebuild ringkasan saldo (idempoten)
    // =========================================================================

    /**
     * Membangun ulang tabel buku_besar_periode untuk satu bulan tertentu.
     * Aman dipanggil berkali-kali Ã¢â‚¬â€ hasil selalu konsisten dengan data jurnal.
     *
     * Kapan dipakai:
     *   - Saat tutup bulan (TutupBulanService memanggil ini)
     *   - Saat ada jurnal backdate yang masuk belakangan
     *   - Saat data buku besar dicurigai tidak konsisten
     */
    public function bangunBukuBesar(int $tahun, int $bulan): void
    {
        // DELETE + INSERT harus atomik: jika proses terhenti di tengah,
        // buku besar bulan ini tidak boleh tertinggal dalam keadaan kosong.
        DB::transaction(fn () => $this->bangunBukuBesarInternal($tahun, $bulan), 3);
    }

    private function bangunBukuBesarInternal(int $tahun, int $bulan, ?int $entitasId = null): void
    {
        $idEntitas = $this->getEntitasAktif($entitasId);

        // Hapus ringkasan periode ini Ã¢â‚¬â€ akan dibangun ulang dari nol
        DB::table('buku_besar_periode')
            ->where('id_entitas', $idEntitas)
            ->where('periode_tahun', $tahun)
            ->where('periode_bulan', $bulan)
            ->delete();

        // Ambil saldo_akhir bulan lalu sebagai saldo_awal bulan ini
        $bulanLalu       = $bulan === 1 ? 12 : $bulan - 1;
        $tahunLalu       = $bulan === 1 ? $tahun - 1 : $tahun;

        $saldoAwalBulanLalu = DB::table('buku_besar_periode')
            ->where('id_entitas', $idEntitas)
            ->where('periode_tahun', $tahunLalu)
            ->where('periode_bulan', $bulanLalu)
            ->get()
            ->keyBy('kode_anak');  // index by kode_anak untuk akses cepat

        // Hitung mutasi bulan ini dari jurnal yang sudah POSTED
        $mutasi = DB::table('jurnal_detail as jd')
            ->join('jurnal_header as jh', 'jh.id_jurnal', '=', 'jd.id_jurnal')
            ->where('jh.id_entitas', $idEntitas)
            ->where('jh.periode_tahun', $tahun)
            ->where('jh.periode_bulan', $bulan)
            ->where('jh.status', 'POSTED')
            ->selectRaw('jd.kode_anak, SUM(jd.debet) as mutasi_debet, SUM(jd.kredit) as mutasi_kredit')
            ->groupBy('jd.kode_anak')
            ->get();

        // Kumpulkan semua akun (dari saldo_awal DAN dari mutasi)
        $semuaAkun = collect($saldoAwalBulanLalu->keys())
            ->merge($mutasi->pluck('kode_anak'))
            ->unique();

        // Bangun ulang baris buku_besar_periode
        $rows = $semuaAkun->map(function ($kodeAnak) use ($saldoAwalBulanLalu, $mutasi, $idEntitas, $tahun, $bulan) {
            $saldoAwal = $saldoAwalBulanLalu->get($kodeAnak);
            $m         = $mutasi->firstWhere('kode_anak', $kodeAnak);

            $saldoAwalD = $saldoAwal ? (float) $saldoAwal->saldo_akhir_debet  : 0.0;
            $saldoAwalK = $saldoAwal ? (float) $saldoAwal->saldo_akhir_kredit : 0.0;

            $mutasiD    = $m ? (float) $m->mutasi_debet  : 0.0;
            $mutasiK    = $m ? (float) $m->mutasi_kredit : 0.0;

            return [
                'id_entitas'       => $idEntitas,
                'periode_tahun'     => $tahun,
                'periode_bulan'     => $bulan,
                'kode_anak'         => $kodeAnak,
                'saldo_awal_debet'  => $saldoAwalD,
                'saldo_awal_kredit' => $saldoAwalK,
                'mutasi_debet'      => $mutasiD,
                'mutasi_kredit'     => $mutasiK,
                'saldo_akhir_debet' => $saldoAwalD + $mutasiD,
                'saldo_akhir_kredit'=> $saldoAwalK + $mutasiK,
                'dihitung_pada'     => now(),
            ];
        })->values()->toArray();

        if (!empty($rows)) {
            DB::table('buku_besar_periode')->insert($rows);
        }
    }

    // =========================================================================
    // PRIVATE HELPERS Ã¢â‚¬â€ Logika internal, tidak dipanggil dari luar
    // =========================================================================

    /**
     * Mengubah template baris (dari master_detail_transaksi) menjadi array
     * baris jurnal konkret dengan kode_anak dan nilai yang sudah diisi.
     *
     * Ini adalah inti logika "resolver akun dinamis".
     */
    private function resolveBarisJurnal($template, array $payload): array
    {
        // Cache unit usaha dan kas bank agar tidak query berulang di setiap baris
        $unitUsaha = null;
        $kasBank   = null;

        if (isset($payload['kode_unit'])) {
            $unitUsaha = DB::table('master_unit_usaha')
                ->where('kode_unit_usaha', $payload['kode_unit'])
                ->first();

            if (!$unitUsaha) {
                throw new AkunTidakDitemukanException(
                    'UNIT_USAHA',
                    "Kode unit usaha [{$payload['kode_unit']}] tidak ditemukan"
                );
            }
        }

        if (isset($payload['id_kas_bank'])) {
            $kasBank = KasBank::find($payload['id_kas_bank']);

            if (!$kasBank) {
                throw new AkunTidakDitemukanException(
                    'KAS_BANK',
                    "id_kas_bank [{$payload['id_kas_bank']}] tidak ditemukan"
                );
            }
        }

        $baris = [];

        foreach ($template as $idx => $t) {
            // Tentukan kode akun: dinamis atau sudah tetap?
            $kodeAkun = match ($t->akun_dinamis) {
                'KAS_BANK'        => $this->resolveKasBank($kasBank, $payload),
                'PERSEDIAAN_UNIT' => $this->resolveUnitField($unitUsaha, 'kode_akun_persediaan'),
                'PENDAPATAN_UNIT' => $this->resolvePendapatan($unitUsaha, $payload),
                'HPP_UNIT'        => $this->resolveUnitField($unitUsaha, 'kode_akun_hpp'),
                default           => $t->kode_anak,  // null Ã¢â€ â€™ kode_anak tetap
            };

            // Ambil nilai dari payload berdasarkan sumber_variabel
            // Contoh: sumber_variabel = 'total_bayar' Ã¢â€ â€™ $payload['total_bayar']
            $nilai = (float) ($payload[$t->sumber_variabel] ?? 0);

            $baris[] = [
                'urutan'    => $idx + 1,
                'kode_anak' => $kodeAkun,
                'debet'     => $t->posisi === 'D' ? $nilai : 0.0,
                'kredit'    => $t->posisi === 'K' ? $nilai : 0.0,
                'id_pihak'  => $payload['id_pihak'] ?? null,
                'keterangan'=> null,
            ];
        }

        return $baris;
    }

    /** Resolve akun KAS_BANK dari objek KasBank */
    private function resolveKasBank(?object $kasBank, array $payload): string
    {
        if (!$kasBank) {
            throw new AkunTidakDitemukanException(
                'KAS_BANK',
                'id_kas_bank tidak ada di payload'
            );
        }
        return $kasBank->kode_akun;
    }

    /** Resolve akun unit usaha (PERSEDIAAN atau HPP) */
    private function resolveUnitField(?object $unitUsaha, string $field): string
    {
        if (!$unitUsaha) {
            throw new AkunTidakDitemukanException(
                strtoupper($field),
                'kode_unit tidak ada di payload'
            );
        }
        return $unitUsaha->$field;
    }

    /** Resolve PENDAPATAN_UNIT Ã¢â‚¬â€ berbeda berdasarkan status anggota */
    private function resolvePendapatan(?object $unitUsaha, array $payload): string
    {
        if (!$unitUsaha) {
            throw new AkunTidakDitemukanException(
                'PENDAPATAN_UNIT',
                'kode_unit tidak ada di payload'
            );
        }

        // is_anggota harus ada di payload Ã¢â‚¬â€ Controller yang menentukannya
        // berdasarkan data master_pihak (apakah pembeli adalah anggota koperasi)
        $isAnggota = $payload['is_anggota'] ?? false;

        return $isAnggota
            ? $unitUsaha->kode_akun_pendapatan_anggota
            : $unitUsaha->kode_akun_pendapatan_non_anggota;
    }

    /** Validasi bahwa periode akuntansi masih OPEN */
    private function pastikanPeriodeTerbuka(int $tahun, int $bulan, ?int $entitasId = null): void
    {
        $entitasId = $this->getEntitasAktif($entitasId);

        $status = DB::table('periode_akuntansi')
            ->where('id_entitas', $entitasId)
            ->where('tahun', $tahun)
            ->where('bulan', $bulan)
            ->value('status');

        if ($status === null) {
            // Periode belum dibuat Ã¢â‚¬â€ anggap terbuka (sistem baru / fresh)
            return;
        }

        if ($status !== 'OPEN') {
            throw new PeriodeTutupException($tahun, $bulan);
        }
    }

    /** Ubah string tanggal menjadi [tahun, bulan] */
    private function parsePeriode(string $tanggal): array
    {
        $dt = \Carbon\Carbon::parse($tanggal);
        return [$dt->year, $dt->month];
    }

    /**
     * Generate nomor jurnal unik untuk periode ini.
     * Format: JRN-{tahun}-{bulan_2digit}-{sequence_4digit}
     * Contoh: JRN-2026-08-0042
     *
     * Memakai counter di tabel penomoran_jurnal dengan SATU statement atomik:
     *   INSERT ... ON DUPLICATE KEY UPDATE nomor = LAST_INSERT_ID(nomor + 1)
     * - Dua posting bersamaan antre di satu baris counter -> nomor tidak kembar.
     * - Tidak memakai gap lock -> tidak ada deadlock antar posting.
     * - Wajib dipanggil di dalam transaksi: jika posting gagal, kenaikan
     *   counter ikut di-rollback sehingga nomor tidak loncat.
     */
    private function generateNomorJurnal(int $tahun, int $bulan, ?int $entitasId = null): string
    {
        $entitasId = $this->getEntitasAktif($entitasId);
        $prefix = sprintf('JRN-%d-%02d-', $tahun, $bulan);

        // Nilai awal jika counter prefix ini belum ada: lanjutkan dari nomor
        // tertinggi yang sudah ada (data lama sebelum tabel counter dibuat).
        $nomorLama = DB::table('jurnal_header')
            ->where('id_entitas', $entitasId)
            ->where('no_jurnal', 'like', $prefix . '%')
            ->pluck('no_jurnal')
            ->map(fn (string $noJurnal) => (int) substr($noJurnal, strlen($prefix)))
            ->max() ?? 0;
        $nomorAwal = $nomorLama + 1;

        if (DB::getDriverName() === 'sqlite') {
            DB::statement(
                'INSERT INTO penomoran_jurnal (id_entitas, prefix, nomor_terakhir)
                 VALUES (?, ?, ?)
                 ON CONFLICT (id_entitas, prefix) DO UPDATE
                 SET nomor_terakhir = penomoran_jurnal.nomor_terakhir + 1',
                [$entitasId, $prefix, $nomorAwal],
            );

            $nomor = (int) DB::table('penomoran_jurnal')
                ->where('id_entitas', $entitasId)
                ->where('prefix', $prefix)
                ->value('nomor_terakhir');

            return $prefix . sprintf('%04d', $nomor);
        }

        DB::statement(
            'INSERT INTO penomoran_jurnal (id_entitas, prefix, nomor_terakhir)
             VALUES (?, ?, LAST_INSERT_ID(?))
             ON DUPLICATE KEY UPDATE nomor_terakhir = LAST_INSERT_ID(nomor_terakhir + 1)',
            [$entitasId, $prefix, $nomorAwal]
        );

        $nomor = (int) DB::selectOne('SELECT LAST_INSERT_ID() AS n')->n;

        return $prefix . sprintf('%04d', $nomor);
    }

    /** Gabungkan header dan baris menjadi JSON string untuk dikirim ke SP */
    private function susunJsonPayload(array $header, array $baris): string
    {
        return json_encode([...$header, 'baris' => $baris], JSON_UNESCAPED_UNICODE);
    }

    /**
     * Generate nomor + CALL sp_post_jurnal di dalam SATU transaksi, lalu
     * kembalikan JurnalHeader.
     *
     * Menangani dua kasus:
     *   a. INSERT baru berhasil -> kembalikan jurnal yang baru dibuat
     *   b. UniqueConstraintViolation pada jurnal_idempoten ->
     *      berarti jurnal sumber ini sudah pernah diposting.
     *      Kembalikan jurnal yang sudah ada (idempoten, aman diulang).
     *
     * DB::transaction(..., 3):
     *   - Pemanggil sudah punya transaksi -> ikut (SAVEPOINT); gagal = rollback
     *     ke savepoint, lalu error diteruskan ke pemanggil.
     *   - Tidak ada transaksi -> transaksi baru; deadlock/lock timeout diulang
     *     otomatis hingga 3x oleh Laravel.
     */
    private function tulisJurnal(
        int     $tahun,
        int     $bulan,
        array   $header,
        array   $baris,
        ?string $sourceType,
        ?int    $sourceId,
        string  $jenisJurnal,
        ?int    $entitasId = null,
    ): JurnalHeader {
        $entitasId = $this->getEntitasAktif($entitasId);

        try {
            return DB::transaction(function () use ($tahun, $bulan, $header, $baris, $entitasId) {
                $noJurnal    = $this->generateNomorJurnal($tahun, $bulan, $entitasId);
                $jsonPayload = $this->susunJsonPayload([
                    'no_jurnal' => $noJurnal,
                    'id_entitas' => $entitasId,
                    ...$header,
                ], $baris);

                if (DB::getDriverName() === 'sqlite') {
                    return $this->postingSqlite($jsonPayload);
                }

                // Pastikan string JSON dan variabel teks di stored procedure memakai
                // collation yang sama dengan kolom kode akun jurnal.
                DB::statement("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");

                // CALL SP -> mengembalikan satu baris dengan kolom id_jurnal.
                // SP tidak COMMIT sendiri; ia ikut transaksi ini.
                $hasil = DB::select('CALL sp_post_jurnal(?, ?, ?)', [
                    $entitasId,
                    Auth::id(),
                    $jsonPayload,
                ]);

                return JurnalHeader::findOrFail($hasil[0]->id_jurnal);
            }, 3);


        } catch (UniqueConstraintViolationException $e) {
            // Idempotency guard: jurnal untuk source ini sudah ada.
            // Kembalikan yang sudah ada tanpa error Ã¢â‚¬â€ aman untuk retry.
            if ($sourceType && $sourceId) {
                $existing = JurnalHeader::where('source_type', $sourceType)
                    ->where('source_id', $sourceId)
                    ->where('jenis_jurnal', $jenisJurnal)
                    ->where('id_entitas', $entitasId)
                    ->first();

                if ($existing) {
                    return $existing;
                }
            }

            // Jika bukan idempotency (no_jurnal duplikat, dsb) Ã¢â€ â€™ lempar ulang
            throw $e;
        }
    }

    private function postingSqlite(string $jsonPayload): JurnalHeader
    {
        $payload = json_decode($jsonPayload, true, 512, JSON_THROW_ON_ERROR);
        $baris = $payload['baris'] ?? [];
        $idEntitas = (int) ($payload['id_entitas'] ?? $this->getEntitasAktif() ?? 1);

        return DB::transaction(function () use ($payload, $baris, $idEntitas) {
            $header = JurnalHeader::create([
                'id_entitas' => $idEntitas,
                'no_jurnal' => $payload['no_jurnal'],
                'nomor_nota' => $payload['nomor_nota'] ?? null,
                'tanggal_jurnal' => $payload['tanggal_jurnal'],
                'periode_tahun' => $payload['periode_tahun'],
                'periode_bulan' => $payload['periode_bulan'],
                'kode_transaksi' => $payload['kode_transaksi'] ?? null,
                'jenis_jurnal' => $payload['jenis_jurnal'],
                'source_type' => $payload['source_type'] ?? null,
                'source_id' => $payload['source_id'] ?? null,
                'keterangan' => $payload['keterangan'] ?? null,
                'total_debet' => collect($baris)->sum('debet'),
                'total_kredit' => collect($baris)->sum('kredit'),
                'status' => 'POSTED',
                'created_by' => Auth::id(),
                'posted_by' => Auth::id(),
                'posted_at' => now(),
            ]);

            foreach ($baris as $index => $line) {
                JurnalDetail::create([
                    'id_jurnal' => $header->id_jurnal,
                    'urutan' => $index + 1,
                    'kode_anak' => $line['kode_anak'],
                    'debet' => $line['debet'],
                    'kredit' => $line['kredit'],
                    'keterangan' => $line['keterangan'] ?? null,
                    'id_pihak' => $line['id_pihak'] ?? null,
                ]);
            }

            $this->bangunBukuBesarInternal(
                (int) $payload['periode_tahun'],
                (int) $payload['periode_bulan'],
                $idEntitas,
            );

            return $header;
        });
    }

    private function getEntitasAktif(?int $entitasId = null): ?int
    {
        if ($entitasId !== null) {
            return $entitasId;
        }

        if (app()->bound('entitas_aktif')) {
            return (int) app('entitas_aktif');
        }

        if (app()->bound('koperasi_aktif')) {
            return (int) app('koperasi_aktif');
        }

        return null;
    }
}



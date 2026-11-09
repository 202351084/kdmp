<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Revisi sp_jurnal_pembalik: tambah parameter p_id_entitas (defense in depth).
 *
 * MASALAH VERSI SEBELUMNYA:
 *   SP hanya menerima p_id_jurnal. Isolasi tenant sepenuhnya bergantung pada
 *   lapisan PHP (JurnalHeader::findOrFail yang ter-scope EntitasScope). Jika
 *   suatu saat ada kode yang memanggil SP langsung tanpa cek tersebut, jurnal
 *   milik desa lain bisa dibalik.
 *
 * VERSI BARU:
 *   sp_jurnal_pembalik(p_id_jurnal, p_id_entitas, p_user_id, p_alasan)
 *   - Database sendiri menolak (SIGNAL 45000) bila jurnal bukan milik
 *     p_id_entitas. Pesan error sengaja sama dengan "tidak ditemukan" agar
 *     tidak membocorkan keberadaan jurnal milik entitas lain.
 *   - Logika lain identik dengan revisi 2026-10-11 (tanpa transaksi sendiri,
 *     FOR UPDATE pada jurnal asal, saldo_awal dari bulan sebelumnya).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::unprepared('DROP PROCEDURE IF EXISTS sp_jurnal_pembalik');

        DB::unprepared(<<<'SQL'
CREATE PROCEDURE sp_jurnal_pembalik(
    IN p_id_jurnal  BIGINT UNSIGNED,
    IN p_id_entitas BIGINT UNSIGNED,
    IN p_user_id    BIGINT UNSIGNED,
    IN p_alasan     VARCHAR(255)
)
BEGIN
    -- KONTRAK: dipanggil di dalam transaksi milik pemanggil (JurnalService::balik).
    DECLARE v_id_entitas      BIGINT UNSIGNED;
    DECLARE v_no_jurnal_lama  VARCHAR(50);
    DECLARE v_kode_transaksi  VARCHAR(50);
    DECLARE v_total_debet     DECIMAL(18,2);
    DECLARE v_total_kredit    DECIMAL(18,2);
    DECLARE v_status          VARCHAR(20) DEFAULT NULL;
    DECLARE v_sudah_dibalik   INT DEFAULT 0;

    DECLARE v_new_id_jurnal   BIGINT UNSIGNED;
    DECLARE v_new_no_jurnal   VARCHAR(50);
    DECLARE v_tahun           SMALLINT UNSIGNED;
    DECLARE v_bulan           TINYINT UNSIGNED;

    -- Validasi 1: jurnal asal ada, milik entitas pemanggil, & POSTED.
    -- Filter id_entitas langsung di WHERE: jurnal entitas lain diperlakukan
    -- sama persis seperti jurnal yang tidak ada.
    SELECT id_entitas, no_jurnal, kode_transaksi, total_debet, total_kredit, status
    INTO   v_id_entitas, v_no_jurnal_lama, v_kode_transaksi, v_total_debet, v_total_kredit, v_status
    FROM   jurnal_header
    WHERE  id_jurnal  = p_id_jurnal
      AND  id_entitas = p_id_entitas
    FOR UPDATE;

    IF v_status IS NULL THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Jurnal tidak ditemukan.';
    END IF;

    IF v_status <> 'POSTED' THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Hanya jurnal dengan status POSTED yang dapat dibalik.';
    END IF;

    -- Validasi 2: belum pernah dibalik
    SELECT COUNT(*) INTO v_sudah_dibalik
    FROM   jurnal_header
    WHERE  id_jurnal_asal = p_id_jurnal;

    IF v_sudah_dibalik > 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Jurnal ini sudah pernah dibalik.';
    END IF;

    SET v_tahun = YEAR(CURRENT_DATE());
    SET v_bulan = MONTH(CURRENT_DATE());
    SET v_new_no_jurnal = CONCAT('REV-', v_no_jurnal_lama);

    -- 1. Header pembalik
    INSERT INTO jurnal_header (
        id_entitas, no_jurnal, tanggal_jurnal, periode_tahun, periode_bulan,
        kode_transaksi, jenis_jurnal, id_jurnal_asal, keterangan,
        total_debet, total_kredit, status, created_by, posted_by, posted_at, created_at
    ) VALUES (
        v_id_entitas, v_new_no_jurnal, CURRENT_DATE(), v_tahun, v_bulan,
        v_kode_transaksi, 'PEMBALIK', p_id_jurnal,
        CONCAT('Koreksi untuk Transaksi ID ', p_id_jurnal, ': ', p_alasan),
        v_total_kredit, v_total_debet, 'POSTED', p_user_id, p_user_id, NOW(), NOW()
    );

    SET v_new_id_jurnal = LAST_INSERT_ID();

    -- 2. Detail dibalik (Debet <-> Kredit)
    INSERT INTO jurnal_detail (id_jurnal, urutan, kode_anak, debet, kredit, id_pihak, keterangan)
    SELECT v_new_id_jurnal, urutan, kode_anak, kredit, debet, id_pihak, keterangan
    FROM   jurnal_detail
    WHERE  id_jurnal = p_id_jurnal;

    -- 3. UPSERT buku besar bulan berjalan.
    --    Baris baru: saldo_awal diambil dari saldo_akhir bulan sebelumnya.
    INSERT INTO buku_besar_periode (
        id_entitas, periode_tahun, periode_bulan, kode_anak,
        saldo_awal_debet, saldo_awal_kredit,
        mutasi_debet, mutasi_kredit,
        saldo_akhir_debet, saldo_akhir_kredit, dihitung_pada
    )
    SELECT
        v_id_entitas, v_tahun, v_bulan, jd.kode_anak,
        IFNULL(prev.saldo_akhir_debet, 0),
        IFNULL(prev.saldo_akhir_kredit, 0),
        jd.kredit,
        jd.debet,
        IFNULL(prev.saldo_akhir_debet, 0)  + jd.kredit,
        IFNULL(prev.saldo_akhir_kredit, 0) + jd.debet,
        NOW()
    FROM jurnal_detail jd
    LEFT JOIN buku_besar_periode prev
           ON prev.id_entitas    = v_id_entitas
          AND prev.periode_tahun = IF(v_bulan = 1, v_tahun - 1, v_tahun)
          AND prev.periode_bulan = IF(v_bulan = 1, 12, v_bulan - 1)
          AND prev.kode_anak     = jd.kode_anak
    WHERE jd.id_jurnal = p_id_jurnal
    ON DUPLICATE KEY UPDATE
        buku_besar_periode.mutasi_debet       = buku_besar_periode.mutasi_debet       + VALUES(buku_besar_periode.mutasi_debet),
        buku_besar_periode.mutasi_kredit      = buku_besar_periode.mutasi_kredit      + VALUES(buku_besar_periode.mutasi_kredit),
        buku_besar_periode.saldo_akhir_debet  = buku_besar_periode.saldo_akhir_debet  + VALUES(buku_besar_periode.mutasi_debet),
        buku_besar_periode.saldo_akhir_kredit = buku_besar_periode.saldo_akhir_kredit + VALUES(buku_besar_periode.mutasi_kredit),
        buku_besar_periode.dihitung_pada      = NOW();

    -- 4. Tandai jurnal asal sebagai REVERSED
    UPDATE jurnal_header
    SET    status = 'REVERSED'
    WHERE  id_jurnal  = p_id_jurnal
      AND  id_entitas = p_id_entitas;

    SELECT v_new_id_jurnal AS id_jurnal;
END
SQL);
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        // Kembali ke versi 3 parameter (revisi 2026-10-11).
        $revisiSebelumnya = require database_path('migrations/2026_10_11_000001_revise_sp_jurnal_tanpa_transaksi.php');
        $revisiSebelumnya->up();
    }
};

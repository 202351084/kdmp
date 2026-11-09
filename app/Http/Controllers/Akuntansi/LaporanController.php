<?php

namespace App\Http\Controllers\Akuntansi;

use App\Http\Controllers\Controller;
use App\Models\Akuntansi\Coa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * LaporanController Ã¢â‚¬â€ Menyajikan 4 laporan keuangan standar:
 *
 *   1. neracaSaldo()  Ã¢â‚¬â€ Trial Balance: semua akun + saldo D/K per bulan
 *   2. bukuBesar()    Ã¢â‚¬â€ Ledger: mutasi per akun + running balance (dihitung PHP)
 *   3. labaRugi()     Ã¢â‚¬â€ Income Statement: Pendapatan Ã¢Ë†â€™ HPP Ã¢Ë†â€™ Biaya = Laba Bersih
 *   4. neraca()       Ã¢â‚¬â€ Balance Sheet: Aset = Kewajiban + Modal
 *
 * Semua bersumber dari buku_besar_periode (sudah teragregasi per bulan)
 * dan jurnal_header/detail (untuk detail buku besar).
 * Tidak ada tabel laporan terpisah Ã¢â‚¬â€ single source of truth = jurnal.
 */
class LaporanController extends Controller
{
    // =========================================================================
    // HELPER Ã¢â‚¬â€ parse filter tahun/bulan dari request
    // =========================================================================

    private function parsePeriode(Request $request): array
    {
        $tahun = (int) $request->query('tahun', date('Y'));
        $bulan = (int) $request->query('bulan', date('n'));
        $bulan = max(1, min(12, $bulan));
        return [$tahun, $bulan];
    }

    private function bulanLabel(int $bulan): string
    {
        $labels = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret',
            4 => 'April', 5 => 'Mei', 6 => 'Juni',
            7 => 'Juli', 8 => 'Agustus', 9 => 'September',
            10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];
        return $labels[$bulan] ?? '-';
    }

    // =========================================================================
    // 1. NERACA SALDO (Trial Balance)
    // =========================================================================

    /**
     * Tampilkan semua akun dengan saldo Debet / Kredit per bulan yang dipilih.
     * Total Debet harus = Total Kredit Ã¢â‚¬â€ jika tidak, ada posting yang salah.
     *
     * Sumber data: buku_besar_periode JOIN master_coa
     * Dua tambahan Plan A:
     *   - Parent rollup: query GROUP BY dilakukan di PHP (tidak disimpan ke DB)
     */
    public function neracaSaldo(Request $request): View
    {
        $idEntitas = app('entitas_aktif');
        [$tahun, $bulan] = $this->parsePeriode($request);

        // Cari bulan terakhir (<= bulan yang dipilih) yang ada datanya untuk tiap akun
        $latestPeriode = DB::table('buku_besar_periode as b2')
            ->select('b2.kode_anak', DB::raw('MAX(b2.periode_bulan) as max_bulan'))
            ->when($idEntitas, fn($q) => $q->where('b2.id_entitas', $idEntitas))
            ->where('b2.periode_tahun', $tahun)
            ->where('b2.periode_bulan', '<=', $bulan)
            ->groupBy('b2.kode_anak');

        // Ambil semua akun yang punya data di periode ini atau sebelumnya
        $rows = DB::table('buku_besar_periode as bbp')
            ->joinSub($latestPeriode, 'latest', function ($join) {
                $join->on('bbp.kode_anak', '=', 'latest.kode_anak')
                     ->on('bbp.periode_bulan', '=', 'latest.max_bulan');
            })
            ->join('master_coa as c', 'c.kode_anak', '=', 'bbp.kode_anak')
            ->when($idEntitas, fn($q) => $q->where('bbp.id_entitas', $idEntitas))
            ->where('bbp.periode_tahun', $tahun)
            ->orderBy('c.urutan_laporan')
            ->orderBy('c.kode_anak')
            ->select([
                'bbp.kode_anak',
                'c.nama_rekening',
                'c.kelompok',
                'c.posisi_normal',
                'bbp.saldo_akhir_debet',
                'bbp.saldo_akhir_kredit',
            ])
            ->get();

        // Hitung saldo normal per akun (untuk tampilan "saldo bersih")
        $bankMap = Coa::bankMapForEntitas($idEntitas);
        $rows = $rows->map(function ($row) use ($bankMap) {
            if (isset($bankMap[$row->kode_anak])) {
                $row->nama_rekening = $bankMap[$row->kode_anak]->nama;
            }
            if ($row->posisi_normal === 'D') {
                $row->saldo_normal = $row->saldo_akhir_debet - $row->saldo_akhir_kredit;
                $row->saldo_d = max(0, $row->saldo_normal);
                $row->saldo_k = max(0, -$row->saldo_normal);
            } else {
                $row->saldo_normal = $row->saldo_akhir_kredit - $row->saldo_akhir_debet;
                $row->saldo_d = max(0, -$row->saldo_normal);
                $row->saldo_k = max(0, $row->saldo_normal);
            }
            return $row;
        });

        $totalDebet  = $rows->sum('saldo_d');
        $totalKredit = $rows->sum('saldo_k');
        $isBalance   = abs($totalDebet - $totalKredit) < 0.01;

        return view('akuntansi.laporan.neraca-saldo', compact(
            'rows', 'tahun', 'bulan', 'totalDebet', 'totalKredit', 'isBalance'
        ));
    }

    // =========================================================================
    // 2. BUKU BESAR (General Ledger)
    // =========================================================================

    /**
     * Tampilkan mutasi per akun: saldo awal Ã¢â€ â€™ baris transaksi Ã¢â€ â€™ saldo akhir.
     * Running balance dihitung di PHP (Plan A tambahan #1).
     *
     * Sumber: jurnal_detail JOIN jurnal_header WHERE status = POSTED
     */
    public function bukuBesar(Request $request): View
    {
        $idEntitas = app('entitas_aktif');
        [$tahun, $bulan] = $this->parsePeriode($request);
        $kodeAnak = $request->query('kode_anak', '');

        // Semua akun leaf (is_transaction = T) untuk dropdown pilih akun
        $akunList = Coa::where('is_transaction', 'T')
            ->where('is_active', true)
            ->orderBy('urutan_laporan')
            ->orderBy('kode_anak')
            ->get(['kode_anak', 'nama_rekening', 'kelompok', 'posisi_normal'])
            ->map(function ($akun) use ($idEntitas) {
                $bankMap = Coa::bankMapForEntitas($idEntitas);
                if (isset($bankMap[$akun->kode_anak])) {
                    $akun->nama_rekening = $bankMap[$akun->kode_anak]->nama;
                }
                return $akun;
            });

        $mutasi      = collect();
        $saldoAwal   = 0;
        $akunDipilih = null;

        if ($kodeAnak) {
            $akunDipilih = $akunList->firstWhere('kode_anak', $kodeAnak);

            // Saldo awal: ambil saldo_akhir bulan sebelumnya dari buku_besar_periode
            $prevTahun  = ($bulan === 1) ? $tahun - 1 : $tahun;
            $prevBulan  = ($bulan === 1) ? 12 : $bulan - 1;

            $bbpSebelum = DB::table('buku_besar_periode')
                ->when($idEntitas, fn($q) => $q->where('id_entitas', $idEntitas))
                ->where('periode_tahun', $prevTahun)
                ->where('periode_bulan', $prevBulan)
                ->where('kode_anak', $kodeAnak)
                ->first(['saldo_akhir_debet', 'saldo_akhir_kredit']);

            if ($bbpSebelum && $akunDipilih) {
                $saldoAwal = ($akunDipilih->posisi_normal === 'D')
                    ? (float) $bbpSebelum->saldo_akhir_debet - (float) $bbpSebelum->saldo_akhir_kredit
                    : (float) $bbpSebelum->saldo_akhir_kredit - (float) $bbpSebelum->saldo_akhir_debet;
            }

            // Ambil mutasi bulan ini dari jurnal
            $mutasi = DB::table('jurnal_detail as jd')
                ->join('jurnal_header as jh', 'jh.id_jurnal', '=', 'jd.id_jurnal')
                ->when($idEntitas, fn($q) => $q->where('jh.id_entitas', $idEntitas))
                ->where('jh.status', 'POSTED')
                ->where('jh.periode_tahun', $tahun)
                ->where('jh.periode_bulan', $bulan)
                ->where('jd.kode_anak', $kodeAnak)
                ->orderBy('jh.tanggal_jurnal')
                ->orderBy('jh.posted_at')
                ->orderBy('jd.urutan')
                ->select([
                    'jh.tanggal_jurnal',
                    'jh.no_jurnal',
                    'jh.kode_transaksi',
                    'jh.keterangan',
                    'jd.debet',
                    'jd.kredit',
                ])
                ->get();

            // Plan A Tambahan #1: hitung running balance di PHP
            if ($akunDipilih) {
                $running = $saldoAwal;
                $mutasi = $mutasi->map(function ($row) use (&$running, $akunDipilih) {
                    if ($akunDipilih->posisi_normal === 'D') {
                        $running += ((float) $row->debet - (float) $row->kredit);
                    } else {
                        $running += ((float) $row->kredit - (float) $row->debet);
                    }
                    $row->saldo_berjalan = $running;
                    return $row;
                });
            }
        }

        return view('akuntansi.laporan.buku-besar', compact(
            'akunList', 'kodeAnak', 'akunDipilih',
            'mutasi', 'saldoAwal', 'tahun', 'bulan'
        ));
    }

    // =========================================================================
    // 3. LABA / RUGI (Income Statement)
    // =========================================================================

    /**
     * Tampilkan Pendapatan Ã¢Ë†â€™ HPP Ã¢Ë†â€™ Biaya = Laba Bersih untuk periode yang dipilih.
     *
     * Filter: tahun + bulan_dari s.d. bulan_sampai
     * Sumber: SUM(mutasi) dari buku_besar_periode (bukan saldo_akhir, agar
     *         range periode bisa dipilih bebas, mis. Q1 = Jan-Mar)
     */
    public function labaRugi(Request $request): View
    {
        $idEntitas = app('entitas_aktif');
        $tahun      = (int) $request->query('tahun', date('Y'));
        $bulanDari  = (int) $request->query('bulan_dari', 1);
        $bulanSampai= (int) $request->query('bulan_sampai', (int) date('n'));
        $bulanDari  = max(1, min(12, $bulanDari));
        $bulanSampai= max($bulanDari, min(12, $bulanSampai));

        $kelompokLR = ['Pendapatan', 'HPP', 'Biaya', 'Non-Operasional'];

        // SUM mutasi debet & kredit per akun untuk range bulan yang dipilih
        $rows = DB::table('buku_besar_periode as bbp')
            ->join('master_coa as c', 'c.kode_anak', '=', 'bbp.kode_anak')
            ->when($idEntitas, fn($q) => $q->where('bbp.id_entitas', $idEntitas))
            ->where('bbp.periode_tahun', $tahun)
            ->whereBetween('bbp.periode_bulan', [$bulanDari, $bulanSampai])
            ->whereIn('c.kelompok', $kelompokLR)
            ->groupBy('bbp.kode_anak', 'c.nama_rekening', 'c.kelompok', 'c.posisi_normal', 'c.urutan_laporan')
            ->orderBy('c.urutan_laporan')
            ->select([
                'bbp.kode_anak',
                'c.nama_rekening',
                'c.kelompok',
                'c.posisi_normal',
                DB::raw('SUM(bbp.mutasi_debet)  as mutasi_debet'),
                DB::raw('SUM(bbp.mutasi_kredit) as mutasi_kredit'),
            ])
            ->get()
            ->map(function ($row) use ($idEntitas) {
                $bankMap = Coa::bankMapForEntitas($idEntitas);
                if (isset($bankMap[$row->kode_anak])) {
                    $row->nama_rekening = $bankMap[$row->kode_anak]->nama;
                }
                // Saldo normal = nilai yang "wajar" untuk kelompok ini
                $row->nilai = ($row->posisi_normal === 'K')
                    ? (float) $row->mutasi_kredit - (float) $row->mutasi_debet
                    : (float) $row->mutasi_debet  - (float) $row->mutasi_kredit;
                return $row;
            });

        // Kelompokkan per seksi laporan
        $pendapatan    = $rows->where('kelompok', 'Pendapatan');
        $hpp           = $rows->where('kelompok', 'HPP');
        $biaya         = $rows->where('kelompok', 'Biaya');
        $nonOperasional= $rows->where('kelompok', 'Non-Operasional');

        $totalPendapatan = $pendapatan->sum('nilai');
        $totalHPP        = $hpp->sum('nilai');
        $labaKotor       = $totalPendapatan - $totalHPP;
        $totalBiaya      = $biaya->sum('nilai');
        $labaOperasi     = $labaKotor - $totalBiaya;

        // Non-Operasional: bisa + (pendapatan) atau - (biaya)
        // Gunakan posisi_normal untuk tentukan arahnya
        $netNonOp = $nonOperasional->sum('nilai');
        $labaBersih = $labaOperasi + $netNonOp;

        return view('akuntansi.laporan.laba-rugi', compact(
            'pendapatan', 'hpp', 'biaya', 'nonOperasional',
            'totalPendapatan', 'totalHPP', 'labaKotor',
            'totalBiaya', 'labaOperasi', 'netNonOp', 'labaBersih',
            'tahun', 'bulanDari', 'bulanSampai'
        ));
    }

    // =========================================================================
    // 4. NERACA (Balance Sheet)
    // =========================================================================

    /**
     * Tampilkan posisi Aset = Kewajiban + Modal per akhir bulan yang dipilih.
     *
     * Sumber: saldo_akhir dari buku_besar_periode bulan yang dipilih.
     * saldo_akhir sudah kumulatif (SP menyimpan saldo_awal + mutasi = saldo_akhir).
     *
     * Plan A Tambahan #2: rollup ke parent dihitung di PHP via kode_induk.
     */
    public function neraca(Request $request): View
    {
        $idEntitas = app('entitas_aktif');
        [$tahun, $bulan] = $this->parsePeriode($request);

        $kelompokNeraca = ['Aktiva', 'Kewajiban', 'Modal'];
        $kelompokLR     = ['Pendapatan', 'HPP', 'Biaya', 'Non-Operasional'];
        $allGroups      = array_merge($kelompokNeraca, $kelompokLR);

        // Cari bulan terakhir (<= bulan yang dipilih) yang ada datanya untuk tiap akun
        $latestPeriode = DB::table('buku_besar_periode as b2')
            ->select('b2.kode_anak', DB::raw('MAX(b2.periode_bulan) as max_bulan'))
            ->when($idEntitas, fn($q) => $q->where('b2.id_entitas', $idEntitas))
            ->where('b2.periode_tahun', $tahun)
            ->where('b2.periode_bulan', '<=', $bulan)
            ->groupBy('b2.kode_anak');

        // Ambil saldo_akhir dari bulan terakhir tersebut
        $rows = DB::table('buku_besar_periode as bbp')
            ->joinSub($latestPeriode, 'latest', function ($join) {
                $join->on('bbp.kode_anak', '=', 'latest.kode_anak')
                     ->on('bbp.periode_bulan', '=', 'latest.max_bulan');
            })
            ->join('master_coa as c', 'c.kode_anak', '=', 'bbp.kode_anak')
            ->when($idEntitas, fn($q) => $q->where('bbp.id_entitas', $idEntitas))
            ->where('bbp.periode_tahun', $tahun)
            ->whereIn('c.kelompok', $allGroups)
            ->orderBy('c.urutan_laporan')
            ->select([
                'bbp.kode_anak',
                'c.kode_induk',
                'c.nama_rekening',
                'c.kelompok',
                'c.posisi_normal',
                'c.level',
                'c.urutan_laporan',
                'bbp.saldo_akhir_debet',
                'bbp.saldo_akhir_kredit',
            ])
            ->get()
            ->map(function ($row) use ($idEntitas) {
                $bankMap = Coa::bankMapForEntitas($idEntitas);
                if (isset($bankMap[$row->kode_anak])) {
                    $row->nama_rekening = $bankMap[$row->kode_anak]->nama;
                }
                $row->saldo = ($row->posisi_normal === 'D')
                    ? (float) $row->saldo_akhir_debet - (float) $row->saldo_akhir_kredit
                    : (float) $row->saldo_akhir_kredit - (float) $row->saldo_akhir_debet;
                return $row;
            });

        $aktiva    = $rows->where('kelompok', 'Aktiva');
        $kewajiban = $rows->where('kelompok', 'Kewajiban');
        
        // Modal Dasar dari COA
        $modal     = clone $rows->where('kelompok', 'Modal');

        // Hitung Laba/Rugi Tahun Berjalan untuk disuntikkan ke Modal
        $pendapatan = $rows->where('kelompok', 'Pendapatan')->sum('saldo');
        $hpp        = $rows->where('kelompok', 'HPP')->sum('saldo');
        $biaya      = $rows->where('kelompok', 'Biaya')->sum('saldo');
        $nonOp      = $rows->where('kelompok', 'Non-Operasional')->sum('saldo');
        
        $labaBersih = $pendapatan - $hpp - $biaya + $nonOp;

        if ($labaBersih != 0) {
            $modal->push((object)[
                'kode_anak'          => '341',
                'kode_induk'         => '34',
                'nama_rekening'      => 'Laba / Rugi Tahun Berjalan',
                'kelompok'           => 'Modal',
                'posisi_normal'      => 'K',
                'level'              => 3,
                'urutan_laporan'     => 9999, // Taruh di paling bawah Modal
                'saldo'              => $labaBersih
            ]);
        }

        $totalAktiva    = $aktiva->sum('saldo');
        $totalKewajiban = $kewajiban->sum('saldo');
        $totalModal     = $modal->sum('saldo');
        $totalPassiva   = $totalKewajiban + $totalModal;
        $isBalance      = abs($totalAktiva - $totalPassiva) < 0.01;

        return view('akuntansi.laporan.neraca', compact(
            'aktiva', 'kewajiban', 'modal',
            'totalAktiva', 'totalKewajiban', 'totalModal', 'totalPassiva',
            'isBalance', 'tahun', 'bulan'
        ));
    }

    // =========================================================================
    // 5. ARUS KAS Ã¢â‚¬â€ Belum Diimplementasi (butuh klasifikasi tambahan di COA)
    // =========================================================================

    public function arusKas(Request $request): View
    {
        $idEntitas = app('entitas_aktif');
        [$tahun, $bulan] = $this->parsePeriode($request);

        // 1. Dapatkan Laba Bersih Bulan Ini
        // Sama dengan logika di labaRugi(), tapi hanya untuk 1 bulan terpilih
        $kelompokLR = ['Pendapatan', 'HPP', 'Biaya', 'Non-Operasional'];
        
        $mutasiLR = DB::table('buku_besar_periode as bbp')
            ->join('master_coa as c', 'c.kode_anak', '=', 'bbp.kode_anak')
            ->when($idEntitas, fn($q) => $q->where('bbp.id_entitas', $idEntitas))
            ->where('bbp.periode_tahun', $tahun)
            ->where('bbp.periode_bulan', $bulan)
            ->whereIn('c.kelompok', $kelompokLR)
            ->select([
                'c.posisi_normal',
                DB::raw('SUM(bbp.mutasi_debet) as m_debet'),
                DB::raw('SUM(bbp.mutasi_kredit) as m_kredit'),
            ])
            ->groupBy('c.posisi_normal')
            ->get();

        $labaBersih = 0;
        foreach ($mutasiLR as $row) {
            if ($row->posisi_normal === 'K') { // Pendapatan
                $labaBersih += ($row->m_kredit - $row->m_debet);
            } else { // Biaya & HPP
                $labaBersih -= ($row->m_debet - $row->m_kredit);
            }
        }

        // 2. Bandingkan Saldo Bulan Ini vs Bulan Lalu untuk pos Neraca
        $prevTahun = ($bulan === 1) ? $tahun - 1 : $tahun;
        $prevBulan = ($bulan === 1) ? 12 : $bulan - 1;

        $saldoSekarang = DB::table('buku_besar_periode as bbp')
            ->join('master_coa as c', 'c.kode_anak', '=', 'bbp.kode_anak')
            ->when($idEntitas, fn($q) => $q->where('bbp.id_entitas', $idEntitas))
            ->where('bbp.periode_tahun', $tahun)
            ->where('bbp.periode_bulan', $bulan)
            ->select('bbp.kode_anak', 'c.posisi_normal', 'bbp.saldo_akhir_debet as sd', 'bbp.saldo_akhir_kredit as sk')
            ->get()->keyBy('kode_anak');

        $saldoLalu = DB::table('buku_besar_periode as bbp')
            ->join('master_coa as c', 'c.kode_anak', '=', 'bbp.kode_anak')
            ->when($idEntitas, fn($q) => $q->where('bbp.id_entitas', $idEntitas))
            ->where('bbp.periode_tahun', $prevTahun)
            ->where('bbp.periode_bulan', $prevBulan)
            ->select('bbp.kode_anak', 'c.posisi_normal', 'bbp.saldo_akhir_debet as sd', 'bbp.saldo_akhir_kredit as sk')
            ->get()->keyBy('kode_anak');

        $delta = function ($kodeAnakList) use ($saldoSekarang, $saldoLalu) {
            $selisih = 0;
            foreach ($kodeAnakList as $kode) {
                $now = $saldoSekarang->get($kode);
                $past = $saldoLalu->get($kode);
                
                $valNow = 0;
                if ($now) {
                    $valNow = $now->posisi_normal === 'D' ? ($now->sd - $now->sk) : ($now->sk - $now->sd);
                }
                
                $valPast = 0;
                if ($past) {
                    $valPast = $past->posisi_normal === 'D' ? ($past->sd - $past->sk) : ($past->sk - $past->sd);
                }
                
                $selisih += ($valNow - $valPast);
            }
            return $selisih;
        };

        // Kumpulkan semua COA yang relevan
        $semuaCoa = Coa::all();
        
        $akunKas        = $semuaCoa->filter(fn($c) => str_starts_with($c->kode_anak, '111'))->pluck('kode_anak');
        $akunPiutang    = $semuaCoa->filter(fn($c) => str_starts_with($c->kode_anak, '113'))->pluck('kode_anak');
        $akunPersediaan = $semuaCoa->filter(fn($c) => str_starts_with($c->kode_anak, '112'))->pluck('kode_anak');
        $akunAsetTetap  = $semuaCoa->filter(fn($c) => str_starts_with($c->kode_anak, '12') && $c->posisi_normal === 'D')->pluck('kode_anak');
        $akunAkumPenyusutan = $semuaCoa->filter(fn($c) => str_starts_with($c->kode_anak, '12') && $c->posisi_normal === 'K')->pluck('kode_anak');
        
        $akunHutangLancar = $semuaCoa->filter(fn($c) => str_starts_with($c->kode_anak, '21'))->pluck('kode_anak');
        $akunHutangPanjang = $semuaCoa->filter(fn($c) => str_starts_with($c->kode_anak, '22'))->pluck('kode_anak');
        $akunModal      = $semuaCoa->filter(fn($c) => str_starts_with($c->kode_anak, '3') && !str_contains(strtolower($c->nama_rekening), 'laba'))->pluck('kode_anak');

        // --- ARUS KAS OPERASI ---
        $penyusutan = $delta($akunAkumPenyusutan); // Akumulasi bertambah = kas tidak keluar
        $perubahanPiutang = -$delta($akunPiutang); // Piutang naik = minus kas
        $perubahanPersediaan = -$delta($akunPersediaan);
        $perubahanHutangLancar = $delta($akunHutangLancar); // Hutang naik = plus kas

        $arusKasOperasi = $labaBersih + $penyusutan + $perubahanPiutang + $perubahanPersediaan + $perubahanHutangLancar;

        // --- ARUS KAS INVESTASI ---
        $perubahanAsetTetap = -$delta($akunAsetTetap); // Beli aset (aset naik) = minus kas
        $arusKasInvestasi = $perubahanAsetTetap;

        // --- ARUS KAS PENDANAAN ---
        $perubahanHutangPanjang = $delta($akunHutangPanjang);
        $perubahanModal = $delta($akunModal);
        $arusKasPendanaan = $perubahanHutangPanjang + $perubahanModal;

        // --- TOTAL KENAIKAN / PENURUNAN KAS ---
        $netCashFlow = $arusKasOperasi + $arusKasInvestasi + $arusKasPendanaan;

        // --- SALDO KAS ---
        $kasAwal = 0;
        foreach ($akunKas as $kode) {
            $past = $saldoLalu->get($kode);
            if ($past) {
                $kasAwal += ($past->posisi_normal === 'D' ? ($past->sd - $past->sk) : ($past->sk - $past->sd));
            }
        }
        $kasAkhir = $kasAwal + $netCashFlow;

        return view('akuntansi.laporan.arus-kas', compact(
            'tahun', 'bulan',
            'labaBersih', 'penyusutan', 'perubahanPiutang', 'perubahanPersediaan', 'perubahanHutangLancar',
            'arusKasOperasi',
            'perubahanAsetTetap', 'arusKasInvestasi',
            'perubahanHutangPanjang', 'perubahanModal', 'arusKasPendanaan',
            'netCashFlow', 'kasAwal', 'kasAkhir'
        ));
    }
}




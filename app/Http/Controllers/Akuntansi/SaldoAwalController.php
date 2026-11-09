<?php

namespace App\Http\Controllers\Akuntansi;

use App\Http\Controllers\Controller;
use App\Models\Akuntansi\Coa;
use App\Services\Finance\JurnalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * SaldoAwalController — Input saldo awal (jurnal pembuka) untuk entitas baru.
 *
 * ⚠️  PIVOT NOTE (baca 05-solusi-pivot-ekonomi-kreatif.md):
 *     Fitur ini berlaku untuk SEMUA jenis_unit: koperasi, BUMDes, kelompok tani,
 *     kelompok pengrajin, UMKM. Saldo awal adalah konsep akuntansi dasar — bukan
 *     spesifik koperasi. Jangan hardcode logika SHU atau akun 331-334 di sini.
 *
 * Alur:
 *   1. GET  /akuntansi/saldo-awal  → form input + tabel saldo yang sudah ada
 *   2. POST /akuntansi/saldo-awal  → simpan baris saldo via JurnalService (kode JOP)
 *
 * Jurnal yang dibuat:
 *   - kode_transaksi = 'JOP' (Jurnal Pembuka)
 *   - jenis_jurnal   = 'SALDO_AWAL'
 *   - tanggal_jurnal = 1 Januari tahun buku
 *
 * Jika sudah ada JOP untuk tahun yang sama, jurnal lama dibalik otomatis
 * sebelum jurnal baru diposting (idempoten — aman diulang).
 */
class SaldoAwalController extends Controller
{
    public function __construct(private JurnalService $jurnalService) {}

    // =========================================================================
    // GET /akuntansi/saldo-awal
    // =========================================================================

    public function index(Request $request): View
    {
        $idEntitas = app('entitas_aktif');
        $tahun     = (int) $request->query('tahun', date('Y'));

        $bankMap = Coa::bankMapForEntitas($idEntitas);

        // Semua akun leaf aktif untuk dropdown pilih akun
        $akunList = Coa::where('is_transaction', 'T')
            ->where('is_active', true)
            ->orderBy('urutan_laporan')
            ->orderBy('kode_anak')
            ->get(['kode_anak', 'nama_rekening', 'kelompok', 'posisi_normal'])
            ->map(function ($akun) use ($bankMap) {
                if (isset($bankMap[$akun->kode_anak])) {
                    $akun->nama_rekening = $bankMap[$akun->kode_anak]->nama;
                }
                return $akun;
            });

        $saldoTersimpan  = collect();
        $sudahAdaJurnal  = false;
        $idJurnalPembuka = null;
        $totalDebet      = 0;
        $totalKredit     = 0;
        $isBalance       = false;

        if ($idEntitas) {
            $jurnalPembuka = DB::table('jurnal_header')
                ->where('id_entitas', $idEntitas)
                ->where('kode_transaksi', 'JOP')
                ->where('periode_tahun', $tahun)
                ->where('status', 'POSTED')
                ->first();

            if ($jurnalPembuka) {
                $sudahAdaJurnal  = true;
                $idJurnalPembuka = $jurnalPembuka->id_jurnal;

                $saldoTersimpan = DB::table('jurnal_detail as jd')
                    ->join('master_coa as c', 'c.kode_anak', '=', 'jd.kode_anak')
                    ->where('jd.id_jurnal', $jurnalPembuka->id_jurnal)
                    ->orderBy('c.urutan_laporan')
                    ->orderBy('c.kode_anak')
                    ->select([
                        'jd.id_detail',
                        'jd.kode_anak',
                        'c.nama_rekening',
                        'c.kelompok',
                        'c.posisi_normal',
                        'jd.debet',
                        'jd.kredit',
                    ])
                    ->get()
                    ->map(function ($row) use ($bankMap) {
                        if (isset($bankMap[$row->kode_anak])) {
                            $row->nama_rekening = $bankMap[$row->kode_anak]->nama;
                        }
                        $row->saldo = ($row->posisi_normal === 'D')
                            ? (float) $row->debet - (float) $row->kredit
                            : (float) $row->kredit - (float) $row->debet;
                        return $row;
                    });

                $totalDebet  = $saldoTersimpan->sum('debet');
                $totalKredit = $saldoTersimpan->sum('kredit');
                $isBalance   = $saldoTersimpan->count() > 0
                    && abs($totalDebet - $totalKredit) < 0.01;
            }
        }

        return view('akuntansi.saldo-awal', compact(
            'akunList', 'tahun', 'saldoTersimpan',
            'sudahAdaJurnal', 'idJurnalPembuka',
            'totalDebet', 'totalKredit', 'isBalance',
            'idEntitas'
        ));
    }

    // =========================================================================
    // POST /akuntansi/saldo-awal
    // Simpan / ganti seluruh saldo awal untuk tahun yang dipilih.
    // =========================================================================

    public function store(Request $request): RedirectResponse
    {
        $idEntitas = app('entitas_aktif');
        if (! $idEntitas) {
            return back()->with('error', 'Pilih Entitas terlebih dahulu sebelum input saldo awal.');
        }

        $validated = $request->validate([
            'tahun'             => ['required', 'integer', 'min:2000', 'max:2099'],
            'baris'             => ['required', 'array', 'min:1'],
            'baris.*.kode_anak' => ['required', 'string', 'exists:master_coa,kode_anak'],
            'baris.*.posisi'    => ['required', 'in:D,K'],
            'baris.*.nilai'     => ['required', 'numeric', 'min:0'],
        ], [
            'baris.required'           => 'Minimal 1 baris akun harus diisi.',
            'baris.*.kode_anak.exists' => 'Kode akun tidak ditemukan di COA.',
            'baris.*.nilai.min'        => 'Nilai saldo tidak boleh negatif.',
        ]);

        $tahun = (int) $validated['tahun'];

        // Filter baris yang bernilai > 0
        $baris = [];
        foreach ($validated['baris'] as $b) {
            if ((float) $b['nilai'] == 0) {
                continue;
            }
            $baris[] = [
                'kode_anak'  => $b['kode_anak'],
                'posisi'     => $b['posisi'],
                'nilai'      => (float) $b['nilai'],
                'id_pihak'   => null,
                'keterangan' => 'Saldo Awal',
            ];
        }

        if (empty($baris)) {
            return back()->withInput()->with('error', 'Tidak ada baris saldo yang bernilai > 0.');
        }

        // Validasi balance sebelum posting (sebelum menyentuh jurnal lama)
        $totalD = array_sum(array_map(fn($b) => $b['posisi'] === 'D' ? $b['nilai'] : 0, $baris));
        $totalK = array_sum(array_map(fn($b) => $b['posisi'] === 'K' ? $b['nilai'] : 0, $baris));

        if (abs($totalD - $totalK) > 0.01) {
            return back()
                ->withInput()
                ->with('error', sprintf(
                    'Jurnal tidak balance: Total Debet Rp %s ≠ Total Kredit Rp %s (selisih Rp %s). '
                    . 'Pastikan jumlah sisi Debet sama dengan sisi Kredit.',
                    number_format($totalD, 0, ',', '.'),
                    number_format($totalK, 0, ',', '.'),
                    number_format(abs($totalD - $totalK), 0, ',', '.')
                ));
        }

        try {
            // Balik JOP lama + posting JOP baru = SATU transaksi.
            // Jika posting baru gagal (atau koneksi putus), pembalikan JOP lama
            // ikut dibatalkan -> saldo awal tidak pernah "hilang" di tengah jalan.
            DB::transaction(function () use ($idEntitas, $tahun, $baris) {
                $jopAktif = $this->queryJopAktif($idEntitas, $tahun)
                    ->lockForUpdate()
                    ->pluck('id_jurnal');

                foreach ($jopAktif as $idJurnalLama) {
                    $this->jurnalService->balik(
                        (int) $idJurnalLama,
                        'Penggantian saldo awal tahun ' . $tahun
                    );
                }

                $this->jurnalService->postingManual(
                    header: [
                        'tanggal_jurnal' => $tahun . '-01-01',
                        'jenis_jurnal'   => 'SALDO_AWAL',
                        'kode_transaksi' => 'JOP',
                        'keterangan'     => 'Jurnal Pembuka — Saldo Awal Tahun ' . $tahun,
                        'source_type'    => null,
                        'source_id'      => null,
                    ],
                    baris: $baris
                );
            });

            return redirect()
                ->route('akuntansi.saldo-awal.index', ['tahun' => $tahun])
                ->with('success', '✅ Saldo awal tahun ' . $tahun . ' berhasil disimpan.');

        } catch (\App\Exceptions\Finance\JurnalTidakBalanceException $e) {
            return back()->withInput()->with('error', 'Jurnal tidak balance: ' . $e->getMessage());
        } catch (\App\Exceptions\Finance\PeriodeTutupException $e) {
            return back()->withInput()->with('error', 'Periode ditutup, saldo awal tidak dapat diposting: ' . $e->getMessage());
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', 'Gagal menyimpan saldo awal: ' . $e->getMessage());
        }
    }

    /**
     * JOP (jurnal pembuka) tahun ini yang masih berlaku: POSTED, jenis SALDO_AWAL,
     * dan belum pernah dibalik. Jurnal pembalik (jenis PEMBALIK) otomatis tidak ikut.
     */
    private function queryJopAktif(int $idEntitas, int $tahun)
    {
        return DB::table('jurnal_header as jh')
            ->where('jh.id_entitas', $idEntitas)
            ->where('jh.kode_transaksi', 'JOP')
            ->where('jh.jenis_jurnal', 'SALDO_AWAL')
            ->where('jh.periode_tahun', $tahun)
            ->where('jh.status', 'POSTED')
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('jurnal_header as rev')
                    ->whereColumn('rev.id_jurnal_asal', 'jh.id_jurnal');
            });
    }
}

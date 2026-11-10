<?php

namespace App\Http\Controllers\Survei;

use App\Http\Controllers\Controller;
use App\Models\Survei\SesiSurvei;
use App\Models\Survei\ProduksiProdusen;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class PublicSurveiProdusenController extends Controller
{
    /**
     * Daftar komoditas yang disarankan — sesuai sistemsurvey.txt poin 4.
     * Kelompok Tani: hanya komoditas pangan pokok sehari-hari yang disebutkan di .txt.
     * Ekraf: .txt tidak menyebut item spesifik (hanya "Kreativitas Pangan/Benda Desa"),
     *         sehingga tidak ada chip saran — responden isi sendiri.
     */
    private array $saranKomoditas = [
        'Pertanian'  => ['Beras', 'Cabai', 'Bawang Merah', 'Bawang Putih', 'Kedelai'],
        'Perkebunan' => ['Kopi', 'Gula'],
        'Peternakan' => ['Ayam', 'Telur Ayam'],
        'Kelautan'   => ['Garam'],
        'Pangan'     => [],   // Ekraf Pangan: kreativitas bebas, tidak ada daftar tetap di .txt
        'Non Pangan' => [],   // Ekraf Non Pangan: kreativitas bebas, tidak ada daftar tetap di .txt
    ];

    /** Satuan umum */
    private array $satuanList = ['kg', 'ton', 'kwintal', 'gram', 'liter', 'buah', 'ikat', 'pack', 'karung', 'lusin'];

    /**
     * Frekuensi panen per tahun (nilai tetap, tanpa tabel database).
     * Sumber data: alursurvei.txt — frekuensi = 365 hari ÷ durasi satu siklus lengkap (tanam/pelihara s.d. panen).
     * Bila acuan berupa rentang (mis. ±3–4 siklus), dipakai batas bawah (siklus lengkap yang pasti tercapai).
     * Komoditas yang di sumbernya "belum dihitung" / bukan siklus tanam-panen sengaja TIDAK dimasukkan,
     * sehingga tidak diisi otomatis.
     */
    private array $frekuensiPanen = [
        'padi sawah'         => ['item' => 'Padi sawah',         'frekuensi' => 2, 'rentang' => '±2',    'durasi' => '135–145 hari', 'sumber' => 'Kementan'],
        'jagung pipilan'     => ['item' => 'Jagung pipilan',     'frekuensi' => 3, 'rentang' => '±3–4',  'durasi' => '80–103 hari',  'sumber' => 'BRMP'],
        'kedelai'            => ['item' => 'Kedelai',            'frekuensi' => 4, 'rentang' => '±4–5',  'durasi' => '70–90 hari',   'sumber' => 'Kementan'],
        'kacang tanah'       => ['item' => 'Kacang tanah',       'frekuensi' => 3, 'rentang' => '±3–4',  'durasi' => '90–105 hari',  'sumber' => 'BRMP'],
        'bawang merah'       => ['item' => 'Bawang merah',       'frekuensi' => 3, 'rentang' => '±3–5',  'durasi' => '65–100 hari',  'sumber' => 'BRMP'],
        'bayam'              => ['item' => 'Bayam',              'frekuensi' => 9, 'rentang' => '±9–12', 'durasi' => '25–40 hari',   'sumber' => 'Ditjen Hortikultura; BRMP'],
        'cabai rawit'        => ['item' => 'Cabai rawit',        'frekuensi' => 3, 'rentang' => '±3–4',  'durasi' => '75–120 hari',  'sumber' => 'Kementan'],
        'kangkung'           => ['item' => 'Kangkung',           'frekuensi' => 8, 'rentang' => '±8–12', 'durasi' => '27–45 hari',   'sumber' => 'Ditjen Hortikultura; BBPP'],
        'sawi'               => ['item' => 'Sawi',               'frekuensi' => 6, 'rentang' => '±6',    'durasi' => '±60 hari',     'sumber' => 'BBPP Kupang'],
        'semangka'           => ['item' => 'Semangka',           'frekuensi' => 5, 'rentang' => '±5–6',  'durasi' => '60–70 hari',   'sumber' => 'Kementan'],
        'jamur tiram'        => ['item' => 'Jamur tiram',        'frekuensi' => 3, 'rentang' => '±3–4',  'durasi' => '90–120 hari (masa produktif baglog)', 'sumber' => 'Kementan'],
        'daging sapi'        => ['item' => 'Daging sapi',        'frekuensi' => 2, 'rentang' => '±2–3',  'durasi' => '120–180 hari (khusus penggemukan)', 'sumber' => 'Permentan No. 46 Tahun 2015'],
        'daging ayam broiler' => ['item' => 'Daging ayam broiler', 'frekuensi' => 8, 'rentang' => '±8–10', 'durasi' => '35–42 hari (belum termasuk jeda sanitasi kandang)', 'sumber' => 'Dinas Peternakan Kab. Lebak'],
    ];

    /** Nama lain yang dikenali → kunci pada $frekuensiPanen. */
    private array $aliasFrekuensi = [
        'padi'           => 'padi sawah',
        'beras'          => 'padi sawah',
        'jagung'         => 'jagung pipilan',
        'sapi'           => 'daging sapi',
        'sapi potong'    => 'daging sapi',
        'ayam broiler'   => 'daging ayam broiler',
        'broiler'        => 'daging ayam broiler',
    ];

    /** Peta nama (kanonik + alias) → data frekuensi, untuk dipakai di JavaScript. */
    private function petaFrekuensi(): array
    {
        $peta = $this->frekuensiPanen;
        foreach ($this->aliasFrekuensi as $alias => $kunci) {
            $peta[$alias] = $this->frekuensiPanen[$kunci];
        }
        return $peta;
    }

    /**
     * Tampilkan halaman survei publik Produsen.
     */
    public function show(string $token): View
    {
        $sesi = SesiSurvei::where('token_produsen', $token)
            ->with(['wilayah'])
            ->firstOrFail();

        // Validasi periode: hanya boleh diakses bulan aktif
        if ($sesi->bulan != (int) date('n') || $sesi->tahun != (int) date('Y')) {
            abort(403, 'Tautan survei Produsen ini sudah kedaluwarsa. Survei hanya berlaku untuk periode '
                . $sesi->bulan . '/' . $sesi->tahun . '.');
        }

        $bulans = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];

        return view('survei.public.produsen', [
            'sesi'           => $sesi,
            'token'          => $token,
            'bulans'         => $bulans,
            'saranKomoditas' => $this->saranKomoditas,
            'satuanList'     => $this->satuanList,
            'frekuensiPanen' => $this->petaFrekuensi(),
        ]);
    }

    /**
     * Simpan batch komoditas dalam satu POST.
     * Payload: nama_responden + komoditas[] (array of items)
     */
    public function store(Request $request, string $token): RedirectResponse
    {
        $sesi = SesiSurvei::where('token_produsen', $token)->firstOrFail();

        if ($sesi->bulan != (int) date('n') || $sesi->tahun != (int) date('Y')) {
            abort(403, 'Tautan survei ini sudah kedaluwarsa.');
        }

        $request->validate([
            'nama_responden'    => 'required|string|max:100',
            'komoditas'         => 'required|array|min:1',
            'komoditas.*.nama_komoditas'  => 'required|string|max:100',
            'komoditas.*.kategori'        => 'required|in:Kelompok Tani,Ekraf',
            'komoditas.*.sub_kategori'    => 'nullable|string|max:50',
            'komoditas.*.jumlah_produksi' => 'nullable|numeric|min:0',
            'komoditas.*.satuan'          => 'nullable|string|max:30',
            'komoditas.*.harga'           => 'nullable|numeric|min:0',
            'komoditas.*.tipe_harga'      => 'nullable|in:Satuan,Total',
            'komoditas.*.bulan_produksi'  => 'required|integer|min:1|max:12',
            'komoditas.*.keterangan'      => 'nullable|string|max:500',
        ], [
            'komoditas.required'                   => 'Harap tambah minimal 1 komoditas ke daftar.',
            'komoditas.*.nama_komoditas.required'  => 'Nama komoditas tidak boleh kosong.',
            'komoditas.*.bulan_produksi.required'  => 'Bulan produksi wajib dipilih.',
        ]);

        // Bersihkan nama responden dari titik (voice-to-text)
        $namaResponden = ucwords(strtolower(trim(str_replace('.', '', $request->nama_responden))));

        $rows = [];
        $now  = now();

        foreach ($request->komoditas as $item) {
            $rows[] = [
                'id_sesi'         => $sesi->id,
                'id_wilayah'      => $sesi->id_wilayah,
                'nama_responden'  => $namaResponden,
                'kategori'        => $item['kategori'],
                'sub_kategori'    => $item['sub_kategori'] ?? null,
                'nama_komoditas'  => ucwords(strtolower(trim(str_replace('.', '', $item['nama_komoditas'])))),
                'jumlah_produksi' => filled($item['jumlah_produksi']) ? $item['jumlah_produksi'] : null,
                'satuan'          => $item['satuan'] ?? null,
                'harga'           => filled($item['harga'] ?? null) ? $item['harga'] : null,
                'tipe_harga'      => filled($item['harga'] ?? null) ? ($item['tipe_harga'] ?? 'Satuan') : 'Satuan',
                'bulan_produksi'  => (int) $item['bulan_produksi'],
                'tahun_produksi'  => $sesi->tahun,
                'keterangan'      => filled($item['keterangan'] ?? null)
                    ? trim(str_replace('.', '', $item['keterangan']))
                    : null,
                'created_at'      => $now,
                'updated_at'      => $now,
            ];
        }

        ProduksiProdusen::insert($rows);

        $jumlah = count($rows);
        return redirect()->back()->with('success',
            "✅ Terima kasih, {$namaResponden}! {$jumlah} komoditas berhasil dicatat."
        );
    }

    /**
     * Hapus satu entri produksi (dari admin, bukan publik — tapi sediakan jika diperlukan).
     */
    public function destroy(string $token, int $id): RedirectResponse
    {
        $sesi = SesiSurvei::where('token_produsen', $token)->firstOrFail();

        $item = ProduksiProdusen::where('id', $id)->where('id_sesi', $sesi->id)->firstOrFail();
        $item->delete();

        return redirect()->back()->with('success', 'Data produksi berhasil dihapus.');
    }
}

<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Entitas;
use App\Models\Tenant\Wilayah;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class EntitasController extends Controller
{
    protected string $title = 'Daftar Entitas / Koperasi Desa';
    protected string $routeBase = 'master.entitas';

    public const JENIS_LIST = [
        'koperasi' => 'Koperasi Desa Merah Putih (KDMP)',
        'bumdes' => 'BUMDes / Badan Usaha Milik Desa',
        'poktan' => 'Kelompok Tani / Gabungan Poktan',
        'umkm' => 'UMKM / Unit Usaha Desa',
    ];

    public function index(Request $request): View
    {
        $query = Entitas::with('wilayah')->withCount('pengguna');

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        if ($search = trim((string) $request->query('q', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('kode_entitas', 'like', "%{$search}%")
                  ->orWhere('nama_entitas', 'like', "%{$search}%")
                  ->orWhere('badan_hukum_no', 'like', "%{$search}%");
            });
        }

        $items = $query->orderBy('kode_entitas')->paginate(15)->withQueryString();

        return view('master.entitas.index', [
            'title' => $this->title,
            'routeBase' => $this->routeBase,
            'items' => $items,
            'jenisList' => self::JENIS_LIST,
        ]);
    }

    public function create(): View
    {
        return view('master.entitas.form', [
            'title' => 'Tambah Entitas Baru',
            'routeBase' => $this->routeBase,
            'mode' => 'create',
            'item' => new Entitas([
                'jenis_entitas' => 'koperasi',
                'tahun_buku_awal' => (int) date('Y'),
                'status' => 'APPROVED',
                'is_active' => true,
            ]),
            'wilayahList' => Wilayah::orderBy('nama')->get(),
            'jenisList' => self::JENIS_LIST,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->merge(['kode_entitas' => strtoupper(trim((string) $request->input('kode_entitas')))]);
        $validated = $this->validatePayload($request);

        DB::transaction(function () use ($validated, $request) {
            $idWilayah = $validated['id_wilayah'] ?? null;
            if (! $idWilayah && ! empty($validated['nama_wilayah_baru'])) {
                $wilayah = Wilayah::firstOrCreate(
                    ['nama' => trim($validated['nama_wilayah_baru'])],
                    ['tingkat' => 'desa']
                );
                $idWilayah = $wilayah->id;
            }

            $entitas = Entitas::create([
                'kode_entitas' => $validated['kode_entitas'],
                'nama_entitas' => trim($validated['nama_entitas']),
                'jenis_entitas' => $validated['jenis_entitas'],
                'id_wilayah' => $idWilayah,
                'badan_hukum_no' => $validated['badan_hukum_no'] ?: null,
                'tgl_berdiri' => $validated['tgl_berdiri'] ?: null,
                'tahun_buku_awal' => (int) $validated['tahun_buku_awal'],
                'status' => $validated['status'],
                'is_active' => $request->boolean('is_active', true),
            ]);

            if ($entitas->status === 'APPROVED') {
                self::provisionDefaultMasterData($entitas);
            }
        });

        return redirect()->route('master.entitas.index')
            ->with('success', "Entitas \"{$validated['nama_entitas']}\" berhasil ditambahkan beserta inisialisasi master dasarnya.");
    }

    public function edit(int|string $id): View
    {
        $item = Entitas::with('wilayah')->findOrFail($id);

        return view('master.entitas.form', [
            'title' => 'Ubah Entitas',
            'routeBase' => $this->routeBase,
            'mode' => 'edit',
            'item' => $item,
            'wilayahList' => Wilayah::orderBy('nama')->get(),
            'jenisList' => self::JENIS_LIST,
        ]);
    }

    public function update(Request $request, int|string $id): RedirectResponse
    {
        $item = Entitas::findOrFail($id);
        $request->merge(['kode_entitas' => strtoupper(trim((string) $request->input('kode_entitas')))]);
        $validated = $this->validatePayload($request, $item->id_entitas);

        DB::transaction(function () use ($item, $validated, $request) {
            $idWilayah = $validated['id_wilayah'] ?: $item->id_wilayah;
            if (! empty($validated['nama_wilayah_baru'])) {
                $wilayah = Wilayah::firstOrCreate(
                    ['nama' => trim($validated['nama_wilayah_baru'])],
                    ['tingkat' => 'desa']
                );
                $idWilayah = $wilayah->id;
            }

            $item->update([
                'kode_entitas' => $validated['kode_entitas'],
                'nama_entitas' => trim($validated['nama_entitas']),
                'jenis_entitas' => $validated['jenis_entitas'],
                'id_wilayah' => $idWilayah,
                'badan_hukum_no' => $validated['badan_hukum_no'] ?: null,
                'tgl_berdiri' => $validated['tgl_berdiri'] ?: null,
                'tahun_buku_awal' => (int) $validated['tahun_buku_awal'],
                'status' => $validated['status'],
                'is_active' => $request->boolean('is_active', true),
            ]);

            if ($item->status === 'APPROVED') {
                self::provisionDefaultMasterData($item);
            }
        });

        return redirect()->route('master.entitas.index')
            ->with('success', "Entitas \"{$validated['nama_entitas']}\" berhasil diperbarui.");
    }

    public function destroy(int|string $id): RedirectResponse
    {
        $item = Entitas::findOrFail($id);

        $hasJurnal = DB::table('jurnal_header')->where('id_entitas', $item->id_entitas)->exists();
        $hasPembelian = DB::table('pembelian')->where('id_entitas', $item->id_entitas)->exists();
        $hasPenjualan = DB::table('penjualan')->where('id_entitas', $item->id_entitas)->exists();

        if ($hasJurnal || $hasPembelian || $hasPenjualan) {
            return back()->with('error', "Entitas \"{$item->nama_entitas}\" sudah memiliki data transaksi/jurnal dan tidak dapat dihapus. Nonaktifkan saja jika diperlukan.");
        }

        $nama = $item->nama_entitas;
        $item->delete();

        return redirect()->route('master.entitas.index')
            ->with('success', "Entitas \"{$nama}\" berhasil dihapus.");
    }

    /**
     * Inisialisasi data dasar wajib (Gudang, Kas & Bank, Periode 1..13, dan Pihak Entitas Mitra)
     * untuk entitas yang baru disetujui agar langsung siap bertransaksi secara terpisah.
     */
    public static function provisionDefaultMasterData(Entitas $entitas): void
    {
        $idEntitas = $entitas->id_entitas;
        $tahun = (int) ($entitas->tahun_buku_awal ?: date('Y'));

        // 1. Gudang Utama
        DB::table('gudang')->updateOrInsert(
            ['id_entitas' => $idEntitas, 'kode_gudang' => 'UTAMA'],
            [
                'nama_gudang' => 'Gudang Utama ' . $entitas->nama_entitas,
                'is_active' => true,
            ]
        );

        // 2. Kas & Bank Default
        $hasKas = DB::table('master_kas_bank')
            ->where('id_entitas', $idEntitas)
            ->where('jenis', 'kas')
            ->exists();
        if (! $hasKas) {
            DB::table('master_kas_bank')->insert([
                'id_entitas' => $idEntitas,
                'jenis' => 'kas',
                'nama' => 'Kas Utama',
                'kode_akun' => '1111',
                'is_default' => true,
                'is_active' => true,
            ]);
        }

        $hasBank = DB::table('master_kas_bank')
            ->where('id_entitas', $idEntitas)
            ->where('jenis', 'bank')
            ->exists();
        if (! $hasBank) {
            DB::table('master_kas_bank')->insert([
                'id_entitas' => $idEntitas,
                'jenis' => 'bank',
                'nama' => 'Bank A',
                'kode_akun' => '11121',
                'is_default' => true,
                'is_active' => true,
            ]);
        }

        // 3. Periode Akuntansi (Bulan 1 s/d 13 OPEN)
        for ($bulan = 1; $bulan <= 13; $bulan++) {
            DB::table('periode_akuntansi')->updateOrInsert(
                ['id_entitas' => $idEntitas, 'tahun' => $tahun, 'bulan' => $bulan],
                ['status' => 'OPEN']
            );
        }

        // 4. Relasi Pihak 'entitas_lain' timbal-balik dengan entitas aktif lainnya (untuk Konsinyasi M7)
        $mitraList = Entitas::where('id_entitas', '!=', $idEntitas)
            ->where('is_active', true)
            ->get();

        foreach ($mitraList as $mitra) {
            DB::table('master_pihak')->updateOrInsert(
                ['id_entitas' => $idEntitas, 'id_entitas_mitra' => $mitra->id_entitas],
                [
                    'jenis_pihak' => 'entitas_lain',
                    'nama' => $mitra->nama_entitas,
                    'is_anggota' => false,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );

            DB::table('master_pihak')->updateOrInsert(
                ['id_entitas' => $mitra->id_entitas, 'id_entitas_mitra' => $idEntitas],
                [
                    'jenis_pihak' => 'entitas_lain',
                    'nama' => $entitas->nama_entitas,
                    'is_anggota' => false,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }

    protected function validatePayload(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'kode_entitas' => [
                'required',
                'string',
                'max:30',
                Rule::unique('entitas', 'kode_entitas')->ignore($ignoreId, 'id_entitas'),
            ],
            'nama_entitas' => ['required', 'string', 'max:150'],
            'jenis_entitas' => ['required', 'string', 'max:50'],
            'id_wilayah' => ['nullable', 'exists:wilayah,id'],
            'nama_wilayah_baru' => ['nullable', 'string', 'max:150'],
            'badan_hukum_no' => ['nullable', 'string', 'max:100'],
            'tgl_berdiri' => ['nullable', 'date'],
            'tahun_buku_awal' => ['required', 'integer', 'min:2020', 'max:2100'],
            'status' => ['required', Rule::in(['PENDING', 'APPROVED', 'REJECTED'])],
        ]);
    }
}

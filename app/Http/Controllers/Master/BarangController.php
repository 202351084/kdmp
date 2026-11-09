<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Concerns\ResolvesEntitasAktif;
use App\Http\Controllers\Controller;
use App\Models\Master\Barang;
use App\Models\Master\Komoditas;
use App\Models\Master\Satuan;
use App\Models\Master\UnitUsaha;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BarangController extends Controller
{
    use ResolvesEntitasAktif;

    protected string $title = 'Master Barang & Harga Jual';
    protected string $routeBase = 'master.barang';

    public function index(Request $request): View
    {
        $idEntitas = $this->entitasAktifId();
        $entitas = $this->entitasAktif();

        $query = Barang::with(['komoditas', 'unitUsaha', 'satuanDasar', 'konversiSatuan.satuan'])
            ->forEntitas($idEntitas);

        if ($idUnitUsaha = $request->query('id_unit_usaha')) {
            $query->where('id_unit_usaha', $idUnitUsaha);
        }

        if ($idKomoditas = $request->query('id_komoditas')) {
            $query->where('id_komoditas', $idKomoditas);
        }

        if ($search = trim((string) $request->query('q', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('kode_barang', 'like', "%{$search}%")
                  ->orWhere('nama_barang', 'like', "%{$search}%");
            });
        }

        $items = $query->orderBy('nama_barang')->paginate(15)->withQueryString();

        $settingMap = [];
        if ($idEntitas && $items->isNotEmpty()) {
            $settingMap = DB::table('barang_per_koperasi')
                ->where('id_entitas', $idEntitas)
                ->whereIn('id_barang', $items->pluck('id_barang'))
                ->get()
                ->keyBy('id_barang')
                ->all();
        }

        return view('master.barang.index', [
            'title' => $this->title,
            'routeBase' => $this->routeBase,
            'items' => $items,
            'settingMap' => $settingMap,
            'unitUsahaList' => UnitUsaha::orderBy('nama_unit_usaha')->get(),
            'komoditasList' => Komoditas::forEntitas($idEntitas)->orderBy('nama')->get(),
            'idEntitas' => $idEntitas,
            'entitas' => $entitas,
        ]);
    }

    public function create(): View
    {
        $idEntitas = $this->requireEntitasAktifId();

        return view('master.barang.form', [
            'title' => 'Tambah Barang Baru',
            'routeBase' => $this->routeBase,
            'mode' => 'create',
            'item' => new Barang(['is_active' => true]),
            'setting' => (object) [
                'harga_jual_standar' => 0,
                'stok_minimum' => 10,
                'stok_maksimum' => 1000,
                'is_dijual' => true,
            ],
            'konversiTambahan' => [],
            'komoditasList' => Komoditas::forEntitas($idEntitas)->where('is_active', true)->orderBy('nama')->get(),
            'satuanList' => Satuan::forEntitas($idEntitas)->where('is_active', true)->orderBy('kode_satuan')->get(),
            'unitUsahaList' => UnitUsaha::orderBy('nama_unit_usaha')->get(),
            'entitas' => $this->entitasAktif(),
            'idEntitas' => $idEntitas,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $idEntitas = $this->requireEntitasAktifId();
        $request->merge(['kode_barang' => strtoupper(trim((string) $request->input('kode_barang')))]);
        $validated = $this->validatePayload($request, $idEntitas);

        DB::transaction(function () use ($idEntitas, $validated, $request) {
            $payload = [
                'kode_barang' => $validated['kode_barang'],
                'nama_barang' => trim($validated['nama_barang']),
                'id_komoditas' => $validated['id_komoditas'] ?: null,
                'id_unit_usaha' => $validated['id_unit_usaha'],
                'id_satuan_dasar' => $validated['id_satuan_dasar'],
                'is_active' => $request->boolean('is_active', true),
            ];

            if (Barang::hasEntitasColumn()) {
                $payload['id_entitas'] = $idEntitas;
            }

            $barang = Barang::create($payload);

            // Simpan pengaturan niaga & stok khusus entitas ini
            DB::table('barang_per_koperasi')->updateOrInsert(
                ['id_entitas' => $idEntitas, 'id_barang' => $barang->id_barang],
                [
                    'harga_jual_standar' => (float) ($validated['harga_jual_standar'] ?? 0),
                    'stok_minimum' => (float) ($validated['stok_minimum'] ?? 0),
                    'stok_maksimum' => (float) ($validated['stok_maksimum'] ?? 1000),
                    'is_dijual' => $request->boolean('is_dijual', true),
                ]
            );

            $this->syncKonversiSatuan($barang, $request->input('konversi', []));
        });

        return redirect()->route('master.barang.index')
            ->with('success', "Barang \"{$validated['nama_barang']}\" berhasil ditambahkan ke entitas Anda.");
    }

    public function edit(int|string $id): View
    {
        $idEntitas = $this->requireEntitasAktifId();
        $item = Barang::with('konversiSatuan')->forEntitas($idEntitas)->findOrFail($id);

        $setting = DB::table('barang_per_koperasi')
            ->where('id_entitas', $idEntitas)
            ->where('id_barang', $item->id_barang)
            ->first() ?? (object) [
                'harga_jual_standar' => 0,
                'stok_minimum' => 0,
                'stok_maksimum' => 1000,
                'is_dijual' => true,
            ];

        $konversiTambahan = $item->konversiSatuan
            ->where('id_satuan', '!=', $item->id_satuan_dasar)
            ->values()
            ->map(fn ($k) => [
                'id_satuan' => $k->id_satuan,
                'faktor_ke_dasar' => (float) $k->faktor_ke_dasar,
            ])
            ->all();

        return view('master.barang.form', [
            'title' => 'Ubah Barang & Pengaturan Harga',
            'routeBase' => $this->routeBase,
            'mode' => 'edit',
            'item' => $item,
            'setting' => $setting,
            'konversiTambahan' => $konversiTambahan,
            'komoditasList' => Komoditas::forEntitas($idEntitas)->orderBy('nama')->get(),
            'satuanList' => Satuan::forEntitas($idEntitas)->orderBy('kode_satuan')->get(),
            'unitUsahaList' => UnitUsaha::orderBy('nama_unit_usaha')->get(),
            'entitas' => $this->entitasAktif(),
            'idEntitas' => $idEntitas,
        ]);
    }

    public function update(Request $request, int|string $id): RedirectResponse
    {
        $idEntitas = $this->requireEntitasAktifId();
        $item = Barang::forEntitas($idEntitas)->findOrFail($id);

        $request->merge(['kode_barang' => strtoupper(trim((string) $request->input('kode_barang')))]);
        $validated = $this->validatePayload($request, $idEntitas, $item->id_barang);

        DB::transaction(function () use ($idEntitas, $item, $validated, $request) {
            // Cek apakah barang ini hanya dipakai oleh entitas aktif atau milik entitas aktif
            $sharedOtherEntitas = DB::table('barang_per_koperasi')
                ->where('id_barang', $item->id_barang)
                ->where('id_entitas', '!=', $idEntitas)
                ->exists();

            if (! $sharedOtherEntitas || (Barang::hasEntitasColumn() && (int) $item->id_entitas === $idEntitas)) {
                $item->update([
                    'kode_barang' => $validated['kode_barang'],
                    'nama_barang' => trim($validated['nama_barang']),
                    'id_komoditas' => $validated['id_komoditas'] ?: null,
                    'id_unit_usaha' => $validated['id_unit_usaha'],
                    'id_satuan_dasar' => $validated['id_satuan_dasar'],
                    'is_active' => $request->boolean('is_active', true),
                ]);

                $this->syncKonversiSatuan($item, $request->input('konversi', []));
            }

            // Simpan pengaturan harga jual & stok spesifik untuk entitas ini
            DB::table('barang_per_koperasi')->updateOrInsert(
                ['id_entitas' => $idEntitas, 'id_barang' => $item->id_barang],
                [
                    'harga_jual_standar' => (float) ($validated['harga_jual_standar'] ?? 0),
                    'stok_minimum' => (float) ($validated['stok_minimum'] ?? 0),
                    'stok_maksimum' => (float) ($validated['stok_maksimum'] ?? 1000),
                    'is_dijual' => $request->boolean('is_dijual', true),
                ]
            );
        });

        return redirect()->route('master.barang.index')
            ->with('success', "Data barang \"{$validated['nama_barang']}\" berhasil diperbarui.");
    }

    public function destroy(int|string $id): RedirectResponse
    {
        $idEntitas = $this->requireEntitasAktifId();
        $item = Barang::forEntitas($idEntitas)->findOrFail($id);

        $usedInPembelian = DB::table('detail_pembelian as d')
            ->join('pembelian as p', 'p.id_pembelian', '=', 'd.id_pembelian')
            ->where('p.id_entitas', $idEntitas)
            ->where('d.id_barang', $item->id_barang)
            ->exists();

        $usedInPenjualan = DB::table('detail_penjualan as d')
            ->join('penjualan as p', 'p.id_penjualan', '=', 'd.id_penjualan')
            ->where('p.id_entitas', $idEntitas)
            ->where('d.id_barang', $item->id_barang)
            ->exists();

        $usedInStok = DB::table('kartu_stok')
            ->where('id_entitas', $idEntitas)
            ->where('id_barang', $item->id_barang)
            ->exists();

        if ($usedInPembelian || $usedInPenjualan || $usedInStok) {
            return back()->with('error', "Barang \"{$item->nama_barang}\" sudah memiliki riwayat transaksi/stok pada entitas ini dan tidak dapat dihapus. Nonaktifkan saja jika tidak lagi dijual.");
        }

        $nama = $item->nama_barang;

        DB::transaction(function () use ($idEntitas, $item) {
            DB::table('barang_per_koperasi')
                ->where('id_entitas', $idEntitas)
                ->where('id_barang', $item->id_barang)
                ->delete();

            $stillUsedElsewhere = DB::table('barang_per_koperasi')
                ->where('id_barang', $item->id_barang)
                ->exists()
                || DB::table('detail_pembelian')->where('id_barang', $item->id_barang)->exists()
                || DB::table('detail_penjualan')->where('id_barang', $item->id_barang)->exists()
                || DB::table('kartu_stok')->where('id_barang', $item->id_barang)->exists();

            if (! $stillUsedElsewhere) {
                DB::table('konversi_satuan')->where('id_barang', $item->id_barang)->delete();
                $item->delete();
            }
        });

        return redirect()->route('master.barang.index')
            ->with('success', "Barang \"{$nama}\" berhasil dihapus dari entitas Anda.");
    }

    protected function validatePayload(Request $request, int $idEntitas, ?int $ignoreId = null): array
    {
        $uniqueRule = Rule::unique('master_barang', 'kode_barang')->ignore($ignoreId, 'id_barang');
        if (Barang::hasEntitasColumn()) {
            $uniqueRule->where('id_entitas', $idEntitas);
        }

        return $request->validate([
            'kode_barang' => ['required', 'string', 'max:50', $uniqueRule],
            'nama_barang' => ['required', 'string', 'max:150'],
            'id_komoditas' => ['nullable', 'exists:komoditas,id'],
            'id_unit_usaha' => ['required', 'exists:master_unit_usaha,id_unit_usaha'],
            'id_satuan_dasar' => ['required', 'exists:satuan,id'],
            'harga_jual_standar' => ['required', 'numeric', 'min:0'],
            'stok_minimum' => ['nullable', 'numeric', 'min:0'],
            'stok_maksimum' => ['nullable', 'numeric', 'min:0'],
            'konversi' => ['nullable', 'array'],
            'konversi.*.id_satuan' => ['nullable', 'exists:satuan,id'],
            'konversi.*.faktor_ke_dasar' => ['nullable', 'numeric', 'gt:0'],
        ], [
            'kode_barang.unique' => 'Kode barang tersebut sudah digunakan pada entitas Anda.',
        ]);
    }

    protected function syncKonversiSatuan(Barang $barang, array $konversiRows): void
    {
        // Pastikan satuan dasar memiliki faktor 1
        DB::table('konversi_satuan')->updateOrInsert(
            ['id_barang' => $barang->id_barang, 'id_satuan' => $barang->id_satuan_dasar],
            ['faktor_ke_dasar' => 1, 'is_default_beli' => true, 'is_default_jual' => true]
        );

        $keepSatuanIds = [$barang->id_satuan_dasar];

        foreach ($konversiRows as $row) {
            $idSatuan = ! empty($row['id_satuan']) ? (int) $row['id_satuan'] : null;
            $faktor = isset($row['faktor_ke_dasar']) ? (float) $row['faktor_ke_dasar'] : 0;

            if (! $idSatuan || $idSatuan === (int) $barang->id_satuan_dasar || $faktor <= 0) {
                continue;
            }

            $keepSatuanIds[] = $idSatuan;

            DB::table('konversi_satuan')->updateOrInsert(
                ['id_barang' => $barang->id_barang, 'id_satuan' => $idSatuan],
                ['faktor_ke_dasar' => $faktor, 'is_default_beli' => false, 'is_default_jual' => false]
            );
        }

        DB::table('konversi_satuan')
            ->where('id_barang', $barang->id_barang)
            ->whereNotIn('id_satuan', $keepSatuanIds)
            ->delete();
    }
}

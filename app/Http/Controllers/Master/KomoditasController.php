<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Concerns\ResolvesEntitasAktif;
use App\Http\Controllers\Controller;
use App\Models\Master\Komoditas;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class KomoditasController extends Controller
{
    use ResolvesEntitasAktif;

    protected string $title = 'Komoditas Desa';
    protected string $routeBase = 'master.komoditas';

    public const KATEGORI_LIST = [
        'pangan_pokok' => 'Pangan Pokok',
        'sembako' => 'Sembako',
        'hewani' => 'Protein Hewani & Peternakan',
        'hortikultura' => 'Hortikultura (Sayur & Buah)',
        'perikanan' => 'Perikanan',
        'apotek' => 'Kesehatan & Apotek Desa',
        'lainnya' => 'Lainnya',
    ];

    public function index(Request $request): View
    {
        $idEntitas = $this->entitasAktifId();
        $entitas = $this->entitasAktif();

        $query = Komoditas::query()->forEntitas($idEntitas);

        if ($kategori = $request->query('kategori')) {
            $query->where('kategori', $kategori);
        }

        if ($search = trim((string) $request->query('q', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                  ->orWhere('kategori', 'like', "%{$search}%");
            });
        }

        $items = $query->orderBy('nama')->paginate(15)->withQueryString();

        return view('master.komoditas.index', [
            'title' => $this->title,
            'routeBase' => $this->routeBase,
            'items' => $items,
            'kategoriList' => self::KATEGORI_LIST,
            'idEntitas' => $idEntitas,
            'entitas' => $entitas,
        ]);
    }

    public function create(): View
    {
        $idEntitas = $this->requireEntitasAktifId();

        return view('master.komoditas.form', [
            'title' => 'Tambah Komoditas',
            'routeBase' => $this->routeBase,
            'mode' => 'create',
            'item' => new Komoditas(['kategori' => 'pangan_pokok', 'is_active' => true]),
            'kategoriList' => self::KATEGORI_LIST,
            'entitas' => $this->entitasAktif(),
            'idEntitas' => $idEntitas,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $idEntitas = $this->requireEntitasAktifId();
        $validated = $this->validatePayload($request, $idEntitas);

        $payload = [
            'kategori' => $validated['kategori'],
            'nama' => trim($validated['nama']),
            'alias_json' => $this->parseAliases($request->input('alias_input')),
            'is_active' => $request->boolean('is_active', true),
        ];

        if (Komoditas::hasEntitasColumn()) {
            $payload['id_entitas'] = $idEntitas;
        }

        Komoditas::create($payload);

        return redirect()->route('master.komoditas.index')
            ->with('success', "Komoditas \"{$validated['nama']}\" berhasil ditambahkan untuk entitas Anda.");
    }

    public function edit(int|string $id): View
    {
        $idEntitas = $this->requireEntitasAktifId();
        $item = Komoditas::query()->forEntitas($idEntitas)->findOrFail($id);

        return view('master.komoditas.form', [
            'title' => 'Ubah Komoditas',
            'routeBase' => $this->routeBase,
            'mode' => 'edit',
            'item' => $item,
            'kategoriList' => self::KATEGORI_LIST,
            'entitas' => $this->entitasAktif(),
            'idEntitas' => $idEntitas,
        ]);
    }

    public function update(Request $request, int|string $id): RedirectResponse
    {
        $idEntitas = $this->requireEntitasAktifId();
        $item = Komoditas::query()->forEntitas($idEntitas)->findOrFail($id);

        $validated = $this->validatePayload($request, $idEntitas, $item->id);

        $item->update([
            'kategori' => $validated['kategori'],
            'nama' => trim($validated['nama']),
            'alias_json' => $this->parseAliases($request->input('alias_input')),
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('master.komoditas.index')
            ->with('success', "Komoditas \"{$validated['nama']}\" berhasil diperbarui.");
    }

    public function destroy(int|string $id): RedirectResponse
    {
        $idEntitas = $this->requireEntitasAktifId();
        $item = Komoditas::query()->forEntitas($idEntitas)->findOrFail($id);

        $usedInBarang = DB::table('master_barang')->where('id_komoditas', $item->id)->exists();
        $usedInStandar = DB::table('standar_kebutuhan_komoditas')->where('id_komoditas', $item->id)->exists();
        $usedInKetersediaan = DB::table('ketersediaan_komoditas')->where('id_komoditas', $item->id)->exists();
        $usedInHasil = DB::table('hasil_kalkulasi')->where('id_komoditas', $item->id)->exists();

        if ($usedInBarang || $usedInStandar || $usedInKetersediaan || $usedInHasil) {
            return back()->with('error', "Komoditas \"{$item->nama}\" sudah digunakan pada Master Barang atau Perencanaan (M2/M3) dan tidak dapat dihapus. Nonaktifkan saja jika tidak digunakan.");
        }

        $nama = $item->nama;
        $item->delete();

        return redirect()->route('master.komoditas.index')
            ->with('success', "Komoditas \"{$nama}\" berhasil dihapus.");
    }

    protected function validatePayload(Request $request, int $idEntitas, ?int $ignoreId = null): array
    {
        $uniqueRule = Rule::unique('komoditas', 'nama')->ignore($ignoreId, 'id');
        if (Komoditas::hasEntitasColumn()) {
            $uniqueRule->where('id_entitas', $idEntitas);
        }

        return $request->validate([
            'kategori' => ['required', 'string', 'max:50'],
            'nama' => ['required', 'string', 'max:100', $uniqueRule],
            'alias_input' => ['nullable', 'string', 'max:255'],
        ], [
            'nama.unique' => 'Nama komoditas tersebut sudah terdaftar pada entitas Anda.',
        ]);
    }

    protected function parseAliases(?string $input): array
    {
        if (! $input) {
            return [];
        }

        return array_values(array_filter(array_map('trim', explode(',', $input)), fn ($v) => $v !== ''));
    }
}

<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Concerns\ResolvesEntitasAktif;
use App\Http\Controllers\Controller;
use App\Models\Master\Satuan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SatuanController extends Controller
{
    use ResolvesEntitasAktif;

    protected string $title = 'Satuan Ukur';
    protected string $routeBase = 'master.satuan';

    public function index(Request $request): View
    {
        $idEntitas = $this->entitasAktifId();
        $entitas = $this->entitasAktif();

        $query = Satuan::query()->forEntitas($idEntitas);

        if ($search = trim((string) $request->query('q', ''))) {
            $query->where('kode_satuan', 'like', "%{$search}%");
        }

        $items = $query->orderBy('kode_satuan')->paginate(15)->withQueryString();

        return view('master.satuan.index', [
            'title' => $this->title,
            'routeBase' => $this->routeBase,
            'items' => $items,
            'idEntitas' => $idEntitas,
            'entitas' => $entitas,
        ]);
    }

    public function create(): View
    {
        $idEntitas = $this->requireEntitasAktifId();

        return view('master.satuan.form', [
            'title' => 'Tambah Satuan Ukur',
            'routeBase' => $this->routeBase,
            'mode' => 'create',
            'item' => new Satuan(['is_active' => true]),
            'entitas' => $this->entitasAktif(),
            'idEntitas' => $idEntitas,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $idEntitas = $this->requireEntitasAktifId();
        $request->merge(['kode_satuan' => strtoupper(trim((string) $request->input('kode_satuan')))]);
        $validated = $this->validatePayload($request, $idEntitas);

        $payload = [
            'kode_satuan' => $validated['kode_satuan'],
            'alias_json' => $this->parseAliases($request->input('alias_input')),
            'is_active' => $request->boolean('is_active', true),
        ];

        if (Satuan::hasEntitasColumn()) {
            $payload['id_entitas'] = $idEntitas;
        }

        Satuan::create($payload);

        return redirect()->route('master.satuan.index')
            ->with('success', "Satuan \"{$validated['kode_satuan']}\" berhasil ditambahkan untuk entitas Anda.");
    }

    public function edit(int|string $id): View
    {
        $idEntitas = $this->requireEntitasAktifId();
        $item = Satuan::query()->forEntitas($idEntitas)->findOrFail($id);

        return view('master.satuan.form', [
            'title' => 'Ubah Satuan Ukur',
            'routeBase' => $this->routeBase,
            'mode' => 'edit',
            'item' => $item,
            'entitas' => $this->entitasAktif(),
            'idEntitas' => $idEntitas,
        ]);
    }

    public function update(Request $request, int|string $id): RedirectResponse
    {
        $idEntitas = $this->requireEntitasAktifId();
        $item = Satuan::query()->forEntitas($idEntitas)->findOrFail($id);

        $request->merge(['kode_satuan' => strtoupper(trim((string) $request->input('kode_satuan')))]);
        $validated = $this->validatePayload($request, $idEntitas, $item->id);

        $item->update([
            'kode_satuan' => $validated['kode_satuan'],
            'alias_json' => $this->parseAliases($request->input('alias_input')),
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('master.satuan.index')
            ->with('success', "Satuan \"{$validated['kode_satuan']}\" berhasil diperbarui.");
    }

    public function destroy(int|string $id): RedirectResponse
    {
        $idEntitas = $this->requireEntitasAktifId();
        $item = Satuan::query()->forEntitas($idEntitas)->findOrFail($id);

        $usedInBarang = DB::table('master_barang')->where('id_satuan_dasar', $item->id)->exists();
        $usedInKonversi = DB::table('konversi_satuan')->where('id_satuan', $item->id)->exists();

        if ($usedInBarang || $usedInKonversi) {
            return back()->with('error', "Satuan \"{$item->kode_satuan}\" sedang dipakai oleh Master Barang dan tidak dapat dihapus.");
        }

        $kode = $item->kode_satuan;
        $item->delete();

        return redirect()->route('master.satuan.index')
            ->with('success', "Satuan \"{$kode}\" berhasil dihapus.");
    }

    protected function validatePayload(Request $request, int $idEntitas, ?int $ignoreId = null): array
    {
        $uniqueRule = Rule::unique('satuan', 'kode_satuan')->ignore($ignoreId, 'id');
        if (Satuan::hasEntitasColumn()) {
            $uniqueRule->where('id_entitas', $idEntitas);
        }

        return $request->validate([
            'kode_satuan' => ['required', 'string', 'max:25', $uniqueRule],
            'alias_input' => ['nullable', 'string', 'max:255'],
        ], [
            'kode_satuan.unique' => 'Kode satuan tersebut sudah ada pada entitas Anda.',
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

<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Concerns\ResolvesEntitasAktif;
use App\Http\Controllers\Controller;
use App\Models\Master\Gudang;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class GudangController extends Controller
{
    use ResolvesEntitasAktif;

    protected string $title = 'Gudang Penyimpanan';
    protected string $routeBase = 'master.gudang';

    public function index(Request $request): View
    {
        $idEntitas = $this->entitasAktifId();
        $entitas = $this->entitasAktif();

        $query = Gudang::query()
            ->when($idEntitas, fn ($q) => $q->where('id_entitas', $idEntitas));

        if ($search = trim((string) $request->query('q', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('kode_gudang', 'like', "%{$search}%")
                  ->orWhere('nama_gudang', 'like', "%{$search}%")
                  ->orWhere('alamat', 'like', "%{$search}%");
            });
        }

        $items = $query->orderBy('kode_gudang')->paginate(15)->withQueryString();

        return view('master.gudang.index', [
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

        return view('master.gudang.form', [
            'title' => 'Tambah Gudang',
            'routeBase' => $this->routeBase,
            'mode' => 'create',
            'item' => new Gudang(['is_active' => true]),
            'entitas' => $this->entitasAktif(),
            'idEntitas' => $idEntitas,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $idEntitas = $this->requireEntitasAktifId();
        $request->merge(['kode_gudang' => strtoupper(trim((string) $request->input('kode_gudang')))]);
        $validated = $this->validatePayload($request, $idEntitas);

        Gudang::create([
            'id_entitas' => $idEntitas,
            'kode_gudang' => $validated['kode_gudang'],
            'nama_gudang' => trim($validated['nama_gudang']),
            'alamat' => $validated['alamat'] ?: null,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('master.gudang.index')
            ->with('success', "Gudang \"{$validated['nama_gudang']}\" berhasil ditambahkan untuk entitas Anda.");
    }

    public function edit(int|string $id): View
    {
        $idEntitas = $this->requireEntitasAktifId();
        $item = Gudang::withoutGlobalScopes()
            ->where('id_entitas', $idEntitas)
            ->findOrFail($id);

        return view('master.gudang.form', [
            'title' => 'Ubah Gudang',
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
        $item = Gudang::withoutGlobalScopes()
            ->where('id_entitas', $idEntitas)
            ->findOrFail($id);

        $request->merge(['kode_gudang' => strtoupper(trim((string) $request->input('kode_gudang')))]);
        $validated = $this->validatePayload($request, $idEntitas, $item->id_gudang);

        $item->update([
            'kode_gudang' => $validated['kode_gudang'],
            'nama_gudang' => trim($validated['nama_gudang']),
            'alamat' => $validated['alamat'] ?: null,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('master.gudang.index')
            ->with('success', "Gudang \"{$validated['nama_gudang']}\" berhasil diperbarui.");
    }

    public function destroy(int|string $id): RedirectResponse
    {
        $idEntitas = $this->requireEntitasAktifId();
        $item = Gudang::withoutGlobalScopes()
            ->where('id_entitas', $idEntitas)
            ->findOrFail($id);

        $usedInStok = DB::table('kartu_stok')->where('id_gudang', $item->id_gudang)->exists();
        $usedInPembelian = DB::table('pembelian')->where('id_gudang', $item->id_gudang)->exists();
        $usedInPenjualan = DB::table('penjualan')->where('id_gudang', $item->id_gudang)->exists();

        if ($usedInStok || $usedInPembelian || $usedInPenjualan) {
            return back()->with('error', "Gudang \"{$item->nama_gudang}\" sudah memiliki riwayat mutasi stok/transaksi dan tidak dapat dihapus.");
        }

        $nama = $item->nama_gudang;
        $item->delete();

        return redirect()->route('master.gudang.index')
            ->with('success', "Gudang \"{$nama}\" berhasil dihapus.");
    }

    protected function validatePayload(Request $request, int $idEntitas, ?int $ignoreId = null): array
    {
        return $request->validate([
            'kode_gudang' => [
                'required',
                'string',
                'max:30',
                Rule::unique('gudang', 'kode_gudang')
                    ->where('id_entitas', $idEntitas)
                    ->ignore($ignoreId, 'id_gudang'),
            ],
            'nama_gudang' => ['required', 'string', 'max:150'],
            'alamat' => ['nullable', 'string', 'max:500'],
        ], [
            'kode_gudang.unique' => 'Kode gudang tersebut sudah terdaftar pada entitas Anda.',
        ]);
    }
}

<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Concerns\ResolvesEntitasAktif;
use App\Http\Controllers\Controller;
use App\Models\Master\Pihak;
use App\Models\Tenant\Entitas;
use App\Models\Tenant\Wilayah;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PihakController extends Controller
{
    use ResolvesEntitasAktif;

    protected string $title = 'Pihak / Mitra (Warga, Petani, Supplier, Desa Mitra)';
    protected string $routeBase = 'master.pihak';

    public function index(Request $request): View
    {
        $idEntitas = $this->entitasAktifId();
        $entitas = $this->entitasAktif();

        $query = Pihak::with('wilayah')
            ->when($idEntitas, fn ($q) => $q->where('id_entitas', $idEntitas));

        if ($jenis = $request->query('jenis_pihak')) {
            $query->where('jenis_pihak', $jenis);
        }

        if ($request->filled('is_anggota')) {
            $query->where('is_anggota', $request->query('is_anggota') === '1');
        }

        if ($search = trim((string) $request->query('q', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                  ->orWhere('no_anggota', 'like', "%{$search}%")
                  ->orWhere('nik', 'like', "%{$search}%")
                  ->orWhere('no_hp', 'like', "%{$search}%");
            });
        }

        $items = $query->orderBy('jenis_pihak')->orderBy('nama')->paginate(15)->withQueryString();

        return view('master.pihak.index', [
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
        $entitas = $this->entitasAktif();

        return view('master.pihak.form', [
            'title' => 'Tambah Pihak / Mitra',
            'routeBase' => $this->routeBase,
            'mode' => 'create',
            'item' => new Pihak([
                'jenis_pihak' => 'warga',
                'is_anggota' => true,
                'is_active' => true,
                'id_wilayah' => $entitas?->id_wilayah,
            ]),
            'wilayahList' => Wilayah::orderBy('nama')->get(),
            'entitasMitraList' => Entitas::where('id_entitas', '!=', $idEntitas)
                ->where('is_active', true)
                ->orderBy('nama_entitas')
                ->get(),
            'entitas' => $entitas,
            'idEntitas' => $idEntitas,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $idEntitas = $this->requireEntitasAktifId();
        $validated = $this->validatePayload($request, $idEntitas);

        Pihak::create([
            'id_entitas' => $idEntitas,
            'jenis_pihak' => $validated['jenis_pihak'],
            'id_entitas_mitra' => $validated['jenis_pihak'] === 'entitas_lain' ? ($validated['id_entitas_mitra'] ?? null) : null,
            'nama' => trim($validated['nama']),
            'nik' => $validated['nik'] ?: null,
            'is_anggota' => $request->boolean('is_anggota'),
            'no_anggota' => $request->boolean('is_anggota') ? ($validated['no_anggota'] ?: null) : null,
            'tgl_jadi_anggota' => $request->boolean('is_anggota') ? ($validated['tgl_jadi_anggota'] ?: null) : null,
            'alamat' => $validated['alamat'] ?: null,
            'no_hp' => $validated['no_hp'] ?: null,
            'id_wilayah' => $validated['id_wilayah'] ?: null,
            'kualitas_rating' => $validated['kualitas_rating'] ?? null,
            'estimasi_pengiriman' => $validated['estimasi_pengiriman'] ?: null,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('master.pihak.index')
            ->with('success', "Pihak \"{$validated['nama']}\" berhasil ditambahkan ke entitas Anda.");
    }

    public function edit(int|string $id): View
    {
        $idEntitas = $this->requireEntitasAktifId();
        $item = Pihak::withoutGlobalScopes()
            ->where('id_entitas', $idEntitas)
            ->findOrFail($id);

        return view('master.pihak.form', [
            'title' => 'Ubah Pihak / Mitra',
            'routeBase' => $this->routeBase,
            'mode' => 'edit',
            'item' => $item,
            'wilayahList' => Wilayah::orderBy('nama')->get(),
            'entitasMitraList' => Entitas::where('id_entitas', '!=', $idEntitas)
                ->where('is_active', true)
                ->orderBy('nama_entitas')
                ->get(),
            'entitas' => $this->entitasAktif(),
            'idEntitas' => $idEntitas,
        ]);
    }

    public function update(Request $request, int|string $id): RedirectResponse
    {
        $idEntitas = $this->requireEntitasAktifId();
        $item = Pihak::withoutGlobalScopes()
            ->where('id_entitas', $idEntitas)
            ->findOrFail($id);

        $validated = $this->validatePayload($request, $idEntitas, $item->id_pihak);

        $item->update([
            'jenis_pihak' => $validated['jenis_pihak'],
            'id_entitas_mitra' => $validated['jenis_pihak'] === 'entitas_lain' ? ($validated['id_entitas_mitra'] ?? null) : null,
            'nama' => trim($validated['nama']),
            'nik' => $validated['nik'] ?: null,
            'is_anggota' => $request->boolean('is_anggota'),
            'no_anggota' => $request->boolean('is_anggota') ? ($validated['no_anggota'] ?: null) : null,
            'tgl_jadi_anggota' => $request->boolean('is_anggota') ? ($validated['tgl_jadi_anggota'] ?: null) : null,
            'alamat' => $validated['alamat'] ?: null,
            'no_hp' => $validated['no_hp'] ?: null,
            'id_wilayah' => $validated['id_wilayah'] ?: null,
            'kualitas_rating' => $validated['kualitas_rating'] ?? null,
            'estimasi_pengiriman' => $validated['estimasi_pengiriman'] ?: null,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('master.pihak.index')
            ->with('success', "Data pihak \"{$validated['nama']}\" berhasil diperbarui.");
    }

    public function destroy(int|string $id): RedirectResponse
    {
        $idEntitas = $this->requireEntitasAktifId();
        $item = Pihak::withoutGlobalScopes()
            ->where('id_entitas', $idEntitas)
            ->findOrFail($id);

        $usedInPembelian = DB::table('pembelian')->where('id_pihak', $item->id_pihak)->exists();
        $usedInPenjualan = DB::table('penjualan')->where('id_pihak', $item->id_pihak)->exists();
        $usedInPiutang = DB::table('piutang')->where('id_pihak', $item->id_pihak)->exists();
        $usedInHutang = DB::table('hutang')->where('id_pihak', $item->id_pihak)->exists();
        $usedInSimpanan = DB::table('simpanan_anggota')->where('id_pihak', $item->id_pihak)->exists();
        $usedInPr = DB::table('permintaan_pengadaan')->where('id_pihak', $item->id_pihak)->exists();

        if ($usedInPembelian || $usedInPenjualan || $usedInPiutang || $usedInHutang || $usedInSimpanan || $usedInPr) {
            return back()->with('error', "Pihak \"{$item->nama}\" sudah memiliki riwayat transaksi pada entitas ini dan tidak dapat dihapus. Nonaktifkan saja jika tidak lagi digunakan.");
        }

        $nama = $item->nama;
        $item->delete();

        return redirect()->route('master.pihak.index')
            ->with('success', "Pihak \"{$nama}\" berhasil dihapus.");
    }

    protected function validatePayload(Request $request, int $idEntitas, ?int $ignoreId = null): array
    {
        return $request->validate([
            'jenis_pihak' => ['required', Rule::in(['warga', 'petani', 'supplier', 'entitas_lain'])],
            'id_entitas_mitra' => [
                'nullable',
                Rule::requiredIf($request->input('jenis_pihak') === 'entitas_lain'),
                'exists:entitas,id_entitas',
            ],
            'nama' => [
                'required',
                'string',
                'max:150',
                Rule::unique('master_pihak', 'nama')
                    ->where('id_entitas', $idEntitas)
                    ->ignore($ignoreId, 'id_pihak'),
            ],
            'nik' => ['nullable', 'string', 'max:25'],
            'no_anggota' => ['nullable', 'string', 'max:50'],
            'tgl_jadi_anggota' => ['nullable', 'date'],
            'no_hp' => ['nullable', 'string', 'max:30'],
            'alamat' => ['nullable', 'string', 'max:500'],
            'id_wilayah' => ['nullable', 'exists:wilayah,id'],
            'kualitas_rating' => ['nullable', 'numeric', 'min:0', 'max:5'],
            'estimasi_pengiriman' => ['nullable', 'string', 'max:50'],
        ], [
            'nama.unique' => 'Nama pihak tersebut sudah terdaftar pada entitas Anda.',
            'id_entitas_mitra.required' => 'Pilih entitas/koperasi desa mitra untuk jenis pihak Entitas Lain.',
        ]);
    }
}

<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Concerns\ResolvesEntitasAktif;
use App\Http\Controllers\Controller;
use App\Models\Akuntansi\Coa;
use App\Models\Master\KasBank;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class KasBankController extends Controller
{
    use ResolvesEntitasAktif;

    protected string $title = 'Kas & Bank';
    protected string $routeBase = 'master.kas-bank';

    public function index(Request $request): View
    {
        $idEntitas = $this->entitasAktifId();
        $entitas = $this->entitasAktif();

        $query = KasBank::with('akun')
            ->when($idEntitas, fn ($q) => $q->where('id_entitas', $idEntitas));

        if ($jenis = $request->query('jenis')) {
            $query->where('jenis', $jenis);
        }

        if ($search = trim((string) $request->query('q', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                  ->orWhere('no_rekening', 'like', "%{$search}%")
                  ->orWhere('kode_akun', 'like', "%{$search}%");
            });
        }

        $items = $query->orderBy('jenis')->orderByDesc('is_default')->orderBy('nama')->paginate(15)->withQueryString();

        return view('master.kas-bank.index', [
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

        return view('master.kas-bank.form', [
            'title' => 'Tambah Kas / Bank',
            'routeBase' => $this->routeBase,
            'mode' => 'create',
            'item' => new KasBank(['jenis' => 'bank', 'kode_akun' => '11121', 'is_active' => true, 'is_default' => false]),
            'akunKasBank' => $this->akunKasBankOptions(),
            'entitas' => $this->entitasAktif(),
            'idEntitas' => $idEntitas,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $idEntitas = $this->requireEntitasAktifId();
        $validated = $this->validatePayload($request, $idEntitas);

        DB::transaction(function () use ($idEntitas, $validated, $request) {
            $isDefault = $request->boolean('is_default');

            $hasSameJenis = KasBank::withoutGlobalScopes()
                ->where('id_entitas', $idEntitas)
                ->where('jenis', $validated['jenis'])
                ->where('is_default', true)
                ->exists();

            if (! $hasSameJenis) {
                $isDefault = true;
            }

            if ($isDefault) {
                KasBank::withoutGlobalScopes()
                    ->where('id_entitas', $idEntitas)
                    ->where('jenis', $validated['jenis'])
                    ->update(['is_default' => false]);
            }

            KasBank::create([
                'id_entitas' => $idEntitas,
                'jenis' => $validated['jenis'],
                'nama' => trim($validated['nama']),
                'no_rekening' => ! empty($validated['no_rekening']) ? trim($validated['no_rekening']) : null,
                'kode_akun' => $validated['kode_akun'],
                'is_default' => $isDefault,
                'is_active' => $request->boolean('is_active', true),
            ]);
        });

        return redirect()->route('master.kas-bank.index')
            ->with('success', "Data {$validated['nama']} berhasil ditambahkan untuk entitas Anda.");
    }

    public function edit(int|string $id): View
    {
        $idEntitas = $this->requireEntitasAktifId();
        $item = KasBank::withoutGlobalScopes()
            ->where('id_entitas', $idEntitas)
            ->findOrFail($id);

        return view('master.kas-bank.form', [
            'title' => 'Ubah Kas / Bank',
            'routeBase' => $this->routeBase,
            'mode' => 'edit',
            'item' => $item,
            'akunKasBank' => $this->akunKasBankOptions(),
            'entitas' => $this->entitasAktif(),
            'idEntitas' => $idEntitas,
        ]);
    }

    public function update(Request $request, int|string $id): RedirectResponse
    {
        $idEntitas = $this->requireEntitasAktifId();
        $item = KasBank::withoutGlobalScopes()
            ->where('id_entitas', $idEntitas)
            ->findOrFail($id);

        $validated = $this->validatePayload($request, $idEntitas, $item->id_kas_bank);

        DB::transaction(function () use ($idEntitas, $item, $validated, $request) {
            $isDefault = $request->boolean('is_default');

            if ($isDefault) {
                KasBank::withoutGlobalScopes()
                    ->where('id_entitas', $idEntitas)
                    ->where('jenis', $validated['jenis'])
                    ->where('id_kas_bank', '!=', $item->id_kas_bank)
                    ->update(['is_default' => false]);
            }

            $item->update([
                'jenis' => $validated['jenis'],
                'nama' => trim($validated['nama']),
                'no_rekening' => ! empty($validated['no_rekening']) ? trim($validated['no_rekening']) : null,
                'kode_akun' => $validated['kode_akun'],
                'is_default' => $isDefault,
                'is_active' => $request->boolean('is_active', true),
            ]);
        });

        return redirect()->route('master.kas-bank.index')
            ->with('success', "Data {$validated['nama']} berhasil diperbarui.");
    }

    public function destroy(int|string $id): RedirectResponse
    {
        $idEntitas = $this->requireEntitasAktifId();
        $item = KasBank::withoutGlobalScopes()
            ->where('id_entitas', $idEntitas)
            ->findOrFail($id);

        $usedInKas = DB::table('kas_transaksi')
            ->where('id_kas_bank', $item->id_kas_bank)
            ->orWhere('id_kas_bank_tujuan', $item->id_kas_bank)
            ->exists();
        $usedInPembelian = DB::table('pembelian')->where('id_kas_bank', $item->id_kas_bank)->exists();
        $usedInPenjualan = DB::table('penjualan')->where('id_kas_bank', $item->id_kas_bank)->exists();
        $usedInPelunasan = DB::table('pelunasan')->where('id_kas_bank', $item->id_kas_bank)->exists();

        if ($usedInKas || $usedInPembelian || $usedInPenjualan || $usedInPelunasan) {
            return back()->with('error', "Kas/Bank \"{$item->nama}\" sudah dipakai pada transaksi keuangan dan tidak dapat dihapus. Nonaktifkan saja jika tidak lagi digunakan.");
        }

        $nama = $item->nama;
        $item->delete();

        return redirect()->route('master.kas-bank.index')
            ->with('success', "Kas/Bank \"{$nama}\" berhasil dihapus.");
    }

    protected function validatePayload(Request $request, int $idEntitas, ?int $ignoreId = null): array
    {
        return $request->validate([
            'jenis' => ['required', Rule::in(['kas', 'bank'])],
            'nama' => [
                'required',
                'string',
                'max:100',
                Rule::unique('master_kas_bank', 'nama')
                    ->where('id_entitas', $idEntitas)
                    ->ignore($ignoreId, 'id_kas_bank'),
            ],
            'no_rekening' => ['nullable', 'string', 'max:50'],
            'kode_akun' => [
                'required',
                Rule::exists('master_coa', 'kode_anak')->where('is_transaction', 'T'),
                function ($attribute, $value, $fail) use ($request) {
                    $jenis = $request->input('jenis');
                    if ($jenis === 'kas' && $value !== '1111') {
                        $fail('Akun jenis Kas harus terhubung ke akun COA 1111 (Kas).');
                    }
                    if ($jenis === 'bank' && ! str_starts_with((string) $value, '1112')) {
                        $fail('Akun jenis Bank harus terhubung ke kelompok COA 1112 (11121, 11122, 11123, dst.).');
                    }
                },
            ],
        ], [
            'nama.unique' => 'Nama Kas/Bank tersebut sudah terdaftar pada entitas Anda.',
        ]);
    }

    protected function akunKasBankOptions()
    {
        return Coa::where('is_transaction', 'T')
            ->where(function ($q) {
                $q->where('kode_anak', '1111')
                  ->orWhere('kode_anak', 'like', '1112%');
            })
            ->orderBy('kode_anak')
            ->get();
    }
}

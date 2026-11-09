<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Concerns\ResolvesEntitasAktif;
use App\Http\Controllers\Controller;
use App\Models\Akuntansi\Coa;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CoaController extends Controller
{
    use ResolvesEntitasAktif;

    protected string $title = 'Chart of Accounts';
    protected string $routeBase = 'master.coa';

    public function index(Request $request): View
    {
        $idEntitas = $this->entitasAktifId();
        $entitas = $this->entitasAktif();
        $bankMap = Coa::bankMapForEntitas($idEntitas);

        $query = Coa::query();

        if ($search = trim((string) $request->query('q', ''))) {
            $matchingBankCodes = collect($bankMap)
                ->filter(fn ($b) => str_contains(strtolower($b->nama), strtolower($search)))
                ->keys()
                ->all();

            $query->where(function ($q) use ($search, $matchingBankCodes) {
                $q->where('kode_anak', 'like', "%{$search}%")
                  ->orWhere('nama_rekening', 'like', "%{$search}%");
                if (! empty($matchingBankCodes)) {
                    $q->orWhereIn('kode_anak', $matchingBankCodes);
                }
            });
        }

        $items = $query->orderBy('kode_anak')->get()->map(function (Coa $item) use ($bankMap) {
            $item->setAttribute('is_bank_editable', $item->isBankAccount());
            $item->setAttribute('nama_template', $item->nama_rekening);

            if ($item->isBankAccount() && isset($bankMap[$item->kode_anak])) {
                $bank = $bankMap[$item->kode_anak];
                $item->setAttribute('nama_tampil', $bank->nama);
                $item->setAttribute('no_rekening_entitas', $bank->no_rekening);
                $item->setAttribute('id_kas_bank_entitas', $bank->id_kas_bank);
            } else {
                $item->setAttribute('nama_tampil', $item->nama_rekening);
                $item->setAttribute('no_rekening_entitas', null);
                $item->setAttribute('id_kas_bank_entitas', null);
            }

            return $item;
        });

        return view('master.coa.index', [
            'title' => $this->title,
            'routeBase' => $this->routeBase,
            'items' => $items,
            'idEntitas' => $idEntitas,
            'entitas' => $entitas,
        ]);
    }

    public function edit(string $kodeAnak): RedirectResponse
    {
        return redirect()->route('master.coa.index', ['edit_bank' => $kodeAnak]);
    }

    /**
     * Hanya mengizinkan perubahan Nama Bank (dan No. Rekening) pada akun Bank (11121, 11122, 11123, dst.)
     * yang disimpan terpisah per entitas pada tabel master_kas_bank.
     * Struktur dan akun COA lainnya bersifat baku (template bersama seluruh entitas).
     */
    public function update(Request $request, string $kodeAnak): RedirectResponse
    {
        $idEntitas = $this->entitasAktifId();
        if (! $idEntitas) {
            return back()->with('error', 'Pilih entitas terlebih dahulu untuk mengubah nama rekening Bank entitas.');
        }

        $coa = Coa::findOrFail($kodeAnak);

        if (! $coa->isBankAccount()) {
            return back()->with('error', 'Template COA bersifat baku dan sama untuk seluruh entitas. Hanya nama akun Bank (kelompok 1112) yang dapat disesuaikan per entitas.');
        }

        $validated = $request->validate([
            'nama_bank' => ['required', 'string', 'max:100'],
            'no_rekening' => ['nullable', 'string', 'max:50'],
            'is_default' => ['nullable', 'boolean'],
        ], [
            'nama_bank.required' => 'Nama Bank wajib diisi.',
        ]);

        DB::transaction(function () use ($idEntitas, $coa, $validated, $request) {
            $existing = DB::table('master_kas_bank')
                ->where('id_entitas', $idEntitas)
                ->where('kode_akun', $coa->kode_anak)
                ->where('jenis', 'bank')
                ->first();

            $isDefault = $request->boolean('is_default');

            // Jika belum ada bank default sama sekali di entitas ini, jadikan default
            $hasAnyDefaultBank = DB::table('master_kas_bank')
                ->where('id_entitas', $idEntitas)
                ->where('jenis', 'bank')
                ->where('is_default', true)
                ->exists();

            if (! $hasAnyDefaultBank) {
                $isDefault = true;
            }

            if ($isDefault) {
                DB::table('master_kas_bank')
                    ->where('id_entitas', $idEntitas)
                    ->where('jenis', 'bank')
                    ->update(['is_default' => false]);
            }

            if ($existing) {
                DB::table('master_kas_bank')
                    ->where('id_kas_bank', $existing->id_kas_bank)
                    ->update([
                        'nama' => trim($validated['nama_bank']),
                        'no_rekening' => ! empty($validated['no_rekening']) ? trim($validated['no_rekening']) : null,
                        'is_default' => $isDefault ? true : $existing->is_default,
                        'is_active' => true,
                    ]);
            } else {
                DB::table('master_kas_bank')->insert([
                    'id_entitas' => $idEntitas,
                    'jenis' => 'bank',
                    'nama' => trim($validated['nama_bank']),
                    'no_rekening' => ! empty($validated['no_rekening']) ? trim($validated['no_rekening']) : null,
                    'kode_akun' => $coa->kode_anak,
                    'is_default' => $isDefault,
                    'is_active' => true,
                ]);
            }
        });

        return redirect()->route('master.coa.index')
            ->with('success', "Nama rekening untuk akun {$coa->kode_anak} pada entitas ini berhasil diperbarui menjadi \"{$validated['nama_bank']}\".");
    }

    public function create(): RedirectResponse
    {
        return redirect()->route('master.coa.index')
            ->with('info', 'Struktur Chart of Accounts (COA) menggunakan template baku standar KDMP. Anda cukup mengubah Nama Bank pada baris akun Bank (11121–11123).');
    }

    public function store(Request $request): RedirectResponse
    {
        return redirect()->route('master.coa.index')
            ->with('info', 'Struktur Chart of Accounts (COA) menggunakan template baku standar KDMP.');
    }

    public function destroy(string $kodeAnak): RedirectResponse
    {
        return redirect()->route('master.coa.index')
            ->with('error', 'Rekening pada template standar COA tidak dapat dihapus.');
    }
}

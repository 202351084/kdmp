<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Concerns\ResolvesEntitasAktif;
use App\Http\Controllers\Controller;
use App\Models\Tenant\PeriodeAkuntansi;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PeriodeController extends Controller
{
    use ResolvesEntitasAktif;

    protected string $title = 'Periode Akuntansi';
    protected string $routeBase = 'master.periode';

    public function index(Request $request): View
    {
        $idEntitas = $this->entitasAktifId();
        $entitas = $this->entitasAktif();

        $tahun = (int) $request->query('tahun', $entitas?->tahun_buku_awal ?? date('Y'));

        $daftarTahun = PeriodeAkuntansi::withoutGlobalScopes()
            ->when($idEntitas, fn ($q) => $q->where('id_entitas', $idEntitas))
            ->distinct()
            ->orderByDesc('tahun')
            ->pluck('tahun')
            ->all();

        if (! in_array($tahun, $daftarTahun, true)) {
            $daftarTahun[] = $tahun;
            rsort($daftarTahun);
        }

        $items = PeriodeAkuntansi::withoutGlobalScopes()
            ->when($idEntitas, fn ($q) => $q->where('id_entitas', $idEntitas))
            ->where('tahun', $tahun)
            ->orderBy('bulan')
            ->get();

        return view('master.periode.index', [
            'title' => $this->title,
            'routeBase' => $this->routeBase,
            'items' => $items,
            'tahun' => $tahun,
            'daftarTahun' => $daftarTahun,
            'idEntitas' => $idEntitas,
            'entitas' => $entitas,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $idEntitas = $this->requireEntitasAktifId();

        $validated = $request->validate([
            'tahun' => ['required', 'integer', 'min:2020', 'max:2100'],
        ]);

        $tahun = (int) $validated['tahun'];

        DB::transaction(function () use ($idEntitas, $tahun) {
            for ($bulan = 1; $bulan <= 13; $bulan++) {
                DB::table('periode_akuntansi')->updateOrInsert(
                    ['id_entitas' => $idEntitas, 'tahun' => $tahun, 'bulan' => $bulan],
                    ['status' => 'OPEN']
                );
            }
        });

        return redirect()->route('master.periode.index', ['tahun' => $tahun])
            ->with('success', "Periode akuntansi tahun buku {$tahun} (Bulan 1 s/d 13) berhasil dibuka untuk entitas Anda.");
    }

    public function edit(int|string $id): RedirectResponse
    {
        return redirect()->route('master.periode.index');
    }

    public function update(Request $request, int|string $id): RedirectResponse
    {
        $idEntitas = $this->requireEntitasAktifId();
        $periode = PeriodeAkuntansi::withoutGlobalScopes()
            ->where('id_entitas', $idEntitas)
            ->findOrFail($id);

        $validated = $request->validate([
            'status' => ['required', Rule::in(['OPEN', 'CLOSED'])],
        ]);

        $periode->update([
            'status' => $validated['status'],
            'tgl_tutup' => $validated['status'] === 'CLOSED' ? now() : null,
            'ditutup_oleh' => $validated['status'] === 'CLOSED' ? auth()->id() : null,
        ]);

        return redirect()->route('master.periode.index', ['tahun' => $periode->tahun])
            ->with('success', "Status periode Bulan {$periode->bulan} Tahun {$periode->tahun} diubah menjadi {$validated['status']}.");
    }
}

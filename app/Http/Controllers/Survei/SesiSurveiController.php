<?php

namespace App\Http\Controllers\Survei;

use App\Http\Controllers\Concerns\ModuleCrudController;
use App\Models\Survei\SesiSurvei;
use App\Models\Survei\NarasumberSesi;
use App\Models\Survei\DemografiNarasumber;
use App\Models\Survei\Jawaban;
use App\Models\Survei\RekamanSuara;
use App\Models\Tenant\Wilayah;
use App\Models\Pengguna;
use App\Services\Survei\SurveySyncService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Throwable;

class SesiSurveiController extends ModuleCrudController
{
    protected string $model = SesiSurvei::class;
    protected string $view = 'survei.sesi';
    protected string $title = 'Sesi Survei';
    protected string $routeBase = 'survei.sesi';
    protected array $withRelations = ['wilayah', 'petugas', 'jawaban'];

    /**
     * Mengambil ID wilayah milik desa pengguna yang sedang login.
     * Return null jika pengguna adalah level Pusat / Super Admin (akses semua wilayah).
     */
    private function getUserWilayahId(): ?int
    {
        $user = auth()->user();
        if (!$user || !$user->id_entitas) {
            return null;
        }

        return $user->entitas?->id_wilayah;
    }

    private function checkHakAksesSesi(SesiSurvei $sesi): void
    {
        $userWilayahId = $this->getUserWilayahId();
        if ($userWilayahId && (int) $sesi->id_wilayah !== (int) $userWilayahId) {
            abort(403, 'Anda tidak memiliki hak akses untuk mengelola sesi survei desa ini.');
        }
    }

    public function index(Request $request): View
    {
        $query = SesiSurvei::with(['wilayah', 'petugas', 'jawaban', 'narasumber']);

        if ($userWilayahId = $this->getUserWilayahId()) {
            $query->where('id_wilayah', $userWilayahId);
        }

        if ($search = $request->query('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('catatan', 'like', "%{$search}%")
                    ->orWhereHas('wilayah', fn ($qw) => $qw->where('nama', 'like', "%{$search}%"))
                    ->orWhereHas('petugas', fn ($qp) => $qp->where('nama', 'like', "%{$search}%"));
            });
        }

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        $items = $query->latest('id')->paginate($this->perPage)->withQueryString();

        return view('survei.sesi.index', [
            'items' => $items,
            'title' => $this->title,
            'routeBase' => $this->routeBase,
            'columns' => ['id', 'id_wilayah', 'tahun', 'tanggal_survei', 'status'],
        ]);
    }

    public function create(): View
    {
        $userWilayahId = $this->getUserWilayahId();
        if ($userWilayahId) {
            $wilayahs = Wilayah::where('id', $userWilayahId)->get();
        } else {
            $wilayahs = Wilayah::all();
        }

        $petugasList = Pengguna::where('is_active', true)->get();

        $bulans = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];

        return view('survei.sesi.create', [
            'title' => 'Buat Sesi Survei Baru',
            'wilayahs' => $wilayahs,
            'petugasList' => $petugasList,
            'bulans' => $bulans,
            'routeBase' => $this->routeBase
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'id_wilayah'     => 'required|exists:wilayah,id',
            'id_petugas'     => 'nullable|exists:pengguna,id',
            'tanggal_survei' => 'required|date',
            'tahun'          => 'nullable|integer|min:2000|max:2100',
            'bulan'          => 'nullable|integer|min:1|max:12',
            'salin_data'     => 'nullable|boolean',
        ]);

        $userWilayahId = $this->getUserWilayahId();
        if ($userWilayahId) {
            $validated['id_wilayah'] = $userWilayahId;
        }

        if (empty($validated['tahun']) || empty($validated['bulan'])) {
            $dt = \Illuminate\Support\Carbon::parse($validated['tanggal_survei']);
            $validated['tahun'] = $validated['tahun'] ?? (int) $dt->format('Y');
            $validated['bulan'] = $validated['bulan'] ?? (int) $dt->format('n');
        }

        $validated['status']           = 'DRAFT';
        $validated['token_rt']         = Str::random(32);
        $validated['token_masyarakat'] = Str::random(32);
        $validated['token_produsen']   = Str::random(32);
        $validated['id_petugas']       = $validated['id_petugas'] ?? auth()->id();

        $sesi = SesiSurvei::create($validated);

        // Salin data narasumber & demografi dari periode sebelumnya secara otomatis jika opsi diaktifkan
        $salin = $request->has('salin_data') ? $request->boolean('salin_data') : true;
        $pesanSalin = '';
        if ($salin) {
            $disalin = $sesi->salinDataDariPeriodeSebelumnya();
            if ($disalin) {
                $pesanSalin = ' Data narasumber & demografi dari periode sebelumnya otomatis disalin sebagai acuan.';
            }
        }

        return redirect()->route('survei.sesi.show', $sesi->id)
            ->with('success', 'Sesi survei berhasil dibuat.' . $pesanSalin);
    }

    /**
     * Helper untuk menyalin data narasumber & demografi dari sesi survei sebelumnya di desa yang sama.
     */
    public function salinDataNarasumberSesiSebelumnya(SesiSurvei $targetSesi): bool
    {
        return $targetSesi->salinDataDariPeriodeSebelumnya();
    }

    public function show(int|string $id): View
    {
        $item = SesiSurvei::with(['wilayah.parent.parent.parent', 'petugas'])->findOrFail($id);
        $this->checkHakAksesSesi($item);
        
        return view('survei.sesi.show', [
            'sesi' => $item,
            'item' => $item,
            'title' => 'Detail Sesi Survei #' . $item->id,
            'routeBase' => $this->routeBase,
        ]);
    }

    public function edit(int|string $id): View
    {
        $item = SesiSurvei::findOrFail($id);
        $this->checkHakAksesSesi($item);
        
        $userWilayahId = $this->getUserWilayahId();
        if ($userWilayahId) {
            $wilayahs = Wilayah::where('id', $userWilayahId)->get();
        } else {
            $wilayahs = Wilayah::all();
        }

        $petugasList = Pengguna::where('is_active', true)->get();

        $bulans = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];

        return view('survei.sesi.edit', [
            'title' => 'Ubah Sesi Survei',
            'item' => $item,
            'wilayahs' => $wilayahs,
            'petugasList' => $petugasList,
            'bulans' => $bulans,
            'routeBase' => $this->routeBase
        ]);
    }

    public function update(Request $request, int|string $id): RedirectResponse
    {
        $sesi = SesiSurvei::findOrFail($id);
        $this->checkHakAksesSesi($sesi);

        $validated = $request->validate([
            'id_wilayah'     => 'required|exists:wilayah,id',
            'id_petugas'     => 'nullable|exists:pengguna,id',
            'tanggal_survei' => 'required|date',
            'tahun'          => 'nullable|integer|min:2000|max:2100',
            'bulan'          => 'nullable|integer|min:1|max:12',
        ]);

        $userWilayahId = $this->getUserWilayahId();
        if ($userWilayahId) {
            $validated['id_wilayah'] = $userWilayahId;
        }

        if (empty($validated['tahun']) || empty($validated['bulan'])) {
            $dt = \Illuminate\Support\Carbon::parse($validated['tanggal_survei']);
            $validated['tahun'] = $validated['tahun'] ?? (int) $dt->format('Y');
            $validated['bulan'] = $validated['bulan'] ?? (int) $dt->format('n');
        }

        $sesi->update($validated);

        return redirect()->route('survei.sesi.show', $sesi->id)
            ->with('success', 'Sesi survei berhasil diperbarui.');
    }

    public function destroy(int|string $id): RedirectResponse
    {
        $sesi = SesiSurvei::findOrFail($id);
        $this->checkHakAksesSesi($sesi);
        
        \Illuminate\Support\Facades\DB::transaction(function () use ($sesi) {
            \Illuminate\Support\Facades\DB::table('demografi_desa')
                ->where('id_sesi_survei', $sesi->id)
                ->update(['id_sesi_survei' => null]);

            \Illuminate\Support\Facades\DB::table('ketersediaan_komoditas')
                ->where('id_sesi_survei', $sesi->id)
                ->update(['id_sesi_survei' => null]);

            DemografiNarasumber::where('id_sesi', $sesi->id)->delete();
            NarasumberSesi::where('id_sesi', $sesi->id)->delete();
            Jawaban::where('id_sesi', $sesi->id)->delete();
            RekamanSuara::where('id_sesi', $sesi->id)->delete();

            $sesi->delete();
        });

        return back()->with('success', 'Sesi survei berhasil dihapus.');
    }

    /**
     * Memicu sinkronisasi eksplisit data survei ke modul Perencanaan (M2/M3).
     */
    public function sinkron(Request $request, int|string $id, SurveySyncService $syncService): RedirectResponse
    {
        try {
            $sesi = SesiSurvei::findOrFail($id);
            $hasil = $syncService->syncSesi($sesi);

            $msg = "Sinkronisasi berhasil! ";
            $msg .= "{$hasil['demografi_updated']} baris demografi, ";
            $msg .= "{$hasil['ketersediaan_updated']} ketersediaan komoditas, ";
            $msg .= "{$hasil['standar_updated']} standar kebutuhan diperbarui.";

            if (!empty($hasil['data_gaps'])) {
                $gapMsg = implode('; ', array_column($hasil['data_gaps'], 'keterangan'));
                return back()->with('status', $msg)->with('warning', "Catatan Data Gap: " . $gapMsg);
            }

            return back()->with('status', $msg);
        } catch (Throwable $e) {
            return back()->with('error', 'Gagal melakukan sinkronisasi: ' . $e->getMessage());
        }
    }
}

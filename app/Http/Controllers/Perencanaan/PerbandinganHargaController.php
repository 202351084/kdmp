<?php

namespace App\Http\Controllers\Perencanaan;

use App\Http\Controllers\Concerns\ModuleCrudController;
use App\Models\Master\Komoditas;
use App\Models\Perencanaan\PerbandinganHarga;
use App\Services\Perencanaan\PerbandinganHargaService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use App\Support\Perencanaan\PeriodeKalkulasi;

class PerbandinganHargaController extends ModuleCrudController
{
    protected string $model = PerbandinganHarga::class;
    protected string $view = 'perencanaan.perbandingan-harga';
    protected string $title = 'Perbandingan Harga';
    protected string $routeBase = 'perencanaan.perbandingan-harga';
    protected array $withRelations = ['komoditas', 'wilayahSumber'];

    public function index(Request $request): View
    {
        $validated = $request->validate([
            'id_komoditas' => ['nullable', 'integer', 'exists:komoditas,id'],
            'tahun' => ['nullable', 'integer', 'min:2000', 'max:2100'],
            'periode' => ['nullable', 'in:ganjil,genap'],
            'bulan' => ['nullable', 'integer', 'min:1', 'max:12'],
        ]);
        $user = auth()->user();
        $service = app(PerbandinganHargaService::class);
        $selected = (int) ($validated['id_komoditas'] ?? 0);
        $tahun = (int) ($validated['tahun'] ?? now()->year);
        $periode = isset($validated['bulan'])
            ? (int) $validated['bulan']
            : ($validated['periode'] ?? PeriodeKalkulasi::fromMonth(now()->month));
        $comparison = $selected > 0
            ? $service->bandingkan((int) $user->id_entitas, $selected, $tahun, $periode)
            : ['items' => collect(), 'lowest' => null, 'highest' => null, 'difference' => null, 'satuan' => null];

        return view('perencanaan.perbandingan-harga.index', [
            'title' => $this->title,
            'komoditas' => Komoditas::query()->orderBy('nama')->get(),
            'selected' => $selected,
            'tahun' => $tahun,
            'periode' => $periode,
            ...$comparison,
        ]);
    }
}

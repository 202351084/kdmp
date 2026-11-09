<?php

namespace App\Http\Controllers\Penjualan;

use App\Http\Controllers\Concerns\ModuleCrudController;
use App\Models\Penjualan\Penjualan;
use App\Models\Penjualan\ReturPenjualan;
use App\Services\PenjualanService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReturPenjualanController extends ModuleCrudController
{
    protected string $model = ReturPenjualan::class;
    protected string $view = 'penjualan.retur';
    protected string $title = 'Retur Penjualan';
    protected string $routeBase = 'penjualan.retur';
    protected array $withRelations = ['penjualan'];

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'archive' => ['sometimes', 'in:1'],
            'year' => ['nullable', 'integer', 'min:1900', 'max:9999'],
            'month' => ['nullable', 'integer', 'between:1,12'],
            'day' => ['nullable', 'integer', 'between:1,31'],
        ]);
        $query = ReturPenjualan::with(['penjualan.pihak', 'detail.barang.satuanDasar']);

        if ($search = $request->query('q')) {
            $query->where(function ($builder) use ($search) {
                $builder->where('kode_retur', 'like', "%{$search}%")
                    ->orWhereHas('penjualan', fn ($penjualan) => $penjualan->where('kode_penjualan', 'like', "%{$search}%"));
            });
        }

        foreach (['year' => 'tgl_retur', 'month' => 'tgl_retur', 'day' => 'tgl_retur'] as $filter => $column) {
            if (isset($filters[$filter])) {
                match ($filter) {
                    'year' => $query->whereYear($column, $filters[$filter]),
                    'month' => $query->whereMonth($column, $filters[$filter]),
                    'day' => $query->whereDay($column, $filters[$filter]),
                };
            }
        }

        $archiveActive = isset($filters['archive'])
            || isset($filters['year'])
            || isset($filters['month'])
            || isset($filters['day']);
        $yearlySummary = isset($filters['year']) && !isset($filters['month']) && !isset($filters['day'])
            ? (clone $query)->selectRaw('COUNT(*) as total_transaksi, COALESCE(SUM(total_nilai), 0) as total_nilai')->first()
            : null;
        $items = $query
            ->orderByDesc('tgl_retur')
            ->orderByDesc('id_retur')
            ->when($archiveActive, fn ($builder) => $builder->paginate(15)->withQueryString(), fn ($builder) => $builder->limit(5)->get());

        return view('penjualan.retur.index', [
            'title' => $this->title,
            'routeBase' => $this->routeBase,
            'items' => $items,
            'archiveActive' => $archiveActive,
            'archiveFilters' => $filters,
            'yearlySummary' => $yearlySummary,
        ]);
    }

    public function create(): View
    {
        $sales = Penjualan::where('status', 'selesai')
            ->with(['pihak', 'detail.barang.satuanDasar', 'returs.detail'])
            ->latest('id_penjualan')
            ->get()
            ->each(function (Penjualan $sale) {
                $requestedByBarang = $sale->returs
                    ->where('status', 'diajukan')
                    ->flatMap(fn ($return) => $return->detail)
                    ->groupBy('id_barang')
                    ->map(fn ($details) => (float) $details->sum('qty_dasar'));
                $completedByBarang = $sale->returs
                    ->where('status', 'selesai')
                    ->flatMap(fn ($return) => $return->detail)
                    ->groupBy('id_barang')
                    ->map(fn ($details) => (float) $details->sum('qty_dasar'));
                $reservedByBarang = $sale->returs
                    ->flatMap(fn ($return) => $return->detail)
                    ->groupBy('id_barang')
                    ->map(fn ($details) => (float) $details->sum('qty_dasar'));

                $returnItems = $sale->detail->groupBy('id_barang')->map(function ($lines) use ($requestedByBarang, $completedByBarang, $reservedByBarang) {
                    $line = $lines->first();
                    $sold = (float) $lines->sum('qty_dasar');
                    $line->setAttribute('qty_dijual', $sold);
                    $line->setAttribute('qty_retur_diajukan', (float) $requestedByBarang->get($line->id_barang, 0));
                    $line->setAttribute('qty_retur_selesai', (float) $completedByBarang->get($line->id_barang, 0));
                    $line->setAttribute('qty_sisa_retur', max(0, $sold - (float) $reservedByBarang->get($line->id_barang, 0)));

                    return $line;
                })->values();

                $sale->setRelation('returnItems', $returnItems);
            })
            ->filter(fn (Penjualan $sale) => $sale->returnItems->contains(fn ($line) => $line->qty_sisa_retur > 0))
            ->values();

        return view('penjualan.retur.create', [
            'title' => 'Ajukan Retur Penjualan',
            'routeBase' => $this->routeBase,
            'sales' => $sales,
            'sale' => $sales->firstWhere('id_penjualan', request()->integer('id_penjualan')),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'id_penjualan' => ['required', 'integer', 'exists:penjualan,id_penjualan'],
            'jenis_penyelesaian' => ['required', 'in:uang,potong_piutang,ganti_barang'],
            'alasan' => ['nullable', 'string', 'max:2000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.id_barang' => ['required', 'integer', 'exists:master_barang,id_barang'],
            'items.*.qty_dasar' => ['nullable', 'numeric', 'min:0'],
        ]);

        try {
            $sale = Penjualan::where('status', 'selesai')->findOrFail($validated['id_penjualan']);
            $items = collect($validated['items'])
                ->filter(fn ($item) => (float) ($item['qty_dasar'] ?? 0) > 0)
                ->values()
                ->all();

            $return = PenjualanService::createReturn(
                $sale,
                $items,
                $validated['jenis_penyelesaian'],
                $validated['alasan'] ?? '',
            );

            return redirect()->route("{$this->routeBase}.index")
                ->with('success', "Retur {$return->kode_retur} berhasil dicatat.");
        } catch (\Throwable $exception) {
            return back()->withInput()->with('error', $exception->getMessage());
        }
    }

    public function update(Request $request, int|string $id): RedirectResponse
    {
        try {
            $return = ReturPenjualan::findOrFail($id);
            PenjualanService::completeReturn($return);

            return redirect()->route("{$this->routeBase}.index")
                ->with('success', "Retur {$return->kode_retur} berhasil diselesaikan.");
        } catch (\Throwable $exception) {
            return back()->with('error', $exception->getMessage());
        }
    }

    public function show(int|string $id): View
    {
        $return = ReturPenjualan::with(['penjualan.pihak', 'penjualan.unitUsaha', 'detail.barang.satuanDasar'])
            ->findOrFail($id);

        return view('penjualan.retur.show', [
            'title' => "Retur {$return->kode_retur}",
            'routeBase' => $this->routeBase,
            'item' => $return,
        ]);
    }
}

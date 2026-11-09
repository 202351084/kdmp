<?php

namespace App\Http\Controllers\Penjualan;

use App\Http\Controllers\Concerns\ModuleCrudController;
use App\Models\Master\Barang;
use App\Models\Master\KasBank;
use App\Models\Master\UnitUsaha;
use App\Models\Penjualan\Penjualan;
use App\Services\PenjualanService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PenjualanController extends ModuleCrudController
{
    protected string $model = Penjualan::class;
    protected string $view = 'penjualan.index';
    protected string $title = 'Penjualan';
    protected string $routeBase = 'penjualan.penjualan';
    protected array $withRelations = ['pihak', 'unitUsaha', 'gudang'];

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'archive' => ['sometimes', 'in:1'],
            'year' => ['nullable', 'integer', 'min:1900', 'max:9999'],
            'month' => ['nullable', 'integer', 'between:1,12'],
            'day' => ['nullable', 'integer', 'between:1,31'],
        ]);
        $query = Penjualan::with(['pihak', 'unitUsaha', 'gudang']);

        if ($search = $request->query('q')) {
            $query->where(function ($builder) use ($search) {
                $builder->where('kode_penjualan', 'like', "%{$search}%")
                    ->orWhereHas('pihak', fn ($pihak) => $pihak->where('nama', 'like', "%{$search}%"));
            });
        }

        foreach (['year' => 'tanggal_transaksi', 'month' => 'tanggal_transaksi', 'day' => 'tanggal_transaksi'] as $filter => $column) {
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
            ? (clone $query)->selectRaw('COUNT(*) as total_transaksi, COALESCE(SUM(total_bayar), 0) as total_nilai')->first()
            : null;
        $items = $query
            ->orderByDesc('tanggal_transaksi')
            ->orderByDesc('id_penjualan')
            ->when($archiveActive, fn ($builder) => $builder->paginate(15)->withQueryString(), fn ($builder) => $builder->limit(5)->get());

        return view('penjualan.index', [
            'title' => 'Kasir / POS',
            'routeBase' => $this->routeBase,
            'items' => $items,
            'archiveActive' => $archiveActive,
            'archiveFilters' => $filters,
            'yearlySummary' => $yearlySummary,
        ]);
    }

    public function create(): View
    {
        $idEntitas = auth()->user()->id_entitas
            ?? (app()->bound('entitas_aktif') ? app('entitas_aktif') : null)
            ?? \App\Models\Tenant\Entitas::value('id_entitas');

        abort_unless($idEntitas, 403, 'Entitas aktif tidak ditemukan.');

        $products = Barang::query()
            ->join('barang_per_koperasi', function ($join) use ($idEntitas) {
                $join->on('barang_per_koperasi.id_barang', '=', 'master_barang.id_barang')
                    ->where('barang_per_koperasi.id_entitas', $idEntitas)
                    ->where('barang_per_koperasi.is_dijual', true);
            })
            ->where('master_barang.is_active', true)
            ->select('master_barang.*', 'barang_per_koperasi.harga_jual_standar')
            ->with(['satuanDasar', 'unitUsaha'])
            ->orderBy('master_barang.nama_barang')
            ->get();
        $products->each(fn ($product) => $product->setAttribute(
            'stok_demo',
            PenjualanService::availableDemoStock((int) $idEntitas, (int) $product->id_barang),
        ));
        $warehouseRecommendations = $this->dummyWarehouseRecommendations($products, (int) $idEntitas);

        return view('penjualan.pos', [
            'title' => 'Kasir / POS',
            'routeBase' => $this->routeBase,
            'products' => $products,
            'warehouseRecommendations' => $warehouseRecommendations,
            'units' => UnitUsaha::whereIn('id_unit_usaha', $products->pluck('id_unit_usaha')->unique())
                ->orderBy('nama_unit_usaha')
                ->get(),
            'bankAccounts' => KasBank::query()
                ->where('id_entitas', $idEntitas)
                ->where('jenis', 'bank')
                ->where('is_active', true)
                ->whereNotNull('no_rekening')
                ->where('no_rekening', '<>', '')
                ->orderByDesc('is_default')
                ->orderBy('nama')
                ->get(),
            'entitas' => \App\Models\Tenant\Entitas::find($idEntitas),
        ]);
    }

    /**
     * Read-only sample recommendations until the warehouse module provides real data.
     *
     * @param  Collection<int, Barang>  $products
     * @return Collection<int, object>
     */
    private function dummyWarehouseRecommendations(Collection $products, int $idEntitas): Collection
    {
        $availableProducts = $products
            ->filter(fn (Barang $product) => (float) $product->stok_demo > 0)
            ->values();

        if ($availableProducts->isEmpty()) {
            return collect();
        }

        $templates = [
            [
                'destination' => 'desa_lain',
                'destination_label' => 'Desa lain yang mengalami defisit',
                'quantity' => 10,
                'reason' => 'Contoh rekomendasi gudang: stok surplus dapat ditawarkan ke desa lain yang membutuhkan.',
            ],
            [
                'destination' => 'warga_lokal',
                'destination_label' => 'Warga lokal',
                'quantity' => 5,
                'reason' => 'Contoh rekomendasi gudang: komoditas tersedia untuk ditawarkan kepada warga lokal.',
            ],
        ];

        return collect($templates)
            ->map(function (array $template, int $index) use ($availableProducts, $idEntitas) {
                $product = $availableProducts->get($index % $availableProducts->count());

                return (object) [
                    'id_rekomendasi' => "demo-{$idEntitas}-{$template['destination']}-{$product->id_barang}",
                    'kode_rekomendasi' => sprintf('DEMO-GUDANG-JUAL-%03d', $index + 1),
                    'tujuan' => $template['destination'],
                    'tujuan_label' => $template['destination_label'],
                    'barang' => $product,
                    'qty_rekomendasi' => min(
                        (float) $product->stok_demo,
                        (float) $template['quantity'],
                    ),
                    'alasan' => $template['reason'],
                ];
            })
            ->values();
    }

    public function store(Request $request): RedirectResponse
    {
        $idEntitas = auth()->user()->id_entitas
            ?? (app()->bound('entitas_aktif') ? app('entitas_aktif') : null)
            ?? \App\Models\Tenant\Entitas::value('id_entitas');

        abort_unless($idEntitas, 403, 'Entitas aktif tidak ditemukan.');

        $validated = $request->validate([
            'nama_pembeli' => ['required', 'string', 'max:150'],
            'id_unit_usaha' => ['required', 'integer', 'exists:master_unit_usaha,id_unit_usaha'],
            'metode_bayar' => ['required', 'in:tunai,transfer,kredit'],
            'id_kas_bank' => [
                'required_if:metode_bayar,transfer',
                'nullable',
                'integer',
                Rule::exists('master_kas_bank', 'id_kas_bank')
                    ->where('id_entitas', $idEntitas)
                    ->where('jenis', 'bank')
                    ->where('is_active', true)
                    ->whereNotNull('no_rekening')
                    ->where('no_rekening', '<>', ''),
            ],
            'transfer_pengirim_nama' => ['required_if:metode_bayar,transfer', 'nullable', 'string', 'max:150'],
            'transfer_pengirim_bank' => ['required_if:metode_bayar,transfer', 'nullable', 'string', 'max:100'],
            'transfer_pengirim_rekening' => ['required_if:metode_bayar,transfer', 'nullable', 'string', 'max:50'],
            'transfer_no_referensi' => ['required_if:metode_bayar,transfer', 'nullable', 'string', 'max:100'],
            'transfer_dikonfirmasi' => ['exclude_unless:metode_bayar,transfer', 'required', 'accepted'],
            'tgl_jatuh_tempo' => [
                'required_if:metode_bayar,kredit',
                'nullable',
                'date',
                'after_or_equal:today',
            ],
            'nominal_bayar' => ['nullable', 'numeric', 'min:0'],
            'auto_print' => ['nullable', 'boolean'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.id_barang' => ['required', 'integer', 'distinct', 'exists:master_barang,id_barang'],
            'items.*.qty_dasar' => ['required', 'numeric', 'gt:0'],
        ]);

        try {
            $sale = PenjualanService::createSale(
                (int) $idEntitas,
                null,
                (int) $validated['id_unit_usaha'],
                $validated['metode_bayar'],
                $validated['items'],
                isset($validated['id_kas_bank']) ? (int) $validated['id_kas_bank'] : null,
                [
                    'pengirim_nama' => $validated['transfer_pengirim_nama'] ?? null,
                    'pengirim_bank' => $validated['transfer_pengirim_bank'] ?? null,
                    'pengirim_rekening' => $validated['transfer_pengirim_rekening'] ?? null,
                    'no_referensi' => $validated['transfer_no_referensi'] ?? null,
                    'dikonfirmasi' => isset($validated['transfer_dikonfirmasi']),
                ],
                $validated['tgl_jatuh_tempo'] ?? null,
                $validated['nama_pembeli'],
            );

            $nominalBayar = $validated['metode_bayar'] === 'kredit'
                ? 0.0
                : (float) ($validated['nominal_bayar'] ?? $sale->total_bayar);
            if ($validated['metode_bayar'] === 'transfer') {
                $nominalBayar = (float) $sale->total_bayar;
            }
            $kembalian = max(0, $nominalBayar - (float) $sale->total_bayar);

            return redirect()->route("{$this->routeBase}.show", $sale)
                ->with('success', "Penjualan {$sale->kode_penjualan} berhasil dicatat.")
                ->with('nominal_bayar', $nominalBayar)
                ->with('kembalian', $kembalian)
                ->with('auto_print', $request->boolean('auto_print'));
        } catch (\Throwable $exception) {
            return back()->withInput()->with('error', $exception->getMessage());
        }
    }

    public function show(int|string $id): View
    {
        $sale = Penjualan::with(['pihak', 'unitUsaha', 'gudang', 'kasBank', 'detail.barang.satuanDasar', 'returs.detail.barang.satuanDasar'])
            ->findOrFail($id);

        $returnedByBarang = $sale->returs
            ->flatMap(fn ($retur) => $retur->detail)
            ->groupBy('id_barang')
            ->map(fn ($details) => (float) $details->sum('qty_dasar'));

        $hasReturnableItems = $sale->status === 'selesai' && $sale->detail->contains(function ($line) use ($returnedByBarang) {
            $sold = (float) $line->qty_dasar;
            $returned = (float) $returnedByBarang->get($line->id_barang, 0);
            return ($sold - $returned) > 0.0001;
        });

        $nominalBayar = session('nominal_bayar')
            ?? ($sale->metode_bayar === 'kredit' ? 0.0 : (float) $sale->total_bayar);
        $kembalian = session('kembalian') ?? 0.0;
        $autoPrint = session('auto_print', false);
        $entitas = \App\Models\Tenant\Entitas::find($sale->id_entitas);

        return view('penjualan.show', [
            'title' => "Penjualan {$sale->kode_penjualan}",
            'routeBase' => $this->routeBase,
            'item' => $sale,
            'hasReturnableItems' => $hasReturnableItems,
            'nominalBayar' => $nominalBayar,
            'kembalian' => $kembalian,
            'autoPrint' => $autoPrint,
            'entitas' => $entitas,
        ]);
    }
}

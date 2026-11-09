<?php

namespace App\Http\Controllers\Pembelian;

use App\Http\Controllers\Concerns\ModuleCrudController;
use App\Models\Pembelian\ReturPembelian;
use App\Models\Pembelian\Pembelian;
use App\Services\PembelianService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ReturPembelianController extends ModuleCrudController
{
    protected string $model = ReturPembelian::class;
    protected string $view = 'pembelian.retur';
    protected string $title = 'Retur Pembelian';
    protected string $routeBase = 'pembelian.retur';
    protected array $withRelations = ['pembelian'];

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'archive' => ['sometimes', 'in:1'],
            'year' => ['nullable', 'integer', 'min:1900', 'max:9999'],
            'month' => ['nullable', 'integer', 'between:1,12'],
            'day' => ['nullable', 'integer', 'between:1,31'],
        ]);
        $purchases = $this->purchasesWithReturnQuantities();
        $pendingReturnRequests = $this->dummyReturnRequests($purchases);
        $query = ReturPembelian::with([
            'pembelian.pihak',
            'pembelian.detail',
            'detail.barang.satuanDasar',
            'detail.barang.konversiSatuan.satuan',
        ]);

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
        $returns = $query
            ->orderByDesc('tgl_retur')
            ->orderByDesc('id_retur')
            ->when($archiveActive, fn ($builder) => $builder->paginate(15)->withQueryString(), fn ($builder) => $builder->limit(5)->get());

        $returnCollection = $archiveActive ? $returns->getCollection() : $returns;
        $returnCollection->each(function (ReturPembelian $retur) {
            $retur->detail->each(function ($line) {
                $factor = $this->kilogramFactor($line->barang);
                $line->setAttribute('qty_kg', $factor ? (float) $line->qty_dasar / $factor : null);
            });
        });

        return view('pembelian.retur.dashboard', [
            'title' => $this->title,
            'routeBase' => $this->routeBase,
            'purchases' => $purchases,
            'pendingReturnRequests' => $pendingReturnRequests,
            'returns' => $returns,
            'archiveActive' => $archiveActive,
            'archiveFilters' => $filters,
            'yearlySummary' => $yearlySummary,
        ]);
    }

    public function create(): View
    {
        $purchases = $this->purchasesWithReturnQuantities()
            ->filter(fn (Pembelian $purchase) => $purchase->returItems->contains(fn ($line) => $line->qty_sisa_retur > 0))
            ->values();
        $pendingReturnRequests = $this->dummyReturnRequests($purchases);
        $selectedReturnRequest = $pendingReturnRequests
            ->firstWhere('id_permintaan_retur', request()->query('permintaan_retur'));

        return view('pembelian.retur.create', [
            'title' => 'Ajukan Retur Pembelian',
            'routeBase' => $this->routeBase,
            'purchases' => $purchases,
            'pendingReturnRequests' => $pendingReturnRequests,
            'selectedReturnRequest' => $selectedReturnRequest,
            'pembelian' => $selectedReturnRequest?->pembelian,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'id_permintaan_retur' => 'required|string|max:100',
            'jenis_penyelesaian' => 'required|in:uang,potong_hutang,ganti_barang',
        ]);

        try {
            $purchases = $this->purchasesWithReturnQuantities();
            $returnRequest = $this->dummyReturnRequests($purchases)
                ->firstWhere('id_permintaan_retur', $validated['id_permintaan_retur']);

            if (! $returnRequest) {
                throw new \InvalidArgumentException('Permintaan retur gudang tidak ditemukan atau sudah diproses.');
            }

            PembelianService::createRetur(
                $returnRequest->pembelian,
                [[
                    'id_barang' => $returnRequest->id_barang,
                    'qty_dasar' => $returnRequest->qty_diminta,
                ]],
                $validated['jenis_penyelesaian'],
                $returnRequest->alasan,
            );

            $processedRequests = collect(session('pembelian_dummy_return_requests_processed', []))
                ->push($returnRequest->id_permintaan_retur)
                ->unique()
                ->values()
                ->all();
            session(['pembelian_dummy_return_requests_processed' => $processedRequests]);

            return redirect()->route('pembelian.retur.index')
                ->with('success', 'Permintaan retur dari gudang berhasil diproses.');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    /**
     * Read-only example requests until the warehouse module provides real requests.
     *
     * @param  \Illuminate\Support\Collection<int, Pembelian>  $purchases
     * @return \Illuminate\Support\Collection<int, object>
     */
    private function dummyReturnRequests($purchases)
    {
        $processed = collect(session('pembelian_dummy_return_requests_processed', []));

        return $purchases
            ->flatMap(function (Pembelian $pembelian) {
                return $pembelian->returItems
                    ->filter(fn ($line) => (float) $line->qty_sisa_retur > 0)
                    ->map(function ($line) use ($pembelian) {
                        $qty = min(1.0, (float) $line->qty_sisa_retur);

                        return (object) [
                            'id_permintaan_retur' => "demo-retur-{$pembelian->id_pembelian}-{$line->id_barang}",
                            'kode_permintaan' => sprintf('DEMO-GUDANG-RET-%03d-%03d', $pembelian->id_pembelian, $line->id_barang),
                            'pembelian' => $pembelian,
                            'barang' => $line->barang,
                            'line' => $line,
                            'id_barang' => (int) $line->id_barang,
                            'qty_diminta' => $qty,
                            'alasan' => 'Kerusakan barang saat pemeriksaan/penerimaan di gudang.',
                        ];
                    });
            })
            ->take(2)
            ->reject(fn (object $item) => $processed->contains($item->id_permintaan_retur))
            ->values();
    }

    public function approve(ReturPembelian $retur): RedirectResponse
    {
        try {
            PembelianService::approveRetur($retur);

            return redirect()->route('pembelian.retur.invoice', $retur)
                ->with('success', 'Retur pembelian selesai dan invoice siap ditinjau Akuntansi.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function invoice(ReturPembelian $retur): View
    {
        abort_unless(in_array($retur->status, ['disetujui', 'selesai'], true), 404);

        $retur->load([
            'pembelian.pihak',
            'pembelian.unitUsaha',
            'pembelian.gudang',
            'pembelian.detail',
            'detail.barang.satuanDasar',
            'detail.barang.konversiSatuan.satuan',
        ]);

        $retur->detail->each(function ($line) {
            $barang = $line->barang;
            $factor = $this->kilogramFactor($barang);

            $line->setAttribute('qty_kg', $factor ? (float) $line->qty_dasar / $factor : null);
            $line->setAttribute('hpp_per_kg', $factor ? (float) $line->hpp_rata2 * $factor : null);
        });

        return view('pembelian.retur.invoice', [
            'item' => $retur,
        ]);
    }

    private function purchasesWithReturnQuantities()
    {
        $purchases = Pembelian::whereIn('status', ['disetujui', 'diterima', 'selesai'])
            ->with(['pihak', 'detail.barang.satuanDasar', 'detail.barang.konversiSatuan.satuan', 'returs.detail'])
            ->latest('id_pembelian')
            ->get();
        return $purchases->each(function (Pembelian $pembelian) {
                $requestedByBarang = $pembelian->returs
                    ->where('status', 'diajukan')
                    ->flatMap(fn ($retur) => $retur->detail)
                    ->groupBy('id_barang')
                    ->map(fn ($details) => (float) $details->sum('qty_dasar'));
                $returnedByBarang = $pembelian->returs
                    ->whereIn('status', ['disetujui', 'selesai'])
                    ->flatMap(fn ($retur) => $retur->detail)
                    ->groupBy('id_barang')
                    ->map(fn ($details) => (float) $details->sum('qty_dasar'));
                $reservedByBarang = $pembelian->returs
                    ->flatMap(fn ($retur) => $retur->detail)
                    ->groupBy('id_barang')
                    ->map(fn ($details) => (float) $details->sum('qty_dasar'));

                $returnItems = $pembelian->detail->groupBy('id_barang')->map(function ($lines) use ($requestedByBarang, $returnedByBarang, $reservedByBarang) {
                    $detail = $lines->first();
                    $qtyDibeli = (float) $lines->sum('qty_dasar');
                    $requested = (float) $requestedByBarang->get($detail->id_barang, 0);
                    $returned = (float) $returnedByBarang->get($detail->id_barang, 0);
                    $reserved = (float) $reservedByBarang->get($detail->id_barang, 0);
                    $nilaiBeli = $lines->sum(function ($line) {
                        $faktor = (float) $line->faktor_konversi;
                        $hargaDasar = $faktor > 0
                            ? (float) $line->harga_satuan_input / $faktor
                            : (float) $line->harga_satuan_input;

                        return (float) $line->qty_dasar * $hargaDasar;
                    });

                    $factor = $this->kilogramFactor($detail->barang);

                    if (! $factor) {
                        return null;
                    }

                    $qtyDibeliKg = $qtyDibeli / $factor;
                    $detail->setAttribute('qty_dasar', $qtyDibeliKg);
                    $detail->setAttribute('qty_diajukan', $requested / $factor);
                    $detail->setAttribute('qty_diretur', $returned / $factor);
                    $detail->setAttribute('qty_sisa_retur', max(0, $qtyDibeli - $reserved) / $factor);
                    $detail->setAttribute(
                        'hpp_retur_dasar',
                        $qtyDibeliKg > 0 ? $nilaiBeli / $qtyDibeliKg : 0,
                    );
                    return $detail;
                })->filter()->values();

                $pembelian->setRelation('returItems', $returnItems);
            });
    }

    private function kilogramFactor($barang): ?float
    {
        if (! $barang) {
            return null;
        }

        if (strtoupper($barang->satuanDasar?->kode_satuan ?? '') === 'KG') {
            return 1.0;
        }

        $factor = $barang->konversiSatuan
            ->first(fn ($conversion) => strtoupper($conversion->satuan?->kode_satuan ?? '') === 'KG')
            ?->faktor_ke_dasar;

        return $factor !== null && (float) $factor > 0 ? (float) $factor : null;
    }
}

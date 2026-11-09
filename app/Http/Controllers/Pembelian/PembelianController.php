<?php

namespace App\Http\Controllers\Pembelian;

use App\Http\Controllers\Concerns\ModuleCrudController;
use App\Models\Master\Gudang;
use App\Models\Pembelian\Pembelian;
use App\Models\Master\Barang;
use App\Models\Master\KasBank;
use App\Models\Master\Pihak;
use App\Services\PembelianService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PembelianController extends ModuleCrudController
{
    protected string $model = Pembelian::class;
    protected string $view = 'pembelian.index';
    protected string $title = 'Pembelian';
    protected string $routeBase = 'pembelian.pembelian';
    protected array $withRelations = ['pihak', 'unitUsaha', 'gudang', 'kasBank', 'detail.barang.satuanDasar', 'detail.satuanInput'];

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'archive' => ['sometimes', 'in:1'],
            'year' => ['nullable', 'integer', 'min:1900', 'max:9999'],
            'month' => ['nullable', 'integer', 'between:1,12'],
            'day' => ['nullable', 'integer', 'between:1,31'],
        ]);
        $query = Pembelian::with(['pihak', 'unitUsaha', 'gudang', 'kasBank']);

        if ($search = $request->query('q')) {
            $query->where(function ($builder) use ($search) {
                $builder->where('kode_pembelian', 'like', "%{$search}%")
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
            ? (clone $query)->selectRaw('COUNT(*) as total_transaksi, COALESCE(SUM(total_pembelian), 0) as total_nilai')->first()
            : null;
        $items = $query
            ->orderByDesc('tanggal_transaksi')
            ->orderByDesc('id_pembelian')
            ->when($archiveActive, fn ($builder) => $builder->paginate(15)->withQueryString(), fn ($builder) => $builder->limit(5)->get());

        return view('pembelian.index', [
            'title' => $this->title,
            'routeBase' => $this->routeBase,
            'items' => $items,
            'archiveActive' => $archiveActive,
            'archiveFilters' => $filters,
            'yearlySummary' => $yearlySummary,
        ]);
    }

    /** Tampilkan form pembelian dari desa lain/warga lokal dan stok konsinyasi. */
    public function create(): View
    {
        $idEntitas = auth()->user()->id_entitas
            ?? (app()->bound('entitas_aktif') ? app('entitas_aktif') : null)
            ?? \App\Models\Tenant\Entitas::value('id_entitas');

        $pihaks = Pihak::where('id_entitas', $idEntitas)
            ->where('is_active', true)
            ->whereIn('jenis_pihak', ['entitas_lain', 'petani', 'warga'])
            ->orderBy('nama')
            ->get();
        $barangs = Barang::with(['unitUsaha', 'satuanDasar', 'konversiSatuan.satuan'])
            ->where('is_active', true)
            ->orderBy('nama_barang')
            ->get();
        $barangKgIds = $this->barangKgIds($barangs);
        $gudangs = Gudang::where('id_entitas', $idEntitas)
            ->where('is_active', true)
            ->orderBy('nama_gudang')
            ->get();
        $kasBanks = KasBank::when($idEntitas, fn ($q) => $q->where('id_entitas', $idEntitas))
            ->where('is_active', true)
            ->orderBy('jenis')
            ->orderBy('nama')
            ->get();

        if ($kasBanks->isEmpty()) {
            $kasBanks = KasBank::where('is_active', true)->orderBy('jenis')->orderBy('nama')->get();
        }

        $permintaanPembelianPending = $this->dummyPurchaseRequests($barangs, $gudangs, $pihaks, (int) $idEntitas);
        $permintaanPembelian = $permintaanPembelianPending
            ->firstWhere('id_permintaan_pembelian', request()->query('permintaan'));

        return view('pembelian.create', [
            'title' => 'Tambah Pembelian',
            'routeBase' => $this->routeBase,
            'pihaks' => $pihaks,
            'barangs' => $barangs,
            'barangKgIds' => $barangKgIds,
            'gudangs' => $gudangs,
            'kasBanks' => $kasBanks,
            'permintaanPembelian' => $permintaanPembelian,
            'permintaanPembelianPending' => $permintaanPembelianPending,
        ]);
    }

    /** Simpan pembelian dari desa lain atau warga lokal. */
    public function store(Request $request): RedirectResponse
    {
        try {
            $idEntitas = auth()->user()->id_entitas
                ?? (app()->bound('entitas_aktif') ? app('entitas_aktif') : null)
                ?? \App\Models\Tenant\Entitas::value('id_entitas');

            if (!$idEntitas) {
                throw new \RuntimeException('Entitas aktif tidak ditemukan. Masuk menggunakan akun koperasi yang terhubung ke entitas.');
            }

            $source = $request->input('source');
            $pihakExistsRule = Rule::exists('master_pihak', 'id_pihak')
                ->where('id_entitas', $idEntitas)
                ->where('is_active', true)
                ->whereIn('jenis_pihak', in_array($source, ['desa_lain', 'pengambilan_konsinyasi'], true)
                    ? ['entitas_lain']
                    : ['petani', 'warga']);
            if ($source === 'pengambilan_konsinyasi') {
                $pihakExistsRule->whereNotNull('id_entitas_mitra');
            }

            $validated = $request->validate([
                'source' => 'required|in:desa_lain,warga_lokal,pengambilan_konsinyasi',
                'id_permintaan_pembelian' => [
                    'nullable',
                    'string',
                    'max:40',
                    Rule::prohibitedIf($request->input('source') === 'pengambilan_konsinyasi'),
                ],
                'id_pihak' => [
                    'required',
                    'integer',
                    $pihakExistsRule,
                ],
                'jenis_pembayaran' => [
                    'required',
                    Rule::in($request->input('source') === 'pengambilan_konsinyasi'
                        ? ['kredit']
                        : ['tunai', 'transfer', 'kredit']),
                ],
                'id_kas_bank' => [
                    'nullable',
                    Rule::prohibitedIf($request->input('source') === 'pengambilan_konsinyasi'),
                    'exists:master_kas_bank,id_kas_bank',
                    function ($attribute, $value, $fail) use ($idEntitas) {
                        if ($value && ! KasBank::where('id_kas_bank', $value)->where('id_entitas', $idEntitas)->exists()) {
                            $fail('Kas/Bank tidak tersedia untuk entitas aktif.');
                        }
                    },
                ],
                'tgl_jatuh_tempo' => [
                    'nullable',
                    'date_format:Y-m-d',
                    'after_or_equal:today',
                    Rule::requiredIf($request->input('jenis_pembayaran') === 'kredit'),
                ],
                'id_unit_usaha' => ['required', 'exists:master_unit_usaha,id_unit_usaha'],
                'id_gudang' => [
                    'required',
                    Rule::exists('gudang', 'id_gudang')->where('id_entitas', $idEntitas)->where('is_active', true),
                ],
                'items' => ['required', 'array', 'min:1'],
                'items.*.id_barang' => ['required', 'exists:master_barang,id_barang'],
                'items.*.id_satuan' => ['required', 'exists:satuan,id'],
                'items.*.qty_dasar' => ['required', 'numeric', 'min:0.01'],
                'items.*.harga_satuan' => [
                    'required',
                    'numeric',
                    'min:' . ($request->input('source') === 'pengambilan_konsinyasi' ? '0.01' : '0'),
                ],
            ]);

            $permintaanPembelian = null;
            if (! empty($validated['id_permintaan_pembelian'])) {
                $barangs = Barang::with(['unitUsaha', 'satuanDasar', 'konversiSatuan.satuan'])
                    ->where('is_active', true)
                    ->orderBy('nama_barang')
                    ->get();
                $gudangs = Gudang::where('id_entitas', $idEntitas)
                    ->where('is_active', true)
                    ->orderBy('nama_gudang')
                    ->get();
                $pihaks = Pihak::where('id_entitas', $idEntitas)
                    ->where('is_active', true)
                    ->whereIn('jenis_pihak', ['entitas_lain', 'petani', 'warga'])
                    ->orderBy('nama')
                    ->get();
                $permintaanPembelian = $this->dummyPurchaseRequests(
                    $barangs,
                    $gudangs,
                    $pihaks,
                    (int) $idEntitas,
                )->firstWhere('id_permintaan_pembelian', $validated['id_permintaan_pembelian']);

                if (! $permintaanPembelian) {
                    throw new \RuntimeException('Permintaan dummy tidak ditemukan atau sudah diproses.');
                }

                if ($validated['source'] !== $permintaanPembelian->sumber_pembelian) {
                    throw new \RuntimeException('Sumber pembelian harus sesuai rekomendasi permintaan.');
                }

                $requestedItems = array_values($validated['items']);
                if (
                    (int) $validated['id_gudang'] !== (int) $permintaanPembelian->gudang->id_gudang
                    || (int) $validated['id_unit_usaha'] !== (int) $permintaanPembelian->barang->id_unit_usaha
                    || count($requestedItems) !== 1
                    || (int) $requestedItems[0]['id_barang'] !== (int) $permintaanPembelian->id_barang
                    || (int) $requestedItems[0]['id_satuan'] !== (int) $permintaanPembelian->barang->id_satuan_dasar
                    || abs((float) $requestedItems[0]['qty_dasar'] - (float) $permintaanPembelian->qty_diminta) > 0.00005
                ) {
                    throw new \RuntimeException('Barang, jumlah, unit usaha, dan gudang harus sesuai notifikasi yang dipilih.');
                }

                if (! $permintaanPembelian->supplier_recommendations->contains('id_pihak', (int) $validated['id_pihak'])) {
                    throw new \RuntimeException('Pilih pemasok yang termasuk dalam rekomendasi permintaan.');
                }
            }

            $idKasBank = $validated['id_kas_bank'] ?? null;
            if (! $idKasBank && in_array($validated['jenis_pembayaran'], ['tunai', 'transfer'], true)) {
                $idKasBank = KasBank::where('id_entitas', $idEntitas)
                    ->where('jenis', $validated['jenis_pembayaran'] === 'tunai' ? 'kas' : 'bank')
                    ->where('is_active', true)
                    ->value('id_kas_bank')
                    ?? KasBank::where('is_active', true)->value('id_kas_bank');
            }

            $items = collect($validated['items'] ?? [])
                ->map(function ($item) {
                    return [
                        'id_barang' => (int) $item['id_barang'],
                        'id_satuan' => (int) $item['id_satuan'],
                        'qty_dasar' => (float) $item['qty_dasar'],
                        'harga_satuan' => (float) $item['harga_satuan'],
                    ];
                })
                ->values()
                ->all();

            $pembelian = PembelianService::createQuickPurchase(
                $idEntitas,
                (int) $validated['id_pihak'],
                (int) $validated['id_unit_usaha'],
                (int) $validated['id_gudang'],
                $items,
                $validated['jenis_pembayaran'],
                $validated['tgl_jatuh_tempo'] ?? null,
                $idKasBank ? (int) $idKasBank : null,
                $validated['source'],
            );

            if ($permintaanPembelian) {
                $processed = collect(session('pembelian_dummy_requests_processed', []))
                    ->push($permintaanPembelian->id_permintaan_pembelian)
                    ->unique()
                    ->values()
                    ->all();
                session(['pembelian_dummy_requests_processed' => $processed]);
            }

            return redirect()->route("{$this->routeBase}.index")
                ->with('success', "Pembelian {$pembelian->kode_pembelian} berhasil ditambahkan.");
        } catch (\Exception $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    /**
     * Temporary, read-only examples until the warehouse module provides real requests.
     *
     * @param  Collection<int, Barang>  $barangs
     * @param  Collection<int, Gudang>  $gudangs
     * @param  Collection<int, Pihak>  $pihaks
     * @return Collection<int, object>
     */
    private function dummyPurchaseRequests(
        Collection $barangs,
        Collection $gudangs,
        Collection $pihaks,
        int $idEntitas,
    ): Collection {
        $barangKgIds = $this->barangKgIds($barangs);
        $barangsKg = $barangs->whereIn('id_barang', $barangKgIds)->values();

        if ($barangsKg->isEmpty() || $gudangs->isEmpty()) {
            return collect();
        }

        $processed = collect(session('pembelian_dummy_requests_processed', []));
        $templates = [
            ['id' => "demo-{$idEntitas}-gula", 'code' => 'DEMO-GUDANG-001', 'source' => 'desa_lain', 'quantity' => 10],
            ['id' => "demo-{$idEntitas}-beras", 'code' => 'DEMO-GUDANG-002', 'source' => 'warga_lokal', 'quantity' => 25],
        ];

        return collect($templates)
            ->values()
            ->map(function (array $template, int $index) use ($barangsKg, $gudangs, $pihaks) {
                $barang = $barangsKg->get($index % $barangsKg->count());
                $idSatuanKg = strtoupper($barang->satuanDasar?->kode_satuan ?? '') === 'KG'
                    ? $barang->id_satuan_dasar
                    : $barang->konversiSatuan
                        ->first(fn ($conversion) => strtoupper($conversion->satuan?->kode_satuan ?? '') === 'KG')
                        ?->id_satuan;
                $recommendedTypes = $template['source'] === 'desa_lain'
                    ? ['entitas_lain']
                    : ['petani', 'warga'];

                return (object) [
                    'id_permintaan_pembelian' => $template['id'],
                    'kode_permintaan' => $template['code'],
                    'sumber_pembelian' => $template['source'],
                    'barang' => $barang,
                    'id_barang' => $barang->id_barang,
                    'id_satuan' => $idSatuanKg,
                    'gudang' => $gudangs->first(),
                    'qty_diminta' => $template['quantity'],
                    'supplier_recommendations' => $pihaks
                        ->whereIn('jenis_pihak', $recommendedTypes)
                        ->take(3)
                        ->values(),
                ];
            })
            ->reject(fn (object $requestItem) => $processed->contains($requestItem->id_permintaan_pembelian))
            ->values();
    }

    /**
     * Tampilkan detail pembelian
     */
    public function show(int|string $id): View
    {
        $pembelian = Pembelian::with($this->withRelations)->findOrFail($id);

        return view('pembelian.show', [
            'title' => "Pembelian {$pembelian->kode_pembelian}",
            'routeBase' => $this->routeBase,
            'item' => $pembelian,
        ]);
    }

    public function invoice(Pembelian $pembelian): View
    {
        abort_unless(in_array($pembelian->status, ['disetujui', 'diterima', 'selesai'], true), 404);

        $pembelian->load([
            'pihak',
            'unitUsaha',
            'gudang',
            'kasBank',
            'detail.barang.satuanDasar',
            'detail.barang.konversiSatuan.satuan',
            'detail.satuanInput',
        ]);

        $pembelian->detail->each(function ($line) {
            $factor = strtoupper($line->barang?->satuanDasar?->kode_satuan ?? '') === 'KG'
                ? 1
                : (float) ($line->barang?->konversiSatuan
                    ->first(fn ($conversion) => strtoupper($conversion->satuan?->kode_satuan ?? '') === 'KG')
                    ?->faktor_ke_dasar ?? 0);

            $qtyKg = $factor > 0 ? (float) $line->qty_dasar / $factor : null;
            $line->setAttribute('qty_kg', $qtyKg);
            $line->setAttribute('harga_per_kg', $qtyKg > 0 ? (float) $line->subtotal / $qtyKg : null);
        });

        return view('pembelian.invoice', [
            'item' => $pembelian,
        ]);
    }

    /**
     * Form edit pembelian (hanya untuk status draft)
     */
    public function edit(int|string $id): View
    {
        $pembelian = Pembelian::with(['detail.barang.satuanDasar', 'detail.barang.konversiSatuan.satuan', 'detail.satuanInput', 'pihak', 'unitUsaha', 'gudang', 'kasBank'])
            ->findOrFail($id);

        abort_if(
            $pembelian->status !== 'draft',
            403,
            "Pembelian dengan status '{$pembelian->status}' tidak dapat diedit. Menu edit hanya tersedia untuk pembelian berstatus draft."
        );

        $idEntitas = auth()->user()->id_entitas
            ?? (app()->bound('entitas_aktif') ? app('entitas_aktif') : null)
            ?? \App\Models\Tenant\Entitas::value('id_entitas');

        $pihaks = Pihak::when($idEntitas, fn ($q) => $q->where('id_entitas', $idEntitas))->get();
        $barangs = Barang::with(['unitUsaha', 'satuanDasar', 'konversiSatuan.satuan'])->forEntitas($idEntitas)
            ->where('is_active', true)
            ->orderBy('nama_barang')
            ->get();
        $barangKgIds = $this->barangKgIds($barangs);
        $editItems = $pembelian->detail->map(function ($detail) {
            $barang = $detail->barang;
            $kgConversion = strtoupper($barang?->satuanDasar?->kode_satuan ?? '') === 'KG'
                ? null
                : $barang?->konversiSatuan
                    ->first(fn ($conversion) => strtoupper($conversion->satuan?->kode_satuan ?? '') === 'KG');
            $factorKg = strtoupper($barang?->satuanDasar?->kode_satuan ?? '') === 'KG'
                ? 1.0
                : (float) ($kgConversion?->faktor_ke_dasar ?? 0);

            if ($factorKg <= 0) {
                return [
                    'id_barang' => $detail->id_barang,
                    'id_satuan' => $detail->id_satuan_input,
                    'qty_dasar' => $detail->qty_input,
                    'harga_satuan' => $detail->harga_satuan_input,
                ];
            }

            $qtyKg = (float) $detail->qty_dasar / $factorKg;

            return [
                'id_barang' => $detail->id_barang,
                'id_satuan' => $kgConversion?->id_satuan ?? $barang->id_satuan_dasar,
                'qty_dasar' => $qtyKg,
                'harga_satuan' => $qtyKg > 0 ? (float) $detail->subtotal / $qtyKg : 0,
            ];
        });

        $kasBanks = KasBank::when($idEntitas, fn ($q) => $q->where('id_entitas', $idEntitas))
            ->where('is_active', true)
            ->orderBy('jenis')
            ->orderBy('nama')
            ->get();

        if ($kasBanks->isEmpty()) {
            $kasBanks = KasBank::where('is_active', true)->orderBy('jenis')->orderBy('nama')->get();
        }

        return view('pembelian.edit', [
            'title' => "Edit Pembelian {$pembelian->kode_pembelian}",
            'routeBase' => $this->routeBase,
            'item' => $pembelian,
            'pihaks' => $pihaks,
            'barangs' => $barangs,
            'barangKgIds' => $barangKgIds,
            'editItems' => $editItems,
            'kasBanks' => $kasBanks,
        ]);
    }

    /**
     * Update pembelian (hanya untuk status draft)
     */
    public function update(Request $request, int|string $id): RedirectResponse
    {
        $pembelian = Pembelian::findOrFail($id);

        if ($pembelian->status !== 'draft') {
            return redirect()->route("{$this->routeBase}.show", $pembelian)
                ->with('error', "Pembelian dengan status '{$pembelian->status}' tidak dapat diedit. Menu edit hanya tersedia untuk pembelian berstatus draft.");
        }

        $idEntitas = auth()->user()->id_entitas
            ?? (app()->bound('entitas_aktif') ? app('entitas_aktif') : null)
            ?? \App\Models\Tenant\Entitas::value('id_entitas');
        $isConsignmentIntake = $pembelian->sumber_pembelian === 'pengambilan_konsinyasi';
        $pihakExistsRule = Rule::exists('master_pihak', 'id_pihak')
            ->where('id_entitas', $idEntitas)
            ->where('is_active', true)
            ->whereIn('jenis_pihak', $isConsignmentIntake ? ['entitas_lain'] : ['entitas_lain', 'petani', 'warga']);
        if ($isConsignmentIntake) {
            $pihakExistsRule->whereNotNull('id_entitas_mitra');
        }

        $validated = $request->validate([
            'id_pihak' => [
                'required',
                'integer',
                $pihakExistsRule,
            ],
            'jenis_pembayaran' => [
                'required',
                Rule::in($isConsignmentIntake ? ['kredit'] : ['tunai', 'transfer', 'kredit']),
            ],
            'id_kas_bank' => [
                'nullable',
                Rule::prohibitedIf($isConsignmentIntake),
                Rule::requiredIf(in_array($request->input('jenis_pembayaran'), ['tunai', 'transfer'], true)),
                Rule::exists('master_kas_bank', 'id_kas_bank')
                    ->where('id_entitas', $idEntitas),
            ],
            'tgl_jatuh_tempo' => [
                'nullable',
                'date_format:Y-m-d',
                'after_or_equal:today',
                Rule::requiredIf($request->input('jenis_pembayaran') === 'kredit'),
            ],
            'id_unit_usaha' => 'nullable|exists:master_unit_usaha,id_unit_usaha',
            'id_gudang' => ['nullable', 'integer', new TenantOwnershipRule('gudang', 'id_gudang')],
            'items' => 'required|array|min:1',
            'items.*.id_barang' => 'required|exists:master_barang,id_barang',
            'items.*.id_satuan' => 'required|exists:satuan,id',
            'items.*.qty_dasar' => 'required|numeric|min:0.01',
            'items.*.harga_satuan' => [
                'required',
                'numeric',
                'min:' . ($isConsignmentIntake ? '0.01' : '0'),
            ],
        ]);

        try {
            PembelianService::updatePurchase(
                $pembelian,
                $validated,
            );

            return redirect()->route("{$this->routeBase}.show", $pembelian)
                ->with('success', "Pembelian {$pembelian->kode_pembelian} berhasil diperbarui.");
        } catch (\Exception $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    /**
     * Approve pembelian
     */
    public function approve(Pembelian $pembelian): RedirectResponse
    {
        try {
            PembelianService::approvePembelian($pembelian);

            return redirect()->route("{$this->routeBase}.invoice", $pembelian)
                ->with('success', 'Pembelian disetujui dan invoice siap ditinjau Akuntansi.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }

    }

    /**
     * @param  Collection<int, Barang>  $barangs
     * @return list<int>
     */
    private function barangKgIds(Collection $barangs): array
    {
        return $barangs
            ->filter(fn (Barang $barang) => strtoupper($barang->satuanDasar?->kode_satuan ?? '') === 'KG'
                || $barang->konversiSatuan->contains(
                    fn ($conversion) => strtoupper($conversion->satuan?->kode_satuan ?? '') === 'KG',
                ))
            ->pluck('id_barang')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
    }

    public function cancel(Pembelian $pembelian): RedirectResponse
    {
        try {
            PembelianService::cancelPurchase($pembelian);

            return redirect()->route("{$this->routeBase}.index")
                ->with('success', "Pembelian {$pembelian->kode_pembelian} dibatalkan.");
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Tampilkan form GRN (penerimaan barang)
     */
    public function createGrn(Pembelian $pembelian): RedirectResponse
    {
        return redirect()->route("{$this->routeBase}.show", $pembelian)
            ->with('info', 'Penerimaan Gudang belum dihubungkan pada tahap ini. Pembelian tetap tersimpan di modul Pembelian.');
    }

    /**
     * Store GRN
     */
    public function storeGrn(Request $request, Pembelian $pembelian): RedirectResponse
    {
        return redirect()->route("{$this->routeBase}.show", $pembelian)
            ->with('info', 'Penerimaan Gudang belum dihubungkan pada tahap ini.');
    }

    /**
     * Tampilkan retur pembelian
     */
    public function showRetur(Pembelian $pembelian): RedirectResponse
    {
        return redirect()->route('pembelian.retur.index');
    }
}

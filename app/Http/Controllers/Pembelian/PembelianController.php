<?php

namespace App\Http\Controllers\Pembelian;

use App\Http\Controllers\Concerns\ModuleCrudController;
use App\Models\Pembelian\Pembelian;
use App\Models\Perencanaan\PermintaanPengadaan;
use App\Models\Master\Barang;
use App\Models\Master\KasBank;
use App\Models\Master\Pihak;
use App\Models\Master\UnitUsaha;
use App\Rules\TenantOwnershipRule;
use App\Services\PembelianService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
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
        $query = Pembelian::with(['pihak', 'unitUsaha', 'gudang', 'kasBank']);

        if ($search = $request->query('q')) {
            $query->where(function ($builder) use ($search) {
                $builder->where('kode_pembelian', 'like', "%{$search}%")
                    ->orWhereHas('pihak', fn ($pihak) => $pihak->where('nama', 'like', "%{$search}%"));
            });
        }

        return view('pembelian.index', [
            'title' => $this->title,
            'routeBase' => $this->routeBase,
            'items' => $query->latest('id_pembelian')->paginate(15)->withQueryString(),
        ]);
    }

    /**
     * Tampilkan form create pembelian: pilih PR atau buat quick purchase
     */
    public function create(): View
    {
        $idEntitas = auth()->user()->id_entitas
            ?? (app()->bound('entitas_aktif') ? app('entitas_aktif') : null)
            ?? \App\Models\Tenant\Entitas::value('id_entitas');

        $prs = PermintaanPengadaan::where('status', 'disetujui')
            ->when($idEntitas, fn ($q) => $q->where('id_entitas', $idEntitas))
            ->get();
        $pihaks = Pihak::when($idEntitas, fn ($q) => $q->where('id_entitas', $idEntitas))->get();
        $barangs = Barang::with(['unitUsaha', 'satuanDasar', 'konversiSatuan.satuan'])->forEntitas($idEntitas)
            ->where('is_active', true)
            ->orderBy('nama_barang')
            ->get();

        $kasBanks = KasBank::when($idEntitas, fn ($q) => $q->where('id_entitas', $idEntitas))
            ->where('is_active', true)
            ->orderBy('jenis')
            ->orderBy('nama')
            ->get();

        if ($kasBanks->isEmpty()) {
            $kasBanks = KasBank::where('is_active', true)->orderBy('jenis')->orderBy('nama')->get();
        }

        return view('pembelian.create', [
            'title' => 'Tambah Pembelian',
            'routeBase' => $this->routeBase,
            'prs' => $prs,
            'pihaks' => $pihaks,
            'barangs' => $barangs,
            'kasBanks' => $kasBanks,
        ]);
    }

    /**
     * Store: Create from PR atau Quick Purchase
     */
    public function store(Request $request): RedirectResponse
    {
        try {
            $idEntitas = auth()->user()->id_entitas
                ?? (app()->bound('entitas_aktif') ? app('entitas_aktif') : null)
                ?? \App\Models\Tenant\Entitas::value('id_entitas');

            if (!$idEntitas) {
                throw new \RuntimeException('Entitas aktif tidak ditemukan. Masuk menggunakan akun koperasi yang terhubung ke entitas.');
            }

            $validated = $request->validate([
                'source' => 'required|in:from_pr,quick_purchase',
                'id_permintaan' => [
                    'exclude_unless:source,from_pr',
                    'required',
                    Rule::exists('permintaan_pengadaan', 'id_permintaan')
                        ->where('id_entitas', $idEntitas)
                        ->where('status', 'disetujui'),
                ],
                'id_pihak' => 'required|exists:master_pihak,id_pihak',
                'jenis_pembayaran' => 'required|in:tunai,transfer,kredit',
                'id_kas_bank' => [
                    'nullable',
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
                    Rule::requiredIf($request->input('jenis_pembayaran') === 'kredit'),
                ],
                'id_unit_usaha' => ['exclude_unless:source,quick_purchase', 'required', 'exists:master_unit_usaha,id_unit_usaha'],
                'id_gudang' => [
                    'exclude_unless:source,quick_purchase',
                    'required',
                    'integer',
                    new TenantOwnershipRule('gudang', 'id_gudang'),
                ],
                'items' => ['exclude_unless:source,quick_purchase', 'required', 'array', 'min:1'],
                'items.*.id_barang' => ['required', 'exists:master_barang,id_barang'],
                'items.*.id_satuan' => ['required', 'exists:satuan,id'],
                'items.*.qty_dasar' => ['required', 'numeric', 'min:0.01'],
                'items.*.harga_satuan' => ['required', 'numeric', 'min:0'],
            ]);

            $idKasBank = $validated['id_kas_bank'] ?? null;
            if (! $idKasBank && in_array($validated['jenis_pembayaran'], ['tunai', 'transfer'], true)) {
                $idKasBank = KasBank::where('id_entitas', $idEntitas)
                    ->where('jenis', $validated['jenis_pembayaran'] === 'tunai' ? 'kas' : 'bank')
                    ->where('is_active', true)
                    ->value('id_kas_bank')
                    ?? KasBank::where('is_active', true)->value('id_kas_bank');
            }

            if ($validated['source'] === 'from_pr') {
                $pr = PermintaanPengadaan::findOrFail($validated['id_permintaan']);
                $pembelian = PembelianService::createFromPR(
                    $pr,
                    (int) $validated['id_pihak'],
                    $validated['jenis_pembayaran'],
                    $validated['tgl_jatuh_tempo'] ?? null,
                    $idKasBank ? (int) $idKasBank : null,
                );
            } else {
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
                );
            }

            return redirect()->route("{$this->routeBase}.index")
                ->with('success', "Pembelian {$pembelian->kode_pembelian} berhasil ditambahkan.");
        } catch (\Exception $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
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

    /**
     * Form edit pembelian (hanya untuk status draft)
     */
    public function edit(int|string $id): View
    {
        $pembelian = Pembelian::with(['detail.barang.satuanDasar', 'detail.satuanInput', 'pihak', 'unitUsaha', 'gudang', 'kasBank'])
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

        $validated = $request->validate([
            'id_pihak' => 'required|exists:master_pihak,id_pihak',
            'jenis_pembayaran' => 'required|in:tunai,transfer,kredit',
            'id_kas_bank' => [
                'nullable',
                Rule::requiredIf(in_array($request->input('jenis_pembayaran'), ['tunai', 'transfer'], true)),
                'exists:master_kas_bank,id_kas_bank',
            ],
            'tgl_jatuh_tempo' => [
                'nullable',
                'date_format:Y-m-d',
                Rule::requiredIf($request->input('jenis_pembayaran') === 'kredit'),
            ],
            'id_unit_usaha' => 'nullable|exists:master_unit_usaha,id_unit_usaha',
            'id_gudang' => ['nullable', 'integer', new TenantOwnershipRule('gudang', 'id_gudang')],
            'items' => 'required|array|min:1',
            'items.*.id_barang' => 'required|exists:master_barang,id_barang',
            'items.*.id_satuan' => 'required|exists:satuan,id',
            'items.*.qty_dasar' => 'required|numeric|min:0.01',
            'items.*.harga_satuan' => 'required|numeric|min:0',
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

            return back()->with('success', "Pembelian disetujui.");
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
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


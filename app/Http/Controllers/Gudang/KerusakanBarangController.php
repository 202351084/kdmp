<?php

namespace App\Http\Controllers\Gudang;

use App\Http\Controllers\Concerns\ModuleCrudController;
use App\Models\Gudang\KerusakanBarang;
use App\Models\Gudang\Stok;
use App\Models\Master\Barang;
use App\Models\Master\Gudang;
use App\Services\StokService;
use App\Services\StokBatchService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use RuntimeException;

class KerusakanBarangController extends ModuleCrudController
{
    public function __construct(private StokBatchService $stokBatch) {}

    protected string $model = KerusakanBarang::class;
    protected string $view = 'gudang.kerusakan';
    protected string $title = 'Kerusakan / Susut Barang';
    protected string $routeBase = 'gudang.kerusakan';
    protected array $withRelations = ['barang.satuanDasar'];

    public function index(Request $request): View
    {
        $relations = ['barang', 'gudang'];
        if (Schema::hasColumn('kerusakan_barang', 'id_batch')) {
            $relations[] = 'batch';
        }
        $query = KerusakanBarang::query()->with($relations);

        if ($search = $request->query('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('jenis_kejadian', 'like', "%{$search}%")
                    ->orWhereHas('barang', fn ($b) => $b->where('nama_barang', 'like', "%{$search}%"));
            });
        }

        $items = $query->latest('id_kerusakan')->paginate(15)->withQueryString();

        return view('gudang.kerusakan.index', [
            'items' => $items,
            'title' => $this->title,
        ]);
    }

    public function show(int|string $id): View
    {
        $relations = ['barang.satuanDasar', 'gudang'];
        if (Schema::hasColumn('kerusakan_barang', 'id_batch')) {
            $relations[] = 'batch';
        }

        return view('gudang.kerusakan.show', [
            'item' => KerusakanBarang::query()->with($relations)->findOrFail($id),
            'title' => 'Detail ' . $this->title,
        ]);
    }

    public function create(): View
    {
        $gudang = Gudang::query()->where('is_active', true)->orderBy('nama_gudang')->get();
        $barang = Barang::query()->where('is_active', true)->orderBy('nama_barang')->get();
        $batchTrackingAvailable = Schema::hasColumn('barang_per_koperasi', 'lacak_batch');
        $entitasId = auth()->user()->id_entitas
            ?? (app()->bound('entitas_aktif') ? app('entitas_aktif') : null);
        $pelacakanBarang = $batchTrackingAvailable && $entitasId !== null
            ? DB::table('barang_per_koperasi')
                ->where('id_entitas', $entitasId)
                ->pluck('lacak_batch', 'id_barang')
                ->map(fn ($value): bool => (bool) $value)
            : collect();
        $batchData = Schema::hasTable('stok_batch')
            ? DB::table('stok_batch')
                ->whereIn('id_gudang', $gudang->pluck('id_gudang'))
                ->where('qty_on_hand', '>', 0)
                ->get([
                    'id_batch',
                    'id_gudang',
                    'id_barang',
                    'kode_batch',
                    'tanggal_kedaluwarsa',
                    'qty_on_hand',
                ])
            : collect();

        return view('gudang.kerusakan.create', [
            'gudang' => $gudang,
            'barang' => $barang,
            'batchData' => $batchData,
            'pelacakanBarang' => $pelacakanBarang,
            'batchTrackingAvailable' => $batchTrackingAvailable,
            'title' => 'Tambah ' . $this->title,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $rules = [
            'id_gudang' => ['required', 'integer', 'exists:gudang,id_gudang'],
            'id_barang' => ['required', 'integer', 'exists:master_barang,id_barang'],
            'tanggal' => ['required', 'date'],
            'qty' => ['required', 'numeric', 'gt:0'],
            'jenis_kejadian' => ['required', 'in:rusak,susut,hilang,kadaluarsa'],
            'keterangan' => ['nullable', 'string'],
        ];
        if (Schema::hasTable('stok_batch')) {
            $rules['id_batch'] = ['nullable', 'integer', 'exists:stok_batch,id_batch'];
        }
        $data = $request->validate($rules);

        return DB::transaction(function () use ($data) {
            $entitasId = (int) (auth()->user()->id_entitas
                ?? (app()->bound('entitas_aktif') ? app('entitas_aktif') : 0));
            $lacakBatch = $this->stokBatch->pelacakanAktif($entitasId, (int) $data['id_barang']);
            if ($lacakBatch && empty($data['id_batch'])) {
                throw ValidationException::withMessages([
                    'id_batch' => 'Pilih batch yang akan dicatat untuk barang ini.',
                ]);
            }

            if ($lacakBatch) {
                $batch = DB::table('stok_batch')
                    ->where('id_batch', $data['id_batch'])
                    ->where('id_gudang', $data['id_gudang'])
                    ->where('id_barang', $data['id_barang'])
                    ->first();
                if (! $batch) {
                    throw ValidationException::withMessages([
                        'id_batch' => 'Batch tidak sesuai dengan gudang dan barang yang dipilih.',
                    ]);
                }

                $batchKedaluwarsa = $batch->tanggal_kedaluwarsa !== null
                    && $batch->tanggal_kedaluwarsa < now()->toDateString();
                if (
                    ($data['jenis_kejadian'] === 'kadaluarsa') !== $batchKedaluwarsa
                    && ($data['jenis_kejadian'] === 'kadaluarsa' || $batchKedaluwarsa)
                ) {
                    throw ValidationException::withMessages([
                        'id_batch' => $data['jenis_kejadian'] === 'kadaluarsa'
                            ? 'Batch belum kedaluwarsa. Catat sebagai kerusakan biasa atau pilih batch yang sudah kedaluwarsa.'
                            : 'Batch kedaluwarsa hanya dapat dikeluarkan melalui pencatatan kejadian kedaluwarsa.',
                    ]);
                }
            }

            $stok = Stok::query()
                ->where('id_gudang', $data['id_gudang'])
                ->where('id_barang', $data['id_barang'])
                ->first();

            if (! $stok || bccomp((string) $stok->qty_on_hand, (string) $data['qty'], 4) < 0) {
                throw ValidationException::withMessages([
                    'qty' => 'Stok tidak mencukupi untuk kerusakan barang.',
                ]);
            }

            $hpp = (string) $stok->hpp_rata2;
            $nilaiKerugian = bcmul((string) $data['qty'], $hpp, 2);

            $dataKerusakan = [
                'id_entitas' => app()->bound('entitas_aktif') ? app('entitas_aktif') : null,
                'id_gudang' => $data['id_gudang'],
                'id_barang' => $data['id_barang'],
                'tanggal' => $data['tanggal'],
                'qty' => (string) $data['qty'],
                'hpp_rata2' => $hpp,
                'nilai_kerugian' => $nilaiKerugian,
                'jenis_kejadian' => $data['jenis_kejadian'],
                'keterangan' => $data['keterangan'] ?? null,
                'foto_bukti' => null,
                'status' => 'diposting',
                'approved_by' => auth()->id(),
            ];
            if (Schema::hasColumn('kerusakan_barang', 'id_batch')) {
                $dataKerusakan['id_batch'] = $lacakBatch ? (int) $data['id_batch'] : null;
            }
            $item = KerusakanBarang::query()->create($dataKerusakan);

            $service = app(StokService::class);
            try {
                $service->keluar(
                    entitasId: (int) ($item->id_entitas ?? $entitasId),
                    gudangId: (int) $data['id_gudang'],
                    barangId: (int) $data['id_barang'],
                    qty: (string) $data['qty'],
                    refTipe: 'KERUSAKAN',
                    refId: (int) $item->id_kerusakan,
                    createdBy: auth()->id(),
                    idBatch: $lacakBatch ? (int) $data['id_batch'] : null,
                    izinkanBatchKedaluwarsa: $lacakBatch && $data['jenis_kejadian'] === 'kadaluarsa',
                );
            } catch (RuntimeException $exception) {
                throw ValidationException::withMessages([
                    $lacakBatch ? 'id_batch' : 'qty' => $exception->getMessage(),
                ]);
            }

            return redirect()->route('gudang.kerusakan.index')->with('success', 'Data kerusakan barang berhasil disimpan.');
        });
    }

}

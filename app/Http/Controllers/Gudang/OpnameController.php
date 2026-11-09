<?php

namespace App\Http\Controllers\Gudang;

use App\Http\Controllers\Concerns\ModuleCrudController;
use App\Models\Gudang\OpnameDetail;
use App\Models\Gudang\OpnameHeader;
use App\Models\Master\Barang;
use App\Models\Master\Gudang;
use App\Services\StokService;
use App\Services\StokBatchService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class OpnameController extends ModuleCrudController
{
    protected string $model = OpnameHeader::class;
    protected string $view = 'gudang.opname';
    protected string $title = 'Stock Opname';
    protected string $routeBase = 'gudang.opname';

    public function index(Request $request): View
    {
        $query = OpnameHeader::query()->with('detail');

        if ($search = $request->query('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('kode_opname', 'like', "%{$search}%")
                    ->orWhereHas('detail', fn ($d) => $d->whereHas('barang', fn ($b) => $b->where('nama_barang', 'like', "%{$search}%")));
            });
        }

        $items = $query->latest('id_opname')->paginate(15)->withQueryString();

        return view('gudang.opname.index', [
            'items' => $items,
            'title' => $this->title,
        ]);
    }

    public function create(): View
    {
        $gudang = Gudang::query()->where('is_active', true)->orderBy('nama_gudang')->get();
        $barang = Barang::query()->where('is_active', true)->orderBy('nama_barang')->get();

        return view('gudang.opname.create', [
            'gudang' => $gudang,
            'barang' => $barang,
            'title' => 'Tambah ' . $this->title,
        ]);
    }

    public function show(int|string $id): View
    {
        $item = OpnameHeader::query()
            ->with(['gudang', 'detail.barang.satuanDasar'])
            ->findOrFail($id);

        return view('gudang.opname.show', [
            'item' => $item,
            'title' => 'Detail ' . $this->title,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'id_gudang' => ['required', 'integer', 'exists:gudang,id_gudang'],
            'kode_opname' => ['required', 'string', 'max:30'],
            'tanggal' => ['required', 'date'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.id_barang' => ['required', 'integer', 'distinct', 'exists:master_barang,id_barang'],
            'items.*.qty_fisik' => ['required', 'numeric', 'regex:/^\d{1,14}(?:\.\d{1,4})?$/', 'gte:0'],
            'items.*.keterangan' => ['nullable', 'string'],
        ]);

        return DB::transaction(function () use ($data) {
            $header = OpnameHeader::query()->create([
                'id_entitas' => app()->bound('entitas_aktif') ? app('entitas_aktif') : null,
                'id_gudang' => $data['id_gudang'],
                'kode_opname' => $data['kode_opname'],
                'tanggal' => $data['tanggal'],
                'status' => 'diposting',
                'approved_by' => null,
            ]);

            $stokService = app(StokService::class);
            $stokBatchService = app(StokBatchService::class);

            foreach ($data['items'] as $item) {
                $barangId = (int) $item['id_barang'];
                $qtyFisik = (string) $item['qty_fisik'];
                $hasilOpname = $stokService->sesuaikanOpname(
                    entitasId: (int) ($header->id_entitas ?? app('entitas_aktif')),
                    gudangId: (int) $data['id_gudang'],
                    barangId: $barangId,
                    qtyFisik: $qtyFisik,
                    opnameId: (int) $header->id_opname,
                    createdBy: auth()->id(),
                );

                $detail = OpnameDetail::query()->create([
                    'id_opname' => $header->id_opname,
                    'id_barang' => $barangId,
                    'qty_sistem' => $hasilOpname['qty_sistem'],
                    'qty_fisik' => $qtyFisik,
                    'selisih' => $hasilOpname['selisih'],
                    'hpp_rata2' => $hasilOpname['hpp_rata2'],
                    'nilai_selisih' => $hasilOpname['nilai_selisih'],
                    'keterangan' => $item['keterangan'] ?? null,
                ]);

                $stokBatchService->catatPenyesuaianOpname(
                    detailId: (int) $detail->id_detail,
                    gudangId: (int) $data['id_gudang'],
                    barangId: $barangId,
                    qtySistem: $hasilOpname['qty_sistem'],
                    qtyFisik: $qtyFisik,
                    idKartu: $hasilOpname['id_kartu'],
                );
            }

            return redirect()->route('gudang.opname.index')->with('success', 'Data opname berhasil disimpan.');
        });
    }
}

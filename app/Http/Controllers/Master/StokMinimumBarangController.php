<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\Tenant\AuditLog;
use App\Services\StokBatchService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class StokMinimumBarangController extends Controller
{
    public function __construct(private StokBatchService $stokBatch) {}

    public function index(Request $request): View
    {
        $entitasId = $this->entitasBerwenang();
        $pelacakanBatchTersedia = Schema::hasColumn('barang_per_koperasi', 'lacak_batch');

        $query = DB::table('barang_per_koperasi as pengaturan')
            ->join('master_barang as barang', 'barang.id_barang', '=', 'pengaturan.id_barang')
            ->leftJoin('satuan as satuan', 'satuan.id', '=', 'barang.id_satuan_dasar')
            ->where('pengaturan.id_entitas', $entitasId);

        $items = $query
            ->when($request->filled('q'), function ($query) use ($request): void {
                $search = $request->string('q')->toString();
                $query->where(function ($query) use ($search): void {
                    $query->where('barang.nama_barang', 'like', "%{$search}%")
                        ->orWhere('barang.kode_barang', 'like', "%{$search}%");
                });
            })
            ->select([
                'pengaturan.id_barang',
                'pengaturan.stok_minimum',
                'barang.kode_barang',
                'barang.nama_barang',
                'satuan.kode_satuan',
            ])
            ->when(
                $pelacakanBatchTersedia,
                fn ($query) => $query->addSelect('pengaturan.lacak_batch'),
            )
            ->orderBy('barang.nama_barang')
            ->paginate(15)
            ->withQueryString();

        return view('master.barang.stok-minimum', [
            'items' => $items,
            'title' => 'Pengaturan Stok Minimum',
            'pelacakanBatchTersedia' => $pelacakanBatchTersedia,
        ]);
    }

    public function update(Request $request, int $idBarang): RedirectResponse
    {
        $entitasId = $this->entitasBerwenang();
        $data = $request->validate([
            'stok_minimum' => ['required', 'numeric', 'regex:/^\d{1,14}(?:\.\d{1,4})?$/', 'gte:0'],
            'lacak_batch' => ['sometimes', 'boolean'],
        ]);
        if (array_key_exists('lacak_batch', $data) && ! Schema::hasColumn('barang_per_koperasi', 'lacak_batch')) {
            abort(409, 'Migration pelacakan batch belum dijalankan. Tidak ada perubahan yang disimpan.');
        }

        DB::transaction(function () use ($data, $entitasId, $idBarang, $request): void {
            $pengaturan = DB::table('barang_per_koperasi')
                ->where('id_entitas', $entitasId)
                ->where('id_barang', $idBarang)
                ->lockForUpdate()
                ->first();

            abort_unless($pengaturan, 404, 'Pengaturan barang tidak ditemukan untuk koperasi ini.');

            $nilaiLama = bcadd((string) $pengaturan->stok_minimum, '0', 4);
            $nilaiBaru = bcadd((string) $data['stok_minimum'], '0', 4);
            $dataLama = ['stok_minimum' => $nilaiLama];
            $dataBaru = ['stok_minimum' => $nilaiBaru];
            $perubahan = ['stok_minimum' => $nilaiBaru];
            $berubah = bccomp($nilaiLama, $nilaiBaru, 4) !== 0;

            if (array_key_exists('lacak_batch', $data)) {
                $lacakLama = (bool) $pengaturan->lacak_batch;
                $lacakBaru = (bool) $data['lacak_batch'];
                if ($lacakLama && ! $lacakBaru) {
                    throw ValidationException::withMessages([
                        'lacak_batch' => 'Pelacakan batch tidak dapat dinonaktifkan setelah diaktifkan.',
                    ]);
                }
                $dataLama['lacak_batch'] = $lacakLama;
                $dataBaru['lacak_batch'] = $lacakBaru;
                if ($lacakLama !== $lacakBaru) {
                    if ($lacakBaru) {
                        $this->stokBatch->sediakanSaldoAwal($entitasId, $idBarang);
                    }
                    $perubahan['lacak_batch'] = $lacakBaru;
                    $berubah = true;
                }
            }

            if (! $berubah) {
                return;
            }

            DB::table('barang_per_koperasi')
                ->where('id_entitas', $entitasId)
                ->where('id_barang', $idBarang)
                ->update($perubahan);

            AuditLog::query()->create([
                'id_entitas' => $entitasId,
                'id_pengguna' => auth()->id(),
                'tabel' => 'barang_per_koperasi',
                'record_id' => $idBarang,
                'aksi' => 'UPDATE',
                'data_lama' => $dataLama,
                'data_baru' => $dataBaru,
                'ip_address' => $request->ip(),
            ]);
        });

        return back()->with('status', 'Stok minimum berhasil diperbarui.');
    }

    private function entitasBerwenang(): int
    {
        $pengguna = auth()->user();

        abort_unless(
            $pengguna && ($pengguna->hasPeran('admin_gudang') || $pengguna->hasPeran('manajer')),
            403,
            'Hanya Admin Gudang atau Manajer yang dapat mengatur stok minimum.',
        );

        $entitasId = $pengguna->id_entitas
            ?? (app()->bound('entitas_aktif') ? app('entitas_aktif') : null);

        abort_unless($entitasId, 403, 'Entitas aktif tidak ditemukan.');

        return (int) $entitasId;
    }
}

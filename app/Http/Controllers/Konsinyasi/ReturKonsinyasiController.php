<?php

namespace App\Http\Controllers\Konsinyasi;

use App\Http\Controllers\Concerns\ResolvesEntitasAktif;
use App\Http\Controllers\Controller;
use App\Models\Konsinyasi\KartuKonsinyasi;
use App\Models\Konsinyasi\ReturKonsinyasi;
use App\Models\Konsinyasi\StokKonsinyasi;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ReturKonsinyasiController extends Controller
{
    use ResolvesEntitasAktif;

    public function index(): View
    {
        $entitasId = $this->requireEntitasAktifId();
        $items = ReturKonsinyasi::query()
            ->where(function ($query) use ($entitasId): void {
                $query->where('id_entitas_pemilik', $entitasId)
                    ->orWhere('id_entitas_pencatat', $entitasId);
            })
            ->with([
                'stokKonsinyasi.barang.satuanDasar',
                'stokKonsinyasi.kiriman.koperasiPemilik',
                'stokKonsinyasi.kiriman.pihakPemilik',
                'stokKonsinyasi.kiriman.koperasiPenerima',
            ])
            ->latest('id_retur_konsinyasi')
            ->paginate(15);

        return view('konsinyasi.retur.index', [
            'items' => $items,
            'title' => 'Retur Konsinyasi',
        ]);
    }

    public function create(Request $request): View
    {
        $entitasId = $this->requireEntitasAktifId();
        $stok = $this->stokYangBolehDiretur($entitasId)
            ->with([
                'barang.satuanDasar',
                'kiriman.koperasiPemilik',
                'kiriman.pihakPemilik',
                'kiriman.koperasiPenerima',
            ])
            ->orderBy('id_stok_konsinyasi')
            ->get();

        return view('konsinyasi.retur.create', [
            'items' => $stok,
            'selectedId' => (int) $request->query('stok', 0),
            'title' => 'Catat Retur Konsinyasi',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'id_stok_konsinyasi' => ['required', 'integer', 'exists:stok_konsinyasi,id_stok_konsinyasi'],
            'tanggal' => ['required', 'date'],
            'qty_dikembalikan' => ['required', 'numeric', 'gt:0'],
            'catatan' => ['nullable', 'string', 'max:2000'],
        ]);
        $entitasId = $this->requireEntitasAktifId();

        $retur = DB::transaction(function () use ($validated, $entitasId): ReturKonsinyasi {
            $stok = StokKonsinyasi::query()
                ->with('kiriman')
                ->whereKey($validated['id_stok_konsinyasi'])
                ->lockForUpdate()
                ->firstOrFail();

            $this->pastikanBerwenangMencatat($stok, $entitasId);

            if (bccomp((string) $stok->qty_sisa, (string) $validated['qty_dikembalikan'], 4) < 0) {
                throw ValidationException::withMessages([
                    'qty_dikembalikan' => 'Jumlah retur melebihi sisa stok konsinyasi.',
                ]);
            }

            $qtySisa = bcsub((string) $stok->qty_sisa, (string) $validated['qty_dikembalikan'], 4);
            $qtyRetur = bcadd((string) $stok->qty_retur, (string) $validated['qty_dikembalikan'], 4);
            $retur = ReturKonsinyasi::query()->create([
                'kode_retur' => 'RKN-'.Str::ulid(),
                'id_stok_konsinyasi' => $stok->id_stok_konsinyasi,
                'id_entitas_pemilik' => $stok->id_entitas_pemilik,
                'id_entitas_pencatat' => $entitasId,
                'id_pengguna' => auth()->id(),
                'tanggal' => $validated['tanggal'],
                'qty_dikembalikan' => $validated['qty_dikembalikan'],
                'catatan' => $validated['catatan'] ?? null,
            ]);

            $stok->update([
                'qty_retur' => $qtyRetur,
                'qty_sisa' => $qtySisa,
                'status' => bccomp($qtySisa, '0', 4) === 0
                    ? (bccomp($qtyRetur, '0', 4) > 0 ? 'dikembalikan' : 'habis')
                    : 'aktif',
            ]);

            KartuKonsinyasi::query()->create([
                'id_stok_konsinyasi' => $stok->id_stok_konsinyasi,
                'tanggal' => $validated['tanggal'],
                'jenis_mutasi' => 'RETUR',
                'ref_tipe' => 'RETUR_KONSINYASI',
                'ref_id' => $retur->id_retur_konsinyasi,
                'qty' => $validated['qty_dikembalikan'],
                'harga_titip_satuan' => $stok->harga_titip_satuan,
                'harga_jual_satuan' => $stok->harga_jual_satuan,
                'saldo_qty' => $qtySisa,
                'created_at' => now(),
            ]);

            return $retur;
        });

        return redirect()->route('konsinyasi.retur.index')
            ->with('success', "Retur {$retur->kode_retur} berhasil dicatat.");
    }

    private function stokYangBolehDiretur(int $entitasId)
    {
        return StokKonsinyasi::query()
            ->where('status', 'aktif')
            ->where('qty_sisa', '>', 0)
            ->whereHas('kiriman', function ($query) use ($entitasId): void {
                $query->where(function ($owner) use ($entitasId): void {
                    $owner->where(function ($entityOwner) use ($entitasId): void {
                        $entityOwner->where('jenis_pemilik', 'entitas')
                            ->where('id_entitas_pemilik', $entitasId);
                    })->orWhere(function ($personOwner) use ($entitasId): void {
                        $personOwner->where('jenis_pemilik', 'pihak')
                            ->where('id_entitas_penerima', $entitasId);
                    });
                });
            });
    }

    private function pastikanBerwenangMencatat(StokKonsinyasi $stok, int $entitasId): void
    {
        $kiriman = $stok->kiriman;
        $isPemilikEntitas = $kiriman?->jenis_pemilik === 'entitas'
            && (int) $kiriman->id_entitas_pemilik === $entitasId;
        $isTitipanPeroranganYangDikembalikan = $kiriman?->jenis_pemilik === 'pihak'
            && (int) $kiriman->id_entitas_penerima === $entitasId;

        abort_unless($isPemilikEntitas || $isTitipanPeroranganYangDikembalikan, 403);

        if (bccomp((string) $stok->qty_sisa, '0', 4) <= 0) {
            throw ValidationException::withMessages([
                'id_stok_konsinyasi' => 'Tidak ada sisa stok konsinyasi yang dapat diretur.',
            ]);
        }
    }
}

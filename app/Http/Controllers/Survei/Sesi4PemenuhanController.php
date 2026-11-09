<?php

namespace App\Http\Controllers\Survei;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use App\Models\Master\Komoditas;
use App\Models\Survei\PemenuhanKomoditas;
use App\Models\Survei\SesiSurvei;

class Sesi4PemenuhanController extends Controller
{
    private function checkHakAksesWilayah(SesiSurvei $sesi): void
    {
        $user = auth()->user();
        if ($user && $user->id_entitas) {
            $userWilayahId = $user->entitas?->id_wilayah;
            if ($userWilayahId && (int) $sesi->id_wilayah !== (int) $userWilayahId) {
                abort(403, 'Anda tidak memiliki hak akses untuk mengelola sesi survei desa ini.');
            }
        }
    }

    // ─── Tampilkan Halaman Utama Sesi 4 ──────────────────────────────────────

    public function show(SesiSurvei $sesi): View
    {
        $this->checkHakAksesWilayah($sesi);
        $sesi->load(['wilayah', 'petugas']);

        // Data pemenuhan yang sudah diinput untuk sesi ini
        $pemenuhanList = PemenuhanKomoditas::with('komoditas')
            ->where('id_sesi', $sesi->id)
            ->latest()
            ->get();

        // Semua komoditas aktif untuk dropdown
        $komoditasList = Komoditas::where('is_active', true)
            ->orderByRaw("
                CASE 
                    WHEN kategori = 'Pertanian' THEN 1
                    WHEN kategori = 'Peternakan' THEN 2
                    WHEN kategori = 'Perikanan' THEN 3
                    WHEN kategori = 'Perkebunan' THEN 99
                    ELSE 50
                END
            ")
            ->orderBy('nama')
            ->get();

        $SUMBER_PEMENUHAN = [
            'Pasar Tradisional',
            'Toko',
            'Warung',
            'Minimarket',
            'Supermarket/Swalayan',
            'Pedagang Keliling',
            'Distributor/Agen',
            'Lainnya'
        ];

        return view('survei.sesi4.index', compact('sesi', 'pemenuhanList', 'komoditasList', 'SUMBER_PEMENUHAN'));
    }

    // ─── Simpan Data Pemenuhan Komoditas ─────────────────────────────────────

    public function store(Request $request, SesiSurvei $sesi): RedirectResponse
    {
        $this->checkHakAksesWilayah($sesi);

        $validated = $request->validate([
            'id_komoditas'     => 'required|exists:komoditas,id',
            'sumber_pemenuhan' => 'required|string|max:100',
            'sumber_lainnya'   => 'nullable|string|max:100|required_if:sumber_pemenuhan,Lainnya',
            'nama_tempat'      => 'required|string|max:255',
            'harga'            => 'required|numeric|min:0',
            'satuan_harga'     => 'required|string|max:50',
            'tanggal_survei'   => 'required|date',
            'keterangan'       => 'nullable|string|max:1000',
        ], [
            'id_komoditas.required'     => 'Komoditas wajib dipilih.',
            'sumber_pemenuhan.required' => 'Sumber pemenuhan wajib dipilih.',
            'nama_tempat.required'      => 'Nama tempat wajib diisi.',
            'harga.required'            => 'Harga wajib diisi.',
            'harga.numeric'             => 'Harga harus berupa angka.',
            'harga.min'                 => 'Harga tidak boleh negatif.',
            'satuan_harga.required'     => 'Satuan harga wajib diisi.',
            'tanggal_survei.required'   => 'Tanggal survei wajib diisi.',
        ]);

        $sumberFinal = $validated['sumber_pemenuhan'] === 'Lainnya' 
                        ? $validated['sumber_lainnya'] 
                        : $validated['sumber_pemenuhan'];

        DB::transaction(function () use ($validated, $sesi, $sumberFinal) {
            PemenuhanKomoditas::create([
                'id_sesi'          => $sesi->id,
                'id_komoditas'     => $validated['id_komoditas'],
                'sumber_pemenuhan' => $sumberFinal,
                'nama_tempat'      => $validated['nama_tempat'],
                'harga'            => $validated['harga'],
                'satuan_harga'     => $validated['satuan_harga'],
                'tanggal_survei'   => $validated['tanggal_survei'],
                'keterangan'       => $validated['keterangan'] ?? null,
            ]);

            // Update status otomatis menjadi Sedang Diisi jika masih Belum Diisi
            if (($sesi->status_sesi4 ?? 'belum_diisi') === 'belum_diisi') {
                $sesi->update(['status_sesi4' => 'sedang_diisi']);
            }
        });

        return redirect()
            ->route('survei.sesi4.show', $sesi->id)
            ->with('success', 'Data harga dan pemenuhan komoditas berhasil disimpan.');
    }

    // ─── Hapus Data Pemenuhan Komoditas ──────────────────────────────────────

    public function destroy(SesiSurvei $sesi, PemenuhanKomoditas $pemenuhan): RedirectResponse
    {
        $this->checkHakAksesWilayah($sesi);

        if ((int) $pemenuhan->id_sesi !== (int) $sesi->id) {
            abort(404, 'Data tidak ditemukan di sesi ini.');
        }

        $pemenuhan->delete();

        return redirect()
            ->route('survei.sesi4.show', $sesi->id)
            ->with('success', 'Data pemenuhan komoditas berhasil dihapus.');
    }

    // ─── Simpan Draft ────────────────────────────────────────────────────────

    public function simpanDraft(SesiSurvei $sesi): RedirectResponse
    {
        $this->checkHakAksesWilayah($sesi);
        
        $sesi->update(['status_sesi4' => 'sedang_diisi']);

        return redirect()
            ->route('survei.sesi4.show', $sesi->id)
            ->with('success', 'Sesi 4 berhasil disimpan sebagai Draft.');
    }

    // ─── Selesaikan Sesi 4 ───────────────────────────────────────────────────

    public function selesaikan(SesiSurvei $sesi): RedirectResponse
    {
        $this->checkHakAksesWilayah($sesi);

        if (!$sesi->pemenuhan()->exists()) {
            return redirect()
                ->route('survei.sesi4.show', $sesi->id)
                ->with('error', 'Tidak dapat menyelesaikan Sesi 4 karena belum ada data pemenuhan komoditas yang diinput.');
        }

        $sesi->update([
            'status_sesi4'            => 'selesai',
            'selesai_sesi4_at'        => now(),
            'diselesaikan_sesi4_oleh' => auth()->id(),
        ]);

        return redirect()
            ->route('survei.sesi.show', $sesi->id)
            ->with('success', 'Sesi 4 berhasil diselesaikan!');
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Master\EntitasController;
use App\Models\Peran;
use App\Models\Tenant\Entitas;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SuperAdminController extends Controller
{
    public function dashboard()
    {
        $pendingEntitas = Entitas::where('status', 'PENDING')->get();
        $approvedEntitas = Entitas::where('status', 'APPROVED')->get();

        return view('admin.dashboard', compact('pendingEntitas', 'approvedEntitas'));
    }

    public function approve(Request $request, $id)
    {
        $entitas = Entitas::findOrFail($id);
        $action = $request->input('action'); // 'approve' or 'reject'

        if ($action === 'approve') {
            DB::transaction(function () use ($entitas) {
                $entitas->update([
                    'status' => 'APPROVED',
                    'is_active' => true,
                ]);

                // Aktifkan juga user admin pertama dari entitas ini & berikan peran manajer
                $entitas->pengguna()->update(['is_active' => true]);

                $idPeranManajer = Peran::where('kode', 'manajer')->value('id_peran');
                if ($idPeranManajer) {
                    foreach ($entitas->pengguna as $user) {
                        DB::table('pengguna_peran')->updateOrInsert([
                            'id_pengguna' => $user->id,
                            'id_peran' => $idPeranManajer,
                        ]);
                    }
                }

                // Inisialisasi Gudang, Kas & Bank, Periode 1..13, dan Pihak Entitas Mitra
                EntitasController::provisionDefaultMasterData($entitas);
            });

            return back()->with('success', 'Entitas ' . $entitas->nama_entitas . ' berhasil disetujui dan data dasarnya telah diinisialisasi.');
        } elseif ($action === 'reject') {
            $entitas->update([
                'status' => 'REJECTED',
                'is_active' => false,
            ]);
            return back()->with('success', 'Pendaftaran ' . $entitas->nama_entitas . ' ditolak.');
        }

        return back();
    }
}

<?php

namespace App\Http\Controllers\Gudang;

use App\Http\Controllers\Controller;
use App\Services\StokMinimumService;
use App\Services\StokBatchService;
use Illuminate\View\View;
use Illuminate\Support\Facades\Schema;

class GudangDashboardController extends Controller
{
    public function index(StokMinimumService $stokMinimum, StokBatchService $stokBatch): View
    {
        $entitasId = auth()->user()->id_entitas
            ?? (app()->bound('entitas_aktif') ? app('entitas_aktif') : null);

        return view('gudang.index', [
            'jumlahBarangMenipis' => $stokMinimum->jumlahBarangMenipis(
                $entitasId !== null ? (int) $entitasId : null,
            ),
            'jumlahBatchMendekatiKedaluwarsa' => $stokBatch->jumlahBatchMendekatiKedaluwarsa(
                $entitasId !== null ? (int) $entitasId : null,
            ),
            'pelacakanBatchTersedia' => Schema::hasTable('stok_batch'),
        ]);
    }
}

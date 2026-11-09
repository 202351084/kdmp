<?php

namespace App\Models\Gudang;

use Illuminate\Database\Eloquent\Model;

class KartuStokBatch extends Model
{
    protected $table = 'kartu_stok_batch';
    protected $primaryKey = 'id_kartu_batch';

    protected $fillable = [
        'id_kartu', 'id_batch', 'qty_masuk', 'qty_keluar', 'saldo_batch',
    ];
}

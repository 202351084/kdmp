<?php

namespace App\Models\Gudang;

use App\Models\Master\Barang;
use App\Models\Master\Satuan;
use Illuminate\Database\Eloquent\Model;

class PenerimaanBarangDetail extends Model
{
    protected $table = 'penerimaan_barang_detail';

    protected $primaryKey = 'id_detail';

    public $timestamps = false;

    protected $fillable = [
        'id_penerimaan', 'id_barang', 'id_satuan_input', 'qty_input', 'faktor_konversi',
        'qty_dasar', 'qty_layak', 'qty_tidak_layak', 'harga_satuan_dasar', 'subtotal',
        'alasan_tidak_layak', 'foto_bukti',
    ];

    public function barang()
    {
        return $this->belongsTo(Barang::class, 'id_barang', 'id_barang');
    }

    public function satuanInput()
    {
        return $this->belongsTo(Satuan::class, 'id_satuan_input');
    }

    public function batchDetails()
    {
        return $this->hasMany(PenerimaanBatchDetail::class, 'id_detail', 'id_detail');
    }
}

<?php

namespace App\Models\Gudang;

use App\Models\Master\Barang;
use App\Models\Master\Gudang as GudangModel;
use Illuminate\Database\Eloquent\Model;

class StokBatch extends Model
{
    protected $table = 'stok_batch';
    protected $primaryKey = 'id_batch';

    protected $fillable = [
        'id_gudang', 'id_barang', 'kode_batch', 'tanggal_kedaluwarsa',
        'qty_on_hand', 'is_stok_awal',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_kedaluwarsa' => 'date',
            'qty_on_hand' => 'decimal:4',
            'is_stok_awal' => 'boolean',
        ];
    }

    public function gudang()
    {
        return $this->belongsTo(GudangModel::class, 'id_gudang', 'id_gudang');
    }

    public function barang()
    {
        return $this->belongsTo(Barang::class, 'id_barang', 'id_barang');
    }
}

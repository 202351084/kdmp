<?php

namespace App\Models\Gudang;

use App\Models\Concerns\BelongsToEntitas;
use App\Models\Master\Barang;
use App\Models\Master\Gudang as GudangModel;
use Illuminate\Database\Eloquent\Model;

/** Append-only, kronologis. Backdate DILARANG â€” koreksi = mutasi baru hari ini. */
class KartuStok extends Model
{
    use BelongsToEntitas;

    protected $table = 'kartu_stok';
    protected $primaryKey = 'id_kartu';
    const UPDATED_AT = null;

    protected $fillable = [
        'id_entitas', 'id_gudang', 'id_barang', 'tanggal', 'jenis_mutasi',
        'ref_tipe', 'ref_id', 'qty_masuk', 'qty_keluar', 'harga_satuan',
        'nilai_mutasi', 'saldo_qty', 'saldo_nilai', 'hpp_rata2_setelah',
        'jenis_kejadian', 'id_jurnal', 'created_by',
    ];

    protected function casts(): array
    {
        return ['tanggal' => 'date'];
    }

    public function barang()
    {
        return $this->belongsTo(Barang::class, 'id_barang', 'id_barang');
    }

    public function gudang()
    {
        return $this->belongsTo(GudangModel::class, 'id_gudang', 'id_gudang');
    }
}

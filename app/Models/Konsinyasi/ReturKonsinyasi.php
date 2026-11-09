<?php

namespace App\Models\Konsinyasi;

use App\Models\Pengguna;
use App\Models\Tenant\Entitas;
use Illuminate\Database\Eloquent\Model;

class ReturKonsinyasi extends Model
{
    protected $table = 'retur_konsinyasi';
    protected $primaryKey = 'id_retur_konsinyasi';

    protected $fillable = [
        'kode_retur', 'id_stok_konsinyasi', 'id_entitas_pemilik',
        'id_entitas_pencatat', 'id_pengguna', 'tanggal', 'qty_dikembalikan', 'catatan',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'qty_dikembalikan' => 'decimal:4',
        ];
    }

    public function stokKonsinyasi()
    {
        return $this->belongsTo(StokKonsinyasi::class, 'id_stok_konsinyasi', 'id_stok_konsinyasi');
    }

    public function entitasPemilik()
    {
        return $this->belongsTo(Entitas::class, 'id_entitas_pemilik', 'id_entitas');
    }

    public function entitasPencatat()
    {
        return $this->belongsTo(Entitas::class, 'id_entitas_pencatat', 'id_entitas');
    }

    public function pengguna()
    {
        return $this->belongsTo(Pengguna::class, 'id_pengguna', 'id');
    }
}

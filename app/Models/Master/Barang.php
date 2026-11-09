<?php

namespace App\Models\Master;

use App\Models\Tenant\Entitas;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class Barang extends Model
{
    protected $table = 'master_barang';
    protected $primaryKey = 'id_barang';

    protected $fillable = [
        'id_entitas', 'kode_barang', 'id_komoditas', 'id_unit_usaha', 'nama_barang',
        'id_satuan_dasar', 'is_active',
    ];

    protected static ?bool $hasEntitasCol = null;

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public static function hasEntitasColumn(): bool
    {
        if (static::$hasEntitasCol === null) {
            static::$hasEntitasCol = Schema::hasColumn('master_barang', 'id_entitas');
        }

        return static::$hasEntitasCol;
    }

    /**
     * Filter barang milik entitas aktif (melalui barang_per_koperasi dan/atau kolom id_entitas).
     */
    public function scopeForEntitas(Builder $query, ?int $idEntitas): Builder
    {
        if ($idEntitas === null) {
            return $query;
        }

        return $query->where(function (Builder $sub) use ($idEntitas) {
            $sub->whereExists(function ($q) use ($idEntitas) {
                $q->selectRaw('1')
                    ->from('barang_per_koperasi')
                    ->whereColumn('barang_per_koperasi.id_barang', 'master_barang.id_barang')
                    ->where('barang_per_koperasi.id_entitas', $idEntitas);
            });

            if (static::hasEntitasColumn()) {
                $sub->orWhere('master_barang.id_entitas', $idEntitas);
            }
        });
    }

    public function entitas()
    {
        return $this->belongsTo(Entitas::class, 'id_entitas', 'id_entitas');
    }

    public function komoditas()
    {
        return $this->belongsTo(Komoditas::class, 'id_komoditas');
    }

    public function unitUsaha()
    {
        return $this->belongsTo(UnitUsaha::class, 'id_unit_usaha', 'id_unit_usaha');
    }

    public function satuanDasar()
    {
        return $this->belongsTo(Satuan::class, 'id_satuan_dasar');
    }

    public function konversiSatuan()
    {
        return $this->hasMany(KonversiSatuan::class, 'id_barang', 'id_barang');
    }

    public function pengaturanKoperasi()
    {
        return $this->hasMany(BarangPerKoperasi::class, 'id_barang', 'id_barang');
    }
}

<?php

namespace App\Models\Master;

use App\Models\Tenant\Entitas;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class Komoditas extends Model
{
    protected $table = 'komoditas';

    protected $fillable = ['id_entitas', 'kategori', 'nama', 'alias_json', 'is_active'];

    protected static ?bool $hasEntitasCol = null;

    protected function casts(): array
    {
        return ['alias_json' => 'array', 'is_active' => 'boolean'];
    }

    public static function hasEntitasColumn(): bool
    {
        if (static::$hasEntitasCol === null) {
            static::$hasEntitasCol = Schema::hasColumn('komoditas', 'id_entitas');
        }

        return static::$hasEntitasCol;
    }

    public function scopeForEntitas(Builder $query, ?int $idEntitas): Builder
    {
        if ($idEntitas !== null && static::hasEntitasColumn()) {
            $query->where($this->getTable() . '.id_entitas', $idEntitas);
        }

        return $query;
    }

    public function entitas()
    {
        return $this->belongsTo(Entitas::class, 'id_entitas', 'id_entitas');
    }

    public function barang()
    {
        return $this->hasMany(Barang::class, 'id_komoditas');
    }
}

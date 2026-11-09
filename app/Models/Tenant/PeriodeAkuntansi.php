<?php

namespace App\Models\Tenant;

use App\Models\Concerns\BelongsToEntitas;
use Illuminate\Database\Eloquent\Model;

class PeriodeAkuntansi extends Model
{
    use BelongsToEntitas;

    protected $table = 'periode_akuntansi';
    protected $primaryKey = 'id_periode';
    public $timestamps = false;

    protected $fillable = ['id_entitas', 'tahun', 'bulan', 'status', 'tgl_tutup', 'ditutup_oleh'];

    protected function casts(): array
    {
        return ['tgl_tutup' => 'datetime'];
    }

    public function entitas()
    {
        return $this->belongsTo(Entitas::class, 'id_entitas', 'id_entitas');
    }
}

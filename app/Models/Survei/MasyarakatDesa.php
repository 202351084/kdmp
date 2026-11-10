<?php

namespace App\Models\Survei;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Survei\SesiSurvei;
use App\Models\Tenant\Wilayah;

class MasyarakatDesa extends Model
{
    protected $table = 'masyarakat_desas';

    protected $fillable = [
        'id_sesi',
        'id_wilayah',
        'nama_kepala_keluarga',
        'nomor_hp',
        'umur',
        'jenis_kelamin',
        'provinsi',
        'kabupaten',
        'kecamatan',
        'desa',
        'dusun',
        'rt',
        'rw',
        'nama_jalan',
        'nomor_rumah',
        'detail_alamat',
    ];

    public function sesi(): BelongsTo
    {
        return $this->belongsTo(SesiSurvei::class, 'id_sesi');
    }

    public function wilayah(): BelongsTo
    {
        return $this->belongsTo(Wilayah::class, 'id_wilayah');
    }

    public function anggotaKeluarga(): HasMany
    {
        return $this->hasMany(AnggotaKeluarga::class, 'id_masyarakat');
    }

    public function anggota(): HasMany
    {
        return $this->hasMany(AnggotaKeluarga::class, 'id_masyarakat');
    }

    public function konsumsi(): HasMany
    {
        return $this->hasMany(KonsumsiKeluarga::class, 'id_masyarakat');
    }
}

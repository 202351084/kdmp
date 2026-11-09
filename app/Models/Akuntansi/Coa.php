<?php

namespace App\Models\Akuntansi;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Coa extends Model
{
    protected $table = 'master_coa';
    protected $primaryKey = 'kode_anak';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'kode_anak', 'kode_induk', 'nama_rekening', 'posisi_normal',
        'is_transaction', 'kelompok', 'is_kontra', 'level', 'urutan_laporan', 'is_active',
    ];

    protected function casts(): array
    {
        return ['is_kontra' => 'boolean', 'is_active' => 'boolean'];
    }

    public function induk()
    {
        return $this->belongsTo(self::class, 'kode_induk', 'kode_anak');
    }

    public function anak()
    {
        return $this->hasMany(self::class, 'kode_induk', 'kode_anak');
    }

    /**
     * Apakah akun ini merupakan rekening transaksi Bank yang namanya dapat disesuaikan per entitas?
     */
    public function isBankAccount(): bool
    {
        return $this->is_transaction === 'T'
            && ($this->kode_induk === '1112' || str_starts_with((string) $this->kode_anak, '1112'));
    }

    /**
     * Ambil peta nama bank kustom milik entitas aktif dari tabel master_kas_bank.
     *
     * @return array<string, object{id_kas_bank:int, nama:string, no_rekening:?string, kode_akun:string}>
     */
    public static function bankMapForEntitas(?int $idEntitas): array
    {
        if (! $idEntitas) {
            return [];
        }

        return DB::table('master_kas_bank')
            ->where('id_entitas', $idEntitas)
            ->where('jenis', 'bank')
            ->orderByDesc('is_default')
            ->orderBy('id_kas_bank')
            ->get()
            ->keyBy('kode_akun')
            ->all();
    }
}

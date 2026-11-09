<?php

namespace App\Models\Keuangan;

use App\Models\Concerns\BelongsToEntitas;
use App\Models\Master\KasBank;
use App\Models\Master\Pihak;
use Illuminate\Database\Eloquent\Model;

class Pelunasan extends Model
{
    use BelongsToEntitas;

    protected $table = 'pelunasan';
    protected $primaryKey = 'id_pelunasan';
    const UPDATED_AT = null;

    protected $fillable = [
        'id_entitas', 'kode_pelunasan', 'jenis', 'id_pihak', 'tanggal',
        'id_kas_bank', 'total_nilai', 'status_posting', 'id_jurnal',
        'catatan', 'created_by',
        'transfer_pengirim_nama', 'transfer_pengirim_bank', 'transfer_pengirim_rekening',
        'transfer_no_referensi', 'transfer_penerima_nama', 'transfer_penerima_rekening',
        'transfer_dikonfirmasi_pada', 'transfer_dikonfirmasi_oleh',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'total_nilai' => 'decimal:2',
            'transfer_dikonfirmasi_pada' => 'datetime',
        ];
    }

    public function pihak()
    {
        return $this->belongsTo(Pihak::class, 'id_pihak', 'id_pihak');
    }

    public function kasBank()
    {
        return $this->belongsTo(KasBank::class, 'id_kas_bank', 'id_kas_bank');
    }

    public function detail()
    {
        return $this->hasMany(PelunasanDetail::class, 'id_pelunasan', 'id_pelunasan');
    }
}

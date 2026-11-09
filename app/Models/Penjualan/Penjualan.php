<?php

namespace App\Models\Penjualan;

use App\Models\Concerns\BelongsToEntitas;
use App\Models\Master\Gudang;
use App\Models\Master\KasBank;
use App\Models\Master\Pihak;
use App\Models\Master\UnitUsaha;
use Illuminate\Database\Eloquent\Model;

/**
 * Transaksi penjualan kasir dapat dibayar tunai, transfer, atau kredit.
 * is_pembeli_anggota DISALIN saat transaksi â€” dasar perhitungan jasa anggota
 * untuk pembagian SHU, tidak boleh di-join dari master_pihak saat pelaporan.
 */
class Penjualan extends Model
{
    use BelongsToEntitas;

    protected $table = 'penjualan';
    protected $primaryKey = 'id_penjualan';

    protected $fillable = [
        'id_entitas', 'kode_penjualan', 'id_pihak', 'nama_pembeli', 'id_unit_usaha', 'id_gudang',
        'tanggal_transaksi', 'tgl_jatuh_tempo', 'is_pembeli_anggota', 'ada_baris_konsinyasi',
        'id_kas_bank', 'metode_bayar', 'total_bruto', 'diskon', 'total_bayar',
        'total_hpp', 'status', 'status_posting', 'id_jurnal', 'created_by',
        'transfer_pengirim_nama', 'transfer_pengirim_bank', 'transfer_pengirim_rekening',
        'transfer_no_referensi', 'transfer_penerima_nama', 'transfer_penerima_rekening',
        'transfer_dikonfirmasi_pada', 'transfer_dikonfirmasi_oleh',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_transaksi' => 'date',
            'tgl_jatuh_tempo' => 'date',
            'is_pembeli_anggota' => 'boolean',
            'ada_baris_konsinyasi' => 'boolean',
            'total_bruto' => 'decimal:2',
            'diskon' => 'decimal:2',
            'total_bayar' => 'decimal:2',
            'total_hpp' => 'decimal:2',
            'transfer_dikonfirmasi_pada' => 'datetime',
        ];
    }

    public function pihak()
    {
        return $this->belongsTo(Pihak::class, 'id_pihak', 'id_pihak');
    }

    public function unitUsaha()
    {
        return $this->belongsTo(UnitUsaha::class, 'id_unit_usaha', 'id_unit_usaha');
    }

    public function gudang()
    {
        return $this->belongsTo(Gudang::class, 'id_gudang', 'id_gudang');
    }

    public function kasBank()
    {
        return $this->belongsTo(KasBank::class, 'id_kas_bank', 'id_kas_bank');
    }

    public function detail()
    {
        return $this->hasMany(DetailPenjualan::class, 'id_penjualan', 'id_penjualan');
    }

    public function returs()
    {
        return $this->hasMany(ReturPenjualan::class, 'id_penjualan', 'id_penjualan');
    }
}

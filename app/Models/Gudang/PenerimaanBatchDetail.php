<?php

namespace App\Models\Gudang;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PenerimaanBatchDetail extends Model
{
    protected $table = 'penerimaan_batch_detail';
    protected $primaryKey = 'id_penerimaan_batch';

    protected $fillable = [
        'id_detail', 'id_batch', 'qty_dasar', 'qty_layak',
        'qty_tidak_layak', 'alasan_tidak_layak',
    ];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(StokBatch::class, 'id_batch', 'id_batch');
    }
}

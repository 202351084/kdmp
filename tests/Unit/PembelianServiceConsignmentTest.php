<?php

namespace Tests\Unit;

use App\Services\PembelianService;
use Tests\TestCase;

class PembelianServiceConsignmentTest extends TestCase
{
    public function test_consignment_intake_cannot_use_cash_or_transfer(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('dicatat sebagai hutang');

        PembelianService::createQuickPurchase(
            1,
            1,
            1,
            1,
            [['id_barang' => 1, 'id_satuan' => 1, 'qty_dasar' => 1, 'harga_satuan' => 1]],
            'tunai',
            null,
            null,
            'pengambilan_konsinyasi',
        );
    }

    public function test_consignment_intake_cannot_select_a_cash_or_bank_account(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('tidak menggunakan kas');

        PembelianService::createQuickPurchase(
            1,
            1,
            1,
            1,
            [['id_barang' => 1, 'id_satuan' => 1, 'qty_dasar' => 1, 'harga_satuan' => 1]],
            'kredit',
            today()->addDay()->toDateString(),
            1,
            'pengambilan_konsinyasi',
        );
    }

    public function test_consignment_intake_requires_a_positive_sender_price(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Harga yang ditetapkan desa pengirim');

        PembelianService::createQuickPurchase(
            1,
            1,
            1,
            1,
            [['id_barang' => 1, 'id_satuan' => 1, 'qty_dasar' => 1, 'harga_satuan' => 0]],
            'kredit',
            today()->addDay()->toDateString(),
            null,
            'pengambilan_konsinyasi',
        );
    }
}

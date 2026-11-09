<?php

namespace Tests\Unit;

use App\Services\PenjualanService;
use RuntimeException;
use Tests\TestCase;

class PenjualanServiceTest extends TestCase
{
    public function test_credit_sale_requires_a_due_date(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Tanggal jatuh tempo kredit wajib diisi');

        PenjualanService::createSale(1, 1, 1, 'kredit', [['id_barang' => 1, 'qty_dasar' => 1]]);
    }

    public function test_credit_sale_rejects_a_past_due_date(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Tanggal jatuh tempo kredit wajib diisi');

        PenjualanService::createSale(
            1,
            1,
            1,
            'kredit',
            [['id_barang' => 1, 'qty_dasar' => 1]],
            null,
            [],
            today()->subDay()->toDateString(),
        );
    }

    public function test_credit_sale_accepts_a_due_date_but_requires_cart_items(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Keranjang penjualan masih kosong.');

        PenjualanService::createSale(1, 1, 1, 'kredit', [], null, [], today()->addDay()->toDateString());
    }
}

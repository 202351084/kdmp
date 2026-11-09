<?php

namespace App\Support\Perencanaan;

use InvalidArgumentException;

final class PeriodeKalkulasi
{
    /**
     * @return array{kode: string, label: string, bulan_awal: int, bulan_akhir: int, bulan: list<int>}
     */
    public static function detail(string $periode): array
    {
        return match (strtolower($periode)) {
            'ganjil' => [
                'kode' => 'ganjil',
                'label' => 'Periode Ganjil (Januari–Juni)',
                'bulan_awal' => 1,
                'bulan_akhir' => 6,
                'bulan' => range(1, 6),
            ],
            'genap' => [
                'kode' => 'genap',
                'label' => 'Periode Genap (Juli–Desember)',
                'bulan_awal' => 7,
                'bulan_akhir' => 12,
                'bulan' => range(7, 12),
            ],
            default => throw new InvalidArgumentException('Periode harus berupa ganjil atau genap.'),
        };
    }

    public static function fromMonth(int $month): string
    {
        if ($month < 1 || $month > 12) {
            throw new InvalidArgumentException('Bulan harus bernilai antara 1 sampai 12.');
        }

        return $month <= 6 ? 'ganjil' : 'genap';
    }
}

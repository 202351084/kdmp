<x-layouts.app :title="'Invoice ' . $item->kode_pembelian" eyebrow="Invoice Pembelian">
    <style>
        @media print {
            body * { visibility: hidden; }
            #purchase-invoice, #purchase-invoice * { visibility: visible; }
            #purchase-invoice { position: absolute; inset: 0; width: 100%; }
            .no-print { display: none !important; }
        }
    </style>

    <div class="mx-auto max-w-4xl">
        <div class="no-print mb-4 flex flex-wrap gap-2">
            <button type="button" onclick="window.print()" class="rounded-sm bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700">Cetak Invoice</button>
            <a href="{{ route('pembelian.pembelian.show', $item) }}" class="rounded-sm border border-paper-300 px-4 py-2 text-sm font-medium text-ink-700 hover:bg-paper-100">Kembali ke Pembelian</a>
        </div>

        <article id="purchase-invoice" class="rounded-sm border border-paper-300 bg-white p-6 text-ink-900 sm:p-10">
            <header class="flex flex-wrap justify-between gap-6 border-b border-paper-300 pb-5">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-wide text-emerald-700">{{ $item->sumber_pembelian === 'pengambilan_konsinyasi' ? 'Bukti Pengambilan Konsinyasi' : 'Invoice Pembelian' }}</p>
                    <h1 class="mt-1 text-2xl font-bold">{{ $item->kode_pembelian }}</h1>
                    <p class="mt-1 text-sm text-ink-600">Tanggal: {{ $item->tanggal_transaksi?->format('d M Y') ?? '-' }}</p>
                </div>
                <div class="text-sm sm:text-right">
                    <p><span class="font-medium">Status:</span> {{ ucfirst($item->status) }}</p>
                    <p class="mt-1"><span class="font-medium">Sumber:</span> {{ match ($item->sumber_pembelian) { 'desa_lain' => 'Pembelian dari Desa Lain', 'warga_lokal' => 'Pembelian dari Warga Lokal', 'pengambilan_konsinyasi' => 'Pengambilan Konsinyasi dari Desa Lain', default => 'Pembelian' } }}</p>
                    <p class="mt-1"><span class="font-medium">Pembayaran:</span> {{ $item->sumber_pembelian === 'pengambilan_konsinyasi' ? 'Hutang Konsinyasi' : ucfirst($item->jenis_pembayaran) }}</p>
                </div>
            </header>

            <section class="grid gap-6 py-5 sm:grid-cols-2">
                <div>
                    <h2 class="text-xs font-semibold uppercase tracking-wide text-ink-600">{{ $item->sumber_pembelian === 'pengambilan_konsinyasi' ? 'Desa Pengirim' : 'Supplier' }}</h2>
                    <p class="mt-1 font-semibold">{{ $item->pihak?->nama_pihak ?? $item->pihak?->nama ?? '-' }}</p>
                </div>
                <div>
                    <h2 class="text-xs font-semibold uppercase tracking-wide text-ink-600">Tujuan</h2>
                    <p class="mt-1 font-semibold">{{ $item->unitUsaha?->nama_unit_usaha ?? '-' }}</p>
                    <p class="text-sm text-ink-600">Gudang: {{ $item->gudang?->nama_gudang ?? '-' }}</p>
                    @if ($item->sumber_pembelian === 'pengambilan_konsinyasi')
                        <p class="mt-1 text-sm text-ink-600">Jatuh tempo hutang: {{ $item->tgl_jatuh_tempo?->format('d M Y') ?? '-' }}</p>
                    @endif
                </div>
            </section>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="border-y border-paper-300 bg-paper-100 text-left">
                        <tr>
                            <th class="px-3 py-2">Barang</th>
                            <th class="px-3 py-2 text-right">Jumlah</th>
                            <th class="px-3 py-2 text-right">Harga Satuan</th>
                            <th class="px-3 py-2 text-right">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($item->detail as $line)
                            <tr class="border-b border-paper-200">
                                <td class="px-3 py-3">{{ $line->barang?->nama_barang ?? '-' }}</td>
                                <td class="px-3 py-3 text-right">
                                    @if ($line->qty_kg !== null)
                                        {{ number_format((float) $line->qty_kg, 2, ',', '.') }} KG
                                    @else
                                        {{ number_format((float) $line->qty_input, 2, ',', '.') }} {{ $line->satuanInput?->kode_satuan ?? '-' }}
                                    @endif
                                </td>
                                <td class="px-3 py-3 text-right">
                                    Rp {{ number_format((float) ($line->harga_per_kg ?? $line->harga_satuan_input), 2, ',', '.') }}
                                    / {{ $line->qty_kg !== null ? 'KG' : ($line->satuanInput?->kode_satuan ?? 'unit') }}
                                </td>
                                <td class="px-3 py-3 text-right">Rp {{ number_format((float) $line->subtotal, 2, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="3" class="px-3 py-3 text-right">Total Pembelian</th>
                            <th class="px-3 py-3 text-right">Rp {{ number_format((float) $item->total_pembelian, 2, ',', '.') }}</th>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <footer class="mt-10 border-t border-paper-200 pt-3 text-xs text-ink-500">Ringkasan hasil transaksi Pembelian {{ $item->kode_pembelian }}.</footer>
        </article>
    </div>
</x-layouts.app>

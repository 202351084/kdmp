<x-layouts.app :title="'Invoice Retur ' . $item->kode_retur" eyebrow="Invoice Retur Pembelian">
    <style>
        @media print {
            body * { visibility: hidden; }
            #return-invoice, #return-invoice * { visibility: visible; }
            #return-invoice { position: absolute; inset: 0; width: 100%; }
            .no-print { display: none !important; }
        }
    </style>

    <div class="mx-auto max-w-4xl">
        <div class="no-print mb-4 flex flex-wrap gap-2">
            <button type="button" onclick="window.print()" class="rounded-sm bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700">Cetak Invoice Retur</button>
            <a href="{{ route('pembelian.retur.index') }}" class="rounded-sm border border-paper-300 px-4 py-2 text-sm font-medium text-ink-700 hover:bg-paper-100">Kembali ke Retur</a>
        </div>

        <article id="return-invoice" class="rounded-sm border border-paper-300 bg-white p-6 text-ink-900 sm:p-10">
            <header class="flex flex-wrap justify-between gap-6 border-b border-paper-300 pb-5">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-wide text-emerald-700">Invoice Retur Pembelian</p>
                    <h1 class="mt-1 text-2xl font-bold">{{ $item->kode_retur }}</h1>
                    <p class="mt-1 text-sm text-ink-600">Tanggal: {{ $item->tgl_retur?->format('d M Y') ?? '-' }}</p>
                </div>
                <div class="text-sm sm:text-right">
                    <p><span class="font-medium">Status:</span> {{ ucfirst($item->status) }}</p>
                    <p class="mt-1"><span class="font-medium">Penyelesaian:</span> {{ ucfirst(str_replace('_', ' ', $item->jenis_penyelesaian)) }}</p>
                </div>
            </header>

            <section class="grid gap-6 py-5 sm:grid-cols-2">
                <div>
                    <h2 class="text-xs font-semibold uppercase tracking-wide text-ink-600">Pembelian Asal</h2>
                    <p class="mt-1 font-semibold">{{ $item->pembelian?->kode_pembelian ?? '-' }}</p>
                    <p class="text-sm text-ink-600">Tanggal: {{ $item->pembelian?->tanggal_transaksi?->format('d M Y') ?? '-' }}</p>
                </div>
                <div>
                    <h2 class="text-xs font-semibold uppercase tracking-wide text-ink-600">Supplier</h2>
                    <p class="mt-1 font-semibold">{{ $item->pembelian?->pihak?->nama_pihak ?? $item->pembelian?->pihak?->nama ?? '-' }}</p>
                </div>
            </section>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="border-y border-paper-300 bg-paper-100 text-left">
                        <tr>
                            <th class="px-3 py-2">Barang</th>
                            <th class="px-3 py-2 text-right">Jumlah Retur</th>
                            <th class="px-3 py-2 text-right">Nilai / KG</th>
                            <th class="px-3 py-2 text-right">Nilai Retur</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($item->detail as $line)
                            <tr class="border-b border-paper-200">
                                <td class="px-3 py-3">{{ $line->barang?->nama_barang ?? '-' }}</td>
                                <td class="px-3 py-3 text-right">{{ $line->qty_kg === null ? '—' : number_format((float) $line->qty_kg, 2, ',', '.') . ' KG' }}</td>
                                <td class="px-3 py-3 text-right">{{ $line->hpp_per_kg === null ? '—' : 'Rp ' . number_format((float) $line->hpp_per_kg, 2, ',', '.') }}</td>
                                <td class="px-3 py-3 text-right">Rp {{ number_format((float) $line->nilai, 2, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="3" class="px-3 py-3 text-right">Total Retur</th>
                            <th class="px-3 py-3 text-right">Rp {{ number_format((float) $item->total_nilai, 2, ',', '.') }}</th>
                        </tr>
                    </tfoot>
                </table>
            </div>

            @if ($item->alasan)
                <section class="mt-5">
                    <h2 class="text-sm font-semibold">Alasan Retur</h2>
                    <p class="mt-1 whitespace-pre-line text-sm text-ink-700">{{ $item->alasan }}</p>
                </section>
            @endif

            <footer class="mt-10 border-t border-paper-200 pt-3 text-xs text-ink-500">Ringkasan hasil Retur {{ $item->kode_retur }} untuk pembelian {{ $item->pembelian?->kode_pembelian ?? '-' }}.</footer>
        </article>
    </div>
</x-layouts.app>

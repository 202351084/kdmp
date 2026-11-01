<x-layouts.app :title="$title" eyebrow="Konsinyasi">
    <div class="mb-5 flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-ink-900">Retur Konsinyasi</h1>
            <p class="mt-1 text-sm text-ink-600">Catatan barang titipan yang sudah diterima kembali oleh pemilik.</p>
        </div>
        <a href="{{ route('konsinyasi.retur.create') }}" class="rounded-sm bg-merah-500 px-4 py-2 text-sm font-medium text-paper-50 hover:bg-merah-600">
            + Catat Retur
        </a>
    </div>

    <div class="overflow-hidden rounded-sm border border-paper-300 bg-paper-50">
        <div class="overflow-x-auto">
            <table class="min-w-[900px] w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-paper-300 bg-paper-200/60 font-mono text-[11px] uppercase tracking-wide text-ink-600/70">
                        <th class="px-4 py-3 font-medium">Kode / Tanggal</th>
                        <th class="px-4 py-3 font-medium">Kiriman</th>
                        <th class="px-4 py-3 font-medium">Barang</th>
                        <th class="px-4 py-3 font-medium">Pemilik</th>
                        <th class="px-4 py-3 text-right font-medium">Jumlah Kembali</th>
                        <th class="px-4 py-3 font-medium">Dicatat oleh</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($items as $item)
                        @php
                            $stok = $item->stokKonsinyasi;
                            $kiriman = $stok?->kiriman;
                            $pemilik = $kiriman?->jenis_pemilik === 'pihak'
                                ? ($kiriman->pihakPemilik?->nama ?? '-')
                                : ($kiriman?->koperasiPemilik?->nama_entitas ?? '-');
                        @endphp
                        <tr class="border-b border-paper-200 last:border-0">
                            <td class="px-4 py-3">
                                <p class="font-medium text-ink-900">{{ $item->kode_retur }}</p>
                                <p class="mt-1 text-xs text-ink-600">{{ $item->tanggal?->format('d M Y') }}</p>
                            </td>
                            <td class="px-4 py-3">{{ $kiriman?->kode_kiriman ?? '-' }}</td>
                            <td class="px-4 py-3">{{ $stok?->barang?->nama_barang ?? '-' }}</td>
                            <td class="px-4 py-3">{{ $pemilik }}</td>
                            <td class="px-4 py-3 text-right font-semibold">
                                {{ number_format((float) $item->qty_dikembalikan, 4, ',', '.') }}
                                {{ $stok?->barang?->satuanDasar?->kode_satuan }}
                            </td>
                            <td class="px-4 py-3">{{ $item->entitasPencatat?->nama_entitas ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-10 text-center text-sm text-ink-600">
                                Belum ada retur konsinyasi yang dicatat.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">{{ $items->links() }}</div>
</x-layouts.app>

<x-layouts.app :title="$title" eyebrow="Gudang">
    <div class="mb-4">
        <a href="{{ route('gudang.index') }}" class="text-sm font-medium text-ink-700 hover:text-merah-600">
            &larr; Kembali ke Gudang
        </a>
    </div>

    <div class="mb-5 flex items-center justify-between gap-4">
        <form method="GET" class="flex flex-wrap items-center gap-2">
            <input
                type="text"
                name="q"
                value="{{ request('q') }}"
                placeholder="Cari barang / gudang..."
                class="w-72 rounded-sm border border-paper-300 bg-paper-50 px-3 py-2 text-sm placeholder:text-ink-600/40 focus:border-merah-400 focus:outline-none"
            >
            <select name="status" class="rounded-sm border border-paper-300 bg-paper-50 px-3 py-2 text-sm text-ink-700 focus:border-merah-400 focus:outline-none">
                <option value="">Semua status</option>
                <option value="menipis" @selected($filterStatus === 'menipis')>Menipis</option>
            </select>
            <button class="rounded-sm border border-paper-300 px-3 py-2 text-sm text-ink-700 hover:border-merah-400">
                Terapkan
            </button>
        </form>
    </div>

    <div class="overflow-x-auto rounded-sm border border-paper-300 bg-paper-50">
        <table class="w-full min-w-[760px] text-left text-sm">
            <thead>
                <tr class="border-b border-paper-300 bg-paper-200/60 font-mono text-[11px] uppercase tracking-wide text-ink-600/70">
                    <th class="px-4 py-3 font-medium">Gudang</th>
                    <th class="px-4 py-3 font-medium">Barang</th>
                    <th class="px-4 py-3 text-right font-medium">Tersedia</th>
                    <th class="px-4 py-3 text-right font-medium">Total Koperasi</th>
                    <th class="px-4 py-3 text-center font-medium">Status</th>
                    <th class="px-4 py-3 text-right font-medium">Nilai Persediaan</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($items as $item)
                    @php
                        $qtyTersedia = (float) $item->qty_on_hand - (float) $item->qty_reserved;
                        $qtyTersediaKoperasi = (float) ($item->qty_tersedia_koperasi ?? 0);
                        $qtyDipesan = (float) $item->qty_reserved;
                        $stokMinimum = $item->stok_minimum;
                        $satuan = $item->barang->satuanDasar->kode_satuan ?? '';
                        $stokMenipis = $stokMinimum !== null
                            && bccomp((string) $stokMinimum, '0', 4) > 0
                            && bccomp((string) $item->qty_tersedia_koperasi, (string) $stokMinimum, 4) <= 0;
                    @endphp
                    <tr class="border-b border-paper-200 last:border-0 hover:bg-paper-100/70">
                        <td class="px-4 py-3 text-sm text-ink-800">{{ $item->gudang->nama_gudang ?? '-' }}</td>
                        <td class="px-4 py-3 font-medium text-ink-800">{{ $item->barang->nama_barang ?? '-' }}</td>
                        <td class="px-4 py-3 text-right">
                            <span class="font-medium text-ink-800">
                                {{ rtrim(rtrim(number_format($qtyTersedia, 4, ',', '.'), '0'), ',') }} {{ $satuan }}
                            </span>
                            <span class="mt-1 block text-xs text-ink-600/60">
                                Dipesan: {{ rtrim(rtrim(number_format($qtyDipesan, 4, ',', '.'), '0'), ',') }} {{ $satuan }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <span class="font-medium text-ink-800">
                                {{ rtrim(rtrim(number_format($qtyTersediaKoperasi, 4, ',', '.'), '0'), ',') }} {{ $satuan }}
                            </span>
                            <span class="mt-1 block text-xs text-ink-600/60">
                                @if ($stokMinimum !== null && bccomp((string) $stokMinimum, '0', 4) > 0)
                                    Minimum: {{ rtrim(rtrim(number_format((float) $stokMinimum, 4, ',', '.'), '0'), ',') }} {{ $satuan }}
                                @else
                                    Minimum belum diatur
                                @endif
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if ($stokMinimum === null || bccomp((string) $stokMinimum, '0', 4) <= 0)
                                <span class="rounded-sm bg-paper-200 px-2 py-1 text-xs text-ink-600">Belum diatur</span>
                            @elseif ($stokMenipis)
                                <span class="rounded-sm bg-merah-100 px-2 py-1 text-xs font-medium text-merah-700">Menipis</span>
                            @else
                                <span class="rounded-sm bg-green-100 px-2 py-1 text-xs font-medium text-green-700">Aman</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            <span class="font-medium text-ink-800">Rp {{ number_format((float) $item->nilai_persediaan, 2, ',', '.') }}</span>
                            <span class="mt-1 block text-xs text-ink-600/60">HPP rata-rata: Rp {{ number_format((float) $item->hpp_rata2, 2, ',', '.') }}</span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-10 text-center text-sm text-ink-600/60">
                            Belum ada stok. Jalankan penerimaan barang terlebih dahulu.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $items->links() }}
    </div>
</x-layouts.app>

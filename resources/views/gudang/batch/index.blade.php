<x-layouts.app :title="$title" eyebrow="Gudang">
    <div class="mb-4">
        <a href="{{ route('gudang.index') }}" class="text-sm font-medium text-ink-700 hover:text-merah-600">
            &larr; Kembali ke Gudang
        </a>
    </div>

    <div class="mb-5 flex flex-wrap items-center justify-between gap-4">
        <form method="GET" class="flex flex-wrap items-center gap-2">
            <input
                type="text"
                name="q"
                value="{{ request('q') }}"
                placeholder="Cari batch / barang / gudang..."
                class="w-72 rounded-sm border border-paper-300 bg-paper-50 px-3 py-2 text-sm placeholder:text-ink-600/40 focus:border-merah-400 focus:outline-none"
            >
            <select name="status" class="rounded-sm border border-paper-300 bg-paper-50 px-3 py-2 text-sm text-ink-700 focus:border-merah-400 focus:outline-none">
                <option value="">Semua batch aktif</option>
                <option value="segera" @selected($filterStatus === 'segera')>Kedaluwarsa ≤ 30 hari</option>
                <option value="kedaluwarsa" @selected($filterStatus === 'kedaluwarsa')>Sudah kedaluwarsa</option>
                <option value="tanpa-tanggal" @selected($filterStatus === 'tanpa-tanggal')>Tanpa tanggal</option>
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
                    <th class="px-4 py-3 font-medium">Kode Batch</th>
                    <th class="px-4 py-3 font-medium">Barang</th>
                    <th class="px-4 py-3 font-medium">Gudang</th>
                    <th class="px-4 py-3 text-right font-medium">Saldo</th>
                    <th class="px-4 py-3 font-medium">Kedaluwarsa</th>
                    <th class="px-4 py-3 text-center font-medium">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($items as $item)
                    @php
                        $expired = $item->tanggal_kedaluwarsa !== null && $item->tanggal_kedaluwarsa < now()->toDateString();
                        $nearExpiry = $item->tanggal_kedaluwarsa !== null
                            && ! $expired
                            && $item->tanggal_kedaluwarsa <= now()->addDays(30)->toDateString();
                    @endphp
                    <tr class="border-b border-paper-200 last:border-0 hover:bg-paper-100/70">
                        <td class="px-4 py-3 font-mono text-xs text-ink-800">
                            {{ $item->kode_batch }}
                            @if ($item->is_stok_awal)
                                <span class="mt-1 block font-sans text-[11px] text-ink-600/60">Stok awal / tidak diketahui</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-ink-800">{{ $item->kode_barang }} · {{ $item->nama_barang }}</td>
                        <td class="px-4 py-3 text-ink-700">{{ $item->nama_gudang }}</td>
                        <td class="px-4 py-3 text-right font-medium text-ink-800">
                            {{ rtrim(rtrim(number_format((float) $item->qty_on_hand, 4, ',', '.'), '0'), ',') }}
                            {{ $item->kode_satuan }}
                        </td>
                        <td class="px-4 py-3 text-ink-700">
                            {{ $item->tanggal_kedaluwarsa ? \Illuminate\Support\Carbon::parse($item->tanggal_kedaluwarsa)->format('d M Y') : 'Tidak diketahui' }}
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if ($expired)
                                <span class="rounded-sm bg-merah-100 px-2 py-1 text-xs font-medium text-merah-700">Kedaluwarsa</span>
                            @elseif ($nearExpiry)
                                <span class="rounded-sm bg-amber-100 px-2 py-1 text-xs font-medium text-amber-800">Segera kedaluwarsa</span>
                            @else
                                <span class="rounded-sm bg-green-100 px-2 py-1 text-xs font-medium text-green-700">Aktif</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-10 text-center text-sm text-ink-600/60">
                            Tidak ada batch dengan filter tersebut.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $items->links() }}</div>
</x-layouts.app>

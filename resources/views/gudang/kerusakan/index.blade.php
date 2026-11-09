<x-layouts.app :title="$title" eyebrow="Gudang">
    <div class="mb-4">
        <a href="{{ route('gudang.index') }}" class="text-sm font-medium text-ink-700 hover:text-merah-600">
            &larr; Kembali ke Gudang
        </a>
    </div>

    <div class="mb-5 flex items-center justify-between gap-4">
        <form method="GET" class="flex items-center gap-2">
            <input
                type="text"
                name="q"
                value="{{ request('q') }}"
                placeholder="Cari barang / jenis kejadian..."
                class="w-72 rounded-sm border border-paper-300 bg-paper-50 px-3 py-2 text-sm placeholder:text-ink-600/40 focus:border-merah-400 focus:outline-none"
            >
            <button class="rounded-sm border border-paper-300 px-3 py-2 text-sm text-ink-700 hover:border-merah-400">
                Cari
            </button>
        </form>

        <a href="{{ route('gudang.kerusakan.create') }}"
           class="rounded-sm bg-merah-500 px-4 py-2 text-sm font-medium text-paper-50 hover:bg-merah-600">
            + Tambah Kerusakan
        </a>
    </div>

    <div class="overflow-hidden rounded-sm border border-paper-300 bg-paper-50">
        <table class="w-full text-left text-sm">
            <thead>
                <tr class="border-b border-paper-300 bg-paper-200/60 font-mono text-[11px] uppercase tracking-wide text-ink-600/70">
                    <th class="px-4 py-3 font-medium">Tanggal</th>
                    <th class="px-4 py-3 font-medium">Barang / Batch</th>
                    <th class="px-4 py-3 font-medium">Qty</th>
                    <th class="px-4 py-3 font-medium">Jenis</th>
                    <th class="px-4 py-3 font-medium">Nilai</th>
                    <th class="px-4 py-3 text-right font-medium">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($items as $item)
                    <tr class="border-b border-paper-200 last:border-0 hover:bg-paper-100/70">
                        <td class="px-4 py-3 text-ink-800">{{ $item->tanggal?->format('d M Y') }}</td>
                        <td class="px-4 py-3 text-ink-800">
                            {{ $item->barang->nama_barang ?? '-' }}
                            @if ($item->relationLoaded('batch') && $item->batch)
                                <span class="mt-1 block text-xs text-ink-600/60">Batch {{ $item->batch->kode_batch }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-ink-800">{{ $item->qty }}</td>
                        <td class="px-4 py-3 text-ink-800">{{ $item->jenis_kejadian }}</td>
                        <td class="px-4 py-3 text-ink-800">Rp {{ number_format((float) $item->nilai_kerugian, 2, ',', '.') }}</td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('gudang.kerusakan.show', $item->id_kerusakan) }}" class="text-xs font-medium text-ink-700 hover:text-merah-600">
                                Lihat
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-10 text-center text-sm text-ink-600/60">
                            Belum ada data kerusakan barang.
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

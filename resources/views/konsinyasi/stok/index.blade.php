<x-layouts.app :title="$title" eyebrow="Konsinyasi">
    <div class="mb-5">
        <h1 class="text-2xl font-semibold text-ink-900">Stok Konsinyasi</h1>
        <p class="mt-1 text-sm text-ink-600">Barang titipan yang berada di koperasi Anda. Barang ini bukan stok milik koperasi penerima.</p>
    </div>

    <div class="mb-5 rounded-sm border border-paper-300 bg-paper-50 p-4">
        <form method="GET" class="grid gap-3 md:grid-cols-[1fr_220px_auto] md:items-end">
            <label class="block text-sm">
                <span class="mb-1 block text-xs font-medium text-ink-700">Cari barang, pengirim, atau kode kiriman</span>
                <input type="search" name="q" value="{{ request('q') }}" class="w-full rounded-sm border border-paper-300 bg-paper-50 px-3 py-2 text-sm focus:border-merah-400 focus:outline-none">
            </label>
            <label class="block text-sm">
                <span class="mb-1 block text-xs font-medium text-ink-700">Status stok</span>
                <select name="status" class="w-full rounded-sm border border-paper-300 bg-paper-50 px-3 py-2 text-sm focus:border-merah-400 focus:outline-none">
                    <option value="">Semua status</option>
                    <option value="aktif" @selected(request('status') === 'aktif')>Masih ada</option>
                    <option value="habis" @selected(request('status') === 'habis')>Habis</option>
                    <option value="dikembalikan" @selected(request('status') === 'dikembalikan')>Dikembalikan</option>
                </select>
            </label>
            <button class="rounded-sm border border-paper-300 px-4 py-2 text-sm font-medium text-ink-700 hover:border-merah-400">Tampilkan</button>
        </form>
    </div>

    <div class="overflow-hidden rounded-sm border border-paper-300 bg-paper-50">
        <div class="overflow-x-auto">
            <table class="min-w-[900px] w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-paper-300 bg-paper-200/60 font-mono text-[11px] uppercase tracking-wide text-ink-600/70">
                        <th class="px-4 py-3 font-medium">Barang / Kiriman</th>
                        <th class="px-4 py-3 font-medium">Desa Pemilik</th>
                        <th class="px-4 py-3 text-right font-medium">Dititipkan</th>
                        <th class="px-4 py-3 text-right font-medium">Terjual</th>
                        <th class="px-4 py-3 text-right font-medium">Sisa</th>
                        <th class="px-4 py-3 text-right font-medium">Harga Jual</th>
                        <th class="px-4 py-3 font-medium">Status</th>
                        <th class="px-4 py-3 text-right font-medium">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($items as $item)
                        <tr class="border-b border-paper-200 last:border-0 hover:bg-paper-100/70">
                            <td class="px-4 py-3">
                                <p class="font-medium text-ink-900">{{ $item->barang?->nama_barang ?? 'Barang tidak ditemukan' }}</p>
                                <p class="mt-1 text-xs text-ink-600">{{ $item->kiriman?->kode_kiriman ?? '-' }} · {{ $item->kiriman?->gudangTujuan?->nama_gudang ?? 'Gudang penerima' }}</p>
                            </td>
                            <td class="px-4 py-3 text-ink-800">{{ $item->kiriman?->koperasiPemilik?->nama_entitas ?? 'Desa pemilik tidak ditemukan' }}</td>
                            <td class="px-4 py-3 text-right">{{ number_format((float) $item->qty_titip, 4, ',', '.') }} {{ $item->barang?->satuanDasar?->kode_satuan }}</td>
                            <td class="px-4 py-3 text-right">{{ number_format((float) $item->qty_terjual, 4, ',', '.') }} {{ $item->barang?->satuanDasar?->kode_satuan }}</td>
                            <td class="px-4 py-3 text-right font-semibold">{{ number_format((float) $item->qty_sisa, 4, ',', '.') }} {{ $item->barang?->satuanDasar?->kode_satuan }}</td>
                            <td class="px-4 py-3 text-right">Rp {{ number_format((float) $item->harga_jual_satuan, 2, ',', '.') }}</td>
                            <td class="px-4 py-3">
                                <span class="rounded-full px-2 py-1 text-xs font-medium {{ $item->status === 'aktif' ? 'bg-sawah-100 text-sawah-600' : 'bg-paper-200 text-ink-600' }}">
                                    {{ ['aktif' => 'Masih ada', 'habis' => 'Habis', 'dikembalikan' => 'Dikembalikan'][$item->status] ?? $item->status }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('konsinyasi.stok.show', $item->id_stok_konsinyasi) }}" class="font-medium text-merah-600 hover:text-merah-700">Detail</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-10 text-center text-sm text-ink-600">
                                Belum ada stok titipan yang cocok dengan pencarian ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">{{ $items->links() }}</div>
</x-layouts.app>

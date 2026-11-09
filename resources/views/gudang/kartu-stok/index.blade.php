<x-layouts.app :title="$title" eyebrow="Gudang">
    <div class="mb-4">
        <a href="{{ route('gudang.index') }}" class="text-sm font-medium text-ink-700 hover:text-merah-600">
            &larr; Kembali ke Gudang
        </a>
    </div>

    <div class="mb-5 rounded-sm border border-paper-300 bg-paper-50 p-4">
        <form method="GET" class="grid gap-3 md:grid-cols-2 lg:grid-cols-3 md:items-end">
            <div>
                <label class="mb-1 block text-xs font-medium text-ink-700">Gudang</label>
                <select name="id_gudang" class="w-full rounded-sm border border-paper-300 bg-paper-50 px-3 py-2 text-sm focus:border-merah-400 focus:outline-none">
                    <option value="">Semua gudang</option>
                    @foreach ($gudang as $item)
                        <option value="{{ $item->id_gudang }}" {{ request('id_gudang') == $item->id_gudang ? 'selected' : '' }}>
                            {{ $item->nama_gudang }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-ink-700">Barang</label>
                <select name="id_barang" class="w-full rounded-sm border border-paper-300 bg-paper-50 px-3 py-2 text-sm focus:border-merah-400 focus:outline-none">
                    <option value="">Semua barang</option>
                    @foreach ($barang as $item)
                        <option value="{{ $item->id_barang }}" {{ request('id_barang') == $item->id_barang ? 'selected' : '' }}>
                            {{ $item->nama_barang }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-ink-700">Tanggal Mulai</label>
                <input type="date" name="tanggal_mulai" value="{{ request('tanggal_mulai') }}" class="w-full rounded-sm border border-paper-300 bg-paper-50 px-3 py-2 text-sm focus:border-merah-400 focus:outline-none">
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-ink-700">Tanggal Selesai</label>
                <input type="date" name="tanggal_selesai" value="{{ request('tanggal_selesai') }}" class="w-full rounded-sm border border-paper-300 bg-paper-50 px-3 py-2 text-sm focus:border-merah-400 focus:outline-none">
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-ink-700">Jenis Mutasi</label>
                <select name="jenis_mutasi" class="w-full rounded-sm border border-paper-300 bg-paper-50 px-3 py-2 text-sm focus:border-merah-400 focus:outline-none">
                    <option value="">Semua jenis</option>
                    @foreach (['IN' => 'Barang Masuk', 'OUT' => 'Barang Keluar', 'ADJ_IN' => 'Penyesuaian Masuk', 'ADJ_OUT' => 'Penyesuaian Keluar'] as $value => $label)
                        <option value="{{ $value }}" {{ request('jenis_mutasi') === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex gap-2">
                <button class="flex-1 rounded-sm border border-paper-300 px-4 py-2 text-sm font-medium text-ink-700 hover:border-merah-400">
                    Filter
                </button>
                <a href="{{ route('gudang.kartu-stok.index') }}" class="rounded-sm border border-paper-300 px-4 py-2 text-sm font-medium text-ink-700 hover:border-merah-400">
                    Reset
                </a>
            </div>
        </form>
    </div>

    <div class="overflow-hidden rounded-sm border border-paper-300 bg-paper-50">
        <div class="border-b border-paper-300 px-4 py-3">
            <h2 class="font-display text-base font-semibold text-ink-900">Riwayat Mutasi Stok</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-paper-300 bg-paper-200/60 font-mono text-[11px] uppercase tracking-wide text-ink-600/70">
                        <th class="px-4 py-3 font-medium">Tanggal</th>
                        <th class="px-4 py-3 font-medium">Gudang</th>
                        <th class="px-4 py-3 font-medium">Barang</th>
                        <th class="px-4 py-3 font-medium">Jenis</th>
                        <th class="px-4 py-3 font-medium">Referensi</th>
                        <th class="px-4 py-3 text-right font-medium">Qty Masuk</th>
                        <th class="px-4 py-3 text-right font-medium">Qty Keluar</th>
                        <th class="px-4 py-3 text-right font-medium">Saldo</th>
                        <th class="px-4 py-3 text-right font-medium">Nilai Mutasi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($items as $item)
                        <tr class="border-b border-paper-200 last:border-0 hover:bg-paper-100/70">
                            <td class="px-4 py-3 text-ink-800">{{ $item->tanggal?->format('d M Y') }}</td>
                            <td class="px-4 py-3 text-ink-800">{{ $item->gudang->nama_gudang ?? '-' }}</td>
                            <td class="px-4 py-3 text-ink-800">{{ $item->barang->nama_barang ?? '-' }}</td>
                            <td class="px-4 py-3">
                                <span class="rounded-full px-2 py-0.5 text-xs {{ $item->jenis_mutasi === 'IN' || $item->jenis_mutasi === 'ADJ_IN' ? 'bg-sawah-100 text-sawah-600' : 'bg-merah-100 text-merah-600' }}">
                                    {{ $item->jenis_mutasi }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-ink-700">{{ $item->ref_tipe }} #{{ $item->ref_id ?? '-' }}</td>
                            <td class="px-4 py-3 text-right text-sawah-600">
                                {{ rtrim(rtrim(number_format((float) $item->qty_masuk, 4, ',', '.'), '0'), ',') }}
                                {{ $item->barang->satuanDasar->kode_satuan ?? '' }}
                            </td>
                            <td class="px-4 py-3 text-right text-merah-600">
                                {{ rtrim(rtrim(number_format((float) $item->qty_keluar, 4, ',', '.'), '0'), ',') }}
                                {{ $item->barang->satuanDasar->kode_satuan ?? '' }}
                            </td>
                            <td class="px-4 py-3 text-right text-ink-800">
                                {{ rtrim(rtrim(number_format((float) $item->saldo_qty, 4, ',', '.'), '0'), ',') }}
                                {{ $item->barang->satuanDasar->kode_satuan ?? '' }}
                            </td>
                            <td class="px-4 py-3 text-right text-ink-800">Rp {{ number_format((float) $item->nilai_mutasi, 2, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-4 py-10 text-center text-sm text-ink-600/60">Belum ada riwayat mutasi stok.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">
        {{ $items->links() }}
    </div>
</x-layouts.app>
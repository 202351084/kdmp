<x-layouts.app :title="$title" eyebrow="Master Data Barang">
    <div class="mb-4">
        <a href="{{ route('master.barang.index') }}" class="text-sm font-medium text-ink-700 hover:text-merah-600">
            &larr; Kembali ke Master Barang
        </a>
    </div>

    <div class="mb-5 rounded-sm border border-paper-300 bg-paper-50 p-4">
        <p class="text-sm text-ink-700">
            Atur ambang minimum untuk barang koperasi ini. Nilai 0 berarti peringatan belum diaktifkan.
            Ambang dibandingkan dengan total stok tersedia di seluruh gudang (stok on-hand dikurangi stok dipesan).
            Perubahan dicatat di audit log.
        </p>
    </div>
    @if ($pelacakanBatchTersedia)
        <div class="mb-5 rounded-sm border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900">
            Pelacakan batch bersifat permanen setelah diaktifkan. Saldo barang yang sudah ada akan dicatat sebagai
            <strong>stok awal dengan tanggal kedaluwarsa tidak diketahui</strong>. Penerimaan berikutnya dapat dicatat
            per batch dan pengeluaran stok akan mengikuti FEFO.
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-5 rounded-sm border border-merah-300 bg-merah-50 px-4 py-3 text-sm text-merah-800">
            {{ $errors->first() }}
        </div>
    @endif

    <div class="mb-5 flex items-center justify-between gap-4">
        <form method="GET" class="flex items-center gap-2">
            <input
                type="text"
                name="q"
                value="{{ request('q') }}"
                placeholder="Cari kode / nama barang..."
                class="w-72 rounded-sm border border-paper-300 bg-paper-50 px-3 py-2 text-sm placeholder:text-ink-600/40 focus:border-merah-400 focus:outline-none"
            >
            <button class="rounded-sm border border-paper-300 px-3 py-2 text-sm text-ink-700 hover:border-merah-400">
                Cari
            </button>
        </form>
    </div>

    <div class="overflow-x-auto rounded-sm border border-paper-300 bg-paper-50">
        <table class="w-full min-w-[700px] text-left text-sm">
            <thead>
                <tr class="border-b border-paper-300 bg-paper-200/60 font-mono text-[11px] uppercase tracking-wide text-ink-600/70">
                    <th class="px-4 py-3 font-medium">Kode Barang</th>
                    <th class="px-4 py-3 font-medium">Barang</th>
                    <th class="px-4 py-3 font-medium">Satuan</th>
                    <th class="px-4 py-3 text-right font-medium">Stok Minimum</th>
                    <th class="px-4 py-3 text-right font-medium">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($items as $item)
                    <tr class="border-b border-paper-200 last:border-0 hover:bg-paper-100/70">
                        <td class="px-4 py-3 font-mono text-xs text-ink-700">{{ $item->kode_barang }}</td>
                        <td class="px-4 py-3 text-ink-800">{{ $item->nama_barang }}</td>
                        <td class="px-4 py-3 text-ink-700">{{ $item->kode_satuan ?? '-' }}</td>
                        <td class="px-4 py-3">
                            <form method="POST" action="{{ route('master.barang.stok-minimum.update', $item->id_barang) }}" class="flex justify-end gap-2">
                                @csrf
                                @method('PATCH')
                                <input
                                    type="number"
                                    name="stok_minimum"
                                    min="0"
                                    step="0.0001"
                                    value="{{ rtrim(rtrim((string) $item->stok_minimum, '0'), '.') }}"
                                    class="w-36 rounded-sm border border-paper-300 bg-paper-50 px-3 py-2 text-right text-sm focus:border-merah-400 focus:outline-none"
                                    required
                                >
                                @if ($pelacakanBatchTersedia)
                                    @if ($item->lacak_batch)
                                        <input type="hidden" name="lacak_batch" value="1">
                                        <span class="flex items-center gap-1 whitespace-nowrap rounded-sm border border-paper-300 px-2 text-xs text-ink-700">
                                            <input type="checkbox" checked disabled class="rounded border-paper-300 text-merah-500">
                                            Lacak batch
                                        </span>
                                    @else
                                        <input type="hidden" name="lacak_batch" value="0">
                                        <label class="flex items-center gap-1 whitespace-nowrap rounded-sm border border-paper-300 px-2 text-xs text-ink-700" title="Aktifkan pelacakan batch dan kedaluwarsa untuk barang ini">
                                            <input type="checkbox" name="lacak_batch" value="1" class="rounded border-paper-300 text-merah-500 focus:ring-merah-400">
                                            Lacak batch
                                        </label>
                                    @endif
                                @endif
                                <button class="rounded-sm bg-merah-500 px-3 py-2 text-xs font-medium text-paper-50 hover:bg-merah-600">
                                    Simpan
                                </button>
                            </form>
                        </td>
                        <td class="px-4 py-3 text-right">
                            @if (bccomp((string) $item->stok_minimum, '0', 4) > 0)
                                <span class="rounded-sm bg-paper-200 px-2 py-1 text-xs text-ink-600">Peringatan aktif</span>
                            @else
                                <span class="rounded-sm bg-paper-200 px-2 py-1 text-xs text-ink-600">Belum diatur</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-10 text-center text-sm text-ink-600/60">
                            Belum ada barang yang dikonfigurasi untuk koperasi ini.
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

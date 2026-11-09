<x-layouts.app :title="$title" eyebrow="Gudang">
    <div class="mb-4 flex items-center justify-between gap-4">
        <a href="{{ route('gudang.index') }}" class="text-sm font-medium text-ink-700 hover:text-merah-600">
            &larr; Kembali ke Gudang
        </a>
        <a href="{{ route('gudang.opname.index') }}" class="text-sm font-medium text-ink-700 hover:text-merah-600">
            Kembali ke Opname
        </a>
    </div>

    <div class="mb-5 grid gap-4 md:grid-cols-2">
        <div class="rounded-sm border border-paper-300 bg-paper-50 p-5">
            <p class="font-mono text-[11px] uppercase tracking-wide text-ink-600/60">Kode Opname</p>
            <p class="mt-2 font-display text-xl font-semibold text-ink-900">{{ $item->kode_opname }}</p>
            <p class="mt-1 text-sm text-ink-600">{{ $item->tanggal?->format('d M Y') }}</p>
        </div>

        <div class="rounded-sm border border-paper-300 bg-paper-50 p-5">
            <dl class="grid grid-cols-2 gap-3 text-sm">
                <dt class="text-ink-600">Gudang</dt>
                <dd class="text-right font-medium text-ink-800">{{ $item->gudang->nama_gudang ?? '-' }}</dd>
                <dt class="text-ink-600">Status</dt>
                <dd class="text-right font-medium text-padi-600">{{ $item->status }}</dd>
            </dl>
        </div>
    </div>

    <div class="overflow-hidden rounded-sm border border-paper-300 bg-paper-50">
        <div class="border-b border-paper-300 px-4 py-3">
            <h2 class="font-display text-base font-semibold text-ink-900">Hasil Perhitungan Stok</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-paper-300 bg-paper-200/60 font-mono text-[11px] uppercase tracking-wide text-ink-600/70">
                        <th class="px-4 py-3 font-medium">No</th>
                        <th class="px-4 py-3 font-medium">Barang</th>
                        <th class="px-4 py-3 text-right font-medium">Qty Sistem</th>
                        <th class="px-4 py-3 text-right font-medium">Qty Fisik</th>
                        <th class="px-4 py-3 text-right font-medium">Selisih</th>
                        <th class="px-4 py-3 text-right font-medium">Nilai Selisih</th>
                        <th class="px-4 py-3 font-medium">Keterangan</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($item->detail as $detail)
                        <tr class="border-b border-paper-200 last:border-0">
                            <td class="px-4 py-3 text-ink-600">{{ $loop->iteration }}</td>
                            <td class="px-4 py-3 text-ink-800">{{ $detail->barang->nama_barang ?? '-' }}</td>
                            <td class="px-4 py-3 text-right text-ink-800">
                                {{ rtrim(rtrim(number_format((float) $detail->qty_sistem, 4, ',', '.'), '0'), ',') }}
                                {{ $detail->barang->satuanDasar->kode_satuan ?? '' }}
                            </td>
                            <td class="px-4 py-3 text-right text-ink-800">
                                {{ rtrim(rtrim(number_format((float) $detail->qty_fisik, 4, ',', '.'), '0'), ',') }}
                                {{ $detail->barang->satuanDasar->kode_satuan ?? '' }}
                            </td>
                            <td class="px-4 py-3 text-right font-medium {{ (float) $detail->selisih < 0 ? 'text-merah-600' : ((float) $detail->selisih > 0 ? 'text-sawah-600' : 'text-ink-800') }}">
                                {{ rtrim(rtrim(number_format((float) $detail->selisih, 4, ',', '.'), '0'), ',') }}
                                {{ $detail->barang->satuanDasar->kode_satuan ?? '' }}
                            </td>
                            <td class="px-4 py-3 text-right text-ink-800">
                                Rp {{ number_format((float) $detail->nilai_selisih, 2, ',', '.') }}
                            </td>
                            <td class="px-4 py-3 text-ink-700">{{ $detail->keterangan ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-10 text-center text-sm text-ink-600/60">Belum ada detail opname.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-layouts.app>
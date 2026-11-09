<x-layouts.app :title="$title" eyebrow="Gudang">
    <div class="mb-4 flex items-center justify-between gap-4">
        <a href="{{ route('gudang.index') }}" class="text-sm font-medium text-ink-700 hover:text-merah-600">
            &larr; Kembali ke Gudang
        </a>
        <a href="{{ route('gudang.penerimaan.index') }}" class="text-sm font-medium text-ink-700 hover:text-merah-600">
            Kembali ke Penerimaan
        </a>
    </div>

    <div class="mb-5 grid gap-4 md:grid-cols-2">
        <div class="rounded-sm border border-paper-300 bg-paper-50 p-5">
            <p class="font-mono text-[11px] uppercase tracking-wide text-ink-600/60">Kode Penerimaan</p>
            <p class="mt-2 font-display text-xl font-semibold text-ink-900">{{ $item->kode_penerimaan }}</p>
            <p class="mt-1 text-sm text-ink-600">{{ $item->tanggal_terima?->format('d M Y') }}</p>
        </div>

        <div class="rounded-sm border border-paper-300 bg-paper-50 p-5">
            <dl class="grid grid-cols-2 gap-3 text-sm">
                <dt class="text-ink-600">Gudang</dt>
                <dd class="text-right font-medium text-ink-800">{{ $item->gudang->nama_gudang ?? '-' }}</dd>
                <dt class="text-ink-600">Pihak</dt>
                <dd class="text-right font-medium text-ink-800">{{ $item->pihak->nama ?? '-' }}</dd>
                <dt class="text-ink-600">Status</dt>
                <dd class="text-right font-medium text-sawah-600">{{ $item->status }}</dd>
            </dl>
        </div>
    </div>

    <div class="overflow-hidden rounded-sm border border-paper-300 bg-paper-50">
        <div class="border-b border-paper-300 px-4 py-3">
            <h2 class="font-display text-base font-semibold text-ink-900">Barang yang Diterima</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-paper-300 bg-paper-200/60 font-mono text-[11px] uppercase tracking-wide text-ink-600/70">
                        <th class="px-4 py-3 font-medium">No</th>
                        <th class="px-4 py-3 font-medium">Barang</th>
                        <th class="px-4 py-3 text-right font-medium">Qty Diterima</th>
                        <th class="px-4 py-3 text-right font-medium">Qty Layak (Dasar)</th>
                        <th class="px-4 py-3 text-right font-medium">Qty Tidak Layak (Dasar)</th>
                        <th class="px-4 py-3 font-medium">Alasan</th>
                        <th class="px-4 py-3 text-right font-medium">Harga / Satuan Dasar</th>
                        <th class="px-4 py-3 text-right font-medium">Nilai Layak Masuk Stok</th>
                        <th class="px-4 py-3 text-right font-medium">Nilai Tidak Layak</th>
                        <th class="px-4 py-3 text-right font-medium">Nilai Penerimaan Bruto</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($item->detail as $detail)
                        <tr class="border-b border-paper-200 last:border-0">
                            <td class="px-4 py-3 text-ink-600">{{ $loop->iteration }}</td>
                            <td class="px-4 py-3 text-ink-800">
                                {{ $detail->barang->nama_barang ?? '-' }}
                                @if ($detail->relationLoaded('batchDetails') && $detail->batchDetails->isNotEmpty())
                                    <ul class="mt-2 space-y-1 text-xs text-ink-600">
                                        @foreach ($detail->batchDetails as $batchDetail)
                                            <li>
                                                Batch {{ $batchDetail->batch->kode_batch ?? '-' }}:
                                                {{ rtrim(rtrim(number_format((float) $batchDetail->qty_layak, 4, ',', '.'), '0'), ',') }}
                                                layak
                                                @if ($batchDetail->batch?->tanggal_kedaluwarsa)
                                                    · Kedaluwarsa {{ $batchDetail->batch->tanggal_kedaluwarsa->format('d M Y') }}
                                                @endif
                                            </li>
                                        @endforeach
                                    </ul>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right text-ink-800">
                                {{ rtrim(rtrim(number_format((float) $detail->qty_input, 4, ',', '.'), '0'), ',') }}
                                {{ $detail->satuanInput?->kode_satuan ?? '-' }}
                                <span class="block text-[11px] text-ink-600">
                                    1 {{ $detail->satuanInput?->kode_satuan ?? '-' }} =
                                    {{ rtrim(rtrim(number_format((float) $detail->faktor_konversi, 6, ',', '.'), '0'), ',') }}
                                    {{ $detail->barang->satuanDasar->kode_satuan ?? '' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right text-ink-800">
                                {{ rtrim(rtrim(number_format((float) $detail->qty_layak, 4, ',', '.'), '0'), ',') }}
                                {{ $detail->barang->satuanDasar->kode_satuan ?? '' }}
                            </td>
                            <td class="px-4 py-3 text-right text-ink-800">
                                {{ rtrim(rtrim(number_format((float) $detail->qty_tidak_layak, 4, ',', '.'), '0'), ',') }}
                                {{ $detail->barang->satuanDasar->kode_satuan ?? '' }}
                            </td>
                            <td class="px-4 py-3 text-ink-700">{{ $detail->alasan_tidak_layak ?: '-' }}</td>
                            <td class="px-4 py-3 text-right text-ink-800">
                                Rp {{ rtrim(rtrim(number_format((float) $detail->harga_satuan_dasar, 4, ',', '.'), '0'), ',') }}
                            </td>
                            <td class="px-4 py-3 text-right font-medium text-sawah-600">
                                Rp {{ number_format((float) $detail->qty_layak * (float) $detail->harga_satuan_dasar, 2, ',', '.') }}
                            </td>
                            <td class="px-4 py-3 text-right font-medium text-merah-600">
                                Rp {{ number_format((float) $detail->qty_tidak_layak * (float) $detail->harga_satuan_dasar, 2, ',', '.') }}
                            </td>
                            <td class="px-4 py-3 text-right font-medium text-ink-800">Rp {{ number_format((float) $detail->subtotal, 2, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="px-4 py-10 text-center text-sm text-ink-600/60">Belum ada detail barang.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if ($item->catatan)
        <div class="mt-4 rounded-sm border border-paper-300 bg-paper-50 p-4 text-sm text-ink-700">
            <span class="font-medium">Catatan:</span> {{ $item->catatan }}
        </div>
    @endif
</x-layouts.app>
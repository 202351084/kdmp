<x-layouts.app :title="$title" eyebrow="Gudang">
    <div class="mb-4 flex items-center justify-between gap-4">
        <a href="{{ route('gudang.index') }}" class="text-sm font-medium text-ink-700 hover:text-merah-600">
            &larr; Kembali ke Gudang
        </a>
        <a href="{{ route('gudang.kerusakan.index') }}" class="text-sm font-medium text-ink-700 hover:text-merah-600">
            Kembali ke Kerusakan
        </a>
    </div>

    <div class="mb-5 grid gap-4 md:grid-cols-2">
        <div class="rounded-sm border border-paper-300 bg-paper-50 p-5">
            <p class="font-mono text-[11px] uppercase tracking-wide text-ink-600/60">Kerusakan Barang</p>
            <p class="mt-2 font-display text-xl font-semibold capitalize text-ink-900">{{ $item->jenis_kejadian }}</p>
            <p class="mt-1 text-sm text-ink-600">{{ $item->tanggal?->format('d M Y') }}</p>
        </div>

        <div class="rounded-sm border border-paper-300 bg-paper-50 p-5">
            <dl class="grid grid-cols-2 gap-3 text-sm">
                <dt class="text-ink-600">Gudang</dt>
                <dd class="text-right font-medium text-ink-800">{{ $item->gudang->nama_gudang ?? '-' }}</dd>
                <dt class="text-ink-600">Barang</dt>
                <dd class="text-right font-medium text-ink-800">{{ $item->barang->nama_barang ?? '-' }}</dd>
                @if ($item->relationLoaded('batch') && $item->batch)
                    <dt class="text-ink-600">Batch</dt>
                    <dd class="text-right font-medium text-ink-800">
                        {{ $item->batch->kode_batch }}
                        @if ($item->batch->tanggal_kedaluwarsa)
                            · Kedaluwarsa {{ $item->batch->tanggal_kedaluwarsa->format('d M Y') }}
                        @endif
                    </dd>
                @endif
                <dt class="text-ink-600">Status</dt>
                <dd class="text-right font-medium text-sawah-600">{{ $item->status }}</dd>
            </dl>
        </div>
    </div>

    <div class="overflow-hidden rounded-sm border border-paper-300 bg-paper-50">
        <div class="border-b border-paper-300 px-4 py-3">
            <h2 class="font-display text-base font-semibold text-ink-900">Rincian Kerusakan</h2>
        </div>
        <dl class="grid gap-4 p-5 text-sm md:grid-cols-3">
            <div>
                <dt class="text-ink-600">Qty Rusak / Hilang / Susut / Kedaluwarsa</dt>
                <dd class="mt-1 font-medium text-ink-800">
                    {{ rtrim(rtrim(number_format((float) $item->qty, 4, ',', '.'), '0'), ',') }}
                    {{ $item->barang->satuanDasar->kode_satuan ?? '' }}
                </dd>
            </div>
            <div>
                <dt class="text-ink-600">HPP Rata-rata</dt>
                <dd class="mt-1 font-medium text-ink-800">Rp {{ number_format((float) $item->hpp_rata2, 2, ',', '.') }}</dd>
            </div>
            <div>
                <dt class="text-ink-600">Nilai Kerugian</dt>
                <dd class="mt-1 font-medium text-merah-600">Rp {{ number_format((float) $item->nilai_kerugian, 2, ',', '.') }}</dd>
            </div>
        </dl>
        <div class="border-t border-paper-300 px-5 py-4 text-sm text-ink-700">
            <span class="font-medium">Keterangan:</span> {{ $item->keterangan ?? '-' }}
        </div>
    </div>
</x-layouts.app>
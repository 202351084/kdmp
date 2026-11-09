<x-layouts.app :title="$title" eyebrow="Perencanaan Pengadaan">
    <div class="mx-auto max-w-5xl space-y-6">
        <div class="flex items-start justify-between gap-4"><div><p class="font-mono text-[11px] uppercase tracking-wide text-ink-600/60">Detail PR</p><h1 class="mt-1 font-display text-2xl font-semibold text-ink-900">{{ $item->kode_permintaan }}</h1></div><span class="rounded-full px-3 py-1 text-xs {{ $item->status_badge_class }}">{{ $item->status_label }}</span></div>
        <div class="grid gap-4 rounded-sm border border-paper-300 bg-paper-50 p-5 md:grid-cols-3">
            <div><p class="text-xs text-ink-600/60">Tanggal pengajuan</p><p class="mt-1 font-medium">{{ $item->tgl_pengajuan?->format('d M Y') }}</p></div>
            <div><p class="text-xs text-ink-600/60">Unit usaha</p><p class="mt-1 font-medium">{{ $item->unitUsaha?->nama_unit_usaha ?? '—' }}</p></div>
            <div><p class="text-xs text-ink-600/60">Gudang tujuan</p><p class="mt-1 font-medium">{{ $item->gudang?->nama_gudang ?? '—' }}</p></div>
        </div>
        <div class="overflow-hidden rounded-sm border border-paper-300 bg-paper-50">
            <table class="w-full text-left text-sm"><thead class="border-b border-paper-300 bg-paper-200/60"><tr><th class="px-4 py-3">Barang</th><th class="px-4 py-3">Jumlah</th><th class="px-4 py-3">Harga perkiraan</th><th class="px-4 py-3">Subtotal</th></tr></thead><tbody>
                @foreach ($item->detail as $detail)
                    <tr class="border-b border-paper-200 last:border-0"><td class="px-4 py-3">{{ $detail->barang?->nama_barang ?? '—' }}</td><td class="px-4 py-3">{{ number_format((float) $detail->jumlah_diminta, 2, ',', '.') }}</td><td class="px-4 py-3">Rp {{ number_format((float) $detail->harga_perkiraan, 2, ',', '.') }}</td><td class="px-4 py-3">Rp {{ number_format((float) $detail->subtotal, 2, ',', '.') }}</td></tr>
                @endforeach
            </tbody></table>
        </div>
        <a href="{{ route('perencanaan.permintaan-pengadaan.index') }}" class="inline-block rounded-sm border border-paper-300 px-4 py-2 text-sm text-ink-700">Kembali</a>
    </div>
</x-layouts.app>

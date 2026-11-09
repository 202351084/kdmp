<x-layouts.app :title="$title" eyebrow="Konsinyasi">
    <div class="mb-4">
        <a href="{{ route('konsinyasi.stok.index') }}" class="text-sm font-medium text-ink-700 hover:text-merah-600">
            &larr; Kembali ke Stok Konsinyasi
        </a>
    </div>

    <div class="mb-5 flex flex-wrap items-end justify-between gap-3">
        <div>
            <p class="text-sm text-ink-600">{{ $item->kiriman?->kode_kiriman ?? 'Pengiriman tidak ditemukan' }}</p>
            <h1 class="mt-1 text-2xl font-semibold text-ink-900">{{ $item->barang?->nama_barang ?? 'Barang tidak ditemukan' }}</h1>
        </div>
        <span class="rounded-full px-3 py-1 text-sm font-medium {{ $item->status === 'aktif' ? 'bg-sawah-100 text-sawah-600' : 'bg-paper-200 text-ink-600' }}">
            {{ ['aktif' => 'Masih ada', 'habis' => 'Habis', 'dikembalikan' => 'Dikembalikan'][$item->status] ?? $item->status }}
        </span>
    </div>

    <div class="mb-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <div class="border-l-4 border-merah-500 bg-paper-50 px-4 py-3">
            <p class="text-xs font-medium uppercase text-ink-600">Desa Pemilik</p>
            <p class="mt-1 font-semibold text-ink-900">{{ $item->kiriman?->koperasiPemilik?->nama_entitas ?? '-' }}</p>
        </div>
        <div class="border-l-4 border-merah-500 bg-paper-50 px-4 py-3">
            <p class="text-xs font-medium uppercase text-ink-600">Gudang Penerima</p>
            <p class="mt-1 font-semibold text-ink-900">{{ $item->kiriman?->gudangTujuan?->nama_gudang ?? '-' }}</p>
        </div>
        <div class="border-l-4 border-merah-500 bg-paper-50 px-4 py-3">
            <p class="text-xs font-medium uppercase text-ink-600">Harga Titip</p>
            <p class="mt-1 font-semibold text-ink-900">Rp {{ number_format((float) $item->harga_titip_satuan, 2, ',', '.') }}</p>
        </div>
        <div class="border-l-4 border-merah-500 bg-paper-50 px-4 py-3">
            <p class="text-xs font-medium uppercase text-ink-600">Harga Jual</p>
            <p class="mt-1 font-semibold text-ink-900">Rp {{ number_format((float) $item->harga_jual_satuan, 2, ',', '.') }}</p>
        </div>
    </div>

    <section class="mb-5 overflow-hidden rounded-sm border border-paper-300 bg-paper-50">
        <div class="border-b border-paper-300 px-4 py-3">
            <h2 class="font-semibold text-ink-900">Ringkasan jumlah</h2>
            <p class="mt-1 text-xs text-ink-600">Jumlah titipan = terjual + retur + susut + sisa.</p>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-[650px] w-full text-sm">
                <thead class="bg-paper-200/60 text-left text-ink-600">
                    <tr>
                        <th class="px-4 py-3">Dititipkan</th>
                        <th class="px-4 py-3">Terjual</th>
                        <th class="px-4 py-3">Diretur</th>
                        <th class="px-4 py-3">Susut</th>
                        <th class="px-4 py-3">Sisa</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="px-4 py-3">{{ number_format((float) $item->qty_titip, 4, ',', '.') }}</td>
                        <td class="px-4 py-3">{{ number_format((float) $item->qty_terjual, 4, ',', '.') }}</td>
                        <td class="px-4 py-3">{{ number_format((float) $item->qty_retur, 4, ',', '.') }}</td>
                        <td class="px-4 py-3">{{ number_format((float) $item->qty_susut, 4, ',', '.') }}</td>
                        <td class="px-4 py-3 font-semibold">{{ number_format((float) $item->qty_sisa, 4, ',', '.') }} {{ $item->barang?->satuanDasar?->kode_satuan }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>

    <section class="overflow-hidden rounded-sm border border-paper-300 bg-paper-50">
        <div class="border-b border-paper-300 px-4 py-3">
            <h2 class="font-semibold text-ink-900">Riwayat perubahan stok</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-[800px] w-full text-left text-sm">
                <thead class="bg-paper-200/60 text-ink-600">
                    <tr>
                        <th class="px-4 py-3">Tanggal</th>
                        <th class="px-4 py-3">Jenis</th>
                        <th class="px-4 py-3">Referensi</th>
                        <th class="px-4 py-3 text-right">Jumlah</th>
                        <th class="px-4 py-3 text-right">Sisa setelah mutasi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($mutasi as $baris)
                        <tr class="border-t border-paper-200">
                            <td class="px-4 py-3">{{ $baris->tanggal?->format('d M Y') ?? '-' }}</td>
                            <td class="px-4 py-3">{{ ['TITIP' => 'Titipan masuk', 'JUAL' => 'Terjual', 'RETUR' => 'Dikembalikan', 'SUSUT' => 'Susut'][$baris->jenis_mutasi] ?? $baris->jenis_mutasi }}</td>
                            <td class="px-4 py-3">{{ $baris->ref_tipe }}{{ $baris->ref_id ? ' #'.$baris->ref_id : '' }}</td>
                            <td class="px-4 py-3 text-right">{{ number_format((float) $baris->qty, 4, ',', '.') }}</td>
                            <td class="px-4 py-3 text-right font-medium">{{ number_format((float) $baris->saldo_qty, 4, ',', '.') }} {{ $item->barang?->satuanDasar?->kode_satuan }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-10 text-center text-sm text-ink-600">Belum ada riwayat mutasi stok.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</x-layouts.app>

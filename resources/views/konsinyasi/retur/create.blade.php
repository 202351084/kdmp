<x-layouts.app :title="$title" eyebrow="Konsinyasi">
    <div class="mb-4">
        <a href="{{ route('konsinyasi.retur.index') }}" class="text-sm font-medium text-ink-700 hover:text-merah-600">
            &larr; Kembali ke Retur Konsinyasi
        </a>
    </div>

    <div class="mx-auto max-w-3xl rounded-sm border border-paper-300 bg-paper-50 p-6">
        <div class="mb-5">
            <h1 class="text-xl font-semibold text-ink-900">Catat Barang yang Diterima Kembali</h1>
            <p class="mt-1 text-sm text-ink-600">Simpan retur setelah barang benar-benar diterima pemilik. Stok titipan dan riwayat mutasi akan diperbarui saat disimpan.</p>
        </div>

        @if ($items->contains(fn ($stock) => $stock->kiriman?->jenis_pemilik === 'pihak'))
            <p class="mb-5 rounded-sm border border-padi-500/40 bg-padi-50 px-3 py-2 text-sm text-padi-700">
                Untuk titipan perorangan, catat setelah barang benar-benar diserahkan kembali kepada penitip.
            </p>
        @endif

        <form method="POST" action="{{ route('konsinyasi.retur.store') }}" class="space-y-5">
            @csrf
            <div>
                <label for="id_stok_konsinyasi" class="mb-1 block text-sm font-medium text-ink-700">Barang titipan</label>
                <select id="id_stok_konsinyasi" name="id_stok_konsinyasi" required class="w-full rounded-sm border border-paper-300 bg-paper-50 px-3 py-2 text-sm focus:border-merah-400 focus:outline-none">
                    <option value="">Pilih kiriman dan barang</option>
                    @foreach ($items as $stock)
                        @php
                            $shipment = $stock->kiriman;
                            $owner = $shipment?->jenis_pemilik === 'pihak'
                                ? ($shipment->pihakPemilik?->nama ?? 'Penitip')
                                : ($shipment?->koperasiPemilik?->nama_entitas ?? 'Desa pemilik');
                            $unit = $stock->barang?->satuanDasar?->kode_satuan ?? '';
                            $label = sprintf(
                                '%s — %s — Pemilik: %s — Sisa: %s %s',
                                $shipment?->kode_kiriman ?? 'Kiriman',
                                $stock->barang?->nama_barang ?? 'Barang',
                                $owner,
                                number_format((float) $stock->qty_sisa, 4, ',', '.'),
                                $unit,
                            );
                        @endphp
                        <option value="{{ $stock->id_stok_konsinyasi }}" @selected(old('id_stok_konsinyasi', $selectedId) === $stock->id_stok_konsinyasi)>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
                @error('id_stok_konsinyasi')<p class="mt-1 text-sm text-merah-600">{{ $message }}</p>@enderror
                @if ($items->isEmpty())
                    <p class="mt-2 text-sm text-ink-600">Tidak ada stok titipan aktif yang dapat diretur oleh entitas ini.</p>
                @endif
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="tanggal" class="mb-1 block text-sm font-medium text-ink-700">Tanggal diterima kembali</label>
                    <input id="tanggal" type="date" name="tanggal" value="{{ old('tanggal', now()->format('Y-m-d')) }}" required class="w-full rounded-sm border border-paper-300 bg-paper-50 px-3 py-2 text-sm focus:border-merah-400 focus:outline-none">
                    @error('tanggal')<p class="mt-1 text-sm text-merah-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="qty_dikembalikan" class="mb-1 block text-sm font-medium text-ink-700">Jumlah yang diterima</label>
                    <input id="qty_dikembalikan" type="number" name="qty_dikembalikan" value="{{ old('qty_dikembalikan') }}" min="0.0001" step="0.0001" required class="w-full rounded-sm border border-paper-300 bg-paper-50 px-3 py-2 text-sm focus:border-merah-400 focus:outline-none">
                    @error('qty_dikembalikan')<p class="mt-1 text-sm text-merah-600">{{ $message }}</p>@enderror
                </div>
            </div>

            <div>
                <label for="catatan" class="mb-1 block text-sm font-medium text-ink-700">Catatan (opsional)</label>
                <textarea id="catatan" name="catatan" rows="3" class="w-full rounded-sm border border-paper-300 bg-paper-50 px-3 py-2 text-sm focus:border-merah-400 focus:outline-none">{{ old('catatan') }}</textarea>
                @error('catatan')<p class="mt-1 text-sm text-merah-600">{{ $message }}</p>@enderror
            </div>

            @if ($errors->has('retur'))
                <div class="rounded-sm border border-merah-500/40 bg-merah-50 px-3 py-2 text-sm text-merah-700">{{ $errors->first('retur') }}</div>
            @endif

            <div class="flex justify-end gap-3">
                <a href="{{ route('konsinyasi.retur.index') }}" class="rounded-sm border border-paper-300 px-4 py-2 text-sm text-ink-700 hover:border-merah-400">Batal</a>
                <button type="submit" @disabled($items->isEmpty()) class="rounded-sm bg-merah-500 px-4 py-2 text-sm font-medium text-paper-50 hover:bg-merah-600 disabled:cursor-not-allowed disabled:opacity-50">Simpan Retur</button>
            </div>
        </form>
    </div>
</x-layouts.app>

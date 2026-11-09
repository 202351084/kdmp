<x-layouts.app :title="$title" eyebrow="Perencanaan Pengadaan">
    <div class="mx-auto max-w-4xl space-y-6">
        <div class="rounded-sm border border-merah-200 bg-merah-50 p-4">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <p class="font-mono text-[11px] uppercase tracking-wide text-merah-700">Draft PR Dari Hasil Kalkulasi</p>
                    <h2 class="mt-1 font-display text-2xl font-semibold text-ink-900">PR - Pengadaan Defisit</h2>
                </div>
                @if ($hasil)
                    <span class="rounded-full bg-merah-100 px-3 py-1 text-xs font-medium uppercase text-merah-700">
                        {{ $hasil->status_surplus_defisit }}
                    </span>
                @endif
            </div>
        </div>

        @if ($hasil)
            <div class="grid gap-4 rounded-sm border border-paper-300 bg-paper-50 p-4 md:grid-cols-3">
                <div>
                    <p class="font-mono text-[10px] uppercase tracking-wide text-ink-600/60">Komoditas</p>
                    <p class="mt-1 text-base font-semibold text-ink-900">{{ $hasil->komoditas?->nama ?? '—' }}</p>
                </div>
                <div>
                    <p class="font-mono text-[10px] uppercase tracking-wide text-ink-600/60">Periode</p>
                    <p class="mt-1 text-base font-semibold text-ink-900">{{ $periodeLabel }}</p>
                </div>
                <div>
                    <p class="font-mono text-[10px] uppercase tracking-wide text-ink-600/60">Kebutuhan yang kurang</p>
                    <p class="mt-1 text-base font-semibold text-merah-600">{{ number_format(abs((float) $hasil->selisih), 2, ',', '.') }}</p>
                </div>
            </div>
        @endif

        @if ($hasil)
            <div class="rounded-sm border border-paper-300 bg-paper-50 p-4">
                <h3 class="font-display text-lg font-semibold text-ink-900">Calon Sumber Desa</h3>
                <p class="mt-1 text-sm text-ink-600">Potensi produksi adalah indikasi ketersediaan, bukan stok aktual dan tidak menetapkan supplier.</p>
                @if ($calonSupplier->isEmpty())
                    <p class="mt-3 text-sm text-amber-700">Belum ada data potensi produksi untuk periode ini.</p>
                @else
                    <div class="mt-3 overflow-hidden rounded-sm border border-paper-200">
                        <table class="w-full text-left text-sm">
                            <thead class="border-b border-paper-200 bg-paper-100"><tr><th class="px-3 py-2">Desa sumber</th><th class="px-3 py-2">Potensi produksi</th><th class="px-3 py-2">Harga survei</th><th class="px-3 py-2">Satuan</th></tr></thead>
                            <tbody>
                                @foreach ($calonSupplier as $candidate)
                                    <tr class="border-b border-paper-100 last:border-0"><td class="px-3 py-2">{{ $candidate->wilayah?->nama ?? '—' }}</td><td class="px-3 py-2">{{ number_format((float) $candidate->jumlah_produksi, 2, ',', '.') }}</td><td class="px-3 py-2">@if ($perbandinganHarga->has($candidate->id_wilayah)) Rp {{ number_format((float) $perbandinganHarga->get($candidate->id_wilayah)->harga_efektif, 2, ',', '.') }} @else — @endif</td><td class="px-3 py-2">{{ $candidate->satuan }}</td></tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        @endif

        <form method="POST" action="{{ route('perencanaan.permintaan-pengadaan.store') }}" class="rounded-sm border border-paper-300 bg-paper-50 p-6">
            @csrf
            <input type="hidden" name="id_hasil" value="{{ $hasil?->id_hasil }}">
            <div class="grid gap-6 md:grid-cols-2">
                <div>
                    <label class="block text-sm font-medium text-ink-700">Supplier / Pihak</label>
                    <p class="mt-1 text-sm text-ink-600">Supplier dipilih oleh Pembelian berdasarkan PR dan hasil perbandingan harga.</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-ink-700">Tanggal Pengajuan</label>
                    <input name="tgl_pengajuan" type="date" value="{{ old('tgl_pengajuan', $tanggalPengajuan) }}" class="mt-1 block w-full rounded-sm border border-paper-300 bg-paper-50 px-3 py-2 text-sm text-ink-800 focus:border-merah-400 focus:outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-ink-700">Barang / Item</label>
                    <input type="hidden" name="id_barang" value="{{ $barang?->id_barang }}">
                    <input type="text" value="{{ $barang?->nama_barang ?? ($hasil?->komoditas?->nama ?? '—') }}" class="mt-1 block w-full rounded-sm border border-paper-300 bg-paper-50 px-3 py-2 text-sm text-ink-800 focus:border-merah-400 focus:outline-none" readonly>
                </div>
                <div>
                    <label class="block text-sm font-medium text-ink-700">Jumlah Diminta</label>
                    <input type="text" value="{{ number_format($jumlahDiminta, 2, ',', '.') }}" class="mt-1 block w-full rounded-sm border border-paper-300 bg-paper-50 px-3 py-2 text-sm text-ink-800 focus:border-merah-400 focus:outline-none" readonly>
                </div>
                <div>
                    <label class="block text-sm font-medium text-ink-700">Harga Perkiraan</label>
                    <input type="text" value="{{ $hargaPerkiraan !== null ? 'Rp '.number_format($hargaPerkiraan, 2, ',', '.').' / '.($satuanHarga ?: 'satuan') : 'Belum tersedia untuk periode dan satuan ini' }}" class="mt-1 block w-full rounded-sm border border-paper-300 bg-paper-50 px-3 py-2 text-sm text-ink-800 focus:border-merah-400 focus:outline-none" readonly>
                </div>
                <div>
                    <label class="block text-sm font-medium text-ink-700">Subtotal</label>
                    <input type="text" value="{{ $hargaPerkiraan !== null ? 'Rp '.number_format($jumlahDiminta * $hargaPerkiraan, 2, ',', '.') : 'Belum dapat dihitung tanpa harga survei yang valid' }}" class="mt-1 block w-full rounded-sm border border-paper-300 bg-paper-50 px-3 py-2 text-sm text-ink-800 focus:border-merah-400 focus:outline-none" readonly>
                </div>
                <div>
                    <label for="id_gudang" class="block text-sm font-medium text-ink-700">Gudang Tujuan</label>
                    <select id="id_gudang" name="id_gudang" required class="mt-1 block w-full rounded-sm border border-paper-300 bg-paper-50 px-3 py-2 text-sm text-ink-800 focus:border-merah-400 focus:outline-none">
                        <option value="">Pilih gudang tujuan</option>
                        @foreach ($gudangs as $gudang)
                            <option value="{{ $gudang->id_gudang }}" @selected(old('id_gudang') == $gudang->id_gudang)>{{ $gudang->nama_gudang }}</option>
                        @endforeach
                    </select>
                    @if ($gudangs->isEmpty())
                        <p class="mt-1 text-xs text-merah-600">Belum ada gudang aktif untuk entitas ini.</p>
                    @endif
                </div>
            </div>

            <div class="mt-6 flex items-center justify-between gap-4 border-t border-paper-300 pt-4">
                <a href="{{ route('perencanaan.neraca-komoditas.index', ['tahun' => $hasil?->tahun ?? now()->year, 'bulan' => $hasil?->bulan]) }}" class="rounded-sm border border-paper-300 bg-paper-50 px-4 py-2 text-sm font-medium text-ink-700 hover:border-merah-400">
                    ← Kembali ke Neraca
                </a>
                <button type="submit" class="rounded-sm bg-merah-500 px-4 py-2 text-sm font-medium text-paper-50 hover:bg-merah-600" @disabled(!$hasil || !$barang || $gudangs->isEmpty())>
                    Simpan Draft PR
                </button>
                @if ($errors->any())
                    <div class="mt-4 rounded-sm border border-merah-200 bg-merah-50 p-3 text-sm text-merah-700">{{ $errors->first() }}</div>
                @endif
            </form>
        </div>
    </div>
</x-layouts.app>

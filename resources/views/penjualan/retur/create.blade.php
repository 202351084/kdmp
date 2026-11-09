<x-layouts.app :title="$title" eyebrow="Retur Penjualan">
    @if (session('success'))
        <div class="mb-4 rounded-sm border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="mb-4 rounded-sm border border-merah-300 bg-merah-50 px-4 py-3 text-sm text-merah-800">{{ session('error') }}</div>
    @endif
    @if ($errors->any())
        <div class="mb-4 rounded-sm border border-merah-300 bg-merah-50 p-4 text-sm text-merah-800">
            <ul class="list-inside list-disc space-y-1">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-sm text-ink-600">Pengembalian barang dari pelanggan</p>
            <h1 class="mt-1 text-2xl font-semibold text-ink-900">Ajukan Retur Penjualan</h1>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('penjualan.retur.index') }}" class="rounded-sm border border-paper-300 px-3 py-2 text-sm font-medium text-ink-700 hover:bg-paper-100">Daftar Retur</a>
            <a href="{{ route('penjualan.penjualan.index') }}" class="rounded-sm border border-rose-200 px-3 py-2 text-sm font-semibold text-rose-800 hover:bg-rose-50">Kasir / POS</a>
        </div>
    </div>

    <form method="POST" action="{{ route("$routeBase.store") }}" class="space-y-6">
        @csrf

        {{-- Langkah 1: Pilih Transaksi Penjualan --}}
        <div class="rounded-sm border border-paper-300 bg-paper-50 p-4 sm:p-5">
            <h2 class="mb-3 font-semibold text-ink-900">1. Pilih Transaksi Penjualan</h2>
            <div class="max-w-xl">
                <label class="block text-sm">
                    <span class="mb-1 block font-medium text-ink-700">Nomor Nota / Transaksi *</span>
                    <select id="select-penjualan" name="id_penjualan" required onchange="handleSaleChange(this.value)" class="w-full rounded-sm border border-paper-300 bg-white px-3 py-2 text-sm focus:border-rose-500 focus:outline-none">
                        <option value="">-- Pilih Transaksi Penjualan --</option>
                        @foreach ($sales as $s)
                            <option value="{{ $s->id_penjualan }}" @selected($sale && $sale->id_penjualan == $s->id_penjualan)>
                                {{ $s->kode_penjualan }} — {{ $s->nama_pembeli ?: ($s->pihak?->nama ?? 'Pembeli Umum') }} ({{ $s->tanggal_transaksi?->format('d M Y') ?? '—' }}) — Rp {{ number_format((float) $s->total_bayar, 0, ',', '.') }}
                            </option>
                        @endforeach
                    </select>
                </label>
                @if ($sales->isEmpty())
                    <p class="mt-2 text-xs text-amber-700">Tidak ada transaksi penjualan berstatus 'selesai' yang memiliki barang tersisa untuk diretur.</p>
                @endif
            </div>

            @if ($sale)
                <div class="mt-4 grid gap-3 border-t border-paper-200 pt-3 text-sm sm:grid-cols-4">
                    <div>
                        <span class="text-xs text-ink-600 uppercase font-medium">Kode</span>
                        <p class="font-semibold text-ink-900">{{ $sale->kode_penjualan }}</p>
                    </div>
                    <div>
                        <span class="text-xs text-ink-600 uppercase font-medium">Tanggal</span>
                        <p class="font-semibold text-ink-900">{{ $sale->tanggal_transaksi?->format('d M Y') ?? '—' }}</p>
                    </div>
                    <div>
                        <span class="text-xs text-ink-600 uppercase font-medium">Pembeli</span>
                        <p class="font-semibold text-ink-900">{{ $sale->nama_pembeli ?: ($sale->pihak?->nama ?? 'Pembeli Umum') }}</p>
                    </div>
                    <div>
                        <span class="text-xs text-ink-600 uppercase font-medium">Total Belanja</span>
                        <p class="font-semibold text-rose-700">Rp {{ number_format((float) $sale->total_bayar, 0, ',', '.') }}</p>
                    </div>
                </div>
            @endif
        </div>

        @if ($sale)
            {{-- Langkah 2: Detail Penyelesaian & Alasan --}}
            <div class="rounded-sm border border-paper-300 bg-paper-50 p-4 sm:p-5">
                <h2 class="mb-3 font-semibold text-ink-900">2. Metode Penyelesaian & Alasan</h2>
                <div class="grid gap-4 sm:grid-cols-2">
                    <label class="block text-sm">
                        <span class="mb-1 block font-medium text-ink-700">Jenis Penyelesaian *</span>
                        <select name="jenis_penyelesaian" required class="w-full rounded-sm border border-paper-300 bg-white px-3 py-2 text-sm focus:border-rose-500 focus:outline-none">
                            <option value="uang" @selected(old('jenis_penyelesaian') === 'uang')>Diganti Uang (Pengembalian Tunai/Transfer)</option>
                            <option value="ganti_barang" @selected(old('jenis_penyelesaian') === 'ganti_barang')>Ganti Barang Sejenis</option>
                            <option value="potong_piutang" @selected(old('jenis_penyelesaian') === 'potong_piutang')>Potong Piutang</option>
                        </select>
                    </label>
                    <label class="block text-sm">
                        <span class="mb-1 block font-medium text-ink-700">Alasan Retur</span>
                        <input type="text" name="alasan" value="{{ old('alasan') }}" placeholder="Contoh: Barang cacat, kemasan rusak, salah beli" class="w-full rounded-sm border border-paper-300 bg-white px-3 py-2 text-sm focus:border-rose-500 focus:outline-none">
                    </label>
                </div>
            </div>

            {{-- Langkah 3: Pilih Barang & Qty Retur --}}
            <div class="overflow-hidden rounded-sm border border-paper-300 bg-paper-50">
                <div class="border-b border-paper-300 px-4 py-3">
                    <h2 class="font-semibold text-ink-900">3. Rincian Barang yang Diretur</h2>
                    <p class="text-xs text-ink-600">Masukkan jumlah barang yang akan dikembalikan (maksimum sebesar sisa barang yang belum diretur).</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-[650px] w-full text-sm">
                        <thead class="bg-rose-50 text-left text-rose-950">
                            <tr>
                                <th class="px-4 py-3 font-semibold">Barang</th>
                                <th class="px-4 py-3 text-right font-semibold">Qty Beli</th>
                                <th class="px-4 py-3 text-right font-semibold">Sisa Dapat Diretur</th>
                                <th class="px-4 py-3 text-right font-semibold">Harga Beli</th>
                                <th class="px-4 py-3 text-center font-semibold w-36">Qty Retur</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-paper-200">
                            @foreach ($sale->returnItems as $index => $line)
                                <tr class="hover:bg-rose-50/30">
                                    <td class="px-4 py-3 font-medium text-ink-900">
                                        {{ $line->barang?->nama_barang ?? '-' }}
                                        <input type="hidden" name="items[{{ $index }}][id_barang]" value="{{ $line->id_barang }}">
                                    </td>
                                    <td class="px-4 py-3 text-right">{{ number_format((float) $line->qty_dijual, 2, ',', '.') }} {{ $line->barang?->satuanDasar?->kode_satuan }}</td>
                                    <td class="px-4 py-3 text-right font-semibold text-rose-700">
                                        {{ number_format((float) $line->qty_sisa_retur, 2, ',', '.') }} {{ $line->barang?->satuanDasar?->kode_satuan }}
                                    </td>
                                    <td class="px-4 py-3 text-right">Rp {{ number_format((float) $line->harga_satuan, 0, ',', '.') }}</td>
                                    <td class="px-4 py-3 text-center">
                                        <input
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            max="{{ $line->qty_sisa_retur }}"
                                            name="items[{{ $index }}][qty_dasar]"
                                            value="{{ old("items.$index.qty_dasar", 0) }}"
                                            class="w-28 rounded-sm border border-paper-300 bg-white px-2 py-1.5 text-right text-sm focus:border-rose-500 focus:outline-none"
                                            placeholder="0"
                                        >
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3">
                <a href="{{ route('penjualan.penjualan.show', $sale) }}" class="rounded-sm border border-paper-300 px-4 py-2.5 text-sm font-medium text-ink-700 hover:bg-paper-100">Batal</a>
                <button type="submit" class="rounded-sm bg-rose-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-rose-700">
                    Ajukan Retur Penjualan
                </button>
            </div>
        @else
            <div class="rounded-sm border border-dashed border-paper-300 bg-paper-50 p-8 text-center text-sm text-ink-600">
                Pilih salah satu transaksi penjualan di atas untuk memuat daftar barang yang dapat diajukan retur.
            </div>
        @endif
    </form>

    <script>
        function handleSaleChange(saleId) {
            if (saleId) {
                window.location.href = "{{ route("$routeBase.create") }}?id_penjualan=" + saleId;
            }
        }
    </script>
</x-layouts.app>

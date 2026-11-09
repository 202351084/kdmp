<x-layouts.app :title="$title" eyebrow="M5 Pembelian">
    @if (session('success'))
        <div class="mb-4 rounded-sm border border-sawah-500/40 bg-sawah-100 px-4 py-3 text-sm text-ink-900">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="mb-4 rounded-sm border border-merah-300 bg-merah-50 px-4 py-3 text-sm text-merah-800">{{ session('error') }}</div>
    @endif
    <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <form method="GET" class="flex w-full gap-2 sm:max-w-md">
            <input name="q" value="{{ request('q') }}" placeholder="Cari kode atau supplier..." class="min-w-0 flex-1 rounded-sm border border-paper-300 bg-paper-50 px-3 py-2 text-sm">
            @if ($archiveActive)
                <input type="hidden" name="archive" value="1">
            @endif
            @foreach ($archiveFilters as $filter => $value)
                @if ($filter !== 'archive' && $value !== null)
                    <input type="hidden" name="{{ $filter }}" value="{{ $value }}">
                @endif
            @endforeach
            <button class="rounded-sm border border-paper-300 px-4 py-2 text-sm font-medium text-ink-700 hover:border-emerald-500">Cari</button>
        </form>
        <a href="{{ route("$routeBase.create") }}" class="inline-flex items-center justify-center rounded-sm bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700">+ Tambah Pembelian</a>
    </div>

        <input type="hidden" name="archive" value="1">
        @if (request('q'))
            <input type="hidden" name="q" value="{{ request('q') }}">
        @endif
        <div class="flex flex-wrap items-end gap-3">
            <label class="text-sm">
                <span class="mb-1 block text-ink-700">Tahun</span>
                <input type="number" name="year" min="1900" max="9999" value="{{ $archiveFilters['year'] ?? '' }}" placeholder="Semua" class="w-28 rounded-sm border border-paper-300 bg-white px-3 py-2">
            </label>
            <label class="text-sm">
                <span class="mb-1 block text-ink-700">Bulan</span>
                <select name="month" class="rounded-sm border border-paper-300 bg-white px-3 py-2">
                    <option value="">Semua bulan</option>
                    @foreach ([1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'] as $monthNumber => $monthName)
                        <option value="{{ $monthNumber }}" @selected(($archiveFilters['month'] ?? '') == $monthNumber)>{{ $monthName }}</option>
                    @endforeach
                </select>
            </label>
            <label class="text-sm">
                <span class="mb-1 block text-ink-700">Tanggal</span>
                <input type="number" name="day" min="1" max="31" value="{{ $archiveFilters['day'] ?? '' }}" placeholder="Semua" class="w-24 rounded-sm border border-paper-300 bg-white px-3 py-2">
            </label>
            <button type="submit" class="rounded-sm border border-yellow-500 bg-yellow-50 px-4 py-2 text-sm font-semibold text-emerald-800 shadow-sm hover:bg-yellow-100 focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:ring-offset-2">Cari</button>
            @if ($archiveActive || request('q'))
                <a href="{{ route("$routeBase.index") }}" class="rounded-sm border border-paper-300 px-4 py-2 text-sm font-medium text-ink-700 hover:bg-paper-100">Reset</a>
            @endif
        </div>
        <p class="mt-2 text-xs text-ink-600">Pilih tahun saja untuk rekap tahunan, atau gabungkan dengan bulan dan tanggal untuk melihat transaksi yang lebih spesifik.</p>
    </form>
    @foreach (['year' => 'Tahun', 'month' => 'Bulan', 'day' => 'Tanggal'] as $field => $label)
        @error($field)
            <p class="mb-2 text-sm text-merah-700">{{ $label }} tidak valid: {{ $message }}</p>
        @enderror
    @endforeach

    @if ($yearlySummary)
        <div class="mb-4 grid gap-3 sm:grid-cols-2">
            <div class="rounded-sm border border-emerald-200 bg-emerald-50 px-4 py-3">
                <p class="text-xs font-medium text-emerald-800">Jumlah pembelian tahun {{ $archiveFilters['year'] }}</p>
                <p class="mt-1 text-xl font-semibold text-emerald-950">{{ number_format((int) $yearlySummary->total_transaksi) }}</p>
            </div>
            <div class="rounded-sm border border-emerald-200 bg-emerald-50 px-4 py-3">
                <p class="text-xs font-medium text-emerald-800">Nilai pembelian tahunan</p>
                <p class="mt-1 text-xl font-semibold text-emerald-950">Rp {{ number_format((float) $yearlySummary->total_nilai, 2, ',', '.') }}</p>
            </div>
        </div>
    @endif
    <p class="mb-2 text-sm text-ink-600">{{ $archiveActive ? 'Hasil arsip' : '5 pembelian terbaru' }}@if ($archiveActive) — {{ $items->total() }} transaksi @endif</p>

    <div class="overflow-hidden rounded-sm border border-paper-300 bg-paper-50">
        <div class="overflow-x-auto">
            <table class="min-w-[760px] w-full text-sm">
                <thead class="bg-paper-100 text-left text-ink-700">
                    <tr>
                        <th class="px-4 py-3">Kode</th><th class="px-4 py-3">Tanggal</th><th class="px-4 py-3">Supplier</th><th class="px-4 py-3">Pembayaran</th><th class="px-4 py-3 text-right">Total</th><th class="px-4 py-3">Status</th><th class="px-4 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($items as $item)
                        <tr class="border-t border-paper-200 hover:bg-paper-100/70">
                            <td class="whitespace-nowrap px-4 py-3 font-medium">{{ $item->kode_pembelian }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-ink-700">{{ $item->tanggal_transaksi?->format('d M Y') }}</td>
                            <td class="px-4 py-3">{{ $item->pihak?->nama_pihak ?? 'N/A' }}</td>
                            <td class="px-4 py-3">
                                <span class="font-medium text-ink-900">{{ $item->sumber_pembelian === 'pengambilan_konsinyasi' ? 'Hutang Konsinyasi' : ucfirst($item->jenis_pembayaran) }}</span>
                                @if ($item->sumber_pembelian === 'pengambilan_konsinyasi')
                                    <span class="block text-xs text-sky-700">Pengambilan dari desa lain</span>
                                @endif
                                @if ($item->kasBank)
                                    <span class="block text-xs text-ink-500">{{ $item->kasBank->nama }}</span>
                                @elseif ($item->jenis_pembayaran === 'kredit' && $item->tgl_jatuh_tempo)
                                    <span class="block text-xs text-amber-700">Tempo: {{ $item->tgl_jatuh_tempo->format('d/m/y') }}</span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-right">Rp {{ number_format($item->total_pembelian, 2, ',', '.') }}</td>
                            <td class="px-4 py-3 capitalize">{{ $item->status }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-right"><a href="{{ route("$routeBase.show", $item) }}" class="font-medium text-emerald-700 hover:text-emerald-800">Lihat</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-4 py-12 text-center text-ink-600">Belum ada pembelian. Gunakan tombol Tambah Pembelian untuk membuat transaksi.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if ($archiveActive)
        <div class="mt-4">{{ $items->links() }}</div>
    @endif
</x-layouts.app>
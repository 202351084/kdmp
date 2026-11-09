<x-layouts.app :title="$title" eyebrow="Retur Penjualan">
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-sm text-ink-600">Pemantauan barang yang dikembalikan</p>
            <h1 class="mt-1 text-2xl font-semibold text-ink-900">Retur Penjualan</h1>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('penjualan.retur.create') }}" class="rounded-sm bg-rose-600 px-3 py-2 text-sm font-semibold text-white hover:bg-rose-700">+ Ajukan Retur</a>
            <a href="{{ route('penjualan.penjualan.index') }}" class="rounded-sm border border-rose-200 px-3 py-2 text-sm font-semibold text-rose-800 hover:bg-rose-50">Kembali ke POS</a>
        </div>
    </div>

    <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <form method="GET" class="flex w-full gap-2 sm:max-w-md">
            <input name="q" value="{{ request('q') }}" placeholder="Cari kode retur atau transaksi" class="min-w-0 flex-1 rounded-sm border border-paper-300 bg-paper-50 px-3 py-2 text-sm focus:border-rose-500 focus:outline-none">
            @if ($archiveActive)
                <input type="hidden" name="archive" value="1">
            @endif
            @foreach ($archiveFilters as $filter => $value)
                @if ($filter !== 'archive' && $value !== null)
                    <input type="hidden" name="{{ $filter }}" value="{{ $value }}">
                @endif
            @endforeach
            <button class="rounded-sm border border-paper-300 px-4 py-2 text-sm font-medium text-ink-700 hover:border-rose-400">Cari</button>
        </form>
        <span class="text-sm text-ink-600">{{ $archiveActive ? $items->total() : $items->count() }} retur</span>
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
        <p class="mt-2 text-xs text-ink-600">Pilih tahun saja untuk rekap tahunan, atau gabungkan dengan bulan dan tanggal untuk melihat catatan retur tertentu.</p>
    </form>
    @foreach (['year' => 'Tahun', 'month' => 'Bulan', 'day' => 'Tanggal'] as $field => $label)
        @error($field)
            <p class="mb-2 text-sm text-merah-700">{{ $label }} tidak valid: {{ $message }}</p>
        @enderror
    @endforeach

    @if ($yearlySummary)
        <div class="mb-4 grid gap-3 sm:grid-cols-2">
            <div class="rounded-sm border border-rose-200 bg-rose-50 px-4 py-3">
                <p class="text-xs font-medium text-rose-800">Jumlah retur penjualan tahun {{ $archiveFilters['year'] }}</p>
                <p class="mt-1 text-xl font-semibold text-rose-950">{{ number_format((int) $yearlySummary->total_transaksi) }}</p>
            </div>
            <div class="rounded-sm border border-rose-200 bg-rose-50 px-4 py-3">
                <p class="text-xs font-medium text-rose-800">Nilai retur tahunan</p>
                <p class="mt-1 text-xl font-semibold text-rose-950">Rp {{ number_format((float) $yearlySummary->total_nilai, 2, ',', '.') }}</p>
            </div>
        </div>
    @endif
    @unless ($archiveActive)
        <p class="mb-2 text-sm text-ink-600">5 catatan retur terbaru</p>
    @endunless

    <div class="overflow-hidden rounded-sm border border-paper-300 bg-paper-50">
        <div class="overflow-x-auto">
            <table class="min-w-[720px] w-full text-sm">
                <thead class="bg-rose-50 text-left text-rose-950">
                    <tr>
                        <th class="px-4 py-3 font-semibold">Kode Retur</th>
                        <th class="px-4 py-3 font-semibold">Transaksi</th>
                        <th class="px-4 py-3 font-semibold">Tanggal</th>
                        <th class="px-4 py-3 font-semibold">Penyelesaian</th>
                        <th class="px-4 py-3 text-right font-semibold">Nilai</th>
                        <th class="px-4 py-3 font-semibold">Status</th>
                        <th class="px-4 py-3 text-right font-semibold">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($items as $item)
                        <tr class="border-t border-paper-200 hover:bg-rose-50/40">
                            <td class="whitespace-nowrap px-4 py-3 font-medium text-ink-900">{{ $item->kode_retur }}</td>
                            <td class="px-4 py-3">{{ $item->penjualan?->kode_penjualan ?? '—' }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-ink-700">{{ $item->tgl_retur?->format('d M Y') ?? '—' }}</td>
                            <td class="px-4 py-3 capitalize">{{ str_replace('_', ' ', $item->jenis_penyelesaian) }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-right">Rp {{ number_format((float) $item->total_nilai, 2, ',', '.') }}</td>
                            <td class="px-4 py-3"><span class="inline-flex rounded-sm bg-rose-50 px-2 py-1 text-xs font-medium text-rose-800">{{ ucfirst(str_replace('_', ' ', $item->status)) }}</span></td>
                            <td class="px-4 py-3 text-right"><a href="{{ route("$routeBase.show", $item) }}" class="font-semibold text-rose-700 hover:text-rose-900">Detail</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-4 py-12 text-center text-ink-600">Belum ada retur penjualan.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if ($archiveActive)
        <div class="mt-4">{{ $items->links() }}</div>
    @endif
</x-layouts.app>
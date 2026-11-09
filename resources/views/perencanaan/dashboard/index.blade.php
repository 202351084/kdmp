<x-layouts.app title="Dashboard Kalkulasi dan Perencanaan" eyebrow="Kalkulasi dan Perencanaan Pangan">
    {{-- Flash Notifications --}}
    @if (session('status'))
        <div class="mb-5 flex items-center justify-between rounded-sm border border-sawah-500/40 bg-sawah-100 px-4 py-3 text-sm text-ink-900">
            <div class="flex items-center gap-2">
                <span class="font-bold text-sawah-600">✓ Berhasil:</span>
                <span>{{ session('status') }}</span>
            </div>
            <span class="font-mono text-xs text-ink-600/70">{{ now()->format('H:i') }}</span>
        </div>
    @endif

    @if (session('error'))
        <div class="mb-5 flex items-center justify-between rounded-sm border border-merah-400 bg-merah-50 px-4 py-3 text-sm text-merah-700">
            <div class="flex items-center gap-2">
                <span class="font-bold text-merah-600">⚠ Gagal:</span>
                <span>{{ session('error') }}</span>
            </div>
        </div>
    @endif

    @php
        $namaBulanList = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];
    @endphp

    <div class="mb-7">
        <div class="mb-4">
            <p class="font-mono text-[11px] uppercase tracking-[0.18em] text-ink-600/70">Kalkulasi dan Perencanaan</p>
            <h2 class="mt-1 font-display text-2xl font-semibold text-ink-950">Dashboard Kalkulasi dan Perencanaan</h2>
            <p class="mt-1 text-sm text-ink-600">Pilih fitur untuk mengelola data kebutuhan, neraca, dan laporan komoditas desa.</p>
        </div>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
            <a href="{{ route('perencanaan.dashboard') }}" class="group rounded-sm border border-merah-500 bg-merah-50 p-5 shadow-2xs transition hover:-translate-y-0.5 hover:shadow-md">
                <span class="flex h-10 w-10 items-center justify-center rounded-full bg-merah-600 text-lg text-paper-50">▦</span>
                <h3 class="mt-5 font-display text-xl font-semibold text-ink-950">Dashboard Kalkulasi dan Perencanaan</h3>
                <p class="mt-2 text-xs font-medium uppercase tracking-wide text-ink-600">Pantau status komoditas</p>
            </a>

            <a href="{{ route('perencanaan.demografi.index') }}" class="group rounded-sm border border-paper-300 bg-paper-50 p-5 shadow-2xs transition hover:-translate-y-0.5 hover:border-merah-400 hover:shadow-md">
                <span class="flex h-10 w-10 items-center justify-center rounded-full bg-blue-100 text-lg text-blue-700">⌂</span>
                <h3 class="mt-5 font-display text-xl font-semibold text-ink-950">Data &amp; Parameter</h3>
                <p class="mt-2 text-xs font-medium uppercase tracking-wide text-ink-600">Kelola data dasar kalkulasi</p>
            </a>

            <a href="{{ route('perencanaan.kebutuhan-komoditas.index', ['tahun' => $tahun]) }}" class="group rounded-sm border border-paper-300 bg-paper-50 p-5 shadow-2xs transition hover:-translate-y-0.5 hover:border-merah-400 hover:shadow-md">
                <span class="flex h-10 w-10 items-center justify-center rounded-full bg-amber-100 text-lg text-amber-700">∑</span>
                <h3 class="mt-5 font-display text-xl font-semibold text-ink-950">Kalkulasi Kebutuhan</h3>
                <p class="mt-2 text-xs font-medium uppercase tracking-wide text-ink-600">Proses kebutuhan per enam bulan</p>
            </a>

            <a href="{{ route('perencanaan.neraca-komoditas.index', ['tahun' => $tahun]) }}" class="group rounded-sm border border-paper-300 bg-paper-50 p-5 shadow-2xs transition hover:-translate-y-0.5 hover:border-merah-400 hover:shadow-md">
                <span class="flex h-10 w-10 items-center justify-center rounded-full bg-sawah-100 text-lg text-sawah-700">⇄</span>
                <h3 class="mt-5 font-display text-xl font-semibold text-ink-950">Neraca Komoditas</h3>
                <p class="mt-2 text-xs font-medium uppercase tracking-wide text-ink-600">Baca surplus dan defisit</p>
            </a>

            <a href="{{ route('perencanaan.laporan.index', ['tahun' => $tahun]) }}" class="group rounded-sm border border-paper-300 bg-paper-50 p-5 shadow-2xs transition hover:-translate-y-0.5 hover:border-merah-400 hover:shadow-md">
                <span class="flex h-10 w-10 items-center justify-center rounded-full bg-purple-100 text-lg text-purple-700">▤</span>
                <h3 class="mt-5 font-display text-xl font-semibold text-ink-950">Laporan Kalkulasi</h3>
                <p class="mt-2 text-xs font-medium uppercase tracking-wide text-ink-600">Rekap hasil kalkulasi</p>
            </a>
        </div>
    </div>

    <div class="mb-4 flex items-center justify-between border-l-4 border-merah-500 bg-paper-50 px-4 py-3">
        <div>
            <h2 class="font-display text-lg font-semibold text-ink-950">Ringkasan Status Kebutuhan</h2>
            <p class="mt-0.5 text-xs text-ink-600">Surplus, seimbang, dan defisit komoditas pada periode terpilih.</p>
        </div>
        <span class="hidden font-mono text-[10px] uppercase tracking-wider text-ink-600/70 sm:block">Dashboard Kalkulasi</span>
    </div>

    {{-- Filter & Action Header --}}
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4 rounded-sm border border-paper-300 bg-paper-50 p-4 shadow-2xs">
        <form method="GET" action="{{ route('perencanaan.dashboard') }}" class="flex flex-wrap items-center gap-3">
            {{-- Wilayah Desa Aktif (Read-Only Context) --}}
            <div class="flex items-center gap-1.5 rounded-sm border border-paper-300 bg-paper-100 px-3 py-2 text-xs font-medium text-ink-800">
                <span class="font-mono text-[10px] uppercase text-ink-600/70">Wilayah:</span>
                <span class="font-semibold text-ink-950">{{ $wilayah?->nama ?? 'Desa Terdaftar' }}</span>
            </div>

            {{-- Filter Kategori --}}
            <select name="kategori" class="rounded-sm border border-paper-300 bg-paper-100 px-3 py-2 text-xs font-medium text-ink-800 focus:border-merah-400 focus:outline-none">
                <option value="">Semua Kategori</option>
                @foreach ($daftarKategori as $kat)
                    <option value="{{ $kat }}" {{ $kategori === $kat ? 'selected' : '' }}>
                        {{ ucwords(str_replace('_', ' ', $kat)) }}
                    </option>
                @endforeach
            </select>

            {{-- Filter Tahun --}}
            <select name="tahun" class="rounded-sm border border-paper-300 bg-paper-100 px-3 py-2 text-xs font-medium text-ink-800 focus:border-merah-400 focus:outline-none">
                @foreach (range(2025, 2030) as $t)
                    <option value="{{ $t }}" {{ $tahun == $t ? 'selected' : '' }}>Tahun {{ $t }}</option>
                @endforeach
            </select>

            {{-- Filter periode enam bulanan --}}
            <select name="periode" class="rounded-sm border border-paper-300 bg-paper-100 px-3 py-2 text-xs font-medium text-ink-800 focus:border-merah-400 focus:outline-none">
                <option value="">Semua Periode</option>
                <option value="ganjil" {{ request('periode') === 'ganjil' ? 'selected' : '' }}>Periode Ganjil (Januari–Juni)</option>
                <option value="genap" {{ request('periode') === 'genap' ? 'selected' : '' }}>Periode Genap (Juli–Desember)</option>
            </select>

            <button type="submit" class="rounded-sm border border-paper-300 bg-paper-200/80 px-3 py-2 text-xs font-medium text-ink-800 hover:border-merah-400 hover:text-merah-600">
                Terapkan Filter
            </button>
            @if ($periode || $kategori)
                <a href="{{ route('perencanaan.dashboard', ['tahun' => $tahun]) }}" class="text-xs text-ink-600 hover:text-merah-600 hover:underline">
                    Reset
                </a>
            @endif
        </form>

        {{-- Tombol Utama --}}
        <div class="flex items-center gap-2">
            <button type="button"
                    onclick="document.getElementById('modal-kalkulasi').classList.remove('hidden')"
                    class="inline-flex items-center gap-2 rounded-sm bg-merah-500 px-4 py-2 text-xs font-semibold text-paper-50 shadow-xs hover:bg-merah-600">
                <span>⚡</span>
                <span>Jalankan Kalkulasi</span>
            </button>
        </div>
    </div>

    {{-- Banner Status Terkini & Waktu Kalkulasi --}}
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3 border-l-4 border-merah-500 bg-paper-50 px-4 py-3 text-xs text-ink-800 shadow-2xs">
        <div class="flex flex-wrap items-center gap-3">
            <span class="font-semibold text-ink-900">Status Snapshot Terkini:</span>
            <span>
                Periode Terpilih: <strong>{{ $periode ? ucfirst($periode) . ' ' : 'Semua Periode ' }}{{ $tahun }}</strong>
            </span>
            <span class="text-paper-300">|</span>
            <span>
                Snapshot Versi: <span class="rounded bg-paper-200 px-1.5 py-0.5 font-mono text-[11px] font-semibold text-ink-800">v{{ $versiAktif }}</span>
            </span>
            <span class="text-paper-300">|</span>
            <span>
                Terakhir Dihitung:
                <strong>{{ $waktuKalkulasiTerakhir ? \Carbon\Carbon::parse($waktuKalkulasiTerakhir)->format('d M Y, H:i') . ' WIB' : 'Belum Pernah' }}</strong>
            </span>
        </div>
        <div>
            @if ($adaDataDemografi)
                <span class="inline-flex items-center gap-1 rounded bg-sawah-100 px-2 py-0.5 font-mono text-[11px] font-medium text-sawah-600">
                    ✓ Data Demografi Survei Siap ({{ number_format($totalPenduduk, 0, ',', '.') }} Jiwa)
                </span>
            @else
                <span class="inline-flex items-center gap-1 rounded bg-merah-100 px-2 py-0.5 font-mono text-[11px] font-medium text-merah-600">
                    ⚠ Data Demografi Belum Tersedia di Survei
                </span>
            @endif
        </div>
    </div>

    {{-- Ringkasan KPI Cards --}}
    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        {{-- Card 1: Komoditas Terhitung --}}
        <div class="rounded-sm border border-paper-300 bg-paper-50 p-4 shadow-2xs">
            <div class="flex items-center justify-between">
                <p class="font-mono text-[11px] uppercase tracking-wider text-ink-600/70">Total Komoditas</p>
                <span class="rounded bg-paper-200 px-1.5 py-0.5 font-mono text-[10px] text-ink-700">Kalkulasi</span>
            </div>
            <p class="mt-2 font-display text-3xl font-semibold text-ink-950">{{ $totalKomoditasTerhitung }}</p>
            <p class="mt-1 text-xs text-ink-600">
                Kebutuhan: <strong class="font-mono text-ink-900">{{ number_format($totalKebutuhanVolume, 1, ',', '.') }}</strong> sat.
            </p>
        </div>

        {{-- Card 2: Komoditas Surplus --}}
        <div class="rounded-sm border border-sawah-500/30 bg-sawah-100/40 p-4 shadow-2xs">
            <div class="flex items-center justify-between">
                <p class="font-mono text-[11px] uppercase tracking-wider text-sawah-600">Komoditas Surplus</p>
                <span class="flex h-5 w-5 items-center justify-center rounded-full bg-sawah-500 text-xs text-paper-50 font-bold">✓</span>
            </div>
            <div class="mt-2 flex items-baseline gap-2">
                <p class="font-display text-3xl font-semibold text-sawah-600">{{ $totalSurplus }}</p>
                <span class="text-xs font-medium text-sawah-600/80">({{ $persenSurplus }}%)</span>
            </div>
            <p class="mt-1 text-xs text-ink-700">
                Kelebihan panen desa: <span class="font-mono font-medium text-sawah-600">+{{ number_format($totalSurplusVolume, 1, ',', '.') }}</span>
            </p>
        </div>

        {{-- Card 3: Komoditas Seimbang --}}
        <div class="rounded-sm border border-padi-500/30 bg-padi-100/40 p-4 shadow-2xs">
            <div class="flex items-center justify-between">
                <p class="font-mono text-[11px] uppercase tracking-wider text-padi-600">Komoditas Seimbang</p>
                <span class="flex h-5 w-5 items-center justify-center rounded-full bg-padi-500 text-xs text-paper-50 font-bold">=</span>
            </div>
            <div class="mt-2 flex items-baseline gap-2">
                <p class="font-display text-3xl font-semibold text-ink-900">{{ $totalSeimbang }}</p>
                <span class="text-xs font-medium text-padi-600/90">({{ $persenSeimbang }}%)</span>
            </div>
            <p class="mt-1 text-xs text-ink-600">
                Produksi tepat memenuhi konsumsi warga
            </p>
        </div>

        {{-- Card 4: Komoditas Defisit --}}
        <div class="rounded-sm border border-merah-500/30 bg-merah-50 p-4 shadow-2xs">
            <div class="flex items-center justify-between">
                <p class="font-mono text-[11px] uppercase tracking-wider text-merah-600">Komoditas Defisit</p>
                <span class="flex h-5 w-5 items-center justify-center rounded-full bg-merah-500 text-xs text-paper-50 font-bold">!</span>
            </div>
            <div class="mt-2 flex items-baseline gap-2">
                <p class="font-display text-3xl font-semibold text-merah-600">{{ $totalDefisit }}</p>
                <span class="text-xs font-medium text-merah-600/80">({{ $persenDefisit }}%)</span>
            </div>
            <p class="mt-1 text-xs text-ink-700">
                Kekurangan pasokan: <span class="font-mono font-semibold text-merah-600">-{{ number_format($totalDefisitVolume, 1, ',', '.') }}</span>
            </p>
        </div>
    </div>

    {{-- Grid 2 Kolom: Grafik Distribusi Status & Daftar Top Defisit --}}
    <div class="mb-6 grid grid-cols-1 gap-6 lg:grid-cols-12">
        {{-- Kolom Kiri (7 Kolom): Visualisasi & Perbandingan Volume Komoditas --}}
        <div class="space-y-6 lg:col-span-7">
            {{-- Visualisasi Distribusi Status --}}
            <div class="rounded-sm border border-paper-300 bg-paper-50 p-5 shadow-2xs">
                <div class="flex items-center justify-between border-b border-paper-200 pb-3">
                    <div>
                        <h3 class="font-display text-base font-semibold text-ink-900">Distribusi Status Neraca Komoditas</h3>
                        <p class="text-xs text-ink-600">Ringkasan per sektor dan komoditas; klik kartu untuk melihat periode</p>
                    </div>
                    <span class="font-mono text-xs text-ink-600/70">{{ $totalKomoditasTerhitung }} Komoditas</span>
                </div>

                {{-- Stacked Progress Bar --}}
                <div class="mt-4">
                    <div class="flex h-4 w-full overflow-hidden rounded-xs bg-paper-200">
                        @if ($totalKomoditasTerhitung > 0)
                            <div style="width: {{ $persenSurplus }}%" class="bg-sawah-500 transition-all" title="Surplus: {{ $persenSurplus }}%"></div>
                            <div style="width: {{ $persenSeimbang }}%" class="bg-padi-500 transition-all" title="Seimbang: {{ $persenSeimbang }}%"></div>
                            <div style="width: {{ $persenDefisit }}%" class="bg-merah-500 transition-all" title="Defisit: {{ $persenDefisit }}%"></div>
                        @else
                            <div class="w-full bg-paper-300" title="Belum ada data"></div>
                        @endif
                    </div>

                    {{-- Legend --}}
                    <div class="mt-3 flex flex-wrap items-center justify-between gap-4 text-xs">
                        <div class="flex items-center gap-2">
                            <span class="h-3 w-3 rounded-[2px] bg-sawah-500"></span>
                            <span class="text-ink-800">Surplus: <strong>{{ $totalSurplus }}</strong> ({{ $persenSurplus }}%)</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="h-3 w-3 rounded-[2px] bg-padi-500"></span>
                            <span class="text-ink-800">Seimbang: <strong>{{ $totalSeimbang }}</strong> ({{ $persenSeimbang }}%)</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="h-3 w-3 rounded-[2px] bg-merah-500"></span>
                            <span class="text-ink-800">Defisit: <strong>{{ $totalDefisit }}</strong> ({{ $persenDefisit }}%)</span>
                        </div>
                    </div>
                </div>

                {{-- Rincian ringkas per sektor dan komoditas --}}
                <div class="mt-6">
                    <div class="space-y-3">
                        @forelse ($distribusiNeraca as $sektor => $komoditasSektor)
                            <details class="group rounded-sm border border-paper-200 bg-paper-100/60" @if ($loop->first) open @endif>
                                <summary class="flex cursor-pointer list-none items-center justify-between px-3 py-2.5">
                                    <span class="font-semibold capitalize text-ink-950">{{ $sektor }}</span>
                                    <span class="text-xs text-ink-600">{{ $komoditasSektor->count() }} komoditas <span class="transition-transform group-open:inline-block group-open:rotate-180">⌄</span></span>
                                </summary>
                                <div class="grid gap-2 border-t border-paper-200 p-3 md:grid-cols-2">
                                    @foreach ($komoditasSektor as $ringkasan)
                                        @php
                                            $statusClass = $ringkasan['status_terbaru'] === 'surplus' ? 'text-sawah-600' : ($ringkasan['status_terbaru'] === 'defisit' ? 'text-merah-600' : 'text-ink-700');
                                        @endphp
                                        <details class="group rounded-sm border border-paper-200 bg-paper-50">
                                            <summary class="flex cursor-pointer list-none items-center justify-between gap-2 p-3">
                                                <span class="font-semibold text-ink-950">{{ $ringkasan['komoditas']?->nama ?? 'Komoditas' }}</span>
                                                <span class="text-xs font-semibold capitalize {{ $statusClass }}">{{ $ringkasan['status_terbaru'] }}</span>
                                            </summary>
                                            <div class="border-t border-paper-200 px-3 pb-3 text-xs text-ink-700">
                                                <div class="mt-2 grid grid-cols-2 gap-2">
                                                    <span>Kebutuhan: <strong>{{ number_format($ringkasan['total_kebutuhan'], 1, ',', '.') }}</strong></span>
                                                    <span>Ketersediaan: <strong>{{ number_format($ringkasan['total_ketersediaan'], 1, ',', '.') }}</strong></span>
                                                </div>
                                                <p class="mt-2 font-semibold text-ink-900">Rincian periode</p>
                                                @foreach ($ringkasan['items'] as $hasil)
                                                    <div class="mt-1 flex items-center justify-between border-b border-paper-200/70 py-1 last:border-0">
                                                        <span>{{ \Carbon\Carbon::createFromDate($hasil->tahun, $hasil->bulan, 1)->translatedFormat('F') }} {{ $hasil->tahun }}</span>
                                                        <span class="capitalize {{ $hasil->status_surplus_defisit === 'surplus' ? 'text-sawah-600' : ($hasil->status_surplus_defisit === 'defisit' ? 'text-merah-600' : 'text-ink-700') }}">{{ $hasil->status_surplus_defisit }}</span>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </details>
                                    @endforeach
                                </div>
                            </details>
                        @empty
                            <p class="py-4 text-center text-xs text-ink-600/70">Belum ada data kalkulasi untuk periode ini.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        {{-- Kolom Kanan (5 Kolom): Daftar Defisit Terbesar & Info Sumber Data Survei --}}
        <div class="space-y-6 lg:col-span-5">
            {{-- Tabel Komoditas Defisit Terbesar (Top Deficits) --}}
            <div class="rounded-sm border border-merah-500/30 bg-paper-50 p-5 shadow-2xs">
                <div class="flex items-center justify-between border-b border-paper-200 pb-3">
                    <div>
                        <h3 class="font-display text-base font-semibold text-merah-700">Defisit Terbesar (Prioritas Pengadaan)</h3>
                        <p class="text-xs text-ink-600">Komoditas yang memerlukan pemenuhan pasokan luar</p>
                    </div>
                    <span class="rounded bg-merah-100 px-2 py-0.5 font-mono text-xs font-semibold text-merah-700">
                        {{ $komoditasDefisitTerbesar->count() }} Item
                    </span>
                </div>

                <div class="mt-4 divide-y divide-paper-200">
                    @forelse ($komoditasDefisitTerbesar as $item)
                        <div class="py-3 first:pt-0 last:pb-0">
                            <div class="flex items-start justify-between">
                                <div>
                                    <p class="font-semibold text-ink-950">{{ $item->komoditas?->nama }}</p>
                                    <p class="text-xs text-ink-600">
                                        Kebutuhan: {{ number_format($item->total_kebutuhan, 1, ',', '.') }} | Ketersediaan: {{ number_format($item->total_ketersediaan, 1, ',', '.') }}
                                    </p>
                                </div>
                                <div class="text-right">
                                    <p class="font-mono text-sm font-bold text-merah-600">
                                        {{ number_format($item->selisih, 1, ',', '.') }}
                                    </p>
                                    <span class="text-[10px] text-ink-600/70">Kecukupan: {{ number_format($item->persentase_kecukupan, 1, ',', '.') }}%</span>
                                </div>
                            </div>

                            <div class="mt-2 flex items-center justify-between">
                                <a href="{{ route('perencanaan.neraca-komoditas.show', $item->id_hasil) }}" class="text-xs text-ink-700 hover:text-merah-600 hover:underline">
                                    Lihat Detail &rarr;
                                </a>
                                <a href="{{ route('perencanaan.permintaan-pengadaan.from-hasil', ['hasil' => $item->id_hasil]) }}"
                                   class="inline-flex items-center gap-1 rounded-sm bg-merah-500 px-2.5 py-1 text-[11px] font-semibold text-paper-50 hover:bg-merah-600">
                                    <span>+ Buat Permintaan Pengadaan</span>
                                </a>
                            </div>
                        </div>
                    @empty
                        <div class="py-6 text-center text-xs text-ink-600">
                            <p class="font-semibold text-sawah-600">Tidak ada komoditas defisit</p>
                            <p class="mt-1 text-ink-600/70">Seluruh komoditas pada periode ini berada dalam status seimbang atau surplus.</p>
                        </div>
                    @endforelse
                </div>
            </div>

            {{-- Kartu Info Sumber Data Survei --}}
            <div class="rounded-sm border border-paper-300 bg-paper-50 p-5 shadow-2xs">
                <div class="flex items-center justify-between border-b border-paper-200 pb-3">
                    <h3 class="font-display text-base font-semibold text-ink-900">Sumber Data Survei Lapangan</h3>
                    <span class="rounded bg-paper-200 px-2 py-0.5 font-mono text-[10px] text-ink-700">Survei &rarr; Kalkulasi</span>
                </div>

                <div class="mt-4 space-y-3 text-xs text-ink-800">
                    <div class="flex items-start justify-between border-b border-paper-200/60 pb-2">
                        <div>
                            <p class="font-medium text-ink-900">Demografi Kependudukan (Survei Sesi 1)</p>
                            <p class="text-[11px] text-ink-600/80">Basis jumlah jiwa per kelompok umur</p>
                        </div>
                        <div class="text-right">
                            <span class="font-mono font-semibold text-ink-900">{{ number_format($totalPenduduk, 0, ',', '.') }} Jiwa</span>
                            <p class="text-[10px] text-sawah-600 font-medium">Tahun {{ $tahun }}</p>
                        </div>
                    </div>

                    {{-- Mini Breakdown Kelompok Umur --}}
                    <div class="grid grid-cols-5 gap-1 text-center font-mono text-[10px]">
                        <div class="rounded bg-paper-100 p-1">
                            <div class="text-ink-600/70">Balita</div>
                            <div class="font-semibold text-ink-900">{{ $demografiPerKelompok['balita'] }}</div>
                        </div>
                        <div class="rounded bg-paper-100 p-1">
                            <div class="text-ink-600/70">Anak</div>
                            <div class="font-semibold text-ink-900">{{ $demografiPerKelompok['anak'] }}</div>
                        </div>
                        <div class="rounded bg-paper-100 p-1">
                            <div class="text-ink-600/70">Remaja</div>
                            <div class="font-semibold text-ink-900">{{ $demografiPerKelompok['remaja'] }}</div>
                        </div>
                        <div class="rounded bg-paper-100 p-1">
                            <div class="text-ink-600/70">Dewasa</div>
                            <div class="font-semibold text-ink-900">{{ $demografiPerKelompok['dewasa'] }}</div>
                        </div>
                        <div class="rounded bg-paper-100 p-1">
                            <div class="text-ink-600/70">Lansia</div>
                            <div class="font-semibold text-ink-900">{{ $demografiPerKelompok['lansia'] }}</div>
                        </div>
                    </div>

                    <div class="flex items-start justify-between border-b border-paper-200/60 pb-2 pt-1">
                        <div>
                            <p class="font-medium text-ink-900">Potensi Panen & Ketersediaan (Survei Sesi 2)</p>
                            <p class="text-[11px] text-ink-600/80">Produksi pertanian komoditas lokal</p>
                        </div>
                        <div class="text-right">
                            <span class="font-mono font-semibold text-ink-900">{{ $jumlahCatatanProduksi }} Komoditas</span>
                            <p class="text-[10px] text-ink-600">Bulan panen aktif</p>
                        </div>
                    </div>

                    <div class="flex items-start justify-between pt-1">
                        <div>
                            <p class="font-medium text-ink-900">Sesi Survei Terakhir</p>
                            <p class="text-[11px] text-ink-600/80">Petugas survei lapangan desa</p>
                        </div>
                        <div class="text-right">
                            @if ($sesiSurveiTerakhir)
                                <span class="font-mono font-semibold text-ink-900">Sesi #{{ $sesiSurveiTerakhir->id }}</span>
                                <p class="text-[10px] text-ink-600">{{ $sesiSurveiTerakhir->tanggal_survei ? $sesiSurveiTerakhir->tanggal_survei->format('d/m/Y') : '—' }}</p>
                            @else
                                <span class="text-ink-600">—</span>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="mt-4 border-t border-paper-200 pt-3">
                    <a href="{{ route('survei.sesi.index') }}" class="inline-flex items-center gap-1 text-xs font-medium text-merah-600 hover:underline">
                        <span>Buka Modul Survei</span>
                        <span>&rarr;</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal jalankan kalkulasi --}}
    <div id="modal-kalkulasi" class="fixed inset-0 z-50 flex hidden items-center justify-center bg-ink-950/70 p-4 backdrop-blur-xs">
        <div class="w-full max-w-md rounded-sm border border-paper-300 bg-paper-50 p-6 shadow-2xl">
            <div class="flex items-center justify-between border-b border-paper-200 pb-3">
                <div class="flex items-center gap-2">
                    <span class="flex h-7 w-7 items-center justify-center rounded-sm bg-merah-500 font-bold text-paper-50 text-sm">⚡</span>
                    <div>
                        <h3 class="font-display text-base font-semibold text-ink-950">Jalankan Kalkulasi Kebutuhan</h3>
                        <p class="text-[11px] text-ink-600">Perhitungan snapshot kebutuhan & neraca desa</p>
                    </div>
                </div>
                <button type="button" onclick="document.getElementById('modal-kalkulasi').classList.add('hidden')"
                        class="text-ink-600 hover:text-ink-900 text-lg leading-none">&times;</button>
            </div>

            <form method="POST" action="{{ route('perencanaan.kebutuhan-komoditas.kalkulasi') }}" class="mt-4 space-y-4">
                @csrf
                <div>
                    <label class="mb-1 block text-xs font-semibold text-ink-800">Tahun Perencanaan</label>
                    <select name="tahun" required class="w-full rounded-sm border border-paper-300 bg-paper-100 px-3 py-2 text-sm text-ink-900 focus:border-merah-400 focus:outline-none">
                        @foreach (range(2025, 2030) as $t)
                            <option value="{{ $t }}" {{ $tahun == $t ? 'selected' : '' }}>Tahun {{ $t }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="mb-1 block text-xs font-semibold text-ink-800">Periode Perencanaan</label>
                    <select name="periode" required class="w-full rounded-sm border border-paper-300 bg-paper-100 px-3 py-2 text-sm text-ink-900 focus:border-merah-400 focus:outline-none">
                        <option value="ganjil" @selected(($periode ?? (now()->month <= 6 ? 'ganjil' : 'genap')) === 'ganjil')>Periode Ganjil (Januari–Juni)</option>
                        <option value="genap" @selected(($periode ?? (now()->month <= 6 ? 'ganjil' : 'genap')) === 'genap')>Periode Genap (Juli–Desember)</option>
                    </select>
                </div>

                <div>
                    <label class="mb-1 block text-xs font-semibold text-ink-800">Komoditas</label>
                    <select name="id_komoditas" class="w-full rounded-sm border border-paper-300 bg-paper-100 px-3 py-2 text-sm text-ink-900 focus:border-merah-400 focus:outline-none">
                        <option value="">-- Semua Komoditas Aktif --</option>
                        @foreach ($daftarKomoditas as $k)
                            <option value="{{ $k->id }}">{{ $k->nama }} ({{ $k->kategori }})</option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-[11px] text-ink-600/70">Pilih komoditas spesifik atau hitung seluruh komoditas sekaligus.</p>
                </div>

                <div>
                    <label class="mb-1 block text-xs font-semibold text-ink-800">Tampilkan Hasil Pada:</label>
                    <div class="grid grid-cols-3 gap-2">
                        <label class="flex items-center gap-1.5 rounded-sm border border-paper-300 bg-paper-100 p-2 text-xs cursor-pointer hover:border-merah-400">
                            <input type="radio" name="redirect_to" value="dashboard" checked class="text-merah-500">
                            <span class="font-medium text-ink-900">Dashboard</span>
                        </label>
                        <label class="flex items-center gap-1.5 rounded-sm border border-paper-300 bg-paper-100 p-2 text-xs cursor-pointer hover:border-merah-400">
                            <input type="radio" name="redirect_to" value="kebutuhan" class="text-merah-500">
                            <span class="font-medium text-ink-900">Kebutuhan</span>
                        </label>
                        <label class="flex items-center gap-1.5 rounded-sm border border-paper-300 bg-paper-100 p-2 text-xs cursor-pointer hover:border-merah-400">
                            <input type="radio" name="redirect_to" value="neraca" class="text-merah-500">
                            <span class="font-medium text-ink-900">Neraca</span>
                        </label>
                    </div>
                </div>

                <div class="rounded-sm border border-paper-300/80 bg-paper-200/60 p-3 text-xs text-ink-700">
                    <p class="font-semibold text-ink-900">Formula Kalkulasi:</p>
                    <p class="mt-0.5 font-mono text-[11px] text-ink-800">Kebutuhan = Populasi × Standar Konsumsi × Hari × Musiman</p>
                    <p class="mt-1 text-[10px] text-ink-600/80">Hasil kalkulasi disimpan sebagai snapshot idempoten di <code class="text-ink-900">kebutuhan_komoditas</code> dan dimutakhirkan ke <code class="text-ink-900">hasil_kalkulasi</code>.</p>
                </div>

                <div class="flex items-center justify-end gap-3 pt-2">
                    <button type="button"
                            onclick="document.getElementById('modal-kalkulasi').classList.add('hidden')"
                            class="rounded-sm border border-paper-300 px-3 py-2 text-xs font-medium text-ink-700 hover:border-paper-400">
                        Batal
                    </button>
                    <button type="submit" class="rounded-sm bg-merah-500 px-4 py-2 text-xs font-semibold text-paper-50 hover:bg-merah-600 shadow-xs">
                        Proses Kalkulasi
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-layouts.app>

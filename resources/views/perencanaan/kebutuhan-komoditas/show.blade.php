<x-layouts.app :title="$title" eyebrow="Detail Kalkulasi Kebutuhan">
    {{-- Navigasi kalkulasi --}}
    <x-perencanaan.nav-m2 active="kebutuhan" />

    @php
        $namaBulanList = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];
        $totalVolKebutuhan = $rincianKelompok->sum('kebutuhan_bulanan');
        $totalJiwa = $rincianKelompok->sum('jumlah_penduduk');
        $daysInMonth = \Carbon\Carbon::createFromDate($item->tahun, $item->bulan, 1)->daysInMonth;
    @endphp

    {{-- Breadcrumbs & Navigasi Cepat --}}
    <div class="mb-5 flex flex-wrap items-center justify-between gap-3 text-xs">
        <div class="flex items-center gap-2">
            <a href="{{ route('perencanaan.kebutuhan-komoditas.index', ['tahun' => $item->tahun, 'bulan' => $item->bulan]) }}"
               class="font-medium text-merah-600 hover:underline">
                &larr; Kembali ke Daftar Kebutuhan
            </a>
            <span class="text-paper-300">/</span>
            <span class="text-ink-600 font-mono">Detail Snapshot {{ $item->komoditas?->nama }}</span>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('perencanaan.standar-kebutuhan.index', ['id_komoditas' => $item->id_komoditas]) }}"
               class="rounded-sm border border-paper-300 bg-paper-50 px-3 py-1.5 font-medium text-ink-700 hover:border-merah-400 hover:text-merah-600">
                ⚙ Standar Gizi Komoditas Ini &rarr;
            </a>
            <a href="{{ route('perencanaan.neraca-komoditas.index', ['tahun' => $item->tahun, 'bulan' => $item->bulan, 'id_komoditas' => $item->id_komoditas]) }}"
               class="rounded-sm border border-paper-300 bg-paper-50 px-3 py-1.5 font-medium text-ink-700 hover:border-merah-400 hover:text-merah-600">
                ⚖ Lihat di Neraca Komoditas &rarr;
            </a>
            <button type="button"
                    onclick="document.getElementById('modal-kalkulasi').classList.remove('hidden')"
                    class="rounded-sm bg-merah-500 px-3 py-1.5 font-semibold text-paper-50 shadow-xs hover:bg-merah-600">
                ⚡ Hitung Ulang Komoditas Ini
            </button>
        </div>
    </div>

    {{-- Header Kartu Komoditas --}}
    <div class="mb-6 rounded-sm border border-paper-300 bg-paper-50 p-6 shadow-2xs">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <div class="flex items-center gap-2">
                    <span class="rounded bg-paper-200 px-2 py-0.5 font-mono text-[10px] uppercase text-ink-700">
                        {{ str_replace('_', ' ', $item->komoditas?->kategori ?? 'Komoditas') }}
                    </span>
                    <span class="text-xs text-ink-600/70">•</span>
                    <span class="font-mono text-xs text-ink-600">{{ $item->wilayah?->nama ?? 'Wilayah Desa' }}</span>
                </div>
                <h1 class="mt-1 font-display text-2xl font-bold text-ink-950">{{ $item->komoditas?->nama }}</h1>
                <p class="text-xs text-ink-600">
                    Periode Perencanaan: <strong>{{ $item->bulan <= 6 ? 'Ganjil (Januari–Juni)' : 'Genap (Juli–Desember)' }} {{ $item->tahun }}</strong>
                    • Satuan Dasar: <span class="font-mono font-semibold">{{ $item->satuan }}</span>
                    • Durasi: <span class="font-mono">{{ $daysInMonth }} Hari Kalender</span>
                </p>
            </div>

            <div class="flex flex-col items-end gap-2">
                {{-- Indikator Kelengkapan Data Standar --}}
                @if ($standarLengkap)
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-sawah-100 border border-sawah-500/30 px-3 py-1 font-mono text-xs font-semibold text-sawah-700">
                        <span>✓</span>
                        <span>Standar Gizi Lengkap ({{ $kelompokValidStandar }}/{{ $totalKelompok }} Kelompok)</span>
                    </span>
                @elseif ($kelompokValidStandar > 0)
                    <a href="{{ route('perencanaan.standar-kebutuhan.index', ['id_komoditas' => $item->id_komoditas]) }}"
                       class="inline-flex items-center gap-1.5 rounded-full bg-amber-100 border border-amber-400/40 px-3 py-1 font-mono text-xs font-semibold text-amber-800 hover:underline"
                       title="Sebagian kelompok umur belum memiliki standar gizi. Klik untuk melengkapi.">
                        <span>⚠</span>
                        <span>Standar Sebagian ({{ $kelompokValidStandar }}/{{ $totalKelompok }} Kelompok) &rarr;</span>
                    </a>
                @else
                    <a href="{{ route('perencanaan.standar-kebutuhan.index', ['id_komoditas' => $item->id_komoditas]) }}"
                       class="inline-flex items-center gap-1.5 rounded-full bg-merah-100 border border-merah-400/40 px-3 py-1 font-mono text-xs font-semibold text-merah-700 hover:underline"
                       title="Standar gizi belum diatur untuk komoditas ini! Klik untuk mengatur standar.">
                        <span>⚠</span>
                        <span>Standar Kosong (0/{{ $totalKelompok }} Kelompok) &rarr;</span>
                    </a>
                @endif

                @if ($hasilNeraca)
                    <div class="text-right">
                        <span class="inline-flex items-center rounded-full px-3 py-1 font-mono text-xs font-bold uppercase tracking-wider
                              {{ $hasilNeraca->status_surplus_defisit === 'surplus' ? 'bg-sawah-100 text-sawah-600 border border-sawah-500/30' : ($hasilNeraca->status_surplus_defisit === 'defisit' ? 'bg-merah-100 text-merah-700 border border-merah-400/30' : 'bg-padi-100 text-padi-600 border border-padi-500/30') }}">
                            STATUS NERACA: {{ $hasilNeraca->status_surplus_defisit }}
                        </span>
                        <p class="mt-0.5 text-xs text-ink-600">
                            Tingkat Kecukupan: <strong class="font-mono">{{ number_format($hasilNeraca->persentase_kecukupan, 1, ',', '.') }}%</strong>
                        </p>
                    </div>
                @endif
            </div>
        </div>

        {{-- KPI Ringkas Komoditas Ini --}}
        <div class="mt-6 grid grid-cols-2 gap-4 border-t border-paper-200 pt-4 sm:grid-cols-4 text-xs">
            <div class="rounded-sm border border-paper-200 bg-paper-100/60 p-3">
                <span class="text-ink-600 font-mono text-[10px] uppercase">1. Total Kebutuhan Konsumsi</span>
                <p class="mt-0.5 font-mono text-lg font-bold text-merah-600">
                    {{ number_format($totalVolKebutuhan, 2, ',', '.') }} <span class="text-xs font-normal text-ink-700">{{ $item->satuan }}</span>
                </p>
                <p class="text-[10px] text-ink-600">Bulan {{ $namaBulanList[$item->bulan] ?? $item->bulan }} ({{ $daysInMonth }} hari)</p>
            </div>
            <div class="rounded-sm border border-paper-200 bg-paper-100/60 p-3">
                <span class="text-ink-600 font-mono text-[10px] uppercase">2. Ketersediaan Panen Lokal</span>
                <p class="mt-0.5 font-mono text-lg font-bold text-ink-900">
                    {{ number_format((float) ($ketersediaan?->jumlah_produksi ?? ($hasilNeraca?->total_ketersediaan ?? 0)), 2, ',', '.') }} <span class="text-xs font-normal text-ink-700">{{ $item->satuan }}</span>
                </p>
                <p class="text-[10px] text-ink-600">{{ $ketersediaan ? 'Tercatat di Kalender Panen' : 'Tidak ada panen bulan ini' }}</p>
            </div>
            <div class="rounded-sm border border-paper-200 bg-paper-100/60 p-3">
                <span class="text-ink-600 font-mono text-[10px] uppercase">3. Selisih Neraca (2 - 1)</span>
                <p class="mt-0.5 font-mono text-lg font-bold {{ ($hasilNeraca?->selisih ?? 0) < 0 ? 'text-merah-600' : (($hasilNeraca?->selisih ?? 0) > 0 ? 'text-sawah-600' : 'text-ink-800') }}">
                    {{ ($hasilNeraca?->selisih ?? 0) > 0 ? '+' : '' }}{{ number_format((float) ($hasilNeraca?->selisih ?? 0), 2, ',', '.') }} <span class="text-xs font-normal text-ink-700">{{ $item->satuan }}</span>
                </p>
                <p class="text-[10px] text-ink-600">
                    {{ ($hasilNeraca?->selisih ?? 0) < 0 ? 'Defisit pasokan pangan' : (($hasilNeraca?->selisih ?? 0) > 0 ? 'Surplus pasokan panen' : 'Pasokan seimbang') }}
                </p>
            </div>
            <div class="rounded-sm border border-paper-200 bg-paper-100/60 p-3">
                <span class="text-ink-600 font-mono text-[10px] uppercase">4. Populasi Konsumen Desa</span>
                <p class="mt-0.5 font-mono text-lg font-bold text-ink-900">
                    {{ number_format($totalJiwa, 0, ',', '.') }} <span class="text-xs font-normal text-ink-700">Jiwa</span>
                </p>
                <p class="text-[10px] text-ink-600">Basis data kependudukan survei</p>
            </div>
        </div>
    </div>

    {{-- Banner Peringatan Jika Standar Belum Lengkap --}}
    @if (!$standarLengkap)
        <div class="mb-6 flex flex-wrap items-center justify-between gap-3 rounded-sm border border-amber-300 bg-amber-50 px-4 py-3 text-xs text-amber-900 shadow-2xs">
            <div class="flex items-center gap-2">
                <span class="text-base font-bold text-amber-600">⚠</span>
                <div>
                    <span class="font-semibold">Standar Kebutuhan Belum Lengkap:</span>
                    <span>Terdapat kelompok umur yang belum memiliki standar konsumsi gizi harian per kapita. Hal ini menyebabkan estimasi kebutuhan kelompok tersebut bernilai 0.</span>
                </div>
            </div>
            <a href="{{ route('perencanaan.standar-kebutuhan.index', ['id_komoditas' => $item->id_komoditas]) }}"
               class="rounded-sm bg-amber-200 px-3 py-1 font-semibold text-amber-900 hover:bg-amber-300">
                Lengkapi Standar Kebutuhan Komoditas Ini &rarr;
            </a>
        </div>
    @endif

    {{-- Rincian Perhitungan Per Kelompok Umur --}}
    <div class="mb-6 rounded-sm border border-paper-300 bg-paper-50 shadow-2xs">
        <div class="flex flex-wrap items-center justify-between border-b border-paper-200 px-5 py-4 gap-2">
            <div>
                <h2 class="font-display text-base font-semibold text-ink-900">Rincian Perhitungan Kebutuhan Berdasarkan Kelompok Umur</h2>
                <p class="text-xs text-ink-600">
                    Formula: <code class="font-mono text-ink-800">Populasi &times; Standar Harian &times; Hari Kalender ({{ $daysInMonth }}) &times; Faktor Musiman</code>
                </p>
            </div>
            <span class="rounded bg-paper-200 px-2.5 py-1 font-mono text-xs text-ink-700">
                {{ $rincianKelompok->count() }} Kelompok Umur Terhitung
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-paper-200 bg-paper-200/60 font-mono text-[11px] uppercase tracking-wide text-ink-600/80">
                        <th class="px-4 py-3 font-medium">Kelompok Umur</th>
                        <th class="px-4 py-3 text-right font-medium">Populasi (Jiwa)</th>
                        <th class="px-4 py-3 text-right font-medium">Standar Gizi Harian</th>
                        <th class="px-4 py-3 text-center font-medium">Hari / Periode</th>
                        <th class="px-4 py-3 text-center font-medium">Faktor Musiman</th>
                        <th class="px-4 py-3 text-right font-medium">Kebutuhan Periode ({{ $item->satuan }})</th>
                        <th class="px-4 py-3 font-medium">Status Standar &amp; Sumber</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rincianKelompok as $rk)
                        @php
                            $standarAda = $rk->id_standar !== null && (float) $rk->per_kapita_harian > 0;
                            $faktorHari = ($rk->standar && $rk->standar->satuan_periode === 'bulanan') ? 1 : $daysInMonth;
                        @endphp
                        <tr class="border-b border-paper-200 last:border-0 hover:bg-paper-100/70">
                            <td class="px-4 py-3 font-semibold text-ink-900 uppercase">
                                <span class="rounded bg-paper-200 px-2 py-0.5 font-mono text-xs text-ink-800">
                                    {{ $rk->kelompok_umur }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right font-mono text-xs text-ink-800">
                                {{ number_format($rk->jumlah_penduduk, 0, ',', '.') }}
                            </td>
                            <td class="px-4 py-3 text-right font-mono text-xs">
                                @if ($standarAda)
                                    <span class="font-semibold text-ink-950">{{ number_format($rk->per_kapita_harian, 6, ',', '.') }}</span>
                                    <span class="text-[10px] text-ink-600">/hari</span>
                                @else
                                    <span class="inline-flex items-center gap-1 rounded bg-amber-100 px-1.5 py-0.5 font-mono text-[10px] font-semibold text-amber-800">
                                        ⚠ Belum Diatur (0)
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center font-mono text-xs text-ink-700">
                                {{ $faktorHari }} {{ $faktorHari == 1 ? 'Bulan' : 'Hari' }}
                            </td>
                            <td class="px-4 py-3 text-center font-mono text-xs text-ink-700">
                                {{ number_format($rk->faktor_musiman, 2, ',', '.') }}x
                            </td>
                            <td class="px-4 py-3 text-right font-mono text-xs font-bold {{ (float)$rk->kebutuhan_bulanan > 0 ? 'text-merah-600' : 'text-ink-600' }}">
                                {{ number_format($rk->kebutuhan_bulanan, 4, ',', '.') }}
                                @if (!$standarAda)
                                    <span class="block text-[9px] font-normal text-amber-700 font-sans">(Standar 0)</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-xs text-ink-600">
                                @if ($standarAda)
                                    <div>
                                        <span class="font-medium text-ink-800">{{ $rk->standar?->sumber ?? 'AKG Kemenkes / BPS' }}</span>
                                        @if ($rk->standar?->berlaku_mulai)
                                            <span class="block text-[10px] text-ink-600/70 font-mono">
                                                Mulai: {{ $rk->standar->berlaku_mulai->format('d/m/Y') }}
                                            </span>
                                        @endif
                                    </div>
                                @else
                                    <a href="{{ route('perencanaan.standar-kebutuhan.index', ['id_komoditas' => $item->id_komoditas, 'kelompok_umur' => $rk->kelompok_umur]) }}"
                                       class="inline-flex items-center gap-1 font-medium text-merah-600 underline hover:text-merah-700">
                                        <span>+ Isi Standar {{ ucfirst($rk->kelompok_umur) }}</span>
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="border-t-2 border-paper-300 bg-paper-100/90 font-semibold text-xs">
                        <td class="px-4 py-3 text-ink-950 font-display">TOTAL AKUMULASI DESA</td>
                        <td class="px-4 py-3 text-right font-mono text-ink-950">{{ number_format($totalJiwa, 0, ',', '.') }} Jiwa</td>
                        <td class="px-4 py-3 text-right font-mono text-ink-600">—</td>
                        <td class="px-4 py-3 text-center font-mono text-ink-600">{{ $daysInMonth }} Hari</td>
                        <td class="px-4 py-3 text-center font-mono text-ink-600">—</td>
                        <td class="px-4 py-3 text-right font-mono text-sm text-merah-600 font-bold">
                            {{ number_format($totalVolKebutuhan, 2, ',', '.') }} {{ $item->satuan }}
                        </td>
                        <td class="px-4 py-3 text-xs text-ink-600">
                            @if ($standarLengkap)
                                <span class="font-mono text-sawah-600 font-bold">✓ 100% Terhitung</span>
                            @else
                                <span class="font-mono text-amber-700 font-bold">⚠ Sebagian Belum Diatur</span>
                            @endif
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    {{-- Penjelasan Logika & Multi-Tenant Snapshot --}}
    <div class="mb-6 rounded-sm border border-paper-300 bg-paper-50 p-5 shadow-2xs">
        <h3 class="font-display text-sm font-semibold text-ink-950">Penjelasan Logika Perhitungan &amp; Snapshot Idempoten</h3>
        <div class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-3 text-xs text-ink-700">
            <div class="rounded-sm border border-paper-200 bg-paper-100/50 p-3">
                <span class="font-semibold text-ink-900 block">1. Sumber Data Demografi Survei</span>
                <p class="mt-1 text-ink-600">
                    Jumlah penduduk diambil dari entri demografi desa yang terhubung dengan survei untuk tahun <strong>{{ $item->tahun }}</strong>. Pemutakhiran survei akan otomatis tersinkronisasi saat kalkulasi dijalankan ulang.
                </p>
            </div>
            <div class="rounded-sm border border-paper-200 bg-paper-100/50 p-3">
                <span class="font-semibold text-ink-900 block">2. Standar Konsumsi Gizi Per Kapita</span>
                <p class="mt-1 text-ink-600">
                    Mengacu pada standar AKG resmi per kelompok umur. Standar kebutuhan bersifat modular dan dapat disesuaikan per komoditas melalui menu Data &amp; Parameter Standar Kebutuhan.
                </p>
            </div>
            <div class="rounded-sm border border-paper-200 bg-paper-100/50 p-3">
                <span class="font-semibold text-ink-900 block">3. Snapshot &amp; Isolasi Multi-Tenant</span>
                <p class="mt-1 text-ink-600">
                    Hasil disimpan secara idempoten di tabel <code class="text-ink-900">kebutuhan_komoditas</code> untuk ID Wilayah <strong>{{ $item->id_wilayah }}</strong>. Data aman dan terisolasi dari koperasi desa lain.
                </p>
            </div>
        </div>
    </div>

    {{-- Jejak Snapshot, Tindak Lanjut & Tombol Pengadaan --}}
    <div class="rounded-sm border border-paper-300 bg-paper-50 p-5 shadow-2xs">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div class="text-xs text-ink-700">
                <p class="font-semibold text-ink-900">Jejak Audit Snapshot &amp; Integritas Data:</p>
                <p class="mt-0.5 text-ink-600">
                    Snapshot tersimpan permanen untuk menjaga konsistensi rencana pengadaan dan rekam jejak akuntansi koperasi desa.
                </p>
                <p class="mt-1 font-mono text-[11px] text-ink-600/80">
                    Snapshot dibuat: {{ $item->created_at ? $item->created_at->format('d F Y, H:i:s') : '—' }} WIB
                    • ID Wilayah: {{ $item->id_wilayah }}
                    • ID Komoditas: {{ $item->id_komoditas }}
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                @if ($hasilNeraca && $hasilNeraca->status_surplus_defisit === 'defisit')
                    <a href="{{ route('perencanaan.permintaan-pengadaan.from-hasil', ['hasil' => $hasilNeraca->id_hasil]) }}"
                       class="rounded-sm bg-merah-500 px-4 py-2 text-xs font-semibold text-paper-50 shadow-xs hover:bg-merah-600">
                        + Buat Permintaan Pengadaan
                    </a>
                @elseif ($hasilNeraca && $hasilNeraca->status_surplus_defisit === 'surplus')
                    <a href="{{ route('konsinyasi.pengiriman.create') }}"
                       class="rounded-sm bg-sawah-600 px-4 py-2 text-xs font-semibold text-paper-50 shadow-xs hover:bg-sawah-700">
                        Buat Pengiriman Konsinyasi &rarr;
                    </a>
                @endif
                <a href="{{ route('perencanaan.neraca-komoditas.index', ['tahun' => $item->tahun, 'bulan' => $item->bulan, 'id_komoditas' => $item->id_komoditas]) }}"
                   class="rounded-sm border border-paper-300 bg-paper-100 px-3 py-2 text-xs font-medium text-ink-800 hover:border-paper-400">
                    Buka Neraca Komoditas
                </a>
            </div>
        </div>
    </div>

    {{-- Modal Jalankan Kalkulasi Ulang Komoditas Ini --}}
    <div id="modal-kalkulasi" class="fixed inset-0 z-50 flex hidden items-center justify-center bg-ink-950/70 p-4 backdrop-blur-xs">
        <div class="w-full max-w-md rounded-sm border border-paper-300 bg-paper-50 p-6 shadow-2xl">
            <div class="flex items-center justify-between border-b border-paper-200 pb-3">
                <div class="flex items-center gap-2">
                    <span class="flex h-7 w-7 items-center justify-center rounded-sm bg-merah-500 font-bold text-paper-50 text-sm">⚡</span>
                    <div>
                        <h3 class="font-display text-base font-semibold text-ink-955">Kalkulasi Ulang: {{ $item->komoditas?->nama }}</h3>
                        <p class="text-[11px] text-ink-600">Perbarui estimasi kebutuhan komoditas ini secara instan</p>
                    </div>
                </div>
                <button type="button" onclick="document.getElementById('modal-kalkulasi').classList.add('hidden')"
                        class="text-ink-600 hover:text-ink-900 text-lg leading-none">&times;</button>
            </div>

            <form method="POST" action="{{ route('perencanaan.kebutuhan-komoditas.kalkulasi') }}" class="mt-4 space-y-4">
                @csrf
                <input type="hidden" name="id_komoditas" value="{{ $item->id_komoditas }}">

                <div>
                    <label class="mb-1 block text-xs font-semibold text-ink-800">Tahun Perencanaan</label>
                    <select name="tahun" required class="w-full rounded-sm border border-paper-300 bg-paper-100 px-3 py-2 text-sm text-ink-900 focus:border-merah-400 focus:outline-none">
                        @foreach (range(2025, 2030) as $t)
                            <option value="{{ $t }}" {{ $item->tahun == $t ? 'selected' : '' }}>Tahun {{ $t }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="mb-1 block text-xs font-semibold text-ink-800">Periode Perencanaan</label>
                    <select name="periode" required class="w-full rounded-sm border border-paper-300 bg-paper-100 px-3 py-2 text-sm text-ink-900 focus:border-merah-400 focus:outline-none">
                        <option value="ganjil" @selected($item->bulan <= 6)>Periode Ganjil (Januari–Juni)</option>
                        <option value="genap" @selected($item->bulan > 6)>Periode Genap (Juli–Desember)</option>
                    </select>
                </div>

                <div>
                    <label class="mb-1 block text-xs font-semibold text-ink-800">Tampilkan Hasil Pada:</label>
                    <div class="grid grid-cols-3 gap-2">
                        <label class="flex items-center gap-1.5 rounded-sm border border-paper-300 bg-paper-100 p-2 text-xs cursor-pointer hover:border-merah-400">
                            <input type="radio" name="redirect_to" value="kebutuhan" checked class="text-merah-500">
                            <span class="font-medium text-ink-900">Kebutuhan</span>
                        </label>
                        <label class="flex items-center gap-1.5 rounded-sm border border-paper-300 bg-paper-100 p-2 text-xs cursor-pointer hover:border-merah-400">
                            <input type="radio" name="redirect_to" value="dashboard" class="text-merah-500">
                            <span class="font-medium text-ink-900">Dashboard</span>
                        </label>
                        <label class="flex items-center gap-1.5 rounded-sm border border-paper-300 bg-paper-100 p-2 text-xs cursor-pointer hover:border-merah-400">
                            <input type="radio" name="redirect_to" value="neraca" class="text-merah-500">
                            <span class="font-medium text-ink-900">Neraca</span>
                        </label>
                    </div>
                </div>

                <div class="rounded-sm border border-paper-300/80 bg-paper-200/60 p-3 text-xs text-ink-700">
                    <p class="font-semibold text-ink-900">Formula Kalkulasi:</p>
                    <p class="mt-0.5 font-mono text-[11px] text-ink-800">Populasi &times; Standar Gizi Harian &times; {{ $daysInMonth }} Hari &times; Faktor Musiman</p>
                </div>

                <div class="flex items-center justify-end gap-3 pt-2">
                    <button type="button"
                            onclick="document.getElementById('modal-kalkulasi').classList.add('hidden')"
                            class="rounded-sm border border-paper-300 px-3 py-2 text-xs font-medium text-ink-700 hover:border-paper-400">
                        Batal
                    </button>
                    <button type="submit" class="rounded-sm bg-merah-500 px-4 py-2 text-xs font-semibold text-paper-50 hover:bg-merah-600 shadow-xs">
                        Proses Kalkulasi Ulang
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-layouts.app>

<x-layouts.app :title="$title" eyebrow="Detail Neraca Komoditas">
    {{-- Navigasi kalkulasi --}}
    <x-perencanaan.nav-m2 active="neraca" />

    @php
        $namaBulanList = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];
    @endphp

    <div class="mb-5 flex items-center justify-between">
        <div class="flex items-center gap-2 text-xs">
            <a href="{{ route('perencanaan.neraca-komoditas.index', ['tahun' => $hasil->tahun, 'bulan' => $hasil->bulan]) }}"
               class="font-medium text-merah-600 hover:underline">
                &larr; Kembali ke Daftar Neraca Komoditas
            </a>
            <span class="text-paper-300">/</span>
            <span class="text-ink-600 font-mono">Detail Neraca #{{ $hasil->id_hasil }}</span>
        </div>

        <div class="flex items-center gap-2">
            <span class="rounded bg-paper-200 px-2 py-1 font-mono text-xs text-ink-700">
                Versi Snapshot: v{{ $hasil->versi }}
            </span>
        </div>
    </div>

    {{-- Kartu Ringkasan Neraca Komoditas --}}
    <div class="mb-6 rounded-sm border border-paper-300 bg-paper-50 p-6 shadow-2xs">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <div class="flex items-center gap-2">
                    <span class="rounded bg-paper-200 px-2 py-0.5 font-mono text-[10px] uppercase text-ink-700">
                        {{ str_replace('_', ' ', $hasil->komoditas?->kategori ?? 'Pangan') }}
                    </span>
                    <span class="text-xs text-ink-600/70">•</span>
                    <span class="font-mono text-xs text-ink-600">{{ $hasil->wilayah?->nama ?? 'Wilayah Desa' }}</span>
                </div>
                <h1 class="mt-1 font-display text-2xl font-bold text-ink-950">{{ $hasil->komoditas?->nama }}</h1>
                <p class="text-xs text-ink-600">
                    Periode: <strong>{{ $hasil->bulan <= 6 ? 'Ganjil (Januari–Juni)' : 'Genap (Juli–Desember)' }} {{ $hasil->tahun }}</strong>
                    • Satuan: <span class="font-mono font-semibold">{{ $satuan }}</span>
                </p>
            </div>

            <div class="text-right">
                <span class="inline-flex items-center rounded-full px-3 py-1 font-mono text-xs font-bold uppercase tracking-wider
                      {{ $hasil->status_surplus_defisit === 'surplus' ? 'bg-sawah-100 text-sawah-600 border border-sawah-500/30' : ($hasil->status_surplus_defisit === 'defisit' ? 'bg-merah-100 text-merah-700 border border-merah-400/30' : 'bg-padi-100 text-padi-600') }}">
                    STATUS: {{ $hasil->status_surplus_defisit }}
                </span>
                <p class="mt-1 text-xs text-ink-600">
                    Kecukupan Pangan: <strong class="font-mono text-sm text-ink-950">{{ number_format($hasil->persentase_kecukupan, 1, ',', '.') }}%</strong>
                </p>
            </div>
        </div>

        {{-- 3 Kolom Neraca Komparasi --}}
        <div class="mt-6 grid grid-cols-1 gap-4 border-t border-paper-200 pt-6 sm:grid-cols-3">
            {{-- Kebutuhan Total --}}
            <div class="rounded-sm border border-paper-200 bg-paper-100/70 p-4">
                <span class="font-mono text-[10px] uppercase text-ink-600/70">1. Total Kebutuhan Konsumsi</span>
                <p class="mt-1 font-mono text-2xl font-bold text-ink-950">
                    {{ number_format($hasil->total_kebutuhan, 2, ',', '.') }}
                    <span class="text-xs font-normal text-ink-600">{{ $satuan }}</span>
                </p>
                <p class="mt-1 text-[11px] text-ink-600">Dihitung dari {{ $rincianKebutuhan->sum('jumlah_penduduk') }} jiwa penduduk desa</p>
            </div>

            {{-- Ketersediaan Produksi --}}
            <div class="rounded-sm border border-paper-200 bg-paper-100/70 p-4">
                <span class="font-mono text-[10px] uppercase text-ink-600/70">2. Ketersediaan Produksi Panen</span>
                <p class="mt-1 font-mono text-2xl font-bold text-ink-950">
                    {{ number_format($hasil->total_ketersediaan, 2, ',', '.') }}
                    <span class="text-xs font-normal text-ink-600">{{ $satuan }}</span>
                </p>
                <p class="mt-1 text-[11px] text-ink-600">
                    {{ $ketersediaan ? 'Tercatat pada kalender panen desa' : 'Tidak ada catatan panen lokal pada bulan ini' }}
                </p>
            </div>

            {{-- Selisih Neraca --}}
            <div class="rounded-sm border {{ $hasil->selisih < 0 ? 'border-merah-500/30 bg-merah-50' : ($hasil->selisih > 0 ? 'border-sawah-500/30 bg-sawah-100/50' : 'border-paper-200 bg-paper-100/70') }} p-4">
                <span class="font-mono text-[10px] uppercase {{ $hasil->selisih < 0 ? 'text-merah-600' : ($hasil->selisih > 0 ? 'text-sawah-600' : 'text-ink-600') }}">
                    3. Selisih Neraca (2 - 1)
                </span>
                <p class="mt-1 font-mono text-2xl font-bold {{ $hasil->selisih < 0 ? 'text-merah-600' : ($hasil->selisih > 0 ? 'text-sawah-600' : 'text-ink-950') }}">
                    {{ $hasil->selisih > 0 ? '+' : '' }}{{ number_format($hasil->selisih, 2, ',', '.') }}
                    <span class="text-xs font-normal text-ink-600">{{ $satuan }}</span>
                </p>
                <p class="mt-1 text-[11px] text-ink-700">
                    @if ($hasil->status_surplus_defisit === 'defisit')
                        Kekurangan volume konsumsi warga
                    @elseif ($hasil->status_surplus_defisit === 'surplus')
                        Kelebihan hasil panen komoditas
                    @else
                        Kebutuhan seimbang dengan pasokan
                    @endif
                </p>
            </div>
        </div>
    </div>

    {{-- Rincian komponen kebutuhan kalkulasi --}}
    <div class="mb-6 rounded-sm border border-paper-300 bg-paper-50 shadow-2xs">
        <div class="flex items-center justify-between border-b border-paper-200 px-5 py-4">
            <div>
                <h2 class="font-display text-base font-semibold text-ink-900">Rincian Populasi &amp; Estimasi Kebutuhan</h2>
                <p class="text-xs text-ink-600">Perhitungan per kelompok umur berdasarkan data survei demografi M1</p>
            </div>
            <a href="{{ route('perencanaan.kebutuhan-komoditas.index', ['tahun' => $hasil->tahun, 'bulan' => $hasil->bulan, 'id_komoditas' => $hasil->id_komoditas]) }}"
               class="text-xs font-medium text-merah-600 hover:underline">
                Buka Snapshot Kebutuhan &rarr;
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-paper-200 bg-paper-200/60 font-mono text-[11px] uppercase tracking-wide text-ink-600/80">
                        <th class="px-4 py-3 font-medium">Kelompok Umur</th>
                        <th class="px-4 py-3 text-right font-medium">Populasi (Jiwa)</th>
                        <th class="px-4 py-3 text-right font-medium">Standar Harian</th>
                        <th class="px-4 py-3 text-center font-medium">Faktor Musiman</th>
                        <th class="px-4 py-3 text-right font-medium">Kebutuhan Periode ({{ $satuan }})</th>
                        <th class="px-4 py-3 font-medium">Standar Gizi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rincianKebutuhan as $rk)
                        <tr class="border-b border-paper-200 last:border-0 hover:bg-paper-100/70">
                            <td class="px-4 py-3 font-semibold text-ink-900 uppercase">
                                <span class="rounded bg-paper-200 px-2 py-0.5 font-mono text-xs text-ink-800">
                                    {{ $rk->kelompok_umur }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right font-mono text-xs text-ink-800">
                                {{ number_format($rk->jumlah_penduduk, 0, ',', '.') }}
                            </td>
                            <td class="px-4 py-3 text-right font-mono text-xs text-ink-800">
                                {{ number_format($rk->per_kapita_harian, 6, ',', '.') }}
                            </td>
                            <td class="px-4 py-3 text-center font-mono text-xs text-ink-700">
                                {{ number_format($rk->faktor_musiman, 2, ',', '.') }}x
                            </td>
                            <td class="px-4 py-3 text-right font-mono text-xs font-bold text-merah-600">
                                {{ number_format($rk->kebutuhan_bulanan, 2, ',', '.') }}
                            </td>
                            <td class="px-4 py-3 text-xs text-ink-600">
                                {{ $rk->standar?->sumber ?? 'Standar AKG BPS' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-6 text-center text-xs text-ink-600">
                                Tidak ada data rincian kelompok umur.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Bagian alur lanjutan perencanaan --}}
    <div class="rounded-sm border border-paper-300 bg-paper-50 p-6 shadow-2xs">
        <h3 class="font-display text-base font-semibold text-ink-900">Tindak Lanjut &amp; Jalur Alur Sistem</h3>
        <p class="text-xs text-ink-600">Alur: Survei &rarr; Kalkulasi &rarr; Neraca Komoditas &rarr; Pengadaan</p>

        <div class="mt-4 rounded-sm border p-4
             {{ $hasil->status_surplus_defisit === 'defisit' ? 'border-merah-300 bg-merah-50/70' : ($hasil->status_surplus_defisit === 'surplus' ? 'border-sawah-300 bg-sawah-50/70' : 'border-paper-300 bg-paper-100/60') }}">
            @if ($hasil->status_surplus_defisit === 'defisit')
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <h4 class="font-semibold text-merah-700 text-sm">Status Defisit Terdeteksi: Diperlukan Pengadaan</h4>
                        <p class="mt-0.5 text-xs text-ink-700">
                            Komoditas ini kekurangan pasokan sebesar <strong>{{ number_format(abs((float) $hasil->selisih), 2, ',', '.') }} {{ $satuan }}</strong>. Anda dapat meneruskannya menjadi draf Permintaan Pengadaan (Purchase Requisition - PR) di modul Perencanaan.
                        </p>
                    </div>
                    <a href="{{ route('perencanaan.permintaan-pengadaan.from-hasil', ['hasil' => $hasil->id_hasil]) }}"
                       class="rounded-sm bg-merah-500 px-4 py-2 text-xs font-semibold text-paper-50 shadow-xs hover:bg-merah-600">
                        + Buat Permintaan Pengadaan
                    </a>
                </div>
            @elseif ($hasil->status_surplus_defisit === 'surplus')
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <h4 class="font-semibold text-sawah-700 text-sm">Status Surplus Terdeteksi: Potensi Pasokan Mitra Desa</h4>
                        <p class="mt-0.5 text-xs text-ink-700">
                            Komoditas ini memiliki kelebihan pasokan sebesar <strong>+{{ number_format((float) $hasil->selisih, 2, ',', '.') }} {{ $satuan }}</strong>. Jika akan dititipkan ke desa lain, catat melalui pengiriman konsinyasi.
                        </p>
                    </div>
                    <a href="{{ route('konsinyasi.pengiriman.create') }}"
                       class="rounded-sm border border-sawah-600 bg-sawah-600 px-4 py-2 text-xs font-semibold text-paper-50 hover:bg-sawah-700">
                        Buat Pengiriman Konsinyasi &rarr;
                    </a>
                </div>
            @else
                <div>
                    <h4 class="font-semibold text-ink-900 text-sm">Status Seimbang: Kebutuhan Terpenuhi Tepat</h4>
                    <p class="mt-0.5 text-xs text-ink-600">
                        Produksi lokal tepat memenuhi kebutuhan konsumsi warga desa. Tidak ada tindakan pengadaan darurat yang diperlukan.
                    </p>
                </div>
            @endif
        </div>

        <div class="mt-4 flex items-center justify-between border-t border-paper-200 pt-3 text-[11px] text-ink-600/70 font-mono">
            <span>Snapshot dibuat: {{ $hasil->created_at ? $hasil->created_at->format('d/m/Y H:i:s') : '—' }} WIB</span>
            <span>ID Hasil: {{ $hasil->id_hasil }} | ID Wilayah: {{ $hasil->id_wilayah }}</span>
        </div>
    </div>
</x-layouts.app>

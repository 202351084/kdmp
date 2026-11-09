<x-layouts.app title="Validasi & Laporan Survei" eyebrow="Manajemen Survei">


    <div class="mb-5 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="text-sm text-ink-500">Perbandingan Data Baseline Kependudukan RT vs Survei Real Masyarakat.</p>
            @if(isset($wilayahAdmin) && $wilayahAdmin)
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-blue-100 text-blue-800 mt-1">
                    📍 Wilayah: {{ $wilayahAdmin->nama }}
                </span>
            @endif
        </div>

        <div class="flex items-center gap-3">
            <form id="filterForm" method="GET" action="{{ route('survei.validasi.index') }}" class="flex items-center gap-2">
                <select name="bulan" onchange="document.getElementById('filterForm').submit()" class="rounded-sm border border-paper-300 text-sm py-1.5 px-3">
                    @foreach(range(1, 12) as $m)
                        <option value="{{ $m }}" {{ $bulan == $m ? 'selected' : '' }}>
                            {{ date('F', mktime(0, 0, 0, $m, 10)) }}
                        </option>
                    @endforeach
                </select>
                <select name="tahun" onchange="document.getElementById('filterForm').submit()" class="rounded-sm border border-paper-300 text-sm py-1.5 px-3">
                    @php
                        $years = $availablePeriods->pluck('tahun')->unique()->sortDesc();
                        if($years->isEmpty()) $years = collect([date('Y')]);
                    @endphp
                    @foreach($years as $y)
                        <option value="{{ $y }}" {{ $tahun == $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endforeach
                </select>
            </form>
            <a href="{{ route('survei.dashboard.index') }}"
               class="flex items-center gap-1.5 rounded-sm border border-paper-300 bg-white px-4 py-2 text-sm font-semibold text-ink-700 shadow-sm hover:bg-paper-100 whitespace-nowrap">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Kembali
            </a>
        </div>
    </div>

    <div class="mt-8 overflow-hidden rounded-sm border border-paper-300 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-ink-500">
                <thead class="border-b border-paper-300 bg-paper-50 text-xs uppercase text-ink-700">
                    <tr>
                        <th class="px-6 py-4 font-medium" rowspan="2">Desa / Wilayah</th>
                        <th class="px-6 py-4 font-medium" rowspan="2">No RT</th>
                        <th class="border-b border-paper-300 px-6 py-2 text-center font-medium" colspan="2">Data Baseline (RT)</th>
                        <th class="px-6 py-4 text-center font-medium" rowspan="2">Jiwa Terdata</th>
                        <th class="px-6 py-4 font-medium text-center" rowspan="2">Aksi</th>
                    </tr>
                    <tr>
                        <th class="px-6 py-2 text-center font-medium">KK</th>
                        <th class="px-6 py-2 text-center font-medium">Jiwa</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-paper-200">
                    @forelse($rts as $rt)
                        <tr class="hover:bg-paper-50">
                            <td class="px-6 py-4 font-medium text-ink-900">{{ $rt->wilayah->nama ?? '-' }}</td>
                            <td class="px-6 py-4">
                                {{ $rt->nama_rt }} / {{ $rt->rw ?? '-' }}
                                @if($rt->dusun)
                                    <br><span class="text-xs text-ink-400">Dusun: {{ $rt->dusun }}</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-center font-semibold text-ink-700">{{ $rt->total_kk_baseline }}</td>
                            <td class="px-6 py-4 text-center font-semibold text-ink-700">{{ $rt->total_jiwa_baseline }}</td>
                            <td class="px-6 py-4 text-center font-bold text-ink-900">
                                {{ $rt->jiwa_terdata }}
                            </td>
                            <td class="px-6 py-4 text-center">
                                @if($rt->masyarakat_list->count() > 0)
                                    <a href="{{ route('survei.validasi.rt.show', ['id_sesi' => $rt->id_sesi, 'nama_rt' => $rt->nama_rt, 'rw' => $rt->rw]) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-md bg-blue-50 text-blue-700 hover:bg-blue-100 border border-blue-200 transition-colors shadow-xs">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                        Lihat Detail
                                    </a>
                                @else
                                    <span class="text-xs text-ink-400 italic">Belum ada data</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr class="hover:bg-paper-50">
                            <td colspan="6" class="px-6 py-8 text-center text-ink-500">
                                Belum ada data RT yang terdaftar untuk dibandingkan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-paper-300 bg-paper-50 px-6 py-4 text-sm text-ink-500">
            * Halaman ini memantau jumlah jiwa yang terdata dari kuesioner masyarakat dibandingkan dengan target baseline RT. Klik "Lihat Detail" untuk memeriksa data riil yang diinputkan warga.
        </div>
    </div>

    @if(isset($unmatchedMasyarakat) && $unmatchedMasyarakat->count() > 0)
        <div class="mt-6 rounded-sm border border-amber-200 bg-amber-50/60 p-4 shadow-sm">
            <div class="flex items-start gap-3">
                <span class="text-amber-600 text-base">ℹ️</span>
                <div>
                    <h4 class="text-sm font-semibold text-amber-900">Perhatian: Ada Data Masyarakat yang RT-nya Belum Mengisi Baseline</h4>
                    <p class="text-xs text-amber-700 mt-0.5">
                        Terdapat {{ $unmatchedMasyarakat->sum(fn($m) => $m->anggota->count() > 0 ? $m->anggota->count() : 1) }} Jiwa yang sudah mengisi kuesioner masyarakat, namun Ketua RT terkait belum mengisi formulir data baseline:
                    </p>
                    <div class="mt-2.5 flex flex-wrap gap-2">
                        @foreach($unmatchedMasyarakat->groupBy(fn($m) => 'RT ' . ($m->rt ?: '-') . ' / RW ' . ($m->rw ?: '-')) as $rtGroup => $wargas)
                            @php $firstWarga = $wargas->first(); @endphp
                            <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-sm bg-white border border-amber-200 text-xs text-amber-800 shadow-xs">
                                <strong>{{ $rtGroup }}</strong> ({{ $wargas->sum(fn($m) => $m->anggota->count() > 0 ? $m->anggota->count() : 1) }} Jiwa terdata)
                                <a href="{{ route('survei.validasi.rt.show', ['id_sesi' => $firstWarga->id_sesi, 'nama_rt' => $firstWarga->rt, 'rw' => $firstWarga->rw]) }}" class="inline-flex items-center gap-1 font-semibold text-blue-600 hover:text-blue-800 hover:underline ml-1">
                                    Lihat Detail &rarr;
                                </a>
                            </span>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    @endif
</x-layouts.app>

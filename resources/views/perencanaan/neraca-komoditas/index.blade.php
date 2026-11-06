<x-layouts.app :title="$title" eyebrow="Kalkulasi dan Perencanaan">
    {{-- Navigasi kalkulasi --}}
    <x-perencanaan.nav-m2 active="neraca" />

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
                <span class="font-bold text-merah-600">⚠ Peringatan:</span>
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

    @if (($totalKomoditasKurangStandar ?? 0) > 0)
        <div class="mb-5 flex flex-wrap items-center justify-between gap-3 rounded-sm border border-amber-300 bg-amber-50 px-4 py-3 text-xs text-amber-900 shadow-2xs">
            <div class="flex items-center gap-2">
                <span class="text-base font-bold text-amber-600">⚠</span>
                <div>
                    <span class="font-semibold">Indikator Kelengkapan Data Standar:</span>
                    <span>Terdapat <strong>{{ $totalKomoditasKurangStandar }}</strong> komoditas dengan standar gizi yang belum lengkap atau bernilai 0. Nilai kebutuhan komoditas tersebut mungkin terhitung 0 sehingga status neraca belum akurat.</span>
                </div>
            </div>
            <a href="{{ route('perencanaan.standar-kebutuhan.index') }}" class="font-medium text-merah-600 underline hover:text-merah-700 whitespace-nowrap">
                Lengkapi Standar Kebutuhan &rarr;
            </a>
        </div>
    @endif

    {{-- Ringkasan Status Neraca --}}
    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div class="rounded-sm border border-sawah-500/30 bg-sawah-100/40 p-4 shadow-2xs">
            <div class="flex items-center justify-between">
                <p class="font-mono text-[11px] uppercase tracking-wide text-sawah-600">Komoditas Surplus</p>
                <span class="flex h-5 w-5 items-center justify-center rounded-full bg-sawah-500 text-xs text-paper-50 font-bold">✓</span>
            </div>
            <p class="mt-1 font-display text-3xl font-semibold text-sawah-600">{{ $totalSurplus }}</p>
            <p class="mt-1 text-xs text-ink-700">Produksi panen lokal mencukupi konsumsi warga</p>
        </div>

        <div class="rounded-sm border border-padi-500/30 bg-padi-100/40 p-4 shadow-2xs">
            <div class="flex items-center justify-between">
                <p class="font-mono text-[11px] uppercase tracking-wide text-padi-600">Komoditas Seimbang</p>
                <span class="flex h-5 w-5 items-center justify-center rounded-full bg-padi-500 text-xs text-paper-50 font-bold">=</span>
            </div>
            <p class="mt-1 font-display text-3xl font-semibold text-ink-950">{{ $totalSeimbang }}</p>
            <p class="mt-1 text-xs text-ink-600">Produksi lokal seimbang dengan estimasi kebutuhan</p>
        </div>

        <div class="rounded-sm border border-merah-500/30 bg-merah-50 p-4 shadow-2xs">
            <div class="flex items-center justify-between">
                <p class="font-mono text-[11px] uppercase tracking-wide text-merah-600">Komoditas Defisit</p>
                <span class="flex h-5 w-5 items-center justify-center rounded-full bg-merah-500 text-xs text-paper-50 font-bold">!</span>
            </div>
            <p class="mt-1 font-display text-3xl font-semibold text-merah-600">{{ $totalDefisit }}</p>
            <p class="mt-1 text-xs text-ink-700">Perlu pengadaan dari luar atau mitra desa</p>
        </div>
    </div>

    {{-- Filter Bar --}}
    <div class="mb-5 flex flex-wrap items-center justify-between gap-4">
        <form method="GET" class="flex flex-wrap items-center gap-2">
            <select name="tahun" class="rounded-sm border border-paper-300 bg-paper-50 px-3 py-2 text-xs font-medium text-ink-800 focus:border-merah-400 focus:outline-none">
                @foreach (range(2025, 2030) as $t)
                    <option value="{{ $t }}" {{ $tahun == $t ? 'selected' : '' }}>Tahun {{ $t }}</option>
                @endforeach
            </select>

            <select name="periode" class="rounded-sm border border-paper-300 bg-paper-50 px-3 py-2 text-xs font-medium text-ink-800 focus:border-merah-400 focus:outline-none">
                <option value="">-- Semua Periode --</option>
                <option value="ganjil" {{ request('periode') === 'ganjil' ? 'selected' : '' }}>Ganjil (Januari–Juni)</option>
                <option value="genap" {{ request('periode') === 'genap' ? 'selected' : '' }}>Genap (Juli–Desember)</option>
            </select>

            <select name="id_komoditas" class="rounded-sm border border-paper-300 bg-paper-50 px-3 py-2 text-xs font-medium text-ink-800 focus:border-merah-400 focus:outline-none">
                <option value="">-- Semua Komoditas --</option>
                @foreach ($daftarKomoditas as $k)
                    <option value="{{ $k->id }}" {{ $idKomoditas == $k->id ? 'selected' : '' }}>{{ $k->nama }}</option>
                @endforeach
            </select>

            <select name="status_surplus_defisit" class="rounded-sm border border-paper-300 bg-paper-50 px-3 py-2 text-xs font-medium text-ink-800 focus:border-merah-400 focus:outline-none">
                <option value="">-- Semua Status --</option>
                <option value="surplus" {{ $statusFilter === 'surplus' ? 'selected' : '' }}>Surplus Saja</option>
                <option value="defisit" {{ $statusFilter === 'defisit' ? 'selected' : '' }}>Defisit Saja</option>
                <option value="seimbang" {{ $statusFilter === 'seimbang' ? 'selected' : '' }}>Seimbang</option>
            </select>

            <input
                type="text"
                name="q"
                value="{{ request('q') }}"
                placeholder="Cari nama komoditas..."
                class="w-44 rounded-sm border border-paper-300 bg-paper-50 px-3 py-2 text-xs placeholder:text-ink-600/40 focus:border-merah-400 focus:outline-none"
            >

            <button type="submit" class="rounded-sm border border-paper-300 bg-paper-200/80 px-3 py-2 text-xs font-medium text-ink-800 hover:border-merah-400">
                Filter
            </button>
            @if ($bulan || $idKomoditas || $statusFilter || request('q'))
                <a href="{{ route('perencanaan.neraca-komoditas.index', ['tahun' => $tahun]) }}" class="text-xs text-ink-600 hover:text-merah-600 hover:underline">
                    Reset
                </a>
            @endif
        </form>

        <div class="flex items-center gap-2">
            <button type="button"
                    onclick="document.getElementById('modal-kalkulasi').classList.remove('hidden')"
                    class="inline-flex items-center gap-1.5 rounded-sm bg-merah-500 px-3 py-2 text-xs font-semibold text-paper-50 shadow-xs hover:bg-merah-600">
                <span>⚡</span>
                <span>Jalankan Kalkulasi</span>
            </button>
            <a href="{{ route('perencanaan.kebutuhan-komoditas.index', ['tahun' => $tahun, 'bulan' => $bulan]) }}"
               class="rounded-sm bg-paper-200 px-3 py-2 text-xs font-medium text-ink-800 hover:bg-paper-300">
                &larr; Detail Kebutuhan
            </a>
        </div>
    </div>

    {{-- Tabel Neraca Komoditas hasil kalkulasi --}}
    <div class="overflow-hidden rounded-sm border border-paper-300 bg-paper-50 shadow-2xs">
        <table class="w-full text-left text-sm">
            <thead>
                <tr class="border-b border-paper-300 bg-paper-200/70 font-mono text-[11px] uppercase tracking-wide text-ink-600/80">
                    <th class="px-4 py-3 font-medium text-center w-12">No</th>
                    <th class="px-4 py-3 font-medium">Nama Komoditas</th>
                    <th class="px-4 py-3 font-medium">Kategori</th>
                    <th class="px-4 py-3 font-medium text-center">Satuan</th>
                    <th class="px-4 py-3 font-medium">Periode</th>
                    <th class="px-4 py-3 text-right font-medium">Total Kebutuhan</th>
                    <th class="px-4 py-3 text-right font-medium">Ketersediaan</th>
                    <th class="px-4 py-3 text-right font-medium">Selisih Neraca</th>
                    <th class="px-4 py-3 text-center font-medium">Status</th>
                    <th class="px-4 py-3 text-right font-medium">Kecukupan</th>
                    <th class="px-4 py-3 text-center font-medium">Aksi / Tindak Lanjut</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($items as $idx => $item)
                    @php
                        $satuan = $satuanMap[$item->id_komoditas] ?? 'KG';
                        $rowNumber = ($items->currentPage() - 1) * $items->perPage() + $idx + 1;
                        $ks = $kelengkapanStandarMap[$item->id_komoditas] ?? null;
                    @endphp
                    <tr class="border-b border-paper-200 last:border-0 hover:bg-paper-100/70">
                        <td class="px-4 py-3 text-center font-mono text-xs text-ink-600">
                            {{ $rowNumber }}
                        </td>
                        <td class="px-4 py-3 font-semibold text-ink-950">
                            <div>
                                <span>{{ $item->komoditas?->nama ?? '—' }}</span>
                                @if ($ks)
                                    <div class="mt-1">
                                        @if ($ks['is_lengkap'])
                                            <span class="inline-flex items-center gap-1 rounded bg-sawah-100 text-sawah-700 border border-sawah-500/20 px-1.5 py-0.2 text-[9px] font-mono">
                                                ✓ Standar Lengkap ({{ $ks['valid'] }}/{{ $ks['total'] }})
                                            </span>
                                        @elseif ($ks['valid'] > 0)
                                            <a href="{{ route('perencanaan.standar-kebutuhan.index', ['id_komoditas' => $item->id_komoditas]) }}"
                                               class="inline-flex items-center gap-1 rounded bg-amber-100 text-amber-800 border border-amber-400/30 px-1.5 py-0.2 text-[9px] font-mono hover:underline"
                                               title="Sebagian kelompok umur belum memiliki standar gizi. Klik untuk melengkapi.">
                                                ⚠ Standar Sebagian ({{ $ks['valid'] }}/{{ $ks['total'] }})
                                            </a>
                                        @else
                                            <a href="{{ route('perencanaan.standar-kebutuhan.index', ['id_komoditas' => $item->id_komoditas]) }}"
                                               class="inline-flex items-center gap-1 rounded bg-merah-100 text-merah-700 border border-merah-400/30 px-1.5 py-0.2 text-[9px] font-mono hover:underline"
                                               title="Standar gizi belum diatur! Total kebutuhan bernilai 0. Klik untuk mengatur standar.">
                                                ⚠ Standar Kosong (0/{{ $ks['total'] }})
                                            </a>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        </td>
                        <td class="px-4 py-3 text-ink-700 text-xs">
                            <span class="rounded bg-paper-200/80 px-2 py-0.5 font-mono text-[10px] uppercase text-ink-700">
                                {{ str_replace('_', ' ', $item->komoditas?->kategori ?? 'Umum') }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center font-mono text-xs text-ink-600">
                            {{ $satuan }}
                        </td>
                        <td class="px-4 py-3 font-mono text-xs text-ink-700">
                            {{ $namaBulanList[$item->bulan] ?? 'Bln ' . $item->bulan }} {{ $item->tahun }}
                        </td>
                        <td class="px-4 py-3 text-right font-mono text-xs">
                            <span class="{{ (float)$item->total_kebutuhan == 0 ? 'text-ink-600' : 'text-ink-950 font-semibold' }}">
                                {{ number_format($item->total_kebutuhan, 2, ',', '.') }}
                            </span>
                            @if ((float)$item->total_kebutuhan == 0)
                                <span class="block text-[9px] text-amber-700 font-sans font-normal">
                                    (Kebutuhan 0)
                                </span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right font-mono text-xs text-ink-800">
                            {{ number_format($item->total_ketersediaan, 2, ',', '.') }}
                        </td>
                        <td class="px-4 py-3 text-right font-mono text-xs font-bold {{ $item->selisih < 0 ? 'text-merah-600' : ($item->selisih > 0 ? 'text-sawah-600' : 'text-ink-700') }}">
                            {{ $item->selisih > 0 ? '+' : '' }}{{ number_format($item->selisih, 2, ',', '.') }}
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if ($item->status_surplus_defisit === 'surplus')
                                <span class="rounded-full bg-sawah-100 px-2.5 py-0.5 font-mono text-[10px] font-bold uppercase text-sawah-600 border border-sawah-500/20">
                                    SURPLUS
                                </span>
                            @elseif ($item->status_surplus_defisit === 'defisit')
                                <span class="rounded-full bg-merah-100 px-2.5 py-0.5 font-mono text-[10px] font-bold uppercase text-merah-700 border border-merah-400/20">
                                    DEFISIT
                                </span>
                            @else
                                <span class="rounded-full bg-padi-100 px-2.5 py-0.5 font-mono text-[10px] font-bold uppercase text-padi-600 border border-padi-500/20">
                                    SEIMBANG
                                </span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right font-mono text-xs text-ink-800">
                            {{ number_format($item->persentase_kecukupan, 1, ',', '.') }}%
                        </td>
                        <td class="px-4 py-3 text-center">
                            <div class="flex items-center justify-center gap-1.5">
                                <a href="{{ route('perencanaan.neraca-komoditas.show', $item->id_hasil) }}"
                                   class="rounded-sm border border-paper-300 px-2 py-1 text-xs text-ink-700 hover:border-ink-500">
                                    Detail
                                </a>

                                @if ($item->status_surplus_defisit === 'defisit')
                                    <a href="{{ route('perencanaan.permintaan-pengadaan.from-hasil', ['hasil' => $item->id_hasil]) }}"
                                       class="inline-flex items-center gap-1 rounded-sm bg-merah-500 px-2.5 py-1 text-xs font-semibold text-paper-50 shadow-xs hover:bg-merah-600"
                                       title="Buat Draft Permintaan Pengadaan untuk menutupi kekurangan komoditas ini">
                                        <span>Buat Permintaan Pengadaan</span>
                                    </a>
                                @elseif ($item->status_surplus_defisit === 'surplus')
                                    <form method="POST" action="{{ route('perencanaan.neraca-komoditas.ajukan-ke-gudang', ['hasil' => $item->id_hasil]) }}">
                                        @csrf
                                        <button type="submit"
                                                class="inline-flex items-center gap-1 rounded-sm border border-sawah-600 bg-sawah-600 px-2.5 py-1 text-xs font-semibold text-paper-50 shadow-xs hover:bg-sawah-700"
                                                title="Buat pengajuan penerimaan surplus ke gudang">
                                            Ajukan ke Gudang
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="11" class="px-4 py-12 text-center text-sm text-ink-600/80">
                            <div class="mx-auto max-w-md">
                                <span class="mx-auto flex h-10 w-10 items-center justify-center rounded-full bg-paper-200 font-mono text-base text-ink-600">
                                    ∅
                                </span>
                                <p class="mt-3 font-display text-base font-semibold text-ink-900">Belum Ada Data Neraca Komoditas</p>
                                <p class="mt-1 text-xs text-ink-600">
                                    Klik tombol <strong class="text-merah-600">"Jalankan Kalkulasi"</strong> di atas atau buka modul <a href="{{ route('perencanaan.kebutuhan-komoditas.index') }}" class="text-merah-600 underline">Kalkulasi Kebutuhan</a> untuk menghasilkan neraca komoditas desa secara otomatis.
                                </p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $items->links() }}
    </div>

    {{-- Modal jalankan kalkulasi --}}
    <div id="modal-kalkulasi" class="fixed inset-0 z-50 flex hidden items-center justify-center bg-ink-950/70 p-4 backdrop-blur-xs">
        <div class="w-full max-w-md rounded-sm border border-paper-300 bg-paper-50 p-6 shadow-2xl">
            <div class="flex items-center justify-between border-b border-paper-200 pb-3">
                <div class="flex items-center gap-2">
                    <span class="flex h-7 w-7 items-center justify-center rounded-sm bg-merah-500 font-bold text-paper-50 text-sm">⚡</span>
                    <div>
                        <h3 class="font-display text-base font-semibold text-ink-950">Jalankan Kalkulasi Kebutuhan</h3>
                        <p class="text-[11px] text-ink-600">Perhitungan snapshot kebutuhan &amp; neraca komoditas desa</p>
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
                        <option value="ganjil" @selected(request('periode', $bulan !== null && $bulan > 6 ? 'genap' : 'ganjil') === 'ganjil')>Periode Ganjil (Januari–Juni)</option>
                        <option value="genap" @selected(request('periode', $bulan !== null && $bulan > 6 ? 'genap' : 'ganjil') === 'genap')>Periode Genap (Juli–Desember)</option>
                        @foreach ($namaBulanList as $num => $nama)
                            <option value="{{ $num }}" {{ ($bulan ?? date('n')) == $num ? 'selected' : '' }}>{{ $nama }} (Bulan {{ $num }})</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="mb-1 block text-xs font-semibold text-ink-800">Komoditas</label>
                    <select name="id_komoditas" class="w-full rounded-sm border border-paper-300 bg-paper-100 px-3 py-2 text-sm text-ink-900 focus:border-merah-400 focus:outline-none">
                        <option value="">-- Semua Komoditas Aktif --</option>
                        @foreach ($daftarKomoditas as $k)
                            <option value="{{ $k->id }}" {{ $idKomoditas == $k->id ? 'selected' : '' }}>{{ $k->nama }}</option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-[11px] text-ink-600/70">Pilih komoditas spesifik atau hitung seluruh komoditas sekaligus.</p>
                </div>

                <div>
                    <label class="mb-1 block text-xs font-semibold text-ink-800">Tampilkan Hasil Pada:</label>
                    <div class="grid grid-cols-3 gap-2">
                        <label class="flex items-center gap-1.5 rounded-sm border border-paper-300 bg-paper-100 p-2 text-xs cursor-pointer hover:border-merah-400">
                            <input type="radio" name="redirect_to" value="neraca" checked class="text-merah-500">
                            <span class="font-medium text-ink-900">Neraca</span>
                        </label>
                        <label class="flex items-center gap-1.5 rounded-sm border border-paper-300 bg-paper-100 p-2 text-xs cursor-pointer hover:border-merah-400">
                            <input type="radio" name="redirect_to" value="dashboard" class="text-merah-500">
                            <span class="font-medium text-ink-900">Dashboard</span>
                        </label>
                        <label class="flex items-center gap-1.5 rounded-sm border border-paper-300 bg-paper-100 p-2 text-xs cursor-pointer hover:border-merah-400">
                            <input type="radio" name="redirect_to" value="kebutuhan" class="text-merah-500">
                            <span class="font-medium text-ink-900">Kebutuhan</span>
                        </label>
                    </div>
                </div>

                <div class="rounded-sm border border-paper-300/80 bg-paper-200/60 p-3 text-xs text-ink-700">
                    <p class="font-semibold text-ink-900">Formula Neraca:</p>
                    <p class="mt-0.5 font-mono text-[11px] text-ink-800">Selisih = Total Ketersediaan - Total Kebutuhan</p>
                    <p class="mt-1 text-[10px] text-ink-600/80">Kalkulasi akan menghitung kebutuhan konsumsi warga dan membandingkannya dengan produksi panen lokal periode ini.</p>
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

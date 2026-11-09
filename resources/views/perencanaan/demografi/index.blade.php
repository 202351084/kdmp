    <x-layouts.app title="Demografi Penduduk Desa" eyebrow="Kalkulasi — Data & Parameter">
    {{-- Navigasi kalkulasi --}}
    <x-perencanaan.nav-m2 active="parameter" subActive="demografi" />

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

    {{-- Context Banner Wilayah & Sumber Survei --}}
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4 rounded-sm border border-paper-300 bg-paper-50 p-4 shadow-2xs">
        <form method="GET" action="{{ route('perencanaan.demografi.index') }}" class="flex flex-wrap items-center gap-3">
            <div class="flex items-center gap-1.5 rounded-sm border border-paper-300 bg-paper-100 px-3 py-2 text-xs font-medium text-ink-800">
                <span class="font-mono text-[10px] uppercase text-ink-600/70">Desa:</span>
                <span class="font-semibold text-ink-950">{{ $wilayah?->nama ?? 'Desa Terdaftar' }}</span>
            </div>

            <div class="flex items-center gap-2">
                <label for="tahun" class="font-mono text-xs text-ink-600/80">Tahun:</label>
                <select name="tahun" id="tahun" class="rounded-sm border border-paper-300 bg-paper-100 px-3 py-2 text-xs font-medium text-ink-800 focus:border-merah-400 focus:outline-none">
                    @foreach ($daftarTahun as $t)
                        <option value="{{ $t }}" {{ $tahun == $t ? 'selected' : '' }}>Tahun {{ $t }}</option>
                    @endforeach
                </select>
            </div>

            <button type="submit" class="rounded-sm border border-paper-300 bg-paper-200/80 px-3 py-2 text-xs font-medium text-ink-800 hover:border-merah-400 hover:text-merah-600">
                Terapkan Filter
            </button>
        </form>

        <div class="flex items-center gap-2">
            <a href="{{ route('survei.sesi.index') }}"
               class="inline-flex items-center gap-1.5 rounded-sm border border-paper-300 bg-paper-100 px-3 py-2 text-xs font-medium text-ink-700 hover:border-merah-400">
                <span>📋</span>
                <span>Lihat Survei</span>
            </a>

            <button type="button"
                    onclick="document.getElementById('modal-edit-semua').classList.remove('hidden')"
                    class="inline-flex items-center gap-1.5 rounded-sm bg-merah-500 px-4 py-2 text-xs font-semibold text-paper-50 shadow-xs hover:bg-merah-600">
                <span>✏️</span>
                <span>Perbarui Data Populasi</span>
            </button>
        </div>
    </div>

    {{-- Ringkasan Kartu Demografi --}}
    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        {{-- Card 1: Total Jiwa --}}
        <div class="rounded-sm border border-paper-300 bg-paper-50 p-4 shadow-2xs">
            <div class="flex items-center justify-between">
                <p class="font-mono text-[11px] uppercase tracking-wider text-ink-600/70">Total Populasi</p>
                <span class="rounded bg-paper-200 px-1.5 py-0.5 font-mono text-[10px] text-ink-700">Tahun {{ $tahun }}</span>
            </div>
            <p class="mt-2 font-display text-3xl font-semibold text-ink-950">
                {{ number_format($totalPenduduk, 0, ',', '.') }}
                <span class="text-sm font-normal text-ink-600">Jiwa</span>
            </p>
            <p class="mt-1 text-xs text-ink-600">
                Basis perhitungan seluruh komoditas kalkulasi
            </p>
        </div>

        {{-- Card 2: Usia Produktif (Remaja + Dewasa) --}}
        @php
            $usiaProduktif = ($populasiMap['remaja']['jumlah'] ?? 0) + ($populasiMap['dewasa']['jumlah'] ?? 0);
            $persenProduktif = $totalPenduduk > 0 ? round(($usiaProduktif / $totalPenduduk) * 100, 1) : 0;
        @endphp
        <div class="rounded-sm border border-paper-300 bg-paper-50 p-4 shadow-2xs">
            <div class="flex items-center justify-between">
                <p class="font-mono text-[11px] uppercase tracking-wider text-ink-600/70">Usia Produktif</p>
                <span class="rounded bg-sawah-100 px-1.5 py-0.5 font-mono text-[10px] font-semibold text-sawah-600">{{ $persenProduktif }}%</span>
            </div>
            <p class="mt-2 font-display text-3xl font-semibold text-ink-950">
                {{ number_format($usiaProduktif, 0, ',', '.') }}
                <span class="text-sm font-normal text-ink-600">Jiwa</span>
            </p>
            <p class="mt-1 text-xs text-ink-600">
                Remaja &amp; Dewasa (konsumsi tertinggi)
            </p>
        </div>

        {{-- Card 3: Kelompok Rentan (Balita + Lansia) --}}
        @php
            $kelompokRentan = ($populasiMap['balita']['jumlah'] ?? 0) + ($populasiMap['lansia']['jumlah'] ?? 0);
            $persenRentan = $totalPenduduk > 0 ? round(($kelompokRentan / $totalPenduduk) * 100, 1) : 0;
        @endphp
        <div class="rounded-sm border border-paper-300 bg-paper-50 p-4 shadow-2xs">
            <div class="flex items-center justify-between">
                <p class="font-mono text-[11px] uppercase tracking-wider text-ink-600/70">Kelompok Rentan</p>
                <span class="rounded bg-padi-100 px-1.5 py-0.5 font-mono text-[10px] font-semibold text-padi-600">{{ $persenRentan }}%</span>
            </div>
            <p class="mt-2 font-display text-3xl font-semibold text-ink-950">
                {{ number_format($kelompokRentan, 0, ',', '.') }}
                <span class="text-sm font-normal text-ink-600">Jiwa</span>
            </p>
            <p class="mt-1 text-xs text-ink-600">
                Balita &amp; Lansia (standar gizi khusus)
            </p>
        </div>

        {{-- Card 4: Status Integrasi Survei --}}
        <div class="rounded-sm border border-paper-300 bg-paper-50 p-4 shadow-2xs">
            <div class="flex items-center justify-between">
                <p class="font-mono text-[11px] uppercase tracking-wider text-ink-600/70">Koneksi Survei</p>
                <span class="flex h-5 w-5 items-center justify-center rounded-full {{ $sesiSurveiM1 ? 'bg-sawah-500' : 'bg-paper-400' }} text-xs text-paper-50 font-bold">
                    {{ $sesiSurveiM1 ? '✓' : '•' }}
                </span>
            </div>
            <p class="mt-2 font-display text-base font-semibold text-ink-950">
                {{ $sesiSurveiM1 ? 'Sesi #' . $sesiSurveiM1->id : 'Input Langsung' }}
            </p>
            <p class="mt-1 text-xs text-ink-600">
                {{ $sesiSurveiM1 ? 'Disurvei: ' . ($sesiSurveiM1->tanggal_survei ? $sesiSurveiM1->tanggal_survei->format('d/m/Y') : '—') : 'Belum sinkron dari survei' }}
            </p>
        </div>
    </div>

    {{-- Tabel Rincian Populasi Per Kelompok Umur --}}
    <div class="overflow-hidden rounded-sm border border-paper-300 bg-paper-50 shadow-2xs">
        <div class="border-b border-paper-200 px-5 py-4">
            <h2 class="font-display text-base font-semibold text-ink-900">Rincian Populasi Desa Berdasarkan Kelompok Umur</h2>
            <p class="text-xs text-ink-600">Klasifikasi kelompok umur diselaraskan dengan standar Angka Kecukupan Gizi (AKG) Kementerian Kesehatan.</p>
        </div>

        <table class="w-full text-left text-sm">
            <thead>
                <tr class="border-b border-paper-300 bg-paper-200/70 font-mono text-[11px] uppercase tracking-wide text-ink-600/80">
                    <th class="px-5 py-3 font-medium">Kelompok Umur</th>
                    <th class="px-5 py-3 font-medium">Rentang Usia Acuan</th>
                    <th class="px-5 py-3 text-right font-medium">Jumlah Penduduk</th>
                    <th class="px-5 py-3 font-medium">Proporsi</th>
                    <th class="px-5 py-3 font-medium">Sumber Data</th>
                    <th class="px-5 py-3 text-right font-medium">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $deskripsiUmur = [
                        'balita' => ['label' => 'Balita', 'rentang' => '0 – 4 Tahun', 'badge' => 'bg-padi-100 text-padi-700'],
                        'anak' => ['label' => 'Anak-anak', 'rentang' => '5 – 11 Tahun', 'badge' => 'bg-sawah-100 text-sawah-700'],
                        'remaja' => ['label' => 'Remaja', 'rentang' => '12 – 18 Tahun', 'badge' => 'bg-blue-100 text-blue-700'],
                        'dewasa' => ['label' => 'Dewasa', 'rentang' => '19 – 59 Tahun', 'badge' => 'bg-purple-100 text-purple-700'],
                        'lansia' => ['label' => 'Lanjut Usia (Lansia)', 'rentang' => '60 Tahun ke atas', 'badge' => 'bg-amber-100 text-amber-800'],
                    ];
                @endphp

                @foreach ($kelompokUmurList as $ku)
                    @php
                        $dataKu = $populasiMap[$ku] ?? ['record' => null, 'jumlah' => 0];
                        $jumlah = $dataKu['jumlah'];
                        $record = $dataKu['record'];
                        $persen = $totalPenduduk > 0 ? round(($jumlah / $totalPenduduk) * 100, 1) : 0;
                        $meta = $deskripsiUmur[$ku] ?? ['label' => ucfirst($ku), 'rentang' => '—', 'badge' => 'bg-paper-200 text-ink-800'];
                    @endphp
                    <tr class="border-b border-paper-200 last:border-0 hover:bg-paper-100/70">
                        <td class="px-5 py-3.5">
                            <div class="flex items-center gap-2">
                                <span class="rounded px-2 py-0.5 font-mono text-[11px] font-semibold {{ $meta['badge'] }}">
                                    {{ strtoupper($ku) }}
                                </span>
                                <span class="font-medium text-ink-950">{{ $meta['label'] }}</span>
                            </div>
                        </td>
                        <td class="px-5 py-3.5 font-mono text-xs text-ink-600">
                            {{ $meta['rentang'] }}
                        </td>
                        <td class="px-5 py-3.5 text-right font-mono text-sm font-semibold text-ink-950">
                            {{ number_format($jumlah, 0, ',', '.') }} <span class="text-xs font-normal text-ink-600">Jiwa</span>
                        </td>
                        <td class="px-5 py-3.5">
                            <div class="flex items-center gap-2">
                                <div class="w-24 overflow-hidden rounded-[2px] bg-paper-200 h-2">
                                    <div style="width: {{ $persen }}%" class="h-full bg-merah-500"></div>
                                </div>
                                <span class="font-mono text-xs text-ink-800">{{ $persen }}%</span>
                            </div>
                        </td>
                        <td class="px-5 py-3.5 text-xs text-ink-600">
                            @if ($record && $record->sesiSurvei)
                                <a href="{{ route('survei.sesi.show', $record->id_sesi_survei) }}" class="inline-flex items-center gap-1 text-merah-600 hover:underline">
                                    <span>Survei Sesi #{{ $record->id_sesi_survei }}</span>
                                    <span>&rarr;</span>
                                </a>
                            @else
                                <span class="text-ink-600/70">Tersimpan untuk kalkulasi</span>
                            @endif
                        </td>
                        <td class="px-5 py-3.5 text-right">
                            @if ($record)
                                <button type="button"
                                        onclick="openEditSingle({{ $record->id_demografi }}, '{{ $ku }}', {{ $jumlah }})"
                                        class="rounded-sm border border-paper-300 px-2.5 py-1 text-xs font-medium text-ink-700 hover:border-merah-400 hover:text-merah-600">
                                    Ubah
                                </button>
                            @else
                                <button type="button"
                                        onclick="document.getElementById('modal-edit-semua').classList.remove('hidden')"
                                        class="rounded-sm bg-paper-200 px-2.5 py-1 text-xs font-medium text-ink-700 hover:bg-paper-300">
                                    + Isi Data
                                </button>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="bg-paper-200/50 font-semibold text-ink-950">
                    <td class="px-5 py-3.5" colspan="2">TOTAL PENDUDUK DESA</td>
                    <td class="px-5 py-3.5 text-right font-mono text-base text-merah-600">
                        {{ number_format($totalPenduduk, 0, ',', '.') }} Jiwa
                    </td>
                    <td class="px-5 py-3.5 font-mono text-xs" colspan="3">
                        100.0%
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>

    {{-- Modal Perbarui Semua Kelompok Umur --}}
    <div id="modal-edit-semua" class="fixed inset-0 z-50 flex hidden items-center justify-center bg-ink-950/70 p-4 backdrop-blur-xs">
        <div class="w-full max-w-lg rounded-sm border border-paper-300 bg-paper-50 p-6 shadow-2xl">
            <div class="flex items-center justify-between border-b border-paper-200 pb-3">
                <div>
                    <h3 class="font-display text-base font-semibold text-ink-900">Perbarui Populasi Penduduk Desa</h3>
                    <p class="text-xs text-ink-600">Desa: <strong>{{ $wilayah?->nama }}</strong></p>
                </div>
                <button type="button" onclick="document.getElementById('modal-edit-semua').classList.add('hidden')" class="text-ink-600 hover:text-ink-950">
                    ✕
                </button>
            </div>

            <form action="{{ route('perencanaan.demografi.store') }}" method="POST" class="mt-4 space-y-4">
                @csrf
                <div>
                    <label class="block font-mono text-xs text-ink-700">Tahun Perencanaan:</label>
                    <input type="number" name="tahun" value="{{ $tahun }}" min="2020" max="2100" required
                           class="mt-1 w-full rounded-sm border border-paper-300 bg-paper-100 px-3 py-2 text-xs font-medium focus:border-merah-400 focus:outline-none">
                </div>

                <div class="space-y-3 rounded-sm border border-paper-200 bg-paper-100/50 p-4">
                    <p class="font-mono text-[11px] uppercase tracking-wider text-ink-600/80">Jumlah Jiwa per Kelompok Umur:</p>

                    @foreach ($kelompokUmurList as $ku)
                        <div class="flex items-center justify-between gap-3">
                            <label class="w-40 text-xs font-medium text-ink-900">
                                {{ ucfirst($ku) }} ({{ $deskripsiUmur[$ku]['rentang'] }}):
                            </label>
                            <div class="flex flex-1 items-center gap-2">
                                <input type="number"
                                       name="jumlah_penduduk[{{ $ku }}]"
                                       value="{{ $populasiMap[$ku]['jumlah'] ?? 0 }}"
                                       min="0"
                                       required
                                       class="w-full rounded-sm border border-paper-300 bg-paper-50 px-3 py-1.5 text-right font-mono text-xs font-semibold focus:border-merah-400 focus:outline-none">
                                <span class="font-mono text-xs text-ink-600">Jiwa</span>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="flex items-center justify-end gap-2 border-t border-paper-200 pt-3">
                    <button type="button" onclick="document.getElementById('modal-edit-semua').classList.add('hidden')"
                            class="rounded-sm border border-paper-300 px-4 py-2 text-xs font-medium text-ink-700 hover:bg-paper-100">
                        Batal
                    </button>
                    <button type="submit"
                            class="rounded-sm bg-merah-500 px-5 py-2 text-xs font-semibold text-paper-50 shadow-xs hover:bg-merah-600">
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal Edit Single Record --}}
    <div id="modal-edit-single" class="fixed inset-0 z-50 flex hidden items-center justify-center bg-ink-950/70 p-4 backdrop-blur-xs">
        <div class="w-full max-w-sm rounded-sm border border-paper-300 bg-paper-50 p-6 shadow-2xl">
            <div class="flex items-center justify-between border-b border-paper-200 pb-3">
                <h3 class="font-display text-base font-semibold text-ink-900">Ubah Jumlah Populasi</h3>
                <button type="button" onclick="document.getElementById('modal-edit-single').classList.add('hidden')" class="text-ink-600 hover:text-ink-950">
                    ✕
                </button>
            </div>

            <form id="form-edit-single" method="POST" class="mt-4 space-y-4">
                @csrf
                @method('PUT')
                <div>
                    <label class="block font-mono text-xs text-ink-700">Kelompok Umur:</label>
                    <input type="text" id="edit-kelompok-umur" readonly
                           class="mt-1 w-full rounded-sm border border-paper-300 bg-paper-200 px-3 py-2 font-mono text-xs font-semibold uppercase text-ink-800">
                </div>

                <div>
                    <label class="block font-mono text-xs text-ink-700">Jumlah Penduduk (Jiwa):</label>
                    <input type="number" name="jumlah_penduduk" id="edit-jumlah-penduduk" min="0" required
                           class="mt-1 w-full rounded-sm border border-paper-300 bg-paper-50 px-3 py-2 font-mono text-sm font-semibold focus:border-merah-400 focus:outline-none">
                </div>

                <div class="flex items-center justify-end gap-2 border-t border-paper-200 pt-3">
                    <button type="button" onclick="document.getElementById('modal-edit-single').classList.add('hidden')"
                            class="rounded-sm border border-paper-300 px-4 py-2 text-xs font-medium text-ink-700 hover:bg-paper-100">
                        Batal
                    </button>
                    <button type="submit"
                            class="rounded-sm bg-merah-500 px-5 py-2 text-xs font-semibold text-paper-50 shadow-xs hover:bg-merah-600">
                        Perbarui
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openEditSingle(id, kelompokUmur, jumlah) {
            const form = document.getElementById('form-edit-single');
            form.action = "{{ url('perencanaan/demografi') }}/" + id;
            document.getElementById('edit-kelompok-umur').value = kelompokUmur;
            document.getElementById('edit-jumlah-penduduk').value = jumlah;
            document.getElementById('modal-edit-single').classList.remove('hidden');
        }
    </script>
</x-layouts.app>

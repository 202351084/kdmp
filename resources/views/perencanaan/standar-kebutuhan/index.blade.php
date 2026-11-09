    <x-layouts.app title="Standar Kebutuhan Komoditas" eyebrow="Kalkulasi — Data & Parameter">
    {{-- Navigasi kalkulasi --}}
    <x-perencanaan.nav-m2 active="parameter" subActive="standar" />

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
        $deskripsiUmur = [
            'balita' => ['label' => 'Balita', 'badge' => 'bg-padi-100 text-padi-700'],
            'anak' => ['label' => 'Anak-anak', 'badge' => 'bg-sawah-100 text-sawah-700'],
            'remaja' => ['label' => 'Remaja', 'badge' => 'bg-blue-100 text-blue-700'],
            'dewasa' => ['label' => 'Dewasa', 'badge' => 'bg-purple-100 text-purple-700'],
            'lansia' => ['label' => 'Lansia', 'badge' => 'bg-amber-100 text-amber-800'],
        ];
    @endphp

    {{-- Filter & Action Bar --}}
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4 rounded-sm border border-paper-300 bg-paper-50 p-4 shadow-2xs">
        <form method="GET" action="{{ route('perencanaan.standar-kebutuhan.index') }}" class="flex flex-wrap items-center gap-3">
            <select name="id_komoditas" class="rounded-sm border border-paper-300 bg-paper-100 px-3 py-2 text-xs font-medium text-ink-800 focus:border-merah-400 focus:outline-none">
                <option value="">Semua Komoditas</option>
                @foreach ($daftarKomoditas as $k)
                    <option value="{{ $k->id }}" {{ $idKomoditas == $k->id ? 'selected' : '' }}>{{ $k->nama }}</option>
                @endforeach
            </select>

            <select name="kelompok_umur" class="rounded-sm border border-paper-300 bg-paper-100 px-3 py-2 text-xs font-medium text-ink-800 focus:border-merah-400 focus:outline-none">
                <option value="">Semua Kelompok Umur</option>
                @foreach (['balita', 'anak', 'remaja', 'dewasa', 'lansia'] as $ku)
                    <option value="{{ $ku }}" {{ $kelompokUmur === $ku ? 'selected' : '' }}>{{ ucfirst($ku) }}</option>
                @endforeach
            </select>

            <select name="sektor" class="rounded-sm border border-paper-300 bg-paper-100 px-3 py-2 text-xs font-medium text-ink-800 focus:border-merah-400 focus:outline-none">
                <option value="">Semua Sektor</option>
                @foreach ($daftarSektor as $s)
                    <option value="{{ $s }}" {{ $sektor === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                @endforeach
            </select>

            <input
                type="text"
                name="q"
                value="{{ $search }}"
                placeholder="Cari komoditas / sumber..."
                class="w-48 rounded-sm border border-paper-300 bg-paper-100 px-3 py-2 text-xs placeholder:text-ink-600/40 focus:border-merah-400 focus:outline-none"
            >

            <button type="submit" class="rounded-sm border border-paper-300 bg-paper-200/80 px-3 py-2 text-xs font-medium text-ink-800 hover:border-merah-400 hover:text-merah-600">
                Filter
            </button>

            @if ($idKomoditas || $kelompokUmur || $sektor || $search)
                <a href="{{ route('perencanaan.standar-kebutuhan.index') }}" class="text-xs text-ink-600 hover:text-merah-600 hover:underline">
                    Reset
                </a>
            @endif
        </form>

        <button type="button"
                onclick="openTambahModal()"
                class="inline-flex items-center gap-1.5 rounded-sm bg-merah-500 px-4 py-2 text-xs font-semibold text-paper-50 shadow-xs hover:bg-merah-600">
            <span>+</span>
            <span>Tambah Standar Gizi</span>
        </button>
    </div>

    {{-- Ringkasan KPI Standar Kebutuhan --}}
    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-sm border border-paper-300 bg-paper-50 p-4 shadow-2xs">
            <div class="flex items-center justify-between">
                <p class="font-mono text-[11px] uppercase tracking-wider text-ink-600/70">Total Koefisien Standar</p>
                <span class="rounded bg-paper-200 px-1.5 py-0.5 font-mono text-[10px] text-ink-700">AKG</span>
            </div>
            <p class="mt-2 font-display text-3xl font-semibold text-ink-950">{{ $totalStandar }}</p>
            <p class="mt-1 text-xs text-ink-600">
                Koefisien gizi tersimpan dalam database
            </p>
        </div>

        <div class="rounded-sm border border-paper-300 bg-paper-50 p-4 shadow-2xs">
            <div class="flex items-center justify-between">
                <p class="font-mono text-[11px] uppercase tracking-wider text-sawah-600">Komoditas Terstandar</p>
                <span class="flex h-5 w-5 items-center justify-center rounded-full bg-sawah-500 text-xs text-paper-50 font-bold">✓</span>
            </div>
            <p class="mt-2 font-display text-3xl font-semibold text-sawah-600">{{ $jumlahKomoditasTerstandar }}</p>
            <p class="mt-1 text-xs text-ink-600">
                Komoditas siap dihitung kebutuhan konsumsinya
            </p>
        </div>

        <div class="rounded-sm border border-paper-300 bg-paper-50 p-4 shadow-2xs">
            <div class="flex items-center justify-between">
                <p class="font-mono text-[11px] uppercase tracking-wider text-padi-600">Cakupan Sektor</p>
                <span class="rounded bg-padi-100 px-1.5 py-0.5 font-mono text-[10px] font-semibold text-padi-600">Sektor</span>
            </div>
            <p class="mt-2 font-display text-3xl font-semibold text-ink-950">{{ count($daftarSektor) }}</p>
            <p class="mt-1 text-xs text-ink-600">
                {{ implode(', ', array_map('ucfirst', $daftarSektor)) ?: 'Pangan' }}
            </p>
        </div>

        <div class="rounded-sm border border-paper-300 bg-paper-50 p-4 shadow-2xs">
            <div class="flex items-center justify-between">
                <p class="font-mono text-[11px] uppercase tracking-wider text-ink-600/70">Prinsip Berversi</p>
                <span class="rounded bg-merah-500/10 px-1.5 py-0.5 font-mono text-[10px] text-merah-600 font-semibold">Audit</span>
            </div>
            <p class="mt-2 font-display text-base font-semibold text-ink-900">Histori Terlindungi</p>
            <p class="mt-1 text-xs text-ink-600">
                Revisi standar tidak mengubah snapshot periode sebelumnya
            </p>
        </div>
    </div>

    {{-- Tabel Standar Kebutuhan Komoditas --}}
    <div class="overflow-hidden rounded-sm border border-paper-300 bg-paper-50 shadow-2xs">
        <div class="border-b border-paper-200 px-5 py-4">
            <h2 class="font-display text-base font-semibold text-ink-900">Daftar Koefisien Standar Kebutuhan Gizi Per Kapita</h2>
            <p class="text-xs text-ink-600">Koefisien digunakan oleh service kalkulasi untuk menghitung volume konsumsi bulanan warga desa berdasarkan kelompok umur.</p>
        </div>

        @php
            $kelompokStandar = $items->groupBy('kelompok_umur');
        @endphp
        <div class="divide-y divide-paper-200">
            @forelse ($kelompokStandar as $kelompok => $standarUmur)
                @php
                    $kuMeta = $deskripsiUmur[$kelompok] ?? ['label' => ucfirst($kelompok), 'badge' => 'bg-paper-200 text-ink-800'];
                @endphp
                <details class="group" {{ request('kelompok_umur') === $kelompok ? 'open' : '' }}>
                    <summary class="flex cursor-pointer list-none items-center justify-between gap-4 px-5 py-4 hover:bg-paper-100/70">
                        <div class="flex items-center gap-3">
                            <span class="flex h-8 w-8 items-center justify-center rounded-full bg-paper-200 text-ink-700 transition group-open:rotate-90">›</span>
                            <div>
                                <span class="rounded px-2 py-1 font-mono text-xs font-semibold {{ $kuMeta['badge'] }}">{{ $kuMeta['label'] }}</span>
                                <p class="mt-1 text-[11px] text-ink-600">{{ $standarUmur->count() }} koefisien standar</p>
                            </div>
                        </div>
                        <span class="font-mono text-[10px] uppercase tracking-wide text-ink-500">Klik untuk melihat rincian</span>
                    </summary>
                    <div class="overflow-x-auto border-t border-paper-200 bg-paper-100/40">
                        <table class="w-full min-w-[900px] text-left text-sm">
                            <thead>
                                <tr class="border-b border-paper-200 font-mono text-[10px] uppercase tracking-wide text-ink-600/80">
                                    <th class="px-5 py-2.5 font-medium">Komoditas</th>
                                    <th class="px-5 py-2.5 font-medium">Sektor</th>
                                    <th class="px-5 py-2.5 text-right font-medium">Per Kapita</th>
                                    <th class="px-5 py-2.5 font-medium">Satuan / Periode</th>
                                    <th class="px-5 py-2.5 font-medium">Sumber Acuan</th>
                                    <th class="px-5 py-2.5 font-medium">Masa Berlaku</th>
                                    <th class="px-5 py-2.5 text-right font-medium">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                            @foreach ($standarUmur as $item)
                                @php
                                    $isAktif = (!$item->berlaku_sampai || $item->berlaku_sampai >= now());
                                @endphp
                                <tr class="border-b border-paper-200 last:border-0 hover:bg-paper-50">
                        <td class="px-5 py-3.5">
                            <span class="font-semibold text-ink-950">{{ $item->komoditas?->nama ?? '—' }}</span>
                            <span class="block text-[11px] text-ink-600">{{ $item->komoditas?->kategori ? ucwords(str_replace('_', ' ', $item->komoditas->kategori)) : '' }}</span>
                        </td>
                        <td class="px-5 py-3.5 font-mono text-xs text-ink-600">
                            {{ ucfirst($item->sektor) }}
                        </td>
                        <td class="px-5 py-3.5 text-right font-mono text-sm font-semibold text-ink-950">
                            {{ number_format((float)$item->per_kapita_harian, 6, ',', '.') }}
                        </td>
                        <td class="px-5 py-3.5">
                            <span class="font-mono text-xs font-medium text-ink-900">{{ $item->satuan }}</span>
                            <span class="rounded bg-paper-200 px-1.5 py-0.2 font-mono text-[10px] text-ink-700">/ {{ $item->satuan_periode ?? 'harian' }}</span>
                        </td>
                        <td class="px-5 py-3.5 text-xs text-ink-700">
                            {{ $item->sumber ?? 'Standar Nasional' }}
                        </td>
                        <td class="px-5 py-3.5 text-xs">
                            <div class="flex items-center gap-1.5">
                                <span class="h-2 w-2 rounded-full {{ $isAktif ? 'bg-sawah-500' : 'bg-paper-400' }}"></span>
                                <span class="font-mono text-ink-800">
                                    {{ $item->berlaku_mulai ? \Carbon\Carbon::parse($item->berlaku_mulai)->format('d/m/Y') : '—' }}
                                    @if ($item->berlaku_sampai)
                                        s/d {{ \Carbon\Carbon::parse($item->berlaku_sampai)->format('d/m/Y') }}
                                    @else
                                        <span class="text-ink-600/70">(Aktif)</span>
                                    @endif
                                </span>
                            </div>
                        </td>
                        <td class="px-5 py-3.5 text-right">
                            <div class="inline-flex items-center gap-1.5">
                                <button type="button"
                                        onclick="openEditModal({{ $item->id }}, '{{ addslashes($item->komoditas?->nama ?? '') }}', '{{ $item->sektor }}', '{{ $item->kelompok_umur }}', {{ (float)$item->per_kapita_harian }}, '{{ $item->satuan }}', '{{ $item->satuan_periode ?? 'harian' }}', '{{ addslashes($item->sumber ?? '') }}', '{{ $item->berlaku_mulai ? $item->berlaku_mulai->format('Y-m-d') : '' }}', '{{ $item->berlaku_sampai ? $item->berlaku_sampai->format('Y-m-d') : '' }}')"
                                        class="rounded-sm border border-paper-300 px-2.5 py-1 text-xs font-medium text-ink-700 hover:border-merah-400 hover:text-merah-600">
                                    Ubah
                                </button>
                                <form action="{{ route('perencanaan.standar-kebutuhan.destroy', $item->id) }}" method="POST"
                                      onsubmit="return confirm('Hapus standar kebutuhan komoditas ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="rounded-sm border border-paper-300 px-2 py-1 text-xs text-merah-600 hover:bg-merah-50">
                                        ✕
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </details>
            @empty
                <div class="px-5 py-10 text-center text-xs text-ink-600/70">
                    <p class="font-medium text-ink-800">Tidak ada standar kebutuhan gizi yang cocok dengan filter.</p>
                    <p class="mt-1">Gunakan tombol "+ Tambah Standar Gizi" di atas untuk menambahkan standar baru.</p>
                </div>
            @endforelse
        </div>
    </div>

    <div class="mt-4 rounded-sm border border-blue-200 bg-blue-50 px-4 py-3 text-xs leading-relaxed text-blue-900">
        <p class="font-semibold">Penjelasan singkat</p>
        <p class="mt-1">
            Setiap kelompok umur hanya ditampilkan satu kali. Klik kelompok umur untuk melihat seluruh koefisien komoditas,
            sektor, satuan, periode, dan masa berlakunya. Koefisien digunakan untuk menghitung kebutuhan konsumsi warga
            berdasarkan jumlah penduduk pada kelompok umur tersebut.
        </p>
    </div>

    {{-- Modal Tambah Standar Gizi --}}
    <div id="modal-tambah" class="fixed inset-0 z-50 flex hidden items-center justify-center bg-ink-950/70 p-4 backdrop-blur-xs">
        <div class="w-full max-w-lg rounded-sm border border-paper-300 bg-paper-50 p-6 shadow-2xl">
            <div class="flex items-center justify-between border-b border-paper-200 pb-3">
                <div>
                    <h3 class="font-display text-base font-semibold text-ink-900">Tambah Standar Kebutuhan Gizi Komoditas</h3>
                    <p class="text-xs text-ink-600">Parameter konsumsi harian per kapita</p>
                </div>
                <button type="button" onclick="document.getElementById('modal-tambah').classList.add('hidden')" class="text-ink-600 hover:text-ink-950">
                    ✕
                </button>
            </div>

            <form action="{{ route('perencanaan.standar-kebutuhan.store') }}" method="POST" class="mt-4 space-y-4">
                @csrf
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-mono text-xs text-ink-700">Komoditas:</label>
                        <select name="id_komoditas" required class="mt-1 w-full rounded-sm border border-paper-300 bg-paper-100 px-3 py-2 text-xs font-medium focus:border-merah-400 focus:outline-none">
                            <option value="">-- Pilih Komoditas --</option>
                            @foreach ($daftarKomoditas as $k)
                                <option value="{{ $k->id }}" {{ $idKomoditas == $k->id ? 'selected' : '' }}>{{ $k->nama }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block font-mono text-xs text-ink-700">Sektor:</label>
                        <input type="text" name="sektor" value="pangan" required
                               class="mt-1 w-full rounded-sm border border-paper-300 bg-paper-100 px-3 py-2 text-xs font-medium focus:border-merah-400 focus:outline-none">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-mono text-xs text-ink-700">Kelompok Umur:</label>
                        <select name="kelompok_umur" required class="mt-1 w-full rounded-sm border border-paper-300 bg-paper-100 px-3 py-2 text-xs font-medium focus:border-merah-400 focus:outline-none">
                            @foreach (['balita', 'anak', 'remaja', 'dewasa', 'lansia'] as $ku)
                                <option value="{{ $ku }}">{{ ucfirst($ku) }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block font-mono text-xs text-ink-700">Satuan Periode:</label>
                        <select name="satuan_periode" required class="mt-1 w-full rounded-sm border border-paper-300 bg-paper-100 px-3 py-2 text-xs font-medium focus:border-merah-400 focus:outline-none">
                            <option value="harian">Harian (dikalikan hari bulan)</option>
                            <option value="bulanan">Bulanan (koefisien tetap)</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-mono text-xs text-ink-700">Per Kapita (Standar Gizi):</label>
                        <input type="number" step="0.000001" min="0" name="per_kapita_harian" required placeholder="Contoh: 0.300000"
                               class="mt-1 w-full rounded-sm border border-paper-300 bg-paper-50 px-3 py-2 font-mono text-xs font-semibold focus:border-merah-400 focus:outline-none">
                    </div>

                    <div>
                        <label class="block font-mono text-xs text-ink-700">Satuan Barang:</label>
                        <select name="satuan" required class="mt-1 w-full rounded-sm border border-paper-300 bg-paper-100 px-3 py-2 text-xs font-medium focus:border-merah-400 focus:outline-none">
                            @foreach ($daftarSatuan as $sat)
                                <option value="{{ $sat }}">{{ $sat }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block font-mono text-xs text-ink-700">Sumber Acuan:</label>
                    <input type="text" name="sumber" placeholder="Contoh: AKG Kemenkes 2026 / BPS"
                           class="mt-1 w-full rounded-sm border border-paper-300 bg-paper-100 px-3 py-2 text-xs focus:border-merah-400 focus:outline-none">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-mono text-xs text-ink-700">Berlaku Mulai:</label>
                        <input type="date" name="berlaku_mulai" value="{{ date('Y-01-01') }}" required
                               class="mt-1 w-full rounded-sm border border-paper-300 bg-paper-100 px-3 py-2 text-xs focus:border-merah-400 focus:outline-none">
                    </div>

                    <div>
                        <label class="block font-mono text-xs text-ink-700">Berlaku Sampai (Opsional):</label>
                        <input type="date" name="berlaku_sampai"
                               class="mt-1 w-full rounded-sm border border-paper-300 bg-paper-100 px-3 py-2 text-xs focus:border-merah-400 focus:outline-none">
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 border-t border-paper-200 pt-3">
                    <button type="button" onclick="document.getElementById('modal-tambah').classList.add('hidden')"
                            class="rounded-sm border border-paper-300 px-4 py-2 text-xs font-medium text-ink-700 hover:bg-paper-100">
                        Batal
                    </button>
                    <button type="submit"
                            class="rounded-sm bg-merah-500 px-5 py-2 text-xs font-semibold text-paper-50 shadow-xs hover:bg-merah-600">
                        Simpan Standar
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal Edit Standar Gizi --}}
    <div id="modal-edit" class="fixed inset-0 z-50 flex hidden items-center justify-center bg-ink-950/70 p-4 backdrop-blur-xs">
        <div class="w-full max-w-lg rounded-sm border border-paper-300 bg-paper-50 p-6 shadow-2xl">
            <div class="flex items-center justify-between border-b border-paper-200 pb-3">
                <h3 class="font-display text-base font-semibold text-ink-900">Ubah Standar Kebutuhan Gizi</h3>
                <button type="button" onclick="document.getElementById('modal-edit').classList.add('hidden')" class="text-ink-600 hover:text-ink-950">
                    ✕
                </button>
            </div>

            <form id="form-edit" method="POST" class="mt-4 space-y-4">
                @csrf
                @method('PUT')
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-mono text-xs text-ink-700">Komoditas &amp; Usia:</label>
                        <input type="text" id="edit-komoditas-info" readonly
                               class="mt-1 w-full rounded-sm border border-paper-300 bg-paper-200 px-3 py-2 font-mono text-xs font-semibold text-ink-800">
                    </div>
                    <div>
                        <label class="block font-mono text-xs text-ink-700">Sektor:</label>
                        <input type="text" name="sektor" id="edit-sektor" required
                               class="mt-1 w-full rounded-sm border border-paper-300 bg-paper-100 px-3 py-2 text-xs font-medium focus:border-merah-400 focus:outline-none">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-mono text-xs text-ink-700">Per Kapita Standar:</label>
                        <input type="number" step="0.000001" min="0" name="per_kapita_harian" id="edit-per-kapita" required
                               class="mt-1 w-full rounded-sm border border-paper-300 bg-paper-50 px-3 py-2 font-mono text-xs font-semibold focus:border-merah-400 focus:outline-none">
                    </div>

                    <div>
                        <label class="block font-mono text-xs text-ink-700">Satuan:</label>
                        <input type="text" name="satuan" id="edit-satuan" required
                               class="mt-1 w-full rounded-sm border border-paper-300 bg-paper-50 px-3 py-2 font-mono text-xs uppercase focus:border-merah-400 focus:outline-none">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-mono text-xs text-ink-700">Satuan Periode:</label>
                        <select name="satuan_periode" id="edit-satuan-periode" required class="mt-1 w-full rounded-sm border border-paper-300 bg-paper-100 px-3 py-2 text-xs font-medium focus:border-merah-400 focus:outline-none">
                            <option value="harian">Harian</option>
                            <option value="bulanan">Bulanan</option>
                        </select>
                    </div>

                    <div>
                        <label class="block font-mono text-xs text-ink-700">Sumber Acuan:</label>
                        <input type="text" name="sumber" id="edit-sumber"
                               class="mt-1 w-full rounded-sm border border-paper-300 bg-paper-100 px-3 py-2 text-xs focus:border-merah-400 focus:outline-none">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-mono text-xs text-ink-700">Berlaku Mulai:</label>
                        <input type="date" name="berlaku_mulai" id="edit-berlaku-mulai" required
                               class="mt-1 w-full rounded-sm border border-paper-300 bg-paper-100 px-3 py-2 text-xs focus:border-merah-400 focus:outline-none">
                    </div>

                    <div>
                        <label class="block font-mono text-xs text-ink-700">Berlaku Sampai:</label>
                        <input type="date" name="berlaku_sampai" id="edit-berlaku-sampai"
                               class="mt-1 w-full rounded-sm border border-paper-300 bg-paper-100 px-3 py-2 text-xs focus:border-merah-400 focus:outline-none">
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 border-t border-paper-200 pt-3">
                    <button type="button" onclick="document.getElementById('modal-edit').classList.add('hidden')"
                            class="rounded-sm border border-paper-300 px-4 py-2 text-xs font-medium text-ink-700 hover:bg-paper-100">
                        Batal
                    </button>
                    <button type="submit"
                            class="rounded-sm bg-merah-500 px-5 py-2 text-xs font-semibold text-paper-50 shadow-xs hover:bg-merah-600">
                        Perbarui Standar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openTambahModal() {
            document.getElementById('modal-tambah').classList.remove('hidden');
        }

        function openEditModal(id, namaKomoditas, sektor, kelompokUmur, perKapita, satuan, satuanPeriode, sumber, berlakuMulai, berlakuSampai) {
            const form = document.getElementById('form-edit');
            form.action = "{{ url('perencanaan/standar-kebutuhan') }}/" + id;
            document.getElementById('edit-komoditas-info').value = namaKomoditas + ' (' + kelompokUmur.toUpperCase() + ')';
            document.getElementById('edit-sektor').value = sektor;
            document.getElementById('edit-per-kapita').value = perKapita;
            document.getElementById('edit-satuan').value = satuan;
            document.getElementById('edit-satuan-periode').value = satuanPeriode;
            document.getElementById('edit-sumber').value = sumber;
            document.getElementById('edit-berlaku-mulai').value = berlakuMulai;
            document.getElementById('edit-berlaku-sampai').value = berlakuSampai;
            document.getElementById('modal-edit').classList.remove('hidden');
        }
    </script>
</x-layouts.app>

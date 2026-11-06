<x-layouts.app title="Potensi Produksi Panen" eyebrow="Kalkulasi — Data & Parameter">
    {{-- Navigasi kalkulasi --}}
    <x-perencanaan.nav-m2 active="parameter" subActive="potensi" />

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

    {{-- Filter & Action Bar --}}
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4 rounded-sm border border-paper-300 bg-paper-50 p-4 shadow-2xs">
        <form method="GET" action="{{ route('perencanaan.potensi-produksi.index') }}" class="flex flex-wrap items-center gap-3">
            <div class="flex items-center gap-1.5 rounded-sm border border-paper-300 bg-paper-100 px-3 py-2 text-xs font-medium text-ink-800">
                <span class="font-mono text-[10px] uppercase text-ink-600/70">Desa:</span>
                <span class="font-semibold text-ink-950">{{ $wilayah?->nama ?? 'Desa Terdaftar' }}</span>
            </div>

            <select name="tahun" class="rounded-sm border border-paper-300 bg-paper-100 px-3 py-2 text-xs font-medium text-ink-800 focus:border-merah-400 focus:outline-none">
                @foreach (range(2025, 2030) as $t)
                    <option value="{{ $t }}" {{ $tahun == $t ? 'selected' : '' }}>Tahun {{ $t }}</option>
                @endforeach
            </select>

            <select name="bulan" class="rounded-sm border border-paper-300 bg-paper-100 px-3 py-2 text-xs font-medium text-ink-800 focus:border-merah-400 focus:outline-none">
                <option value="">Semua Bulan Panen</option>
                @foreach ($namaBulanList as $num => $nama)
                    <option value="{{ $num }}" {{ $bulan == $num ? 'selected' : '' }}>{{ $nama }}</option>
                @endforeach
            </select>

            <select name="id_komoditas" class="rounded-sm border border-paper-300 bg-paper-100 px-3 py-2 text-xs font-medium text-ink-800 focus:border-merah-400 focus:outline-none">
                <option value="">Semua Komoditas</option>
                @foreach ($daftarKomoditas as $k)
                    <option value="{{ $k->id }}" {{ $idKomoditas == $k->id ? 'selected' : '' }}>{{ $k->nama }}</option>
                @endforeach
            </select>

            <input
                type="text"
                name="q"
                value="{{ $search }}"
                placeholder="Cari komoditas..."
                class="w-44 rounded-sm border border-paper-300 bg-paper-100 px-3 py-2 text-xs placeholder:text-ink-600/40 focus:border-merah-400 focus:outline-none"
            >

            <button type="submit" class="rounded-sm border border-paper-300 bg-paper-200/80 px-3 py-2 text-xs font-medium text-ink-800 hover:border-merah-400 hover:text-merah-600">
                Filter
            </button>

            @if ($bulan || $idKomoditas || $search)
                <a href="{{ route('perencanaan.potensi-produksi.index', ['tahun' => $tahun]) }}" class="text-xs text-ink-600 hover:text-merah-600 hover:underline">
                    Reset
                </a>
            @endif
        </form>

        <div class="flex items-center gap-2">
            <a href="{{ route('survei.sesi.index') }}"
               class="inline-flex items-center gap-1.5 rounded-sm border border-paper-300 bg-paper-100 px-3 py-2 text-xs font-medium text-ink-700 hover:border-merah-400">
                <span>🌾</span>
                <span>Survei Panen</span>
            </a>

            <button type="button"
                    onclick="openTambahModal()"
                    class="inline-flex items-center gap-1.5 rounded-sm bg-merah-500 px-4 py-2 text-xs font-semibold text-paper-50 shadow-xs hover:bg-merah-600">
                <span>+</span>
                <span>Catat Potensi Panen</span>
            </button>
        </div>
    </div>

    {{-- Ringkasan KPI Potensi Produksi --}}
    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-sm border border-paper-300 bg-paper-50 p-4 shadow-2xs">
            <div class="flex items-center justify-between">
                <p class="font-mono text-[11px] uppercase tracking-wider text-ink-600/70">Catatan Panen</p>
                <span class="rounded bg-paper-200 px-1.5 py-0.5 font-mono text-[10px] text-ink-700">Survei/Kalkulasi</span>
            </div>
            <p class="mt-2 font-display text-3xl font-semibold text-ink-950">{{ $totalCatatan }}</p>
            <p class="mt-1 text-xs text-ink-600">
                Periode: {{ $bulan ? $namaBulanList[$bulan] . ' ' : '' }}{{ $tahun }}
            </p>
        </div>

        <div class="rounded-sm border border-paper-300 bg-paper-50 p-4 shadow-2xs">
            <div class="flex items-center justify-between">
                <p class="font-mono text-[11px] uppercase tracking-wider text-sawah-600">Komoditas Panen</p>
                <span class="flex h-5 w-5 items-center justify-center rounded-full bg-sawah-500 text-xs text-paper-50 font-bold">🌾</span>
            </div>
            <p class="mt-2 font-display text-3xl font-semibold text-sawah-600">{{ $jumlahKomoditas }}</p>
            <p class="mt-1 text-xs text-ink-600">
                Komoditas lokal menghasilkan panen
            </p>
        </div>

        <div class="rounded-sm border border-paper-300 bg-paper-50 p-4 shadow-2xs">
            <div class="flex items-center justify-between">
                <p class="font-mono text-[11px] uppercase tracking-wider text-padi-600">Total Volume Produksi</p>
                <span class="rounded bg-padi-100 px-1.5 py-0.5 font-mono text-[10px] font-semibold text-padi-600">Panen</span>
            </div>
            <p class="mt-2 font-display text-2xl font-semibold text-ink-950">
                {{ number_format($totalVolume, 2, ',', '.') }}
            </p>
            <p class="mt-1 text-xs text-ink-600">
                Akumulasi seluruh komoditas
            </p>
        </div>

        <div class="rounded-sm border border-paper-300 bg-paper-50 p-4 shadow-2xs">
            <div class="flex items-center justify-between">
                <p class="font-mono text-[11px] uppercase tracking-wider text-ink-600/70">Peran Dalam Kalkulasi</p>
                <span class="rounded bg-merah-500/10 px-1.5 py-0.5 font-mono text-[10px] text-merah-600 font-semibold">Neraca</span>
            </div>
            <p class="mt-2 font-display text-base font-semibold text-ink-900">Total Ketersediaan</p>
            <p class="mt-1 text-xs text-ink-600">
                Menjadi pengurang kebutuhan konsumsi di neraca
            </p>
        </div>
    </div>

    {{-- Tabel Catatan Potensi Produksi Panen --}}
    <div class="overflow-hidden rounded-sm border border-paper-300 bg-paper-50 shadow-2xs">
        <div class="border-b border-paper-200 px-5 py-4">
            <h2 class="font-display text-base font-semibold text-ink-900">Kalender Potensi Produksi &amp; Panen Komoditas</h2>
            <p class="text-xs text-ink-600">Data panen dicatat berdasarkan bulan terjadinya panen riil desa (padi tidak panen 12 kali setahun).</p>
        </div>

        <table class="w-full text-left text-sm">
            <thead>
                <tr class="border-b border-paper-300 bg-paper-200/70 font-mono text-[11px] uppercase tracking-wide text-ink-600/80">
                    <th class="px-5 py-3 font-medium">Komoditas</th>
                    <th class="px-5 py-3 font-medium">Kategori</th>
                    <th class="px-5 py-3 font-medium">Bulan Panen</th>
                    <th class="px-5 py-3 text-right font-medium">Volume Produksi</th>
                    <th class="px-5 py-3 font-medium">Satuan</th>
                    <th class="px-5 py-3 font-medium">Sumber Data</th>
                    <th class="px-5 py-3 text-right font-medium">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($items as $item)
                    <tr class="border-b border-paper-200 last:border-0 hover:bg-paper-100/70">
                        <td class="px-5 py-3.5">
                            <span class="font-semibold text-ink-950">{{ $item->komoditas?->nama ?? '—' }}</span>
                        </td>
                        <td class="px-5 py-3.5 font-mono text-xs text-ink-600">
                            {{ $item->komoditas?->kategori ? ucwords(str_replace('_', ' ', $item->komoditas->kategori)) : '—' }}
                        </td>
                        <td class="px-5 py-3.5">
                            <span class="rounded bg-paper-200 px-2 py-0.5 font-mono text-xs font-medium text-ink-800">
                                {{ $namaBulanList[$item->bulan] ?? "Bulan {$item->bulan}" }} {{ $item->tahun }}
                            </span>
                        </td>
                        <td class="px-5 py-3.5 text-right font-mono text-sm font-semibold text-sawah-600">
                            {{ number_format((float)$item->jumlah_produksi, 2, ',', '.') }}
                        </td>
                        <td class="px-5 py-3.5 font-mono text-xs text-ink-600">
                            {{ $item->satuan }}
                        </td>
                        <td class="px-5 py-3.5 text-xs text-ink-600">
                            @if ($item->sesiSurvei)
                                <a href="{{ route('survei.sesi.show', $item->id_sesi_survei) }}" class="inline-flex items-center gap-1 text-merah-600 hover:underline">
                                    <span>Survei Sesi #{{ $item->id_sesi_survei }}</span>
                                    <span>&rarr;</span>
                                </a>
                            @else
                                <span class="text-ink-600/70">Input Mandiri untuk Kalkulasi</span>
                            @endif
                        </td>
                        <td class="px-5 py-3.5 text-right">
                            <div class="inline-flex items-center gap-1.5">
                                <button type="button"
                                        onclick="openEditModal({{ $item->id }}, '{{ addslashes($item->komoditas?->nama ?? '') }}', '{{ $item->komoditas?->kategori ?? 'pertanian' }}', {{ $item->bulan }}, {{ (float)$item->jumlah_produksi }}, '{{ $item->satuan }}')"
                                        class="rounded-sm border border-paper-300 px-2.5 py-1 text-xs font-medium text-ink-700 hover:border-merah-400 hover:text-merah-600">
                                    Ubah
                                </button>
                                <form action="{{ route('perencanaan.potensi-produksi.destroy', $item->id) }}" method="POST"
                                      onsubmit="return confirm('Hapus catatan potensi produksi panen ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="rounded-sm border border-paper-300 px-2 py-1 text-xs text-merah-600 hover:bg-merah-50">
                                        ✕
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-5 py-10 text-center text-xs text-ink-600/70">
                            <p class="font-medium text-ink-800">Belum ada catatan potensi panen untuk filter ini.</p>
                            <p class="mt-1">Gunakan tombol "+ Catat Potensi Panen" di atas atau sinkronkan hasil survei.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $items->links() }}
    </div>

    {{-- Modal Tambah Potensi Panen --}}
    <div id="modal-tambah" class="fixed inset-0 z-50 flex hidden items-center justify-center bg-ink-950/70 p-4 backdrop-blur-xs">
        <div class="w-full max-w-md rounded-sm border border-paper-300 bg-paper-50 p-6 shadow-2xl">
            <div class="flex items-center justify-between border-b border-paper-200 pb-3">
                <div>
                    <h3 class="font-display text-base font-semibold text-ink-900">Catat Potensi Produksi Panen</h3>
                    <p class="text-xs text-ink-600">Desa: <strong>{{ $wilayah?->nama }}</strong></p>
                </div>
                <button type="button" onclick="document.getElementById('modal-tambah').classList.add('hidden')" class="text-ink-600 hover:text-ink-950">
                    ✕
                </button>
            </div>

            <form action="{{ route('perencanaan.potensi-produksi.store') }}" method="POST" class="mt-4 space-y-4">
                @csrf
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-mono text-xs text-ink-700">Tahun:</label>
                        <input type="number" name="tahun" value="{{ $tahun }}" min="2020" max="2100" required
                               class="mt-1 w-full rounded-sm border border-paper-300 bg-paper-100 px-3 py-2 text-xs font-medium focus:border-merah-400 focus:outline-none">
                    </div>

                    <div>
                        <label class="block font-mono text-xs text-ink-700">Bulan Panen:</label>
                        <select name="bulan" required class="mt-1 w-full rounded-sm border border-paper-300 bg-paper-100 px-3 py-2 text-xs font-medium focus:border-merah-400 focus:outline-none">
                            @foreach ($namaBulanList as $num => $nama)
                                <option value="{{ $num }}" {{ ($bulan ?? 1) == $num ? 'selected' : '' }}>{{ $nama }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block font-mono text-xs text-ink-700">Komoditas:</label>
                    <select id="potensi-komoditas" name="id_komoditas" required class="mt-1 w-full rounded-sm border border-paper-300 bg-paper-100 px-3 py-2 text-xs font-medium focus:border-merah-400 focus:outline-none">
                        <option value="">-- Pilih Komoditas --</option>
                        @foreach ($daftarKomoditas as $k)
                            <option value="{{ $k->id }}">{{ $k->nama }} ({{ $k->kategori }})</option>
                        @endforeach
                    </select>
                    <button type="button" onclick="openKomoditasBaru()" class="mt-2 text-xs font-semibold text-merah-600 hover:underline">
                        + Komoditas Baru
                    </button>
                    <div id="komoditas-baru" class="mt-2 hidden rounded-sm border border-paper-200 bg-paper-100 p-3">
                        <div class="grid grid-cols-2 gap-2">
                            <input id="komoditas-baru-nama" type="text" maxlength="150" placeholder="Nama komoditas"
                                   class="rounded-sm border border-paper-300 bg-paper-50 px-2 py-2 text-xs">
                            <select id="komoditas-baru-kategori" class="rounded-sm border border-paper-300 bg-paper-50 px-2 py-2 text-xs">
                                <option value="pertanian">Pertanian</option>
                                <option value="perkebunan">Perkebunan</option>
                                <option value="peternakan">Peternakan</option>
                                <option value="perikanan">Perikanan</option>
                            </select>
                        </div>
                        <button type="button" onclick="simpanKomoditasBaru()" class="mt-2 rounded-sm bg-sawah-600 px-3 py-2 text-xs font-semibold text-paper-50">
                            Simpan &amp; Pilih Komoditas
                        </button>
                        <p id="komoditas-baru-error" class="mt-2 hidden text-xs text-merah-600"></p>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-mono text-xs text-ink-700">Jumlah Panen:</label>
                        <input type="number" step="0.0001" min="0" name="jumlah_produksi" required placeholder="Contoh: 15000"
                               class="mt-1 w-full rounded-sm border border-paper-300 bg-paper-50 px-3 py-2 font-mono text-xs font-semibold focus:border-merah-400 focus:outline-none">
                    </div>

                    <div>
                        <label class="block font-mono text-xs text-ink-700">Satuan:</label>
                        <select name="satuan" required class="mt-1 w-full rounded-sm border border-paper-300 bg-paper-100 px-3 py-2 text-xs font-medium focus:border-merah-400 focus:outline-none">
                            @foreach ($daftarSatuan as $sat)
                                <option value="{{ $sat }}">{{ $sat }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 border-t border-paper-200 pt-3">
                    <button type="button" onclick="document.getElementById('modal-tambah').classList.add('hidden')"
                            class="rounded-sm border border-paper-300 px-4 py-2 text-xs font-medium text-ink-700 hover:bg-paper-100">
                        Batal
                    </button>
                    <button type="submit"
                            class="rounded-sm bg-merah-500 px-5 py-2 text-xs font-semibold text-paper-50 shadow-xs hover:bg-merah-600">
                        Simpan Data
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal Edit Single Record --}}
    <div id="modal-edit" class="fixed inset-0 z-50 flex hidden items-center justify-center bg-ink-950/70 p-4 backdrop-blur-xs">
        <div class="w-full max-w-sm rounded-sm border border-paper-300 bg-paper-50 p-6 shadow-2xl">
            <div class="flex items-center justify-between border-b border-paper-200 pb-3">
                <h3 class="font-display text-base font-semibold text-ink-900">Ubah Produksi Panen</h3>
                <button type="button" onclick="document.getElementById('modal-edit').classList.add('hidden')" class="text-ink-600 hover:text-ink-950">
                    ✕
                </button>
            </div>

            <form id="form-edit" method="POST" class="mt-4 space-y-4">
                @csrf
                @method('PUT')
                <div>
                    <label class="block font-mono text-xs text-ink-700">Komoditas &amp; Bulan:</label>
                    <input type="text" id="edit-nama-komoditas" readonly
                           class="mt-1 w-full rounded-sm border border-paper-300 bg-paper-200 px-3 py-2 font-mono text-xs font-semibold text-ink-800">
                </div>

                <div>
                    <label class="block font-mono text-xs text-ink-700">Sektor:</label>
                    <select name="kategori" id="edit-kategori" required
                            class="mt-1 w-full rounded-sm border border-paper-300 bg-paper-100 px-3 py-2 text-xs font-medium focus:border-merah-400 focus:outline-none">
                        <option value="pertanian">Pertanian</option>
                        <option value="perkebunan">Perkebunan</option>
                        <option value="peternakan">Peternakan</option>
                        <option value="perikanan">Perikanan</option>
                    </select>
                </div>

                <div>
                    <label class="block font-mono text-xs text-ink-700">Jumlah Panen:</label>
                    <input type="number" step="0.0001" min="0" name="jumlah_produksi" id="edit-jumlah-produksi" required
                           class="mt-1 w-full rounded-sm border border-paper-300 bg-paper-50 px-3 py-2 font-mono text-sm font-semibold focus:border-merah-400 focus:outline-none">
                </div>

                <div>
                    <label class="block font-mono text-xs text-ink-700">Satuan:</label>
                    <input type="text" name="satuan" id="edit-satuan" required
                           class="mt-1 w-full rounded-sm border border-paper-300 bg-paper-50 px-3 py-2 font-mono text-xs uppercase focus:border-merah-400 focus:outline-none">
                </div>

                <div class="flex items-center justify-end gap-2 border-t border-paper-200 pt-3">
                    <button type="button" onclick="document.getElementById('modal-edit').classList.add('hidden')"
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
        function openTambahModal() {
            document.getElementById('modal-tambah').classList.remove('hidden');
        }

        function openKomoditasBaru() {
            document.getElementById('komoditas-baru').classList.toggle('hidden');
            document.getElementById('komoditas-baru-nama').focus();
        }

        async function simpanKomoditasBaru() {
            const nama = document.getElementById('komoditas-baru-nama').value.trim();
            const kategori = document.getElementById('komoditas-baru-kategori').value;
            const error = document.getElementById('komoditas-baru-error');
            error.classList.add('hidden');
            if (!nama) {
                error.textContent = 'Nama komoditas wajib diisi.';
                error.classList.remove('hidden');
                return;
            }

            const response = await fetch('{{ route('perencanaan.potensi-produksi.komoditas.store') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({ nama, kategori })
            });
            const data = await response.json();
            if (!response.ok) {
                error.textContent = data.message || 'Komoditas gagal disimpan.';
                error.classList.remove('hidden');
                return;
            }

            const select = document.getElementById('potensi-komoditas');
            let option = Array.from(select.options).find(item => item.value === String(data.id));
            if (!option) {
                option = new Option(`${data.nama} (${data.kategori})`, data.id);
                select.add(option);
            }
            select.value = data.id;
            document.getElementById('komoditas-baru').classList.add('hidden');
            document.getElementById('komoditas-baru-nama').value = '';
        }

        function openEditModal(id, namaKomoditas, kategori, bulan, jumlah, satuan) {
            const form = document.getElementById('form-edit');
            form.action = "{{ url('perencanaan/potensi-produksi') }}/" + id;
            const namaBulanMap = {
                1: 'Januari', 2: 'Februari', 3: 'Maret', 4: 'April',
                5: 'Mei', 6: 'Juni', 7: 'Juli', 8: 'Agustus',
                9: 'September', 10: 'Oktober', 11: 'November', 12: 'Desember'
            };
            document.getElementById('edit-nama-komoditas').value = namaKomoditas + ' (' + (namaBulanMap[bulan] || 'Bulan ' + bulan) + ')';
            document.getElementById('edit-kategori').value = kategori;
            document.getElementById('edit-jumlah-produksi').value = jumlah;
            document.getElementById('edit-satuan').value = satuan;
            document.getElementById('modal-edit').classList.remove('hidden');
        }
    </script>
</x-layouts.app>

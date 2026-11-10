<x-layouts.app title="Master Data Produsen" eyebrow="Manajemen Survei">
    {{-- ── Header ── --}}
    <div class="mb-5 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="text-sm text-ink-500">Kelola nama anggota Kelompok Tani dan Pelaku Ekraf.</p>
        </div>
        <div class="flex items-center gap-3 flex-wrap">
            <a href="{{ route('survei.dashboard.index') }}"
               class="flex items-center gap-1.5 rounded-sm border border-paper-300 bg-white px-4 py-2 text-sm font-semibold text-ink-700 shadow-sm hover:bg-paper-100 whitespace-nowrap">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Kembali
            </a>
            <a href="{{ route('survei.produsen.create') }}"
               class="flex items-center gap-1.5 rounded-sm bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-700 whitespace-nowrap">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Tambah Produsen
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="mb-4 rounded-sm bg-sawah-100 p-4 border border-sawah-200 text-sawah-800 text-sm">{{ session('success') }}</div>
    @endif

    {{-- ── Master Data Anggota ── --}}
    <div class="mb-6 overflow-hidden rounded-sm border border-paper-300 bg-white shadow-sm">
        <div class="px-6 py-4 border-b border-paper-200 bg-paper-50">
            <h2 class="font-display text-base font-semibold text-ink-900">Daftar Anggota / Panitia</h2>
            <p class="text-xs text-ink-500 mt-0.5">Data master nama Kelompok Tani &amp; Ekraf yang terdaftar di desa ini.</p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-ink-500">
                <thead class="border-b border-paper-300 bg-paper-50 text-xs uppercase text-ink-700">
                    <tr>
                        <th class="px-6 py-4 font-medium">No</th>
                        <th class="px-6 py-4 font-medium">Desa / Wilayah</th>
                        <th class="px-6 py-4 font-medium">Kategori</th>
                        <th class="px-6 py-4 font-medium">Nama Anggota</th>
                        <th class="px-6 py-4 font-medium">No. WA</th>
                        <th class="px-6 py-4 font-medium text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-paper-200">
                    @forelse ($produsens as $index => $produsen)
                        <tr class="hover:bg-paper-50">
                            <td class="px-6 py-4">{{ $produsens->firstItem() + $index }}</td>
                            <td class="px-6 py-4 font-medium text-ink-900">{{ $produsen->wilayah->nama ?? '-' }}</td>
                            <td class="px-6 py-4">
                                <span class="rounded-sm px-2 py-1 text-xs font-semibold {{ $produsen->kategori == 'Kelompok Tani' ? 'bg-green-50 text-green-700' : 'bg-purple-50 text-purple-700' }}">
                                    {{ $produsen->kategori }}
                                </span>
                            </td>
                            <td class="px-6 py-4 font-medium text-ink-800">{{ $produsen->nama_anggota }}</td>
                            <td class="px-6 py-4 text-ink-600">
                                @if($produsen->no_wa)
                                    <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $produsen->no_wa) }}" target="_blank" class="hover:text-green-600 hover:underline flex items-center gap-1">
                                        <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M12.031 0C5.397 0 .012 5.385.012 12.02c0 2.124.553 4.195 1.603 6.01L.004 24l6.103-1.602c1.745.962 3.733 1.47 5.924 1.47 6.634 0 12.019-5.385 12.019-12.02S18.665 0 12.031 0zM17.4 17.072c-.22.617-1.282 1.185-1.782 1.258-.456.066-1.047.16-3.328-.786-2.735-1.135-4.505-3.92-4.636-4.095-.13-.174-1.107-1.476-1.107-2.812 0-1.336.687-1.996.93-2.261.242-.266.527-.333.7-.333.172 0 .346 0 .49.006.155.006.362-.057.568.443.212.51.722 1.767.788 1.897.065.13.107.283.02.457-.087.174-.13.284-.26.435-.13.15-.276.333-.39.444-.128.118-.266.248-.12.502.147.253.654 1.08 1.402 1.752.968.87 1.766 1.135 2.01 1.265.242.13.385.108.528-.06.143-.166.617-.716.78-9.61.163-.245.327-.202.556-.12.23.082 1.448.683 1.696.807.247.123.412.184.472.288.06.104.06.602-.16 1.22z"/></svg>
                                        {{ $produsen->no_wa }}
                                    </a>
                                @else
                                    <span class="text-ink-400">-</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-center">
                                <div class="flex justify-center gap-2">
                                    <a href="{{ route('survei.produsen.edit', $produsen->id) }}"
                                       class="rounded-sm bg-blue-50 px-2 py-1 text-xs font-semibold text-blue-600 hover:bg-blue-100">Edit</a>
                                    <form action="{{ route('survei.produsen.destroy', $produsen->id) }}" method="POST"
                                          class="inline-block" onsubmit="return confirm('Yakin ingin menghapus data ini?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="rounded-sm bg-merah-50 px-2 py-1 text-xs font-semibold text-merah-600 hover:bg-merah-100">Hapus</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-8 text-center text-ink-500">Belum ada data Produsen.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($produsens->hasPages())
            <div class="border-t border-paper-300 bg-white px-6 py-4">
                {{ $produsens->links() }}
            </div>
        @endif
    </div>

    {{-- ── Pilih Periode Survei ── --}}
    <div class="mb-6 rounded-sm border border-paper-300 bg-white shadow-sm p-5">
        <h2 class="font-display text-sm font-semibold text-ink-900 mb-3">Tautan &amp; Data Survei per Periode</h2>
        <form method="GET" action="{{ route('survei.produsen.index') }}" class="flex flex-col sm:flex-row gap-3 items-start sm:items-end">
            <div class="flex-1 w-full">
                <label class="block text-xs font-semibold text-ink-600 uppercase tracking-wide mb-1">Pilih Periode</label>
                <select name="sesi_id" class="w-full rounded-sm border border-paper-300 bg-white py-2 px-3 text-sm focus:border-merah-400 focus:outline-none">
                    <option value="">-- Pilih Bulan / Tahun --</option>
                    @foreach($daftarSesi as $s)
                        @php
                            $isAktifSesi = ($s->bulan == (int) date('n') && $s->tahun == (int) date('Y'));
                        @endphp
                        <option value="{{ $s->id }}" {{ request('sesi_id') == $s->id ? 'selected' : '' }}>
                            {{ \Carbon\Carbon::createFromFormat('m', $s->bulan)->translatedFormat('F') }} {{ $s->tahun }}
                            — {{ $s->wilayah->nama ?? '-' }}
                            ({{ $isAktifSesi ? 'Sedang Berlangsung' : 'Berakhir' }})
                        </option>
                    @endforeach
                </select>
            </div>
            <button type="submit"
                    class="shrink-0 rounded-sm bg-blue-600 px-5 py-2 text-sm font-semibold text-white hover:bg-blue-700 shadow-sm">
                Lihat Data
            </button>
        </form>
    </div>

    {{-- ── Tampil setelah periode dipilih ── --}}
    @if($sesiDipilih)
        @php
            $isAktif = ($sesiDipilih->bulan == (int) date('n') && $sesiDipilih->tahun == (int) date('Y'));
        @endphp

        @if($isAktif)
            {{-- Link Survei (Hanya tampil jika periode masih berlangsung) --}}
            <div class="mb-6 rounded-sm border border-green-200 bg-green-50 p-5">
                <div>
                    <h2 class="text-sm font-bold text-green-800">
                        🔗 Tautan Survei Produsen —
                        {{ \Carbon\Carbon::createFromFormat('m', $sesiDipilih->bulan)->translatedFormat('F') }} {{ $sesiDipilih->tahun }}
                    </h2>
                    <p class="text-xs text-green-700 mt-1">Bagikan tautan ini ke Kelompok Tani &amp; Pelaku Ekraf untuk mengisi komoditas produksi mereka.</p>
                </div>
                <div class="mt-3 flex gap-2 items-center">
                    <div class="flex-1 rounded-sm border border-green-300 bg-white p-2.5 font-mono text-xs break-all text-ink-800">
                        {{ url('/survei/isi/produsen/' . $sesiDipilih->token_produsen) }}
                    </div>
                    <button type="button"
                            onclick="navigator.clipboard.writeText('{{ url('/survei/isi/produsen/' . $sesiDipilih->token_produsen) }}').then(() => { this.textContent = '✓ Disalin'; setTimeout(() => this.textContent = 'Salin', 2000); })"
                            class="shrink-0 rounded-sm bg-green-600 px-3 py-2.5 text-xs font-medium text-white hover:bg-green-700 shadow-sm">
                        Salin
                    </button>
                </div>
            </div>
        @endif

        {{-- Data Komoditas Masuk --}}
        @if($produksiPeriode->isNotEmpty())
            <div class="mb-6 rounded-sm border border-paper-300 bg-white shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-paper-200 bg-paper-50 flex items-center justify-between flex-wrap gap-2">
                    <h2 class="font-display text-base font-semibold text-ink-900">Data Komoditas Masuk</h2>
                    <span class="text-xs font-semibold text-ink-600 bg-paper-200 px-3 py-1 rounded-full">
                        {{ $produksiPeriode->count() }} entri
                    </span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-ink-500">
                        <thead class="border-b border-paper-300 bg-paper-50 text-xs uppercase text-ink-700">
                            <tr>
                                <th class="px-5 py-3 font-medium">Responden</th>
                                <th class="px-5 py-3 font-medium">Kategori</th>
                                <th class="px-5 py-3 font-medium">Komoditas</th>
                                <th class="px-5 py-3 font-medium">Jumlah</th>
                                <th class="px-5 py-3 font-medium">Harga / Nilai</th>
                                <th class="px-5 py-3 font-medium">Periode</th>
                                <th class="px-5 py-3 font-medium text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-paper-100">
                            @foreach($produksiPeriode as $item)
                                <tr class="hover:bg-paper-50">
                                    <td class="px-5 py-3 font-medium text-ink-800">{{ $item->nama_responden }}</td>
                                    <td class="px-5 py-3">
                                        <div class="flex flex-col gap-0.5">
                                            <span class="text-xs font-semibold px-2 py-0.5 rounded-full inline-block w-fit
                                                {{ $item->kategori === 'Kelompok Tani' ? 'bg-green-50 text-green-700' : 'bg-purple-50 text-purple-700' }}">
                                                {{ $item->kategori }}
                                            </span>
                                            @if($item->sub_kategori)
                                                <span class="text-[10px] text-ink-500">{{ $item->sub_kategori }}</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-5 py-3 font-medium text-ink-900">{{ $item->nama_komoditas }}</td>
                                    <td class="px-5 py-3">
                                        @if($item->jumlah_produksi)
                                            {{ number_format($item->jumlah_produksi, 0, ',', '.') }} {{ $item->satuan }}
                                        @else
                                            <span class="text-ink-400">-</span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-3 text-xs">
                                        @if($item->harga)
                                            <div class="font-semibold text-ink-900">
                                                @if($item->tipe_harga === 'Satuan')
                                                    Rp {{ number_format($item->harga, 0, ',', '.') }} <span class="text-ink-500 font-normal">/ {{ $item->satuan ?? 'satuan' }}</span>
                                                @else
                                                    Total Rp {{ number_format($item->harga, 0, ',', '.') }}
                                                @endif
                                            </div>
                                            @if($item->jumlah_produksi && $item->jumlah_produksi > 0)
                                                <div class="text-[11px] text-ink-500 mt-0.5">
                                                    @if($item->tipe_harga === 'Satuan')
                                                        (Total: Rp {{ number_format($item->harga_total, 0, ',', '.') }})
                                                    @else
                                                        (Rp {{ number_format($item->harga_satuan, 0, ',', '.') }} / {{ $item->satuan ?? 'satuan' }})
                                                    @endif
                                                </div>
                                            @endif
                                        @else
                                            <span class="text-ink-400">-</span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-3 text-xs text-ink-600">{{ $item->nama_bulan }} {{ $item->tahun_produksi }}</td>
                                    <td class="px-5 py-3 text-center">
                                        <form action="{{ route('survei.produksi.destroy', $item->id) }}" method="POST"
                                              class="inline-block" onsubmit="return confirm('Hapus data ini?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    class="rounded-sm bg-merah-50 px-2 py-1 text-xs font-semibold text-merah-600 hover:bg-merah-100">
                                                Hapus
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @else
            <div class="mb-6 rounded-sm border border-paper-300 bg-white p-6 text-center text-sm text-ink-500 shadow-sm">
                Belum ada komoditas yang dikirim untuk periode ini.
            </div>
        @endif
    @endif
</x-layouts.app>


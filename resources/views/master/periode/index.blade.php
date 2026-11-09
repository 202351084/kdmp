<x-layouts.app :title="$title" eyebrow="M0 Master Data">
    @if (session('success'))
        <div class="mb-4 rounded-sm border border-sawah-500/40 bg-sawah-100 px-4 py-3 text-sm text-ink-900">
            {{ session('success') }}
        </div>
    @endif
    @if (session('error'))
        <div class="mb-4 rounded-sm border border-merah-300 bg-merah-50 px-4 py-3 text-sm text-merah-800">
            {{ session('error') }}
        </div>
    @endif

    @if(! $idEntitas)
        <div class="mb-5 rounded-sm border border-padi-500 bg-padi-100 px-4 py-3 text-sm text-ink-800">
            ⚠️ Pilih <strong>Entitas</strong> terlebih dahulu pada dropdown di bagian atas halaman untuk mengelola Periode Akuntansi entitas.
        </div>
    @endif

    <div class="mb-5 flex flex-wrap items-center justify-between gap-4 rounded-sm border border-paper-300 bg-paper-50 p-4">
        <form method="GET" action="{{ route('master.periode.index') }}" class="flex items-center gap-2">
            <label class="font-mono text-xs text-ink-700">Tahun Buku:</label>
            <select name="tahun" onchange="this.form.submit()"
                    class="rounded-sm border border-paper-300 bg-white px-3 py-1.5 text-sm font-medium text-ink-900">
                @foreach($daftarTahun as $t)
                    <option value="{{ $t }}" @selected($tahun == $t)>Tahun {{ $t }}</option>
                @endforeach
            </select>
        </form>

        @if($idEntitas)
            <form method="POST" action="{{ route('master.periode.store') }}" class="flex items-center gap-2">
                @csrf
                <input type="number" name="tahun" value="{{ $tahun }}" min="2020" max="2100" required
                       class="w-28 rounded-sm border border-paper-300 bg-white px-3 py-1.5 font-mono text-sm text-ink-900">
                <button type="submit"
                        class="rounded-sm bg-merah-500 px-4 py-1.5 text-xs font-semibold text-paper-50 hover:bg-merah-600">
                    + Buka / Inisialisasi 13 Periode Tahun Ini
                </button>
            </form>
        @endif
    </div>

    @php
        $namaBulan = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
            13 => 'Periode 13 (Penyesuaian & Tutup Tahun)',
        ];
    @endphp

    <div class="overflow-hidden rounded-sm border border-paper-300 bg-paper-50">
        <table class="w-full text-left text-sm">
            <thead class="border-b border-paper-300 bg-paper-200/60 font-mono text-[11px] uppercase tracking-wide text-ink-600/80">
                <tr>
                    <th class="px-4 py-3 font-medium">Bulan</th>
                    <th class="px-4 py-3 font-medium">Nama Periode</th>
                    <th class="px-4 py-3 text-center font-medium">Status</th>
                    <th class="px-4 py-3 font-medium">Waktu Tutup Buku</th>
                    <th class="px-4 py-3 text-right font-medium">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-paper-200">
                @forelse ($items as $item)
                    <tr class="hover:bg-paper-100/70">
                        <td class="px-4 py-3 font-mono font-bold text-ink-900">{{ $item->bulan }}</td>
                        <td class="px-4 py-3 font-medium text-ink-900">
                            {{ $namaBulan[$item->bulan] ?? 'Bulan ' . $item->bulan }} {{ $item->tahun }}
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if($item->status === 'OPEN')
                                <span class="rounded-full bg-sawah-100 px-2.5 py-0.5 font-mono text-xs font-semibold text-sawah-700">OPEN</span>
                            @else
                                <span class="rounded-full bg-paper-200 px-2.5 py-0.5 font-mono text-xs font-semibold text-ink-700">CLOSED</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-xs text-ink-600">
                            {{ $item->tgl_tutup ? $item->tgl_tutup->format('d M Y H:i') : '—' }}
                        </td>
                        <td class="px-4 py-3 text-right">
                            @if($idEntitas)
                                <form method="POST" action="{{ route('master.periode.update', $item->id_periode) }}" class="inline">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="status" value="{{ $item->status === 'OPEN' ? 'CLOSED' : 'OPEN' }}">
                                    <button type="submit"
                                            class="rounded-sm border border-paper-300 bg-white px-2.5 py-1 text-xs font-medium text-ink-700 hover:border-merah-400 hover:text-merah-600">
                                        {{ $item->status === 'OPEN' ? 'Kunci (CLOSE)' : 'Buka Kembali (OPEN)' }}
                                    </button>
                                </form>
                            @else
                                <span class="text-xs text-ink-400">—</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-10 text-center text-sm text-ink-600/60">
                            Belum ada baris periode akuntansi untuk tahun {{ $tahun }}. Klik tombol <strong>+ Buka / Inisialisasi 13 Periode Tahun Ini</strong> di atas.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-layouts.app>


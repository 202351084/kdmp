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
            ⚠️ Pilih <strong>Entitas</strong> terlebih dahulu pada dropdown di bagian atas halaman untuk mengelola data Pihak/Mitra entitas.
        </div>
    @endif

    <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <form method="GET" class="flex flex-wrap items-center gap-2">
            <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari nama, no anggota, NIK, HP..."
                   class="w-64 rounded-sm border border-paper-300 bg-paper-50 px-3 py-2 text-sm focus:border-merah-400 focus:outline-none">
            <select name="jenis_pihak" class="rounded-sm border border-paper-300 bg-paper-50 px-3 py-2 text-sm text-ink-800 focus:border-merah-400 focus:outline-none">
                <option value="">Semua Jenis Pihak</option>
                <option value="warga" @selected(request('jenis_pihak') === 'warga')>Warga / Konsumen</option>
                <option value="petani" @selected(request('jenis_pihak') === 'petani')>Petani / Poktan</option>
                <option value="supplier" @selected(request('jenis_pihak') === 'supplier')>Supplier / Vendor</option>
                <option value="entitas_lain" @selected(request('jenis_pihak') === 'entitas_lain')>Entitas / Desa Mitra</option>
            </select>
            <select name="is_anggota" class="rounded-sm border border-paper-300 bg-paper-50 px-3 py-2 text-sm text-ink-800 focus:border-merah-400 focus:outline-none">
                <option value="">Semua Keanggotaan</option>
                <option value="1" @selected(request('is_anggota') === '1')>Anggota Koperasi</option>
                <option value="0" @selected(request('is_anggota') === '0')>Non-Anggota</option>
            </select>
            <button class="rounded-sm border border-paper-300 bg-white px-4 py-2 text-sm font-medium text-ink-700 hover:border-merah-400">
                Filter
            </button>
        </form>

        @if($idEntitas)
            <a href="{{ route('master.pihak.create') }}"
               class="inline-flex items-center justify-center rounded-sm bg-merah-500 px-4 py-2 text-sm font-medium text-paper-50 hover:bg-merah-600">
                + Tambah Pihak
            </a>
        @endif
    </div>

    <div class="overflow-hidden rounded-sm border border-paper-300 bg-paper-50">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-paper-300 bg-paper-200/60 font-mono text-[11px] uppercase tracking-wide text-ink-600/80">
                    <tr>
                        <th class="px-4 py-3 font-medium">Nama Pihak</th>
                        <th class="px-4 py-3 font-medium">Jenis</th>
                        <th class="px-4 py-3 font-medium">Keanggotaan</th>
                        <th class="px-4 py-3 font-medium">Kontak &amp; Wilayah</th>
                        <th class="px-4 py-3 text-center font-medium">Status</th>
                        <th class="px-4 py-3 text-right font-medium">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-paper-200">
                    @forelse ($items as $item)
                        @php
                            $jenisBadge = match($item->jenis_pihak) {
                                'warga' => ['label' => 'Warga', 'class' => 'bg-blue-100 text-blue-700'],
                                'petani' => ['label' => 'Petani / Poktan', 'class' => 'bg-sawah-100 text-sawah-700'],
                                'supplier' => ['label' => 'Supplier', 'class' => 'bg-amber-100 text-amber-800'],
                                'entitas_lain' => ['label' => 'Desa Mitra', 'class' => 'bg-purple-100 text-purple-700'],
                                default => ['label' => $item->jenis_pihak, 'class' => 'bg-paper-200 text-ink-700'],
                            };
                        @endphp
                        <tr class="hover:bg-paper-100/70">
                            <td class="px-4 py-3">
                                <div class="font-medium text-ink-900">{{ $item->nama }}</div>
                                @if($item->nik)
                                    <div class="font-mono text-xs text-ink-500">NIK: {{ $item->nik }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <span class="rounded px-2 py-0.5 font-mono text-[11px] font-semibold uppercase {{ $jenisBadge['class'] }}">
                                    {{ $jenisBadge['label'] }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                @if($item->is_anggota)
                                    <span class="rounded-full bg-sawah-100 px-2 py-0.5 text-xs font-medium text-sawah-700">Anggota</span>
                                    @if($item->no_anggota)
                                        <span class="block font-mono text-xs text-ink-600 mt-0.5">{{ $item->no_anggota }}</span>
                                    @endif
                                @else
                                    <span class="rounded-full bg-paper-200 px-2 py-0.5 text-xs text-ink-600">Non-Anggota</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-xs text-ink-700">
                                <div>{{ $item->no_hp ?: '—' }}</div>
                                <div class="text-ink-500">{{ $item->wilayah?->nama ?? ($item->alamat ? \Illuminate\Support\Str::limit($item->alamat, 35) : '—') }}</div>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="rounded-full px-2 py-0.5 text-xs {{ $item->is_active ? 'bg-sawah-100 text-sawah-700' : 'bg-paper-200 text-ink-600' }}">
                                    {{ $item->is_active ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right space-x-2">
                                @if($idEntitas)
                                    <a href="{{ route('master.pihak.edit', $item->id_pihak) }}"
                                       class="text-xs font-medium text-ink-700 hover:text-merah-600">Ubah</a>
                                    <form action="{{ route('master.pihak.destroy', $item->id_pihak) }}" method="POST" class="inline"
                                          onsubmit="return confirm('Hapus pihak {{ addslashes($item->nama) }}?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-xs font-medium text-merah-600 hover:text-merah-800">Hapus</button>
                                    </form>
                                @else
                                    <span class="text-xs text-ink-400">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-10 text-center text-sm text-ink-600/60">
                                Belum ada data Pihak / Mitra pada entitas ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">
        {{ $items->links() }}
    </div>
</x-layouts.app>


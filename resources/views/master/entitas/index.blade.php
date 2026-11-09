<x-layouts.app :title="$title" eyebrow="M0 Master Data (Super Admin)">
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

    <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <form method="GET" class="flex flex-wrap items-center gap-2">
            <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari kode atau nama entitas..."
                   class="w-64 rounded-sm border border-paper-300 bg-paper-50 px-3 py-2 text-sm focus:border-merah-400 focus:outline-none">
            <select name="status" class="rounded-sm border border-paper-300 bg-paper-50 px-3 py-2 text-sm text-ink-800">
                <option value="">Semua Status</option>
                <option value="APPROVED" @selected(request('status') === 'APPROVED')>APPROVED</option>
                <option value="PENDING" @selected(request('status') === 'PENDING')>PENDING</option>
                <option value="REJECTED" @selected(request('status') === 'REJECTED')>REJECTED</option>
            </select>
            <button class="rounded-sm border border-paper-300 bg-white px-4 py-2 text-sm font-medium text-ink-700 hover:border-merah-400">
                Filter
            </button>
        </form>

        <a href="{{ route('master.entitas.create') }}"
           class="inline-flex items-center justify-center rounded-sm bg-merah-500 px-4 py-2 text-sm font-medium text-paper-50 hover:bg-merah-600">
            + Tambah Entitas
        </a>
    </div>

    <div class="overflow-hidden rounded-sm border border-paper-300 bg-paper-50">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-paper-300 bg-paper-200/60 font-mono text-[11px] uppercase tracking-wide text-ink-600/80">
                    <tr>
                        <th class="px-4 py-3 font-medium">Kode &amp; Nama Entitas</th>
                        <th class="px-4 py-3 font-medium">Jenis</th>
                        <th class="px-4 py-3 font-medium">Wilayah Desa</th>
                        <th class="px-4 py-3 text-center font-medium">Tahun Buku</th>
                        <th class="px-4 py-3 text-center font-medium">Status</th>
                        <th class="px-4 py-3 text-right font-medium">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-paper-200">
                    @forelse ($items as $item)
                        <tr class="hover:bg-paper-100/70">
                            <td class="px-4 py-3">
                                <div class="font-medium text-ink-900">{{ $item->nama_entitas }}</div>
                                <div class="font-mono text-xs text-ink-500">{{ $item->kode_entitas }}</div>
                            </td>
                            <td class="px-4 py-3 uppercase text-xs font-mono text-ink-700">
                                {{ $item->jenis_entitas ?? 'koperasi' }}
                            </td>
                            <td class="px-4 py-3 text-ink-800">{{ $item->wilayah?->nama ?? '—' }}</td>
                            <td class="px-4 py-3 text-center font-mono text-ink-800">{{ $item->tahun_buku_awal }}</td>
                            <td class="px-4 py-3 text-center">
                                <span class="rounded-full px-2.5 py-0.5 font-mono text-xs {{ $item->status === 'APPROVED' && $item->is_active ? 'bg-sawah-100 text-sawah-700' : ($item->status === 'PENDING' ? 'bg-amber-100 text-amber-800' : 'bg-merah-100 text-merah-700') }}">
                                    {{ $item->status }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right space-x-2">
                                <a href="{{ route('master.entitas.edit', $item->id_entitas) }}"
                                   class="text-xs font-medium text-ink-700 hover:text-merah-600">Ubah</a>
                                <form action="{{ route('master.entitas.destroy', $item->id_entitas) }}" method="POST" class="inline"
                                      onsubmit="return confirm('Hapus entitas {{ addslashes($item->nama_entitas) }}?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-xs font-medium text-merah-600 hover:text-merah-800">Hapus</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-10 text-center text-sm text-ink-600/60">
                                Belum ada data Entitas.
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


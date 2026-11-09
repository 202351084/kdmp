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
            ⚠️ Pilih <strong>Entitas</strong> terlebih dahulu pada dropdown di bagian atas halaman untuk menambah atau mengubah Komoditas entitas.
        </div>
    @endif

    <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <form method="GET" class="flex flex-wrap items-center gap-2">
            <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari nama komoditas..."
                   class="w-64 rounded-sm border border-paper-300 bg-paper-50 px-3 py-2 text-sm focus:border-merah-400 focus:outline-none">
            <select name="kategori" class="rounded-sm border border-paper-300 bg-paper-50 px-3 py-2 text-sm text-ink-800 focus:border-merah-400 focus:outline-none">
                <option value="">Semua Kategori</option>
                @foreach($kategoriList as $key => $label)
                    <option value="{{ $key }}" @selected(request('kategori') === $key)>{{ $label }}</option>
                @endforeach
            </select>
            <button class="rounded-sm border border-paper-300 bg-white px-4 py-2 text-sm font-medium text-ink-700 hover:border-merah-400">
                Filter
            </button>
        </form>

        @if($idEntitas)
            <a href="{{ route('master.komoditas.create') }}"
               class="inline-flex items-center justify-center rounded-sm bg-merah-500 px-4 py-2 text-sm font-medium text-paper-50 hover:bg-merah-600">
                + Tambah Komoditas
            </a>
        @endif
    </div>

    <div class="overflow-hidden rounded-sm border border-paper-300 bg-paper-50">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-paper-300 bg-paper-200/60 font-mono text-[11px] uppercase tracking-wide text-ink-600/80">
                    <tr>
                        <th class="px-4 py-3 font-medium">Nama Komoditas</th>
                        <th class="px-4 py-3 font-medium">Kategori</th>
                        <th class="px-4 py-3 font-medium">Alias / Sinonim Survei</th>
                        <th class="px-4 py-3 text-center font-medium">Status</th>
                        <th class="px-4 py-3 text-right font-medium">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-paper-200">
                    @forelse ($items as $item)
                        <tr class="hover:bg-paper-100/70">
                            <td class="px-4 py-3 font-medium text-ink-900">{{ $item->nama }}</td>
                            <td class="px-4 py-3">
                                <span class="rounded bg-paper-200 px-2 py-0.5 font-mono text-xs text-ink-800">
                                    {{ $kategoriList[$item->kategori] ?? ucfirst(str_replace('_', ' ', $item->kategori)) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-xs text-ink-600">
                                @if(! empty($item->alias_json) && is_array($item->alias_json))
                                    {{ implode(', ', $item->alias_json) }}
                                @else
                                    <span class="text-ink-400">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="rounded-full px-2 py-0.5 text-xs {{ $item->is_active ? 'bg-sawah-100 text-sawah-700' : 'bg-paper-200 text-ink-600' }}">
                                    {{ $item->is_active ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right space-x-2">
                                @if($idEntitas)
                                    <a href="{{ route('master.komoditas.edit', $item->id) }}"
                                       class="text-xs font-medium text-ink-700 hover:text-merah-600">Ubah</a>
                                    <form action="{{ route('master.komoditas.destroy', $item->id) }}" method="POST" class="inline"
                                          onsubmit="return confirm('Hapus komoditas {{ addslashes($item->nama) }}?');">
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
                            <td colspan="5" class="px-4 py-10 text-center text-sm text-ink-600/60">
                                Belum ada data Komoditas pada entitas ini.
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


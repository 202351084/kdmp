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
            ⚠️ Pilih <strong>Entitas</strong> terlebih dahulu pada dropdown di bagian atas halaman untuk menambah atau mengubah Kas &amp; Bank entitas.
        </div>
    @endif

    <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <form method="GET" class="flex flex-wrap items-center gap-2">
            <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari nama atau no. rekening..."
                   class="w-64 rounded-sm border border-paper-300 bg-paper-50 px-3 py-2 text-sm focus:border-merah-400 focus:outline-none">
            <select name="jenis" class="rounded-sm border border-paper-300 bg-paper-50 px-3 py-2 text-sm text-ink-800 focus:border-merah-400 focus:outline-none">
                <option value="">Semua Jenis</option>
                <option value="kas" @selected(request('jenis') === 'kas')>Kas Tunai</option>
                <option value="bank" @selected(request('jenis') === 'bank')>Rekening Bank</option>
            </select>
            <button class="rounded-sm border border-paper-300 bg-white px-4 py-2 text-sm font-medium text-ink-700 hover:border-merah-400">
                Filter
            </button>
        </form>

        @if($idEntitas)
            <a href="{{ route('master.kas-bank.create') }}"
               class="inline-flex items-center justify-center rounded-sm bg-merah-500 px-4 py-2 text-sm font-medium text-paper-50 hover:bg-merah-600">
                + Tambah Kas / Bank
            </a>
        @endif
    </div>

    <div class="overflow-hidden rounded-sm border border-paper-300 bg-paper-50">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-paper-300 bg-paper-200/60 font-mono text-[11px] uppercase tracking-wide text-ink-600/80">
                    <tr>
                        <th class="px-4 py-3 font-medium">Jenis</th>
                        <th class="px-4 py-3 font-medium">Nama Kas / Bank Entitas</th>
                        <th class="px-4 py-3 font-medium">No. Rekening</th>
                        <th class="px-4 py-3 font-medium">Akun COA Terhubung</th>
                        <th class="px-4 py-3 text-center font-medium">Default</th>
                        <th class="px-4 py-3 text-center font-medium">Status</th>
                        <th class="px-4 py-3 text-right font-medium">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-paper-200">
                    @forelse ($items as $item)
                        <tr class="hover:bg-paper-100/70">
                            <td class="px-4 py-3">
                                @if($item->jenis === 'kas')
                                    <span class="rounded bg-padi-100 px-2 py-0.5 font-mono text-xs font-semibold uppercase text-padi-700">KAS</span>
                                @else
                                    <span class="rounded bg-blue-100 px-2 py-0.5 font-mono text-xs font-semibold uppercase text-blue-700">BANK</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 font-medium text-ink-900">{{ $item->nama }}</td>
                            <td class="px-4 py-3 font-mono text-xs text-ink-700">{{ $item->no_rekening ?: '—' }}</td>
                            <td class="px-4 py-3 font-mono text-xs text-ink-700">
                                {{ $item->kode_akun }} &mdash; {{ $item->akun?->nama_rekening ?? '-' }}
                            </td>
                            <td class="px-4 py-3 text-center">
                                @if($item->is_default)
                                    <span class="rounded-full bg-sawah-100 px-2 py-0.5 text-xs font-medium text-sawah-700">Utama</span>
                                @else
                                    <span class="text-xs text-ink-400">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="rounded-full px-2 py-0.5 text-xs {{ $item->is_active ? 'bg-sawah-100 text-sawah-700' : 'bg-paper-200 text-ink-600' }}">
                                    {{ $item->is_active ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right space-x-2">
                                @if($idEntitas)
                                    <a href="{{ route('master.kas-bank.edit', $item->id_kas_bank) }}"
                                       class="text-xs font-medium text-ink-700 hover:text-merah-600">Ubah</a>
                                    <form action="{{ route('master.kas-bank.destroy', $item->id_kas_bank) }}" method="POST" class="inline"
                                          onsubmit="return confirm('Hapus Kas/Bank {{ addslashes($item->nama) }}?');">
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
                            <td colspan="7" class="px-4 py-10 text-center text-sm text-ink-600/60">
                                Belum ada data Kas &amp; Bank untuk entitas ini.
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


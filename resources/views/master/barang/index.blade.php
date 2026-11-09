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
            ⚠️ Pilih <strong>Entitas</strong> terlebih dahulu pada dropdown di bagian atas halaman untuk mengelola Master Barang &amp; Harga Jual entitas.
        </div>
    @endif

    <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <form method="GET" class="flex flex-wrap items-center gap-2">
            <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari kode atau nama barang..."
                   class="w-60 rounded-sm border border-paper-300 bg-paper-50 px-3 py-2 text-sm focus:border-merah-400 focus:outline-none">
            <select name="id_unit_usaha" class="rounded-sm border border-paper-300 bg-paper-50 px-3 py-2 text-sm text-ink-800 focus:border-merah-400 focus:outline-none">
                <option value="">Semua Unit Usaha</option>
                @foreach($unitUsahaList as $u)
                    <option value="{{ $u->id_unit_usaha }}" @selected(request('id_unit_usaha') == $u->id_unit_usaha)>
                        {{ $u->nama_unit_usaha }}
                    </option>
                @endforeach
            </select>
            <select name="id_komoditas" class="rounded-sm border border-paper-300 bg-paper-50 px-3 py-2 text-sm text-ink-800 focus:border-merah-400 focus:outline-none">
                <option value="">Semua Komoditas</option>
                @foreach($komoditasList as $k)
                    <option value="{{ $k->id }}" @selected(request('id_komoditas') == $k->id)>{{ $k->nama }}</option>
                @endforeach
            </select>
            <button class="rounded-sm border border-paper-300 bg-white px-4 py-2 text-sm font-medium text-ink-700 hover:border-merah-400">
                Filter
            </button>
        </form>

        @if($idEntitas)
            <a href="{{ route('master.barang.create') }}"
               class="inline-flex items-center justify-center rounded-sm bg-merah-500 px-4 py-2 text-sm font-medium text-paper-50 hover:bg-merah-600">
                + Tambah Barang
            </a>
        @endif
    </div>

    <div class="overflow-hidden rounded-sm border border-paper-300 bg-paper-50">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-paper-300 bg-paper-200/60 font-mono text-[11px] uppercase tracking-wide text-ink-600/80">
                    <tr>
                        <th class="px-4 py-3 font-medium">Kode &amp; Nama Barang</th>
                        <th class="px-4 py-3 font-medium">Komoditas &amp; Unit Usaha</th>
                        <th class="px-4 py-3 font-medium">Satuan &amp; Konversi</th>
                        <th class="px-4 py-3 text-right font-medium">Harga Jual POS</th>
                        <th class="px-4 py-3 text-center font-medium">Status POS</th>
                        <th class="px-4 py-3 text-right font-medium">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-paper-200">
                    @forelse ($items as $item)
                        @php
                            $cfg = $settingMap[$item->id_barang] ?? null;
                            $hargaJual = $cfg ? (float) $cfg->harga_jual_standar : 0;
                            $isDijual = $cfg ? (bool) $cfg->is_dijual : false;
                            $konversiLain = $item->konversiSatuan->where('id_satuan', '!=', $item->id_satuan_dasar);
                        @endphp
                        <tr class="hover:bg-paper-100/70">
                            <td class="px-4 py-3">
                                <div class="font-medium text-ink-900">{{ $item->nama_barang }}</div>
                                <div class="font-mono text-xs text-ink-500">{{ $item->kode_barang }}</div>
                            </td>
                            <td class="px-4 py-3">
                                <div class="text-ink-900">{{ $item->komoditas?->nama ?? '—' }}</div>
                                <span class="rounded bg-paper-200 px-1.5 py-0.5 font-mono text-[10px] text-ink-700">
                                    {{ $item->unitUsaha?->nama_unit_usaha ?? 'Sembako' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-xs">
                                <span class="font-mono font-bold text-ink-900">{{ $item->satuanDasar?->kode_satuan ?? 'KG' }}</span>
                                <span class="text-ink-500">(Dasar)</span>
                                @foreach($konversiLain as $kv)
                                    <div class="font-mono text-[11px] text-ink-600 mt-0.5">
                                        1 {{ $kv->satuan?->kode_satuan }} = {{ (float) $kv->faktor_ke_dasar }} {{ $item->satuanDasar?->kode_satuan }}
                                    </div>
                                @endforeach
                            </td>
                            <td class="px-4 py-3 text-right font-mono font-semibold text-ink-900">
                                Rp {{ number_format($hargaJual, 0, ',', '.') }}
                                <span class="block text-[10px] font-normal text-ink-500">per {{ $item->satuanDasar?->kode_satuan ?? 'satuan' }}</span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                @if($item->is_active && $isDijual)
                                    <span class="rounded-full bg-sawah-100 px-2 py-0.5 text-xs font-medium text-sawah-700">Dijual di POS</span>
                                @elseif(! $item->is_active)
                                    <span class="rounded-full bg-paper-200 px-2 py-0.5 text-xs text-ink-600">Nonaktif</span>
                                @else
                                    <span class="rounded-full bg-amber-100 px-2 py-0.5 text-xs text-amber-800">Disembunyikan</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right space-x-2">
                                @if($idEntitas)
                                    <a href="{{ route('master.barang.edit', $item->id_barang) }}"
                                       class="text-xs font-medium text-ink-700 hover:text-merah-600">Ubah</a>
                                    <form action="{{ route('master.barang.destroy', $item->id_barang) }}" method="POST" class="inline"
                                          onsubmit="return confirm('Hapus barang {{ addslashes($item->nama_barang) }} dari entitas Anda?');">
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
                                Belum ada data Barang pada entitas ini. Klik <strong>+ Tambah Barang</strong> untuk mendaftarkan barang beserta harga jual POS.
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


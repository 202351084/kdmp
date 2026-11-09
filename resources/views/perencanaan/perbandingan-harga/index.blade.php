<x-layouts.app :title="$title" eyebrow="Perencanaan Pengadaan">
    <div class="mx-auto max-w-6xl space-y-6">
        <div><p class="font-mono text-[11px] uppercase tracking-wide text-ink-600/60">Perencanaan Pengadaan</p><h1 class="mt-1 font-display text-2xl font-semibold text-ink-900">{{ $title }}</h1></div>
        <form method="GET" class="grid gap-4 rounded-sm border border-paper-300 bg-paper-50 p-4 md:grid-cols-4">
            <label class="text-sm font-medium text-ink-700">Komoditas
                <select name="id_komoditas" required class="mt-1 block w-full rounded-sm border border-paper-300 bg-paper-50 px-3 py-2 font-normal"><option value="">Pilih komoditas</option>@foreach ($komoditas as $item)<option value="{{ $item->id }}" @selected($selected == $item->id)>{{ $item->nama }}</option>@endforeach</select>
            </label>
            <label class="text-sm font-medium text-ink-700">Tahun<input type="number" name="tahun" value="{{ $tahun }}" min="2000" max="2100" class="mt-1 block w-full rounded-sm border border-paper-300 bg-paper-50 px-3 py-2 font-normal"></label>
            <label class="text-sm font-medium text-ink-700">Acuan harga
                <select name="bulan" class="mt-1 block w-full rounded-sm border border-paper-300 bg-paper-50 px-3 py-2 font-normal">
                    @foreach ([1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'] as $nomorBulan => $namaBulan)
                        <option value="{{ $nomorBulan }}" @selected((int) $periode === $nomorBulan)>{{ $namaBulan }}</option>
                    @endforeach
                </select>
                <input type="hidden" name="periode" value="{{ is_int($periode) ? ($periode <= 6 ? 'ganjil' : 'genap') : $periode }}">
                @if ($periode === 'ganjil')
                    <span class="mt-1 block text-xs font-normal text-ink-600">Periode Ganjil (Januari–Juni)</span>
                @elseif ($periode === 'genap')
                    <span class="mt-1 block text-xs font-normal text-ink-600">Periode Genap (Juli–Desember)</span>
                @endif
            </label>
            <button class="self-end rounded-sm bg-merah-500 px-4 py-2 font-medium text-paper-50">Bandingkan Harga</button>
        </form>
        @if ($selected)
            @if ($items->isEmpty())
                <div class="rounded-sm border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">Belum ada data harga untuk komoditas dan periode tersebut.</div>
            @else
                <div class="grid gap-4 md:grid-cols-3">@foreach ([['Terendah', $lowest], ['Tertinggi', $highest], ['Selisih', $difference]] as [$label, $value])<div class="rounded-sm border border-paper-300 bg-paper-50 p-4"><p class="text-xs text-ink-600/60">{{ $label }}</p><p class="mt-1 text-xl font-semibold">Rp {{ number_format($value, 2, ',', '.') }}</p></div>@endforeach</div>
                <div class="overflow-hidden rounded-sm border border-paper-300 bg-paper-50"><table class="w-full text-left text-sm"><thead class="border-b border-paper-300 bg-paper-200/60"><tr><th class="px-4 py-3">Peringkat</th><th class="px-4 py-3">Desa sumber</th><th class="px-4 py-3">Harga</th><th class="px-4 py-3">Satuan</th><th class="px-4 py-3">Bulan acuan</th></tr></thead><tbody>@foreach ($items as $item)<tr class="border-b border-paper-200 last:border-0"><td class="px-4 py-3">{{ $item->rank_harga }}</td><td class="px-4 py-3">{{ $item->wilayahSumber?->nama ?? '—' }}</td><td class="px-4 py-3">Rp {{ number_format((float) $item->harga_efektif, 2, ',', '.') }}</td><td class="px-4 py-3">{{ $satuan ?: '—' }}</td><td class="px-4 py-3">{{ is_int($periode) ? date('F', mktime(0, 0, 0, $periode, 1)) : ($periode === 'ganjil' ? 'Januari–Juni' : 'Juli–Desember') }} {{ $tahun }}</td></tr>@endforeach</tbody></table></div>
            @endif
        @endif
    </div>
</x-layouts.app>

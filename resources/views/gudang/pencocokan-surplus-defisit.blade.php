<x-layouts.app title="Pencocokan Surplus-Defisit" eyebrow="Manajemen Gudang">
    <div class="space-y-5">
        <div>
            <p class="text-sm font-medium text-padi-700">Rekomendasi antar desa</p>
            <h1 class="mt-1 text-2xl font-semibold text-ink-900">Pencocokan Surplus-Defisit</h1>
            <p class="mt-2 max-w-3xl text-sm text-ink-600">
                Data berikut berasal dari kalkulasi yang sudah disetujui. Gudang membantu menemukan desa yang cocok,
                lalu meneruskan informasinya ke Penjualan atau Pengadaan. Ini bukan stok aktual dan tidak membuat
                persetujuan maupun pengiriman.
            </p>
        </div>

        <form method="GET" class="flex flex-wrap items-end gap-3 rounded-sm border border-paper-300 bg-paper-50 p-4">
            <label class="text-sm font-medium text-ink-700">
                Tahun
                <input type="number" name="tahun" min="2000" max="2100" value="{{ $tahun }}"
                    class="mt-1 block w-28 rounded-sm border border-paper-300 bg-white px-3 py-2">
            </label>
            <label class="text-sm font-medium text-ink-700">
                Bulan
                <select name="bulan" class="mt-1 block rounded-sm border border-paper-300 bg-white px-3 py-2">
                    <option value="">Semua bulan</option>
                    @foreach (range(1, 12) as $nomorBulan)
                        <option value="{{ $nomorBulan }}" @selected($bulan === $nomorBulan)>
                            {{ \Carbon\Carbon::create()->month($nomorBulan)->translatedFormat('F') }}
                        </option>
                    @endforeach
                </select>
            </label>
            <button type="submit" class="rounded-sm bg-ink-800 px-4 py-2 text-sm font-semibold text-white hover:bg-ink-900">
                Tampilkan
            </button>
        </form>

        @forelse ($neracaDesaSaya as $hasil)
            @php
                $kandidat = $pasangan->get($hasil->id_hasil, collect());
                $satuan = $satuanMap->get($hasil->id_komoditas);
                $isSurplus = $hasil->status_surplus_defisit === 'surplus';
            @endphp
            <section class="rounded-sm border border-paper-300 bg-paper-50">
                <div class="flex flex-wrap items-start justify-between gap-4 border-b border-paper-200 p-4">
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <h2 class="text-lg font-semibold text-ink-900">{{ $hasil->komoditas?->nama ?? 'Komoditas' }}</h2>
                            <span class="rounded-full px-2.5 py-1 text-xs font-semibold uppercase {{ $isSurplus ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">
                                {{ $hasil->status_surplus_defisit }}
                            </span>
                        </div>
                        <p class="mt-1 text-sm text-ink-600">
                            {{ \Carbon\Carbon::create()->month($hasil->bulan)->translatedFormat('F') }} {{ $hasil->tahun }}
                            · {{ $isSurplus ? 'Surplus' : 'Kekurangan' }}
                            {{ number_format(abs((float) $hasil->selisih), 2, ',', '.') }} {{ $satuan }}
                        </p>
                    </div>

                    @if ($isSurplus)
                        <a href="{{ route('penjualan.penjualan.create', ['id_hasil' => $hasil->id_hasil]) }}"
                            class="rounded-sm bg-emerald-700 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-800">
                            Teruskan info ke Penjualan
                        </a>
                    @else
                        <a href="{{ route('perencanaan.permintaan-pengadaan.from-hasil', ['hasil' => $hasil->id_hasil]) }}"
                            class="rounded-sm bg-amber-700 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-800">
                            Teruskan info ke Pengadaan
                        </a>
                    @endif
                </div>

                <div class="p-4">
                    <h3 class="text-sm font-semibold text-ink-800">
                        {{ $isSurplus ? 'Desa yang berpotensi membutuhkan' : 'Desa yang berpotensi menyediakan' }}
                    </h3>
                    @if ($kandidat->isEmpty())
                        <p class="mt-2 text-sm text-ink-600">Belum ada pasangan {{ $isSurplus ? 'defisit' : 'surplus' }} untuk komoditas dan periode ini.</p>
                    @else
                        <div class="mt-3 overflow-x-auto">
                            <table class="w-full min-w-[34rem] text-left text-sm">
                                <thead class="border-b border-paper-200 text-xs uppercase text-ink-600">
                                    <tr>
                                        <th class="px-3 py-2">{{ $isSurplus ? 'Desa defisit' : 'Desa surplus' }}</th>
                                        <th class="px-3 py-2">{{ $isSurplus ? 'Kebutuhan' : 'Ketersediaan surplus' }}</th>
                                        <th class="px-3 py-2">Potensi jumlah cocok</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($kandidat as $pasanganDesa)
                                        <tr class="border-b border-paper-100 last:border-0">
                                            <td class="px-3 py-3 font-medium text-ink-900">{{ $pasanganDesa['hasil']->wilayah?->nama ?? 'Desa' }}</td>
                                            <td class="px-3 py-3 text-ink-700">{{ number_format(abs((float) $pasanganDesa['hasil']->selisih), 2, ',', '.') }} {{ $satuan }}</td>
                                            <td class="px-3 py-3 font-semibold text-ink-900">{{ number_format($pasanganDesa['jumlah_cocok'], 2, ',', '.') }} {{ $satuan }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </section>
        @empty
            <div class="rounded-sm border border-dashed border-paper-300 bg-paper-50 px-5 py-12 text-center">
                <h2 class="font-semibold text-ink-900">Belum ada hasil kalkulasi untuk ditampilkan</h2>
                <p class="mt-2 text-sm text-ink-600">Hasil yang belum disetujui tidak ditampilkan. Silakan periksa kembali tahun atau bulan yang dipilih.</p>
            </div>
        @endforelse
    </div>
</x-layouts.app>

<x-layouts.app :title="$title" eyebrow="Perencanaan Pengadaan">
    <div class="mx-auto max-w-6xl space-y-6">
        @if (session('status'))
            <div class="rounded-sm border border-sawah-500/40 bg-sawah-100 px-4 py-3 text-sm text-ink-900">
                <span class="font-semibold text-sawah-700">Berhasil:</span> {{ session('status') }}
            </div>
        @endif
        @if (session('error'))
            <div class="rounded-sm border border-merah-400/50 bg-merah-50 px-4 py-3 text-sm text-merah-800">
                <span class="font-semibold">Gagal:</span> {{ session('error') }}
            </div>
        @endif
        <div class="flex items-center justify-between gap-4">
            <div><p class="font-mono text-[11px] uppercase tracking-wide text-ink-600/60">Perencanaan Pengadaan</p><h1 class="mt-1 font-display text-2xl font-semibold text-ink-900">{{ $title }}</h1></div>
        </div>
        <section class="space-y-4">
            <div>
                <h2 class="font-display text-xl font-semibold text-ink-900">Kebutuhan yang Perlu Direncanakan</h2>
                <p class="mt-1 text-sm text-ink-600">Data berikut berasal dari hasil Kalkulasi berstatus defisit. Potensi produksi hanya menjadi calon sumber desa dan bukan penetapan supplier.</p>
            </div>
            @if ($calonPerencanaanSektor->isEmpty())
                <div class="rounded-sm border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">Belum ada hasil Kalkulasi berstatus defisit untuk wilayah entitas aktif. Jalankan Kalkulasi terlebih dahulu atau periksa periode yang dipilih.</div>
            @else
                <div class="space-y-3">
                    @foreach ($calonPerencanaanSektor as $sektor => $rencanaSektor)
                        <details class="group rounded-sm border border-paper-300 bg-paper-50" @if ($loop->first) open @endif>
                            <summary class="flex cursor-pointer list-none items-center justify-between gap-4 px-5 py-4">
                                <div class="flex items-center gap-3">
                                    <span class="flex h-8 w-8 items-center justify-center rounded-full bg-sawah-100 text-sawah-700">🌾</span>
                                    <div>
                                        <h3 class="font-display text-xl font-semibold capitalize text-ink-900">{{ $sektor }}</h3>
                                        <p class="text-xs text-ink-600">{{ $rencanaSektor->count() }} komoditas perlu direncanakan</p>
                                    </div>
                                </div>
                                <span class="text-lg text-ink-600 transition-transform group-open:rotate-180">⌄</span>
                            </summary>
                            <div class="border-t border-paper-200 p-4">
                                <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                                @foreach ($rencanaSektor as $rencanaKomoditas)
                                    @php
                                        $contoh = $rencanaKomoditas->first();
                                        $namaKomoditas = $contoh['hasil']->komoditas?->nama ?? 'Komoditas tidak ditemukan';
                                        $totalDefisit = $rencanaKomoditas->sum(fn ($rencana) => abs((float) $rencana['hasil']->selisih));
                                        $desaSumber = $rencanaKomoditas
                                            ->flatMap(fn ($rencana) => $rencana['calonSupplier']->map(fn ($supplier) => $supplier->wilayah?->nama ?? '—'))
                                            ->unique()
                                            ->values();
                                    @endphp
                                    <details class="group rounded-sm border border-merah-200 bg-merah-50">
                                        <summary class="flex cursor-pointer list-none items-center justify-between gap-3 p-4">
                                            <div>
                                                <h4 class="font-display text-lg font-semibold text-ink-900">{{ $namaKomoditas }}</h4>
                                                <p class="text-xs text-ink-700">{{ $rencanaKomoditas->count() }} periode · {{ $desaSumber->join(', ') ?: 'Sumber belum tersedia' }}</p>
                                            </div>
                                            <div class="text-right">
                                                <p class="text-xs uppercase tracking-wide text-ink-600/70">Total defisit</p>
                                                <p class="text-lg font-semibold text-merah-700">{{ number_format($totalDefisit, 2, ',', '.') }}</p>
                                                <span class="text-xs text-ink-600 transition-transform group-open:rotate-180">⌄ rincian</span>
                                            </div>
                                        </summary>
                                        <div class="border-t border-merah-200 px-4 pb-4">
                                            @foreach ($rencanaKomoditas as $rencana)
                                                @php($hasil = $rencana['hasil'])
                                                <div class="mt-3 flex flex-wrap items-center justify-between gap-3 rounded-sm border border-merah-200/70 bg-paper-50 p-3">
                                                    <div>
                                                        <p class="text-sm font-semibold text-ink-900">Periode {{ ucfirst($rencana['periode']) }} · {{ $hasil->wilayah?->nama ?? '—' }}</p>
                                                        <p class="text-xs text-ink-700">Defisit {{ number_format(abs((float) $hasil->selisih), 2, ',', '.') }} · Calon sumber: {{ $rencana['calonSupplier']->map(fn ($supplier) => $supplier->wilayah?->nama ?? '—')->unique()->join(', ') ?: 'Belum tersedia' }}</p>
                                                    </div>
                                                    <a href="{{ route('perencanaan.permintaan-pengadaan.from-hasil', $hasil->id_hasil) }}" class="rounded-sm bg-merah-500 px-3 py-2 text-xs font-medium text-paper-50 hover:bg-merah-600">Buat draft PR</a>
                                                </div>
                                            @endforeach
                                        </div>
                                    </details>
                                @endforeach
                                </div>
                            </div>
                        </details>
                    @endforeach
                </div>
            @endif
        </section>
        <div class="overflow-hidden rounded-sm border border-paper-300 bg-paper-50">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-paper-300 bg-paper-200/60"><tr><th class="px-4 py-3">Kode</th><th class="px-4 py-3">Tanggal</th><th class="px-4 py-3">Tujuan</th><th class="px-4 py-3">Total</th><th class="px-4 py-3">Status</th><th class="px-4 py-3 text-right">Aksi</th></tr></thead>
                <tbody>
                    @forelse ($items as $item)
                        <tr class="border-b border-paper-200 last:border-0">
                            <td class="px-4 py-3 font-medium">{{ $item->kode_permintaan }}</td>
                            <td class="px-4 py-3">{{ $item->tgl_pengajuan?->format('d M Y') }}</td>
                            <td class="px-4 py-3">{{ $item->gudang?->nama_gudang ?? '—' }}<span class="block text-xs text-ink-600/60">{{ $item->unitUsaha?->nama_unit_usaha ?? '—' }}</span></td>
                            <td class="px-4 py-3">Rp {{ number_format((float) $item->total_nilai, 2, ',', '.') }}</td>
                            <td class="px-4 py-3"><span class="rounded-full px-2 py-1 text-xs {{ $item->status_badge_class }}">{{ $item->status_label }}</span></td>
                            <td class="px-4 py-3 text-right"><a href="{{ route('perencanaan.permintaan-pengadaan.show', $item->id_permintaan) }}" class="text-xs font-medium text-ink-700 hover:text-merah-600">Lihat detail</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-10 text-center text-sm text-ink-600/60">Belum ada perencanaan pengadaan.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $items->links() }}
    </div>
</x-layouts.app>

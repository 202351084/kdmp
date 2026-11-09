<x-layouts.app :title="$title" eyebrow="Konsinyasi">
    <div class="mb-4 flex items-center justify-between gap-4">
        <a href="{{ route('konsinyasi.pengiriman.index') }}" class="text-sm font-medium text-ink-700 hover:text-merah-600">&larr; Kembali ke Pengiriman</a>
        @if ($item->status_posting !== 'T')
            <form method="POST" action="{{ route('konsinyasi.pengiriman.posting', $item->id_kiriman) }}">
                @csrf
                <button type="submit" class="rounded-sm bg-merah-500 px-4 py-2 text-sm font-medium text-paper-50 hover:bg-merah-600">Posting Pengiriman</button>
            </form>
        @endif
    </div>

    @if ($errors->has('posting'))
        <div class="mb-5 rounded-sm border border-merah-500/40 bg-merah-50 px-4 py-3 text-sm text-merah-700">
            {{ $errors->first('posting') }}
        </div>
    @endif

    @if (session('warning'))
        <div class="mb-5 rounded-sm border border-padi-500/40 bg-padi-50 px-4 py-3 text-sm text-padi-700">
            {{ session('warning') }}
        </div>
    @endif

    <div class="mb-5 grid gap-4 md:grid-cols-2">
        <div class="rounded-sm border border-paper-300 bg-paper-50 p-5"><p class="font-mono text-[11px] uppercase tracking-wide text-ink-600/60">Kode Kiriman</p><p class="mt-2 font-display text-xl font-semibold text-ink-900">{{ $item->kode_kiriman }}</p><p class="mt-1 text-sm text-ink-600">{{ $item->tgl_kirim?->format('d M Y') }}</p></div>
        <div class="rounded-sm border border-paper-300 bg-paper-50 p-5"><dl class="grid grid-cols-2 gap-3 text-sm"><dt class="text-ink-600">Jenis pemilik</dt><dd class="text-right font-medium text-ink-800">{{ $item->jenis_pemilik === 'pihak' ? 'Perorangan' : 'Desa' }}</dd><dt class="text-ink-600">Pemilik</dt><dd class="text-right font-medium text-ink-800">{{ $item->jenis_pemilik === 'pihak' ? ($item->pihakPemilik->nama ?? '-') : ($item->koperasiPemilik->nama_entitas ?? '-') }}</dd><dt class="text-ink-600">Penerima</dt><dd class="text-right font-medium text-ink-800">{{ $item->koperasiPenerima->nama_entitas ?? '-' }}</dd><dt class="text-ink-600">Status</dt><dd class="text-right font-medium text-sawah-600">{{ $item->status }}</dd><dt class="text-ink-600">Posting</dt><dd class="text-right font-medium text-ink-800">{{ $item->status_posting === 'T' ? 'Sudah diposting' : 'Draft' }}</dd></dl></div>
    </div>

    <div class="mb-5 rounded-sm border border-paper-300 bg-paper-50 p-5"><dl class="grid grid-cols-2 gap-3 text-sm md:grid-cols-4"><dt class="text-ink-600">Gudang asal</dt><dd class="font-medium text-ink-800">{{ $item->gudangAsal->nama_gudang ?? ($item->jenis_pemilik === 'pihak' ? 'Tidak melalui gudang desa asal' : '-') }}</dd><dt class="text-ink-600">Gudang tujuan</dt><dd class="font-medium text-ink-800">{{ $item->gudangTujuan->nama_gudang ?? '-' }}</dd><dt class="text-ink-600">Model imbalan</dt><dd class="font-medium text-ink-800">{{ str_replace('_', ' ', $item->model_imbalan) }}</dd><dt class="text-ink-600">Batas titip</dt><dd class="font-medium text-ink-800">{{ $item->tgl_batas_titip?->format('d M Y') ?? '-' }}</dd></dl>
        @if ($item->alamat_asal)<p class="mt-3 text-sm text-ink-700"><span class="font-medium">Alamat asal / pengambilan:</span> {{ $item->alamat_asal }}</p>@endif
    </div>

    <div class="overflow-hidden rounded-sm border border-paper-300 bg-paper-50"><div class="border-b border-paper-300 px-4 py-3"><h2 class="font-display text-base font-semibold text-ink-900">Barang Titipan</h2></div><div class="overflow-x-auto"><table class="w-full text-left text-sm"><thead><tr class="border-b border-paper-300 bg-paper-200/60 font-mono text-[11px] uppercase tracking-wide text-ink-600/70"><th class="px-4 py-3 font-medium">No</th><th class="px-4 py-3 font-medium">Barang</th><th class="px-4 py-3 text-right font-medium">Qty</th><th class="px-4 py-3 text-right font-medium">Harga Titip</th><th class="px-4 py-3 text-right font-medium">HPP Pemilik</th></tr></thead><tbody>@foreach ($item->detail as $detail) @php $qty = rtrim(rtrim(number_format((float) $detail->qty_dasar, 4, ',', '.'), '0'), ','); @endphp<tr class="border-b border-paper-200 last:border-0"><td class="px-4 py-3">{{ $loop->iteration }}</td><td class="px-4 py-3">{{ $detail->barang->nama_barang ?? '-' }}</td><td class="px-4 py-3 text-right">{{ $qty }} {{ $detail->barang->satuanDasar->kode_satuan ?? '' }}</td><td class="px-4 py-3 text-right">Rp {{ number_format((float) $detail->harga_titip_satuan, 2, ',', '.') }}</td><td class="px-4 py-3 text-right">Rp {{ number_format((float) $detail->hpp_pemilik, 2, ',', '.') }}</td></tr>@endforeach</tbody></table></div></div>

    @if ($item->jenis_pemilik === 'pihak')
        <div class="mt-4 rounded-sm border border-padi-500/40 bg-padi-50 p-4 text-sm text-padi-700">Barang perorangan sudah tercatat sebagai stok titipan. Penjualan melalui POS dan pembayaran kepada penitip belum terhubung pada alur ini.</div>
    @endif
    @if ($item->catatan_pengiriman)<div class="mt-4 rounded-sm border border-paper-300 bg-paper-50 p-4 text-sm text-ink-700"><span class="font-medium">Catatan:</span> {{ $item->catatan_pengiriman }}</div>@endif
</x-layouts.app>

<x-layouts.app :title="$title" eyebrow="Detail Pembelian">

<div class="grid gap-6 md:grid-cols-2 xl:grid-cols-3">

    {{-- Informasi Utama --}}
    <div class="md:col-span-2 xl:col-span-2">

        @if (session('success'))
                <div class="mb-4 rounded-sm border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="mb-4 rounded-sm border border-merah-300 bg-merah-50 p-4 text-sm text-merah-800">
                {{ session('error') }}
            </div>
        @endif

        {{-- Header --}}
        <div class="mb-4 rounded-sm border border-paper-300 bg-paper-50 p-4">
            <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-center">
                <div>
                    <h2 class="text-xl font-semibold text-ink-900">
                        {{ $item->kode_pembelian }}
                    </h2>

                    <p class="mt-1 text-sm text-ink-600">
                        {{ $title }}
                    </p>
                </div>

                <div class="flex flex-wrap gap-2">
                    <span class="rounded-sm bg-paper-200 px-2 py-1 text-xs font-medium text-ink-800">
                        {{ ucfirst($item->status) }}
                    </span>
                </div>
            </div>
        </div>

        {{-- Informasi Pembelian --}}
        <div class="mb-4 rounded-sm border border-paper-300 bg-paper-50">

            <div class="border-b border-paper-300 px-4 py-3">
                <h3 class="font-semibold text-ink-900">
                    Informasi Pembelian
                </h3>
            </div>

            <div class="grid gap-4 p-4 sm:grid-cols-2">

                <div>
                    <div class="text-sm font-medium text-ink-600">
                        Tanggal Transaksi
                    </div>

                    <div class="mt-1 text-sm text-ink-900">
                        {{ $item->tanggal_transaksi?->format('d M Y') ?? 'N/A' }}
                    </div>
                </div>

                <div>
                    <div class="text-sm font-medium text-ink-600">
                        {{ $item->sumber_pembelian === 'pengambilan_konsinyasi' ? 'Desa Pengirim' : 'Supplier' }}
                    </div>

                    <div class="mt-1 text-sm text-ink-900">
                        {{ $item->pihak->nama_pihak ?? 'N/A' }}
                    </div>
                </div>

                <div>
                    <div class="text-sm font-medium text-ink-600">
                        Sumber Transaksi
                    </div>

                    <div class="mt-1 text-sm text-ink-900">
                        {{ $item->sumber_pembelian === 'pengambilan_konsinyasi' ? 'Pengambilan Konsinyasi dari Desa Lain' : ($item->sumber_pembelian === 'desa_lain' ? 'Pembelian dari Desa Lain' : 'Pembelian dari Warga Lokal') }}
                    </div>
                </div>

                <div>
                    <div class="text-sm font-medium text-ink-600">
                        Unit Usaha
                    </div>

                    <div class="mt-1 text-sm text-ink-900">
                        {{ $item->unitUsaha->nama_unit_usaha ?? 'N/A' }}
                    </div>
                </div>

                <div>
                    <div class="text-sm font-medium text-ink-600">
                        Gudang
                    </div>

                    <div class="mt-1 text-sm text-ink-900">
                        {{ $item->gudang->nama_gudang ?? 'N/A' }}
                    </div>
                </div>

                <div>
                    <div class="text-sm font-medium text-ink-600">
                        Jenis Pembayaran
                    </div>

                    <div class="mt-1 text-sm text-ink-900">
                        {{ $item->sumber_pembelian === 'pengambilan_konsinyasi' ? 'Hutang Konsinyasi' : ucfirst($item->jenis_pembayaran) }}
                    </div>
                </div>

                @if ($item->kasBank)
                    <div>
                        <div class="text-sm font-medium text-ink-600">
                            Sumber Uang (Kas / Bank)
                        </div>

                        <div class="mt-1 text-sm font-semibold text-ink-900">
                            {{ $item->kasBank->nama }}
                            @if ($item->kasBank->no_rekening)
                                <span class="text-xs font-normal text-ink-600">({{ $item->kasBank->no_rekening }})</span>
                            @endif
                        </div>
                    </div>
                @elseif ($item->jenis_pembayaran === 'kredit')
                    <div>
                        <div class="text-sm font-medium text-ink-600">
                            Tanggal Jatuh Tempo
                        </div>

                        <div class="mt-1 text-sm text-ink-900">
                            {{ $item->tgl_jatuh_tempo?->format('d M Y') ?? '—' }}
                        </div>
                    </div>
                @endif

                <div>
                    <div class="text-sm font-medium text-ink-600">
                        Total Pembelian
                    </div>

                    <div class="mt-1 font-semibold text-ink-900">
                        Rp {{ number_format($item->total_pembelian, 2, ',', '.') }}
                    </div>
                </div>

            </div>
        </div>

        {{-- Detail Pembelian --}}
        <div class="mb-4 rounded-sm border border-paper-300 bg-paper-50">

            <div class="border-b border-paper-300 px-4 py-3">
                <h3 class="font-semibold text-ink-900">
                    Detail Pembelian
                </h3>
            </div>

            <div class="overflow-x-auto p-4">
                <table class="min-w-full text-sm">

                    <thead class="bg-paper-100 text-left text-ink-700">
                        <tr>
                            <th class="px-3 py-2" rowspan="2">Barang</th>
                            <th class="px-3 py-2 text-center" colspan="3">Sebelum Konversi</th>
                            <th class="px-3 py-2 text-center" colspan="3">Setelah Konversi</th>
                            <th class="px-3 py-2 text-right" rowspan="2">Subtotal</th>
                        </tr>
                        <tr>
                            <th class="px-3 py-2 text-right">Qty</th>
                            <th class="px-3 py-2">Satuan</th>
                            <th class="px-3 py-2 text-right">Harga</th>
                            <th class="px-3 py-2 text-right">Qty Dasar</th>
                            <th class="px-3 py-2">Satuan Dasar</th>
                            <th class="px-3 py-2 text-right">Harga Dasar</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-paper-200">

                        @forelse ($item->detail as $line)
                            <tr>

                                <td class="px-3 py-3 text-ink-900">
                                    {{ $line->barang->nama_barang ?? 'N/A' }}
                                </td>

                                <td class="px-3 py-3 text-right text-ink-700">
                                    {{ rtrim(rtrim(number_format((float) $line->qty_input, 2, ',', '.'), '0'), ',') }}
                                </td>

                                <td class="px-3 py-3 text-ink-700">
                                    {{ $line->satuanInput?->kode_satuan ?? 'N/A' }}
                                </td>

                                <td class="px-3 py-3 text-right text-ink-700">
                                    Rp {{ number_format($line->harga_satuan_input, 2, ',', '.') }}
                                </td>

                                <td class="px-3 py-3 text-right text-ink-700">
                                    {{ rtrim(rtrim(number_format((float) $line->qty_dasar, 2, ',', '.'), '0'), ',') }}
                                </td>

                                <td class="px-3 py-3 text-ink-700">
                                    {{ $line->barang->satuanDasar?->kode_satuan ?? 'N/A' }}
                                </td>

                                <td class="px-3 py-3 text-right text-ink-700">
                                    Rp {{ number_format($line->faktor_konversi > 0 ? $line->harga_satuan_input / $line->faktor_konversi : $line->harga_satuan_input, 2, ',', '.') }}
                                </td>

                                <td class="px-3 py-3 text-right font-medium text-ink-900">
                                    Rp {{ number_format($line->subtotal, 2, ',', '.') }}
                                </td>

                            </tr>

                        @empty

                            <tr>
                                <td colspan="8" class="px-3 py-6 text-center text-sm text-ink-500">
                                    Tidak ada detail pembelian.
                                </td>
                            </tr>

                        @endforelse

                    </tbody>

                </table>
            </div>
        </div>

        {{-- Status / Instruksi --}}
        @if ($item->status === 'draft')

            <div class="rounded-sm border border-paper-300 bg-paper-100 p-4">
                <h4 class="font-semibold text-ink-900">
                    Pembelian masih Draft
                </h4>

                <p class="mt-1 text-sm text-ink-700">
                    Pembelian belum disetujui.
                </p>
            </div>

        @elseif ($item->status === 'disetujui')

            <div class="rounded-sm border border-paper-300 bg-paper-100 p-4">
                <h4 class="font-semibold text-ink-900">
                    Pembelian Disetujui
                </h4>

                <p class="mt-1 text-sm text-ink-700">
                    Pembelian sudah tercatat dan disetujui di modul Pembelian.
                </p>
            </div>

        @elseif ($item->status === 'diterima')

            <div class="rounded-sm border border-paper-300 bg-paper-100 p-4">
                <h4 class="font-semibold text-ink-900">
                    Pembelian Selesai
                </h4>

                <p class="mt-1 text-sm text-ink-700">
                    Status selesai tercatat pada modul Pembelian.
                </p>
            </div>

        @endif

    </div>

    {{-- Sidebar --}}
    <div class="md:col-span-2 xl:col-span-1">

        {{-- Aksi --}}
        <div class="rounded-sm border border-paper-300 bg-paper-50 p-4">

            <h3 class="mb-3 font-semibold text-ink-900">
                Aksi
            </h3>

            <div class="space-y-2">

                @if (in_array($item->status, ['disetujui', 'diterima', 'selesai'], true))
                    <a href="{{ route("$routeBase.invoice", $item) }}" class="block w-full rounded-sm bg-emerald-600 px-4 py-2 text-center text-sm font-medium text-white hover:bg-emerald-700">
                        Lihat / Cetak Invoice
                    </a>
                @endif

                @if ($item->status === 'draft')
                    <form
                        action="{{ route('pembelian.approve', $item) }}"
                        method="POST"
                    >
                        @csrf
                        @method('PATCH')

                        <button
                            type="submit"
                            class="w-full rounded-sm bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700"
                        >
                            ✓ Setujui
                        </button>
                    </form>
                @endif

                @if (in_array($item->status, ['draft', 'disetujui']))
                    <form action="{{ route('pembelian.cancel', $item) }}" method="POST" onsubmit="return confirm('Batalkan pembelian ini?')">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="w-full rounded-sm border border-merah-300 px-4 py-2 text-sm font-medium text-merah-700 hover:bg-merah-50">
                            Batalkan Pembelian
                        </button>
                    </form>
                @endif

                @if ($item->status === 'dibatalkan')
                    <div class="rounded-sm border border-merah-300 bg-merah-50 p-3 text-sm text-merah-800">
                        Pembelian ini sudah dibatalkan.
                    </div>
                @endif

                @if ($item->status === 'draft')
                    <a
                        href="{{ route("$routeBase.edit", $item) }}"
                        class="block w-full rounded-sm border border-paper-300 px-4 py-2 text-center text-sm font-medium text-ink-700 hover:bg-paper-100"
                    >
                        Edit
                    </a>
                @endif

                <a
                    href="{{ route("$routeBase.index") }}"
                    class="block w-full rounded-sm border border-paper-300 px-4 py-2 text-center text-sm font-medium text-ink-700 hover:bg-paper-100"
                >
                    Kembali
                </a>

            </div>
        </div>

    </div>
</div>
</x-layouts.app>

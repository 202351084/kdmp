<x-layouts.app :title="$title" eyebrow="M6 Penjualan">
    @if (session('success'))
        <div class="mb-4 rounded-sm border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="mb-4 rounded-sm border border-merah-300 bg-merah-50 px-4 py-3 text-sm text-merah-800">{{ session('error') }}</div>
    @endif

    <div class="no-print mb-5 flex flex-wrap items-end justify-between gap-3">
        <div>
            <div class="flex items-center gap-2">
                <span class="rounded px-2 py-0.5 text-xs font-bold uppercase {{ $item->metode_bayar === 'kredit' ? 'bg-amber-100 text-amber-800' : 'bg-emerald-100 text-emerald-800' }}">{{ $item->metode_bayar === 'kredit' ? 'Piutang' : 'Selesai' }}</span>
                <p class="text-xs text-ink-600">Transaksi Penjualan Kasir</p>
            </div>
            <h1 class="mt-1 text-2xl font-bold text-ink-900">{{ $item->kode_penjualan }}</h1>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            {{-- Tombol Cetak Struk Kasir Thermal --}}
            <button
                type="button"
                onclick="window.printReceipt()"
                class="inline-flex items-center gap-1.5 rounded-sm bg-rose-600 px-3.5 py-2 text-sm font-bold text-white shadow-sm transition hover:bg-rose-700 active:scale-95"
            >
                <span>🖨️</span> Cetak Struk Kasir (Thermal)
            </button>

            @if ($hasReturnableItems)
                <a href="{{ route('penjualan.retur.create', ['id_penjualan' => $item->id_penjualan]) }}" class="rounded-sm border border-rose-300 bg-rose-50 px-3 py-2 text-sm font-semibold text-rose-700 hover:bg-rose-100">
                    Ajukan Retur
                </a>
            @elseif ($item->returs->isNotEmpty())
                <span class="rounded-sm bg-paper-200 px-3 py-2 text-xs font-semibold text-ink-600">Semua Barang Telah Diretur</span>
            @endif

            <a href="{{ route("$routeBase.create") }}" class="rounded-sm bg-emerald-600 px-3 py-2 text-sm font-semibold text-white hover:bg-emerald-700">
                + Transaksi Baru (POS)
            </a>

            <a href="{{ route("$routeBase.index") }}" class="rounded-sm border border-paper-300 px-3 py-2 text-sm font-medium text-ink-700 hover:bg-paper-100">
                Daftar Transaksi
            </a>
        </div>
    </div>

    {{-- Highlight Ringkasan Pembayaran & Kembalian --}}
    <div class="no-print mb-6 grid gap-3 sm:grid-cols-2 xl:grid-cols-6">
        <div class="border-l-4 border-rose-600 bg-rose-50 px-4 py-3">
            <p class="text-xs font-medium uppercase text-rose-800">Tanggal Transaksi</p>
            <p class="mt-1 font-semibold text-rose-950">{{ $item->tanggal_transaksi?->format('d M Y') ?? '-' }}</p>
        </div>
        <div class="border-l-4 border-rose-600 bg-rose-50 px-4 py-3">
            <p class="text-xs font-medium uppercase text-rose-800">Pelanggan</p>
            <p class="mt-1 font-semibold text-rose-950">{{ $item->nama_pembeli ?: ($item->pihak?->nama ?? 'Pembeli Umum') }}</p>
        </div>
        <div class="border-l-4 border-rose-600 bg-rose-50 px-4 py-3">
            <p class="text-xs font-medium uppercase text-rose-800">Metode Bayar</p>
            <p class="mt-1 font-semibold capitalize text-rose-950">{{ $item->metode_bayar }}</p>
        </div>
        <div class="border-l-4 border-rose-600 bg-rose-50 px-4 py-3">
            <p class="text-xs font-medium uppercase text-rose-800">Total Belanja</p>
            <p class="mt-1 font-bold text-rose-950">Rp {{ number_format((float) $item->total_bayar, 0, ',', '.') }}</p>
        </div>
        <div class="border-l-4 border-emerald-600 bg-emerald-50 px-4 py-3">
            <p class="text-xs font-medium uppercase text-emerald-800">{{ $item->metode_bayar === 'kredit' ? 'Sisa Piutang' : 'Uang Diterima' }}</p>
            <p class="mt-1 font-bold text-emerald-950">Rp {{ number_format($item->metode_bayar === 'kredit' ? (float) $item->total_bayar : (float) ($nominalBayar ?? $item->total_bayar), 0, ',', '.') }}</p>
        </div>
        <div class="border-l-4 border-emerald-600 bg-emerald-50 px-4 py-3">
            <p class="text-xs font-medium uppercase text-emerald-800">{{ $item->metode_bayar === 'kredit' ? 'Jatuh Tempo' : 'Uang Kembali' }}</p>
            <p class="mt-1 font-bold text-emerald-950">{{ $item->metode_bayar === 'kredit' ? ($item->tgl_jatuh_tempo?->format('d M Y') ?? '-') : 'Rp '.number_format((float) ($kembalian ?? 0), 0, ',', '.') }}</p>
        </div>
    </div>

    @if ($item->metode_bayar === 'kredit')
        <section class="mb-6 rounded-sm border border-amber-200 bg-amber-50/70 p-4 text-sm">
            <h2 class="font-bold text-amber-950">Informasi Piutang Penjualan</h2>
            <div class="mt-3 grid gap-3 sm:grid-cols-3">
                <div>
                    <p class="text-xs font-semibold uppercase text-amber-800">Pihak / debitur</p>
                    <p class="mt-1 font-semibold text-amber-950">{{ $item->nama_pembeli ?: ($item->pihak?->nama_pihak ?? '-') }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase text-amber-800">Nilai piutang</p>
                    <p class="mt-1 font-semibold text-amber-950">Rp {{ number_format((float) $item->total_bayar, 0, ',', '.') }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase text-amber-800">Tanggal jatuh tempo</p>
                    <p class="mt-1 font-semibold text-amber-950">{{ $item->tgl_jatuh_tempo?->format('d M Y') ?? '-' }}</p>
                </div>
            </div>
        </section>
    @endif

    @if ($item->metode_bayar === 'transfer')
        <section class="mb-6 rounded-sm border border-sky-200 bg-sky-50/70 p-4 text-sm">
            <h2 class="font-bold text-sky-950">Rincian Transfer</h2>
            <div class="mt-3 grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                <div>
                    <p class="text-xs font-semibold uppercase text-sky-800">Rekening penerima</p>
                    <p class="mt-1 font-semibold text-sky-950">{{ $item->transfer_penerima_nama ?: ($item->kasBank?->nama ?? '-') }}</p>
                    <p class="text-sky-900">{{ $item->transfer_penerima_rekening ?: ($item->kasBank?->no_rekening ?: 'Nomor rekening tidak tersedia') }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase text-sky-800">Pengirim</p>
                    <p class="mt-1 font-semibold text-sky-950">{{ $item->transfer_pengirim_nama }}</p>
                    <p class="text-sky-900">{{ $item->transfer_pengirim_bank }} · {{ $item->transfer_pengirim_rekening }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase text-sky-800">Referensi transfer</p>
                    <p class="mt-1 break-all font-semibold text-sky-950">{{ $item->transfer_no_referensi }}</p>
                    <p class="mt-1 text-xs text-sky-800">
                        Dikonfirmasi kasir{{ $item->transfer_dikonfirmasi_pada ? ' pada '.$item->transfer_dikonfirmasi_pada->format('d M Y H:i') : '' }}
                    </p>
                </div>
            </div>
        </section>
    @endif

    {{-- Main Content Grid: Tabel Barang & Pratinjau Struk Thermal Kasir --}}
    <div class="grid gap-6 xl:grid-cols-[1fr_360px]">
        
        {{-- Kolom Kiri: Tabel Barang & Riwayat Retur --}}
        <div class="no-print space-y-6">
            <section class="overflow-hidden rounded-sm border border-paper-300 bg-paper-50 shadow-2xs">
                <div class="border-b border-paper-300 px-4 py-3">
                    <h2 class="font-bold text-ink-900">Rincian Barang Terjual</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-[650px] w-full text-sm">
                        <thead class="bg-rose-50 text-left text-rose-950">
                            <tr>
                                <th class="px-4 py-3">Barang</th>
                                <th class="px-4 py-3 text-right">Qty</th>
                                <th class="px-4 py-3">Satuan</th>
                                <th class="px-4 py-3 text-right">Harga Satuan</th>
                                <th class="px-4 py-3 text-right">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-paper-200 bg-white">
                            @forelse ($item->detail as $line)
                                <tr class="hover:bg-rose-50/20">
                                    <td class="px-4 py-3 font-semibold text-ink-900">{{ $line->barang?->nama_barang ?? '-' }}</td>
                                    <td class="px-4 py-3 text-right font-mono">{{ number_format((float) $line->qty_dasar, 2, ',', '.') }}</td>
                                    <td class="px-4 py-3 text-ink-600">{{ $line->barang?->satuanDasar?->kode_satuan ?? '-' }}</td>
                                    <td class="px-4 py-3 text-right font-mono">Rp {{ number_format((float) $line->harga_satuan, 0, ',', '.') }}</td>
                                    <td class="px-4 py-3 text-right font-mono font-bold text-rose-700">Rp {{ number_format((float) $line->subtotal, 0, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="px-4 py-8 text-center text-ink-600">Tidak ada detail barang.</td></tr>
                            @endforelse
                        </tbody>
                        <tfoot class="border-t border-paper-300 bg-paper-100 font-bold">
                            <tr>
                                <th colspan="4" class="px-4 py-3 text-right">Total Tagihan</th>
                                <th class="px-4 py-3 text-right font-mono text-base text-rose-700">Rp {{ number_format((float) $item->total_bayar, 0, ',', '.') }}</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </section>

            @if ($item->returs && $item->returs->isNotEmpty())
                <section class="overflow-hidden rounded-sm border border-paper-300 bg-paper-50 shadow-2xs">
                    <div class="border-b border-paper-300 px-4 py-3">
                        <h2 class="font-bold text-ink-900">Riwayat Retur Penjualan ({{ $item->returs->count() }})</h2>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-[650px] w-full text-sm">
                            <thead class="bg-rose-50 text-left text-rose-950">
                                <tr>
                                    <th class="px-4 py-3">Kode Retur</th>
                                    <th class="px-4 py-3">Tanggal</th>
                                    <th class="px-4 py-3">Penyelesaian</th>
                                    <th class="px-4 py-3">Status</th>
                                    <th class="px-4 py-3 text-right">Total Nilai</th>
                                    <th class="px-4 py-3 text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-paper-200 bg-white">
                                @foreach ($item->returs as $retur)
                                    <tr class="hover:bg-rose-50/30">
                                        <td class="px-4 py-3 font-semibold text-ink-900">
                                            <a href="{{ route('penjualan.retur.show', $retur) }}" class="text-rose-700 hover:underline">
                                                {{ $retur->kode_retur }}
                                            </a>
                                        </td>
                                        <td class="px-4 py-3 text-ink-700">{{ $retur->tgl_retur?->format('d M Y') ?? '—' }}</td>
                                        <td class="px-4 py-3 capitalize text-ink-700">{{ str_replace('_', ' ', $retur->jenis_penyelesaian) }}</td>
                                        <td class="px-4 py-3">
                                            <span class="inline-flex rounded-sm px-2 py-0.5 text-xs font-semibold {{ $retur->status === 'selesai' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">
                                                {{ ucfirst($retur->status) }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-right font-medium text-rose-700">Rp {{ number_format((float) $retur->total_nilai, 0, ',', '.') }}</td>
                                        <td class="px-4 py-3 text-center">
                                            <a href="{{ route('penjualan.retur.show', $retur) }}" class="rounded-sm border border-paper-300 px-2.5 py-1 text-xs font-medium text-ink-700 hover:bg-paper-100">
                                                Lihat
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </section>
            @endif
        </div>

        {{-- Kolom Kanan: Pratinjau Struk Thermal Kasir 58mm/80mm --}}
        <div>
            <div class="no-print mb-2 flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-ink-700">Pratinjau Struk Kasir</span>
                <button type="button" onclick="window.printReceipt()" class="text-xs font-bold text-rose-700 hover:underline">
                    🖨️ Cetak Struk
                </button>
            </div>

            {{-- ELEMEN CETAK STRUK THERMAL RESMI --}}
            <div id="pos-thermal-receipt" class="rounded border border-paper-300 bg-white p-5 font-mono text-xs leading-relaxed text-black shadow-md">
                <div class="text-center">
                    <p class="text-sm font-bold uppercase tracking-wider">{{ $entitas?->nama_entitas ?? 'KOPERASI DESA MERAH PUTIH' }}</p>
                    <p class="text-[11px] font-semibold">{{ $item->unitUsaha?->nama_unit_usaha ?? 'UNIT TOKO DESA' }}</p>
                    <p class="text-[10px] text-gray-600">Pelayanan Belanja Warga Desa</p>
                    <div class="my-2 border-t border-b border-dashed border-gray-500 py-1 font-bold">
                        STRUK PEMBELIAN KASIR
                    </div>
                </div>

                <div class="space-y-0.5 text-[11px]">
                    <div class="flex justify-between">
                        <span>No Nota :</span>
                        <span class="font-bold">{{ $item->kode_penjualan }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span>Tanggal :</span>
                        <span>{{ $item->tanggal_transaksi?->format('d/m/Y') }} {{ $item->created_at?->format('H:i') }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span>Kasir   :</span>
                        <span>{{ auth()->user()->name ?? 'Petugas Kasir' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span>Pembeli :</span>
                        <span class="font-semibold">{{ $item->nama_pembeli ?: ($item->pihak?->nama ?? 'Pembeli Umum') }}</span>
                    </div>
                </div>

                <div class="my-2.5 border-t border-dashed border-gray-500"></div>

                {{-- Daftar Barang --}}
                <div class="space-y-2 text-[11px]">
                    @foreach ($item->detail as $line)
                        <div>
                            <div class="font-bold text-black">{{ $line->barang?->nama_barang ?? '-' }}</div>
                            <div class="flex justify-between text-gray-700">
                                <span>{{ number_format((float) $line->qty_dasar, 0, ',', '.') }} {{ $line->barang?->satuanDasar?->kode_satuan }} x Rp {{ number_format((float) $line->harga_satuan, 0, ',', '.') }}</span>
                                <span class="font-bold text-black">Rp {{ number_format((float) $line->subtotal, 0, ',', '.') }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="my-2.5 border-t border-dashed border-gray-500"></div>

                {{-- Ringkasan Pembayaran & Kembalian --}}
                <div class="space-y-1 text-[11px]">
                    <div class="flex justify-between font-bold text-xs">
                        <span>TOTAL TAGIHAN :</span>
                        <span>Rp {{ number_format((float) $item->total_bayar, 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between text-gray-700">
                        <span>METODE BAYAR  :</span>
                        <span class="capitalize font-semibold">{{ $item->metode_bayar }}</span>
                    </div>
                    @if ($item->metode_bayar === 'transfer')
                        <div class="border-t border-dashed border-gray-400 pt-1">
                            <p class="font-semibold">REKENING PENERIMA:</p>
                            <p>{{ $item->transfer_penerima_nama ?: ($item->kasBank?->nama ?? '-') }} · {{ $item->transfer_penerima_rekening ?: ($item->kasBank?->no_rekening ?? '-') }}</p>
                            <p>Pengirim: {{ $item->transfer_pengirim_nama }} ({{ $item->transfer_pengirim_bank }} · {{ $item->transfer_pengirim_rekening }})</p>
                            <p>Referensi: {{ $item->transfer_no_referensi }}</p>
                        </div>
                    @endif
                    <div class="flex justify-between">
                        <span>{{ $item->metode_bayar === 'kredit' ? 'DEBITUR :' : 'UANG DITERIMA :' }}</span>
                        <span class="font-semibold">{{ $item->metode_bayar === 'kredit' ? ($item->nama_pembeli ?: ($item->pihak?->nama_pihak ?? '-')) : 'Rp '.number_format((float) ($nominalBayar ?? $item->total_bayar), 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between font-bold">
                        <span>{{ $item->metode_bayar === 'kredit' ? 'JATUH TEMPO :' : 'KEMBALIAN     :' }}</span>
                        <span>{{ $item->metode_bayar === 'kredit' ? ($item->tgl_jatuh_tempo?->format('d/m/Y') ?? '-') : 'Rp '.number_format((float) ($kembalian ?? 0), 0, ',', '.') }}</span>
                    </div>
                    @if ($item->metode_bayar === 'kredit')
                        <div class="flex justify-between font-bold text-amber-900">
                            <span>SISA PIUTANG :</span>
                            <span>Rp {{ number_format((float) $item->total_bayar, 0, ',', '.') }}</span>
                        </div>
                    @endif
                </div>

                <div class="mt-4 border-t border-dashed border-gray-500 pt-3 text-center text-[10px] text-gray-600">
                    <p class="font-bold text-black">*** TERIMA KASIH ***</p>
                    <p class="mt-0.5">Semoga Berkah & Selamat Menikmati</p>
                    <p class="mt-0.5">Barang yang dibeli dapat ditukar/retur sesuai ketentuan koperasi desa.</p>
                </div>
            </div>

            <div class="no-print mt-3 text-center">
                <button
                    type="button"
                    onclick="window.printReceipt()"
                    class="w-full rounded-sm bg-rose-600 py-2.5 text-center text-xs font-bold text-white shadow-sm transition hover:bg-rose-700"
                >
                    🖨️ Cetak Struk Thermal (58mm/80mm)
                </button>
            </div>
        </div>
    </div>

    {{-- Print CSS Khusus Struk Kasir Thermal --}}
    <style>
        @media print {
            body {
                background: white !important;
                margin: 0 !important;
                padding: 0 !important;
            }
            .no-print {
                display: none !important;
            }
            body * {
                visibility: hidden !important;
            }
            #pos-thermal-receipt, #pos-thermal-receipt * {
                visibility: visible !important;
            }
            #pos-thermal-receipt {
                position: absolute !important;
                left: 0 !important;
                top: 0 !important;
                width: 72mm !important;
                max-width: 72mm !important;
                margin: 0 !important;
                padding: 4mm !important;
                border: none !important;
                box-shadow: none !important;
                font-family: monospace !important;
            }
        }
    </style>

    <script>
        window.printReceipt = function () {
            window.print();
        };

        @if ($autoPrint ?? false)
            window.addEventListener('load', function () {
                setTimeout(function () {
                    window.print();
                }, 400);
            });
        @endif
    </script>
</x-layouts.app>

<x-layouts.app :title="$title" eyebrow="M6 Penjualan">
    @if (session('success'))
        <div class="mb-4 rounded-sm border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="mb-4 rounded-sm border border-merah-300 bg-merah-50 px-4 py-3 text-sm text-merah-800">{{ session('error') }}</div>
    @endif
    @if ($errors->any())
        <div class="mb-4 rounded-sm border border-merah-300 bg-merah-50 p-4 text-sm text-merah-800">
            <ul class="list-inside list-disc space-y-1">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <section class="mb-5 rounded-sm border border-sky-300 bg-sky-50 p-4">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h2 class="font-semibold text-sky-950">Rekomendasi Penjualan dari Gudang (Data Contoh)</h2>
                <p class="mt-1 text-sm text-sky-900">
                    Rekomendasi simulasi berdasarkan contoh surplus dan kebutuhan komoditas. Ini hanya saran—kasir bebas menentukan barang dan pembeli, dan rekomendasi tidak mengubah keranjang maupun transaksi.
                </p>
            </div>
        </div>

        @if ($warehouseRecommendations->isEmpty())
            <p class="mt-3 text-sm text-sky-900">Belum ada rekomendasi barang tersedia dari gudang.</p>
        @else
            <ul class="mt-3 grid gap-3 md:grid-cols-2">
                @foreach ($warehouseRecommendations as $recommendation)
                    <li class="rounded-sm border border-sky-200 bg-white p-3 text-sm text-sky-950">
                        <p class="font-semibold">{{ $recommendation->barang->nama_barang }}</p>
                        <p class="mt-1"><strong>Jumlah saran:</strong> {{ number_format((float) $recommendation->qty_rekomendasi, 2, ',', '.') }} {{ $recommendation->barang->satuanDasar?->kode_satuan ?? 'unit' }}</p>
                        <p class="mt-1"><strong>Pasar yang disarankan:</strong> {{ $recommendation->tujuan_label }}</p>
                        <p class="mt-1">{{ $recommendation->alasan }}</p>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>

    {{-- POS Modern Header Bar --}}
    <div class="mb-5 flex flex-wrap items-center justify-between gap-4 rounded-sm border border-paper-300 bg-paper-50 p-4 shadow-sm">
        <div class="flex items-center gap-3">
            <div class="grid size-11 place-items-center rounded-sm bg-rose-600 text-xl font-bold text-white shadow-sm">
                POS
            </div>
            <div>
                <div class="flex items-center gap-2">
                    <h1 class="text-xl font-bold text-ink-900">Kasir / Point of Sale</h1>
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-semibold text-emerald-800">
                        <span class="size-1.5 rounded-full bg-emerald-500 animate-pulse"></span> Terminal Kasir Siap
                    </span>
                </div>
                <p class="text-xs text-ink-600">
                    {{ $entitas?->nama_entitas ?? 'Koperasi Desa Merah Putih' }} &bull; Operator: <span class="font-medium text-ink-800">{{ auth()->user()->name ?? 'Petugas Kasir' }}</span>
                </p>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            {{-- Digital Live Clock --}}
            <div class="flex items-center gap-2 rounded-sm border border-paper-300 bg-white px-3 py-1.5 font-mono text-xs text-ink-700 shadow-2xs">
                <span class="text-ink-400">🕒</span>
                <span id="pos-live-clock">--:--:-- WIB</span>
            </div>

            {{-- Keyboard Shortcut Helper Badge --}}
            <div class="hidden items-center gap-2 text-xs text-ink-500 md:flex">
                <span class="rounded border border-paper-300 bg-paper-100 px-1.5 py-0.5 font-mono">F2</span> Cari
                <span class="rounded border border-paper-300 bg-paper-100 px-1.5 py-0.5 font-mono">F4</span> Uang Pas
                <span class="rounded border border-paper-300 bg-paper-100 px-1.5 py-0.5 font-mono">Esc</span> Reset
            </div>

            <a href="{{ route("$routeBase.index") }}" class="inline-flex items-center gap-1.5 rounded-sm border border-paper-300 bg-white px-3 py-2 text-sm font-medium text-ink-700 shadow-2xs transition hover:border-rose-400 hover:bg-rose-50 hover:text-rose-700">
                <span>📋</span> Riwayat Transaksi
            </a>
        </div>
    </div>

    {{-- Main POS Layout --}}
    <div class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_440px]">
        
        {{-- PANEL KIRI: Katalog Barang & Filter --}}
        <section class="min-w-0">
            <div class="mb-4 rounded-sm border border-paper-300 bg-paper-50 p-4 shadow-sm">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="font-semibold text-ink-900">Katalog Barang Dagang</h2>
                        <p class="text-xs text-ink-600">Pilih atau klik barang untuk langsung memasukkan ke keranjang kasir.</p>
                    </div>

                    <div class="flex w-full flex-col gap-2 sm:max-w-md sm:flex-row">
                        <label class="min-w-0 flex-1 text-sm">
                            <span class="sr-only">Unit usaha</span>
                            <select id="unit-select" name="id_unit_usaha" form="checkout-form" required class="w-full rounded-sm border border-paper-300 bg-white px-3 py-2 text-sm font-medium text-ink-800 shadow-2xs focus:border-rose-500 focus:outline-none">
                                <option value="">Semua Unit Usaha</option>
                                @foreach ($units as $unit)
                                    <option value="{{ $unit->id_unit_usaha }}">{{ $unit->nama_unit_usaha }}</option>
                                @endforeach
                            </select>
                        </label>

                        <div class="relative min-w-0 flex-1">
                            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-xs text-ink-400">🔍</span>
                            <input id="product-search" type="search" placeholder="Cari nama barang... (F2)" class="w-full rounded-sm border border-paper-300 bg-white py-2 pr-8 pl-8 text-sm text-ink-900 shadow-2xs focus:border-rose-500 focus:outline-none">
                            <button id="clear-search" type="button" class="absolute inset-y-0 right-0 hidden items-center pr-2.5 text-xs text-ink-400 hover:text-ink-700">✕</button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Grid Kartu Produk POS --}}
            <div id="product-grid" class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
                @forelse ($products as $product)
                    <button
                        type="button"
                        data-product-card
                        data-add-product="{{ $product->id_barang }}"
                        data-name="{{ $product->nama_barang }}"
                        data-price="{{ (float) $product->harga_jual_standar }}"
                        data-unit="{{ $product->satuanDasar?->kode_satuan ?? 'unit' }}"
                        data-unit-id="{{ $product->id_unit_usaha }}"
                        data-stock="{{ number_format((float) $product->stok_demo, 4, '.', '') }}"
                        @disabled($product->stok_demo <= 0)
                        class="group relative flex min-h-40 flex-col justify-between rounded-sm border border-paper-300 bg-white p-3.5 text-left shadow-2xs transition-all duration-150 hover:-translate-y-0.5 hover:border-rose-500 hover:shadow-md focus:border-rose-600 focus:outline-none focus:ring-2 focus:ring-rose-500/30 disabled:cursor-not-allowed disabled:opacity-40"
                    >
                        {{-- Badge kuantitas di keranjang --}}
                        <span data-cart-badge class="absolute -top-2 -right-2 hidden rounded-full bg-rose-600 px-2 py-0.5 text-xs font-bold text-white shadow-sm">
                            0
                        </span>

                        <div>
                            <div class="mb-2 flex items-center justify-between gap-1">
                                <span class="grid size-9 place-items-center rounded bg-rose-50 text-base text-rose-700 transition group-hover:bg-rose-600 group-hover:text-white" aria-hidden="true">
                                    📦
                                </span>
                                <span class="rounded bg-paper-100 px-1.5 py-0.5 text-[10px] font-semibold uppercase text-ink-600">
                                    {{ $product->unitUsaha?->nama_unit_usaha ?? 'Toko' }}
                                </span>
                            </div>

                            <span class="block line-clamp-2 min-h-10 text-sm font-bold text-ink-900 group-hover:text-rose-700">
                                {{ $product->nama_barang }}
                            </span>
                        </div>

                        <div class="mt-3 border-t border-paper-100 pt-2">
                            <div class="flex items-baseline justify-between gap-1">
                                <span class="text-base font-extrabold text-rose-700">
                                    Rp {{ number_format((float) $product->harga_jual_standar, 0, ',', '.') }}
                                </span>
                                <span class="text-xs text-ink-500">/{{ $product->satuanDasar?->kode_satuan ?? 'unit' }}</span>
                            </div>

                            <div class="mt-1 flex items-center justify-between text-xs">
                                <span data-stock-label class="font-medium {{ $product->stok_demo <= 5 ? 'text-amber-700' : 'text-emerald-700' }}">
                                    Stok: {{ number_format((float) $product->stok_demo, 0, ',', '.') }} {{ $product->satuanDasar?->kode_satuan }}
                                </span>
                                <span class="text-[11px] font-semibold text-rose-600 opacity-0 transition group-hover:opacity-100">
                                    + Tambah
                                </span>
                            </div>
                        </div>
                    </button>
                @empty
                    <div class="col-span-full border border-dashed border-paper-300 bg-white px-4 py-16 text-center shadow-sm">
                        <span class="text-4xl">🌾</span>
                        <p class="mt-2 text-base font-semibold text-ink-800">Belum ada barang untuk dijual.</p>
                        <p class="mt-1 text-xs text-ink-600">Barang aktif dan pengaturan harga jual koperasi akan otomatis muncul di sini.</p>
                    </div>
                @endforelse
            </div>
        </section>

        {{-- PANEL KANAN: Terminal Kasir & Numpad Cepat --}}
        <aside class="self-start rounded-sm border border-rose-200 bg-white shadow-md">
            
            {{-- Header Terminal --}}
            <div class="border-b border-rose-200 bg-rose-50/80 px-4 py-3">
                <div class="flex items-center justify-between gap-3">
                    <div class="flex items-center gap-2">
                        <span class="text-lg">🛒</span>
                        <h2 class="font-bold text-rose-950">Pesanan Pelanggan</h2>
                        <span id="cart-count-badge" class="rounded-full bg-rose-600 px-2 py-0.5 text-xs font-semibold text-white">0</span>
                    </div>
                    <button id="clear-cart" type="button" class="text-xs font-semibold text-rose-700 hover:text-rose-900 hover:underline">
                        Kosongkan (Esc)
                    </button>
                </div>
            </div>

            <form id="checkout-form" method="POST" action="{{ route("$routeBase.store") }}" class="space-y-4 p-4">
                @csrf
                <input type="hidden" name="nominal_bayar" id="input_nominal_bayar" value="0">
                <input type="hidden" name="auto_print" id="input_auto_print" value="1">

                {{-- Wadah Daftar Item Belanja --}}
                <div id="cart-empty" class="border border-dashed border-paper-300 bg-paper-50/70 px-4 py-10 text-center">
                    <span class="text-3xl text-ink-300">🛒</span>
                    <p class="mt-2 text-sm font-medium text-ink-700">Keranjang kasir masih kosong</p>
                    <p class="mt-0.5 text-xs text-ink-500">Klik barang pada katalog di sebelah kiri untuk memulai belanja.</p>
                </div>

                <div id="cart-items" class="max-h-64 space-y-2 overflow-y-auto pr-1"></div>
                <div id="cart-inputs"></div>

                {{-- Data Pelanggan & Metode Pembayaran --}}
                <div class="space-y-3 rounded-sm border border-paper-200 bg-paper-50/60 p-3">
                    <label class="block text-sm">
                        <span id="customer-label" class="mb-1 block text-xs font-semibold uppercase text-ink-700">Nama Pembeli *</span>
                        <input type="text" name="nama_pembeli" value="{{ old('nama_pembeli') }}" required maxlength="150" autocomplete="off" placeholder="Ketik nama pembeli" class="w-full rounded-sm border border-paper-300 bg-white px-3 py-2 text-sm text-ink-900 shadow-2xs focus:border-rose-500 focus:outline-none">
                    </label>

                    <div>
                        <span class="mb-1.5 block text-xs font-semibold uppercase text-ink-700">Metode Pembayaran *</span>
                        <div class="grid grid-cols-3 gap-2">
                            <label class="payment-method-card flex cursor-pointer items-center justify-center gap-2 rounded-sm border border-rose-500 bg-rose-50 p-2 text-xs font-bold text-rose-900 transition has-[:checked]:border-rose-600 has-[:checked]:bg-rose-600 has-[:checked]:text-white">
                                <input type="radio" name="metode_bayar" value="tunai" @checked(old('metode_bayar', 'tunai') === 'tunai') class="sr-only">
                                <span>💵 Tunai (Cash)</span>
                            </label>
                            <label class="payment-method-card flex cursor-pointer items-center justify-center gap-2 rounded-sm border border-paper-300 bg-white p-2 text-xs font-semibold text-ink-700 transition hover:border-paper-400 has-[:checked]:border-rose-600 has-[:checked]:bg-rose-600 has-[:checked]:text-white {{ $bankAccounts->isEmpty() ? 'cursor-not-allowed opacity-50' : '' }}">
                                <input type="radio" name="metode_bayar" value="transfer" @checked(old('metode_bayar') === 'transfer') class="sr-only" @disabled($bankAccounts->isEmpty())>
                                <span>💳 Transfer / Bank</span>
                            </label>
                            <label class="payment-method-card flex cursor-pointer items-center justify-center gap-2 rounded-sm border border-paper-300 bg-white p-2 text-xs font-semibold text-ink-700 transition hover:border-paper-400 has-[:checked]:border-rose-600 has-[:checked]:bg-rose-600 has-[:checked]:text-white">
                                <input type="radio" name="metode_bayar" value="kredit" @checked(old('metode_bayar') === 'kredit') class="sr-only">
                                <span>🧾 Kredit</span>
                            </label>
                        </div>
                        @if ($bankAccounts->isEmpty())
                            <p class="mt-2 text-xs text-merah-700">Metode transfer belum tersedia: rekening bank aktif entitas ini belum dikonfigurasi.</p>
                        @endif
                    </div>

                    <div id="transfer-details-section" class="hidden space-y-3 rounded-sm border border-sky-200 bg-sky-50/60 p-3">
                        <div>
                            <h3 class="text-xs font-bold uppercase text-sky-950">Rincian Transfer</h3>
                            <p class="mt-1 text-xs text-sky-800">Pilih rekening koperasi penerima dan isi data pengirim sesuai bukti transfer.</p>
                        </div>
                        <label class="block text-sm">
                            <span class="mb-1 block text-xs font-semibold text-ink-700">Rekening tujuan / penerima *</span>
                            <select id="transfer-destination" name="id_kas_bank" class="w-full rounded-sm border border-paper-300 bg-white px-3 py-2 text-sm text-ink-900 focus:border-sky-500 focus:outline-none">
                                <option value="">Pilih rekening penerima</option>
                                @foreach ($bankAccounts as $bankAccount)
                                    <option value="{{ $bankAccount->id_kas_bank }}" @selected(old('id_kas_bank') == $bankAccount->id_kas_bank)>
                                        {{ $bankAccount->nama }}{{ $bankAccount->no_rekening ? ' — '.$bankAccount->no_rekening : '' }}{{ $bankAccount->is_default ? ' (Utama)' : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </label>
                        <div class="grid gap-3 sm:grid-cols-2">
                            <label class="block text-sm">
                                <span class="mb-1 block text-xs font-semibold text-ink-700">Nama pengirim *</span>
                                <input name="transfer_pengirim_nama" value="{{ old('transfer_pengirim_nama') }}" maxlength="150" class="transfer-input w-full rounded-sm border border-paper-300 bg-white px-3 py-2 text-sm text-ink-900 focus:border-sky-500 focus:outline-none">
                            </label>
                            <label class="block text-sm">
                                <span class="mb-1 block text-xs font-semibold text-ink-700">Bank pengirim *</span>
                                <input name="transfer_pengirim_bank" value="{{ old('transfer_pengirim_bank') }}" maxlength="100" class="transfer-input w-full rounded-sm border border-paper-300 bg-white px-3 py-2 text-sm text-ink-900 focus:border-sky-500 focus:outline-none">
                            </label>
                            <label class="block text-sm">
                                <span class="mb-1 block text-xs font-semibold text-ink-700">Nomor rekening pengirim *</span>
                                <input name="transfer_pengirim_rekening" value="{{ old('transfer_pengirim_rekening') }}" maxlength="50" class="transfer-input w-full rounded-sm border border-paper-300 bg-white px-3 py-2 text-sm text-ink-900 focus:border-sky-500 focus:outline-none">
                            </label>
                            <label class="block text-sm">
                                <span class="mb-1 block text-xs font-semibold text-ink-700">Nomor referensi transfer *</span>
                                <input name="transfer_no_referensi" value="{{ old('transfer_no_referensi') }}" maxlength="100" class="transfer-input w-full rounded-sm border border-paper-300 bg-white px-3 py-2 text-sm text-ink-900 focus:border-sky-500 focus:outline-none">
                            </label>
                        </div>
                        <label class="flex items-start gap-2 text-xs font-medium text-sky-950">
                            <input id="transfer-confirmed" type="checkbox" name="transfer_dikonfirmasi" value="1" @checked(old('transfer_dikonfirmasi')) class="mt-0.5 rounded border-sky-300 text-sky-600 focus:ring-sky-500">
                            <span>Saya telah memeriksa bukti dan memastikan dana transfer sudah masuk ke rekening tujuan.</span>
                        </label>
                    </div>

                    <div id="credit-details-section" class="hidden space-y-3 rounded-sm border border-amber-200 bg-amber-50/70 p-3">
                        <div>
                            <h3 class="text-xs font-bold uppercase text-amber-950">Penjualan Kredit</h3>
                            <p class="mt-1 text-xs text-amber-900">Pembeli yang dipilih akan dicatat sebagai pihak/debitur yang bertanggung jawab atas piutang ini.</p>
                        </div>
                        <label class="block text-sm">
                            <span class="mb-1 block text-xs font-semibold text-ink-700">Tanggal jatuh tempo *</span>
                            <input id="credit-due-date" type="date" name="tgl_jatuh_tempo" value="{{ old('tgl_jatuh_tempo') }}" min="{{ now()->toDateString() }}" class="w-full rounded-sm border border-paper-300 bg-white px-3 py-2 text-sm text-ink-900 focus:border-amber-500 focus:outline-none">
                        </label>
                    </div>
                </div>

                {{-- Screen Display Total Belanja (LED POS Display Style) --}}
                <div class="rounded-sm bg-gradient-to-r from-ink-950 to-rose-950 p-4 text-white shadow-inner">
                    <div class="flex items-center justify-between text-xs font-medium tracking-wider text-rose-200">
                        <span>TOTAL TAGIHAN</span>
                        <span id="pos-item-summary">0 Item</span>
                    </div>
                    <div id="cart-total-display" class="mt-1 text-right font-mono text-3xl font-extrabold tracking-tight text-white sm:text-4xl">
                        Rp 0
                    </div>
                </div>

                {{-- SECTION KALKULATOR NUMPAD & HITUNG KEMBALIAN (Hanya untuk Tunai) --}}
                <div id="cash-calculator-section" class="space-y-3 rounded-sm border border-rose-200 bg-rose-50/40 p-3">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-wide text-rose-950">Kalkulator Pembayaran Kasir</span>
                        <span class="text-[11px] font-medium text-ink-600">Shortcut: <kbd class="rounded bg-paper-200 px-1 font-mono text-[10px] text-ink-800">F4</kbd> Uang Pas</span>
                    </div>

                    {{-- Form Input Uang yang Diterima (Jelas, Terbaca & Kontras Tinggi) --}}
                    <div class="rounded-sm border-2 border-rose-300 bg-white p-3 shadow-xs">
                        <div class="flex items-center justify-between">
                            <label for="cash-input" class="flex items-center gap-1.5 text-xs font-bold uppercase tracking-wide text-rose-950">
                                <span class="text-base">💵</span>
                                <span>Form Uang yang Diterima</span>
                            </label>
                            <span id="display-uang-diterima" class="rounded bg-rose-100 px-2 py-0.5 font-mono text-xs font-bold text-rose-900">
                                Rp 0
                            </span>
                        </div>
                        <div class="relative mt-2 flex items-center rounded-sm border-2 border-paper-300 bg-paper-50 px-3 py-2 transition-all focus-within:border-rose-600 focus-within:bg-white focus-within:ring-2 focus-within:ring-rose-500/20">
                            <span class="font-mono text-xl font-extrabold text-rose-700 select-none">Rp</span>
                            <input
                                type="text"
                                id="cash-input"
                                name="cash_input_display"
                                inputmode="numeric"
                                value="0"
                                placeholder="0"
                                style="color: #0f172a !important; background-color: transparent !important;"
                                class="w-full bg-transparent pl-2 text-right font-mono text-2xl sm:text-3xl font-black tracking-tight text-ink-950 focus:outline-none"
                            >
                        </div>
                        <p class="mt-1.5 text-[11px] text-ink-500">
                            Nominal uang yang diinput kasir langsung tertera jelas di form ini.
                        </p>
                    </div>

                    {{-- Tombol Pecahan Uang Cepat (Quick Cash Presets) --}}
                    <div>
                        <span class="mb-1 block text-[11px] font-semibold uppercase tracking-wider text-ink-600">Pecahan Cepat:</span>
                        <div class="grid grid-cols-5 gap-1.5 font-mono">
                            <button type="button" data-cash-preset="10000" class="rounded border border-paper-300 bg-white py-1.5 text-center text-xs font-bold text-ink-800 shadow-2xs transition hover:border-rose-400 hover:bg-rose-50 active:scale-95">10k</button>
                            <button type="button" data-cash-preset="20000" class="rounded border border-paper-300 bg-white py-1.5 text-center text-xs font-bold text-ink-800 shadow-2xs transition hover:border-rose-400 hover:bg-rose-50 active:scale-95">20k</button>
                            <button type="button" data-cash-preset="50000" class="rounded border border-paper-300 bg-white py-1.5 text-center text-xs font-bold text-ink-800 shadow-2xs transition hover:border-rose-400 hover:bg-rose-50 active:scale-95">50k</button>
                            <button type="button" data-cash-preset="100000" class="rounded border border-paper-300 bg-white py-1.5 text-center text-xs font-bold text-ink-800 shadow-2xs transition hover:border-rose-400 hover:bg-rose-50 active:scale-95">100k</button>
                            <button type="button" data-cash-preset="200000" class="rounded border border-paper-300 bg-white py-1.5 text-center text-xs font-bold text-ink-800 shadow-2xs transition hover:border-rose-400 hover:bg-rose-50 active:scale-95">200k</button>
                        </div>
                    </div>

                    {{-- Numpad Kasir Kalkulator: Terstruktur Rapi Urut 1 s/d 0 --}}
                    <div class="rounded-sm border border-paper-300 bg-paper-100 p-2 shadow-xs">
                        <div class="grid grid-cols-3 gap-1.5 font-mono">
                            {{-- Baris 1: Angka 1, 2, 3 --}}
                            <button type="button" data-numpad="1" class="rounded border border-paper-200 bg-white py-2.5 text-center text-lg font-bold text-ink-950 shadow-2xs transition hover:border-rose-300 hover:bg-rose-50 active:scale-95 active:bg-rose-100">1</button>
                            <button type="button" data-numpad="2" class="rounded border border-paper-200 bg-white py-2.5 text-center text-lg font-bold text-ink-950 shadow-2xs transition hover:border-rose-300 hover:bg-rose-50 active:scale-95 active:bg-rose-100">2</button>
                            <button type="button" data-numpad="3" class="rounded border border-paper-200 bg-white py-2.5 text-center text-lg font-bold text-ink-950 shadow-2xs transition hover:border-rose-300 hover:bg-rose-50 active:scale-95 active:bg-rose-100">3</button>

                            {{-- Baris 2: Angka 4, 5, 6 --}}
                            <button type="button" data-numpad="4" class="rounded border border-paper-200 bg-white py-2.5 text-center text-lg font-bold text-ink-950 shadow-2xs transition hover:border-rose-300 hover:bg-rose-50 active:scale-95 active:bg-rose-100">4</button>
                            <button type="button" data-numpad="5" class="rounded border border-paper-200 bg-white py-2.5 text-center text-lg font-bold text-ink-950 shadow-2xs transition hover:border-rose-300 hover:bg-rose-50 active:scale-95 active:bg-rose-100">5</button>
                            <button type="button" data-numpad="6" class="rounded border border-paper-200 bg-white py-2.5 text-center text-lg font-bold text-ink-950 shadow-2xs transition hover:border-rose-300 hover:bg-rose-50 active:scale-95 active:bg-rose-100">6</button>

                            {{-- Baris 3: Angka 7, 8, 9 --}}
                            <button type="button" data-numpad="7" class="rounded border border-paper-200 bg-white py-2.5 text-center text-lg font-bold text-ink-950 shadow-2xs transition hover:border-rose-300 hover:bg-rose-50 active:scale-95 active:bg-rose-100">7</button>
                            <button type="button" data-numpad="8" class="rounded border border-paper-200 bg-white py-2.5 text-center text-lg font-bold text-ink-950 shadow-2xs transition hover:border-rose-300 hover:bg-rose-50 active:scale-95 active:bg-rose-100">8</button>
                            <button type="button" data-numpad="9" class="rounded border border-paper-200 bg-white py-2.5 text-center text-lg font-bold text-ink-950 shadow-2xs transition hover:border-rose-300 hover:bg-rose-50 active:scale-95 active:bg-rose-100">9</button>

                            {{-- Baris 4: Angka 0, 00, 000 --}}
                            <button type="button" data-numpad="0" class="rounded border border-paper-200 bg-white py-2.5 text-center text-lg font-bold text-ink-950 shadow-2xs transition hover:border-rose-300 hover:bg-rose-50 active:scale-95 active:bg-rose-100">0</button>
                            <button type="button" data-numpad="00" class="rounded border border-paper-200 bg-paper-50 py-2.5 text-center text-base font-bold text-ink-800 shadow-2xs transition hover:border-rose-300 hover:bg-rose-50 active:scale-95">00</button>
                            <button type="button" data-numpad="000" class="rounded border border-paper-200 bg-paper-50 py-2.5 text-center text-base font-bold text-ink-800 shadow-2xs transition hover:border-rose-300 hover:bg-rose-50 active:scale-95">000</button>

                            {{-- Baris 5: Tombol Aksi Kalkulator (Clear, Uang Pas, Backspace) --}}
                            <button type="button" data-numpad="clear" class="flex items-center justify-center gap-1 rounded border border-merah-200 bg-merah-50 py-2.5 text-center text-xs font-bold text-merah-700 shadow-2xs transition hover:bg-merah-100 active:scale-95" title="Hapus semua angka (Reset ke 0)">
                                <span>C</span>
                                <span class="text-[10px] font-normal opacity-80">(Reset)</span>
                            </button>
                            <button type="button" id="btn-uang-pas" class="flex items-center justify-center gap-1 rounded border border-rose-600 bg-rose-600 py-2.5 text-center text-xs font-bold text-white shadow-2xs transition hover:bg-rose-700 active:scale-95" title="Isi dengan nominal belanja pas (Shortcut: F4)">
                                <span>💵 Pas</span>
                                <span class="text-[10px] font-normal opacity-90">(F4)</span>
                            </button>
                            <button type="button" data-numpad="backspace" class="flex items-center justify-center gap-1 rounded border border-paper-300 bg-paper-200 py-2.5 text-center text-xs font-bold text-ink-800 shadow-2xs transition hover:bg-paper-300 active:scale-95" title="Hapus satu angka terakhir">
                                <span>⌫</span>
                                <span class="text-[10px] font-normal opacity-80">(Hapus)</span>
                            </button>
                        </div>
                    </div>

                    {{-- Kotak Hitung Kembalian Realtime --}}
                    <div id="change-display-box" class="rounded-sm border border-emerald-300 bg-emerald-50/90 p-3 transition-colors">
                        <div class="flex items-center justify-between text-xs font-semibold uppercase">
                            <span id="change-status-label" class="text-emerald-900">UANG KEMBALI</span>
                            <span id="change-badge" class="rounded bg-emerald-600 px-1.5 py-0.5 text-[10px] font-bold text-white">Lunas</span>
                        </div>
                        <div id="change-amount-display" class="mt-1 text-right font-mono text-2xl font-extrabold text-emerald-800">
                            Rp 0
                        </div>
                        <div class="mt-2.5 flex items-center justify-between border-t border-emerald-200/80 pt-2 text-xs font-semibold text-emerald-950">
                            <span>Uang Diterima:</span>
                            <span id="summary-cash-received" class="font-mono text-sm font-bold text-emerald-900">Rp 0</span>
                        </div>
                    </div>
                </div>

                {{-- Opsi Cetak Struk & Tombol Aksi --}}
                <div class="space-y-3 pt-1">
                    <label class="flex items-center gap-2 text-xs font-medium text-ink-700">
                        <input type="checkbox" id="check-auto-print" checked class="rounded text-rose-600 focus:ring-rose-500">
                        <span>Cetak struk nota belanja otomatis setelah disimpan</span>
                    </label>

                    <div class="grid grid-cols-[1fr_auto] gap-2">
                        <button
                            id="checkout-button"
                            type="submit"
                            disabled
                            class="flex items-center justify-center gap-2 rounded-sm bg-rose-600 px-4 py-3 text-sm font-bold tracking-wide text-white shadow-sm transition hover:bg-rose-700 disabled:cursor-not-allowed disabled:opacity-40"
                        >
                            <span>✓</span>
                            <span id="checkout-button-label">Simpan & Cetak Struk</span>
                        </button>

                        <button
                            type="button"
                            id="btn-preview-receipt"
                            title="Pratinjau Struk Belanja"
                            class="rounded-sm border border-paper-300 bg-white px-3 py-3 text-sm font-semibold text-ink-700 shadow-2xs transition hover:border-rose-400 hover:bg-rose-50 hover:text-rose-700"
                        >
                            🧾 Struk
                        </button>
                    </div>
                </div>
            </form>
        </aside>
    </div>

    {{-- MODAL PRATINJAU STRUK KASIR (Thermal 58mm/80mm) --}}
    <div id="receipt-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/60 p-4 backdrop-blur-xs">
        <div class="relative w-full max-w-sm rounded-sm bg-white p-6 shadow-2xl">
            <div class="mb-4 flex items-center justify-between border-b border-paper-200 pb-2">
                <h3 class="text-sm font-bold text-ink-900">Pratinjau Struk Kasir</h3>
                <button type="button" id="close-receipt-modal" class="text-ink-400 hover:text-ink-700">✕</button>
            </div>

            {{-- Format Struk Thermal --}}
            <div id="thermal-receipt-preview" class="border border-paper-300 bg-white p-4 font-mono text-xs leading-relaxed text-black shadow-inner">
                <div class="text-center">
                    <p class="font-bold uppercase text-sm tracking-wide">{{ $entitas?->nama_entitas ?? 'KOPERASI DESA MERAH PUTIH' }}</p>
                    <p class="text-[11px] text-gray-600">UNIT TOKO KOPERASI DESA</p>
                    <p class="text-[10px] text-gray-500">Telp / WA: 0812-3456-7890</p>
                    <p class="mt-1 border-t border-b border-dashed border-gray-400 py-1 text-[11px]">
                        NOTA PENJUALAN KASIR
                    </p>
                </div>

                <div class="mt-2 text-[11px] space-y-0.5">
                    <div class="flex justify-between">
                        <span>No. Nota:</span>
                        <span id="preview-nota-code" class="font-bold">JUL-DRAFT</span>
                    </div>
                    <div class="flex justify-between">
                        <span>Tanggal:</span>
                        <span id="preview-date">{{ now()->format('d/m/Y H:i') }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span>Kasir:</span>
                        <span>{{ auth()->user()->name ?? 'Petugas Toko' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span>Pelanggan:</span>
                        <span id="preview-customer" class="font-medium">-</span>
                    </div>
                </div>

                <div class="my-2 border-t border-dashed border-gray-400"></div>

                {{-- Baris Barang Struk --}}
                <div id="preview-items" class="space-y-1.5 text-[11px]"></div>

                <div class="my-2 border-t border-dashed border-gray-400"></div>

                {{-- Perhitungan Pembayaran --}}
                <div class="space-y-1 text-[11px]">
                    <div class="flex justify-between font-bold text-xs">
                        <span>TOTAL :</span>
                        <span id="preview-total">Rp 0</span>
                    </div>
                    <div class="flex justify-between text-gray-600">
                        <span>Metode:</span>
                        <span id="preview-method" class="capitalize">Tunai</span>
                    </div>
                    <div class="flex justify-between">
                        <span>Bayar :</span>
                        <span id="preview-paid">Rp 0</span>
                    </div>
                    <div id="preview-credit-due-row" class="hidden">
                        <div class="flex justify-between font-semibold text-amber-900">
                            <span>Jatuh tempo:</span>
                            <span id="preview-credit-due">-</span>
                        </div>
                    </div>
                    <div class="flex justify-between font-bold text-emerald-800">
                        <span>Kembali :</span>
                        <span id="preview-change">Rp 0</span>
                    </div>
                </div>

                <div class="mt-4 border-t border-dashed border-gray-400 pt-2 text-center text-[10px] text-gray-500">
                    <p class="font-bold text-gray-700">*** TERIMA KASIH ***</p>
                    <p>Barang yang sudah dibeli dapat diretur sesuai ketentuan koperasi.</p>
                </div>
            </div>

            <div class="mt-4 flex gap-2">
                <button type="button" id="btn-print-from-modal" class="flex-1 rounded-sm bg-rose-600 py-2.5 text-center text-xs font-bold text-white shadow-sm hover:bg-rose-700">
                    🖨️ Cetak Struk Kasir
                </button>
                <button type="button" id="btn-dismiss-modal" class="rounded-sm border border-paper-300 px-4 py-2.5 text-xs font-medium text-ink-700 hover:bg-paper-100">
                    Tutup
                </button>
            </div>
        </div>
    </div>

    {{-- Stylesheet Cetak Struk Thermal --}}
    <style>
        @media print {
            body * {
                visibility: hidden !important;
            }
            #thermal-receipt-preview, #thermal-receipt-preview * {
                visibility: visible !important;
            }
            #thermal-receipt-preview {
                position: absolute !important;
                left: 0 !important;
                top: 0 !important;
                width: 72mm !important;
                margin: 0 !important;
                padding: 4mm !important;
                border: none !important;
                box-shadow: none !important;
            }
        }
    </style>

    {{-- Script Logika Interaktif Kasir Modern, Numpad, Hitung Kembalian & Struk --}}
    <script>
        (() => {
            const cart = new Map();
            const cartItems = document.getElementById('cart-items');
            const cartInputs = document.getElementById('cart-inputs');
            const emptyState = document.getElementById('cart-empty');
            const unitSelect = document.getElementById('unit-select');
            const customerInput = document.querySelector('input[name="nama_pembeli"]');
            const checkoutButton = document.getElementById('checkout-button');
            const checkoutButtonLabel = document.getElementById('checkout-button-label');
            const totalDisplay = document.getElementById('cart-total-display');
            const itemSummaryDisplay = document.getElementById('pos-item-summary');
            const cartCountBadge = document.getElementById('cart-count-badge');
            const searchInput = document.getElementById('product-search');
            const clearSearchBtn = document.getElementById('clear-search');
            
            // Payment & Cash Calculator Elements
            const paymentRadios = document.querySelectorAll('input[name="metode_bayar"]');
            const cashCalcSection = document.getElementById('cash-calculator-section');
            const transferDetailsSection = document.getElementById('transfer-details-section');
            const creditDetailsSection = document.getElementById('credit-details-section');
            const creditDueDate = document.getElementById('credit-due-date');
            const customerLabel = document.getElementById('customer-label');
            const transferDestination = document.getElementById('transfer-destination');
            const transferInputs = document.querySelectorAll('.transfer-input');
            const transferConfirmation = document.getElementById('transfer-confirmed');
            const cashInput = document.getElementById('cash-input');
            const hiddenNominalInput = document.getElementById('input_nominal_bayar');
            const autoPrintCheckbox = document.getElementById('check-auto-print');
            const hiddenAutoPrint = document.getElementById('input_auto_print');
            const btnUangPas = document.getElementById('btn-uang-pas');
            const changeDisplayBox = document.getElementById('change-display-box');
            const changeStatusLabel = document.getElementById('change-status-label');
            const changeAmountDisplay = document.getElementById('change-amount-display');
            const changeBadge = document.getElementById('change-badge');

            // Modal Receipt Elements
            const receiptModal = document.getElementById('receipt-modal');
            const btnPreviewReceipt = document.getElementById('btn-preview-receipt');
            const closeReceiptModal = document.getElementById('close-receipt-modal');
            const btnDismissModal = document.getElementById('btn-dismiss-modal');
            const btnPrintFromModal = document.getElementById('btn-print-from-modal');

            const formatter = new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 });

            let currentTotal = 0;
            let currentCash = 0;

            // Jam Digital Realtime
            function updateClock() {
                const now = new Date();
                const clockElem = document.getElementById('pos-live-clock');
                if (clockElem) {
                    const hours = String(now.getHours()).padStart(2, '0');
                    const mins = String(now.getMinutes()).padStart(2, '0');
                    const secs = String(now.getSeconds()).padStart(2, '0');
                    clockElem.textContent = `${hours}:${mins}:${secs} WIB`;
                }
            }
            setInterval(updateClock, 1000);
            updateClock();

            // Format angka ke format ribuan Rupiah
            function formatNumber(num) {
                return formatter.format(num);
            }

            // Parse input string angka
            function parseCleanNumber(str) {
                return Number(String(str).replace(/[^\d]/g, '')) || 0;
            }

            // Update badge dan status kartu produk di katalog
            function syncProducts() {
                document.querySelectorAll('[data-product-card]').forEach((card) => {
                    const id = card.dataset.addProduct;
                    const item = cart.get(id);
                    const cartQty = item?.quantity ?? 0;
                    const stock = Number(card.dataset.stock);
                    const remaining = Math.max(0, stock - cartQty);
                    
                    const stockLabel = card.querySelector('[data-stock-label]');
                    if (stockLabel) {
                        stockLabel.textContent = `Stok: ${formatNumber(remaining)} ${card.dataset.unit}`;
                    }

                    // Badge di pojok kartu
                    const badge = card.querySelector('[data-cart-badge]');
                    if (badge) {
                        badge.textContent = `${cartQty} di keranjang`;
                        badge.classList.toggle('hidden', cartQty <= 0);
                    }

                    const isUnitMatch = !unitSelect.value || card.dataset.unitId === unitSelect.value;
                    card.disabled = remaining <= 0 || !isUnitMatch;
                    card.classList.toggle('hidden', !isUnitMatch);
                });
            }

            // Hitung kembalian dan validasi tombol submit
            function calculateChange() {
                const selectedMethod = document.querySelector('input[name="metode_bayar"]:checked')?.value || 'tunai';
                
                if (selectedMethod === 'transfer') {
                    cashCalcSection.style.display = 'none';
                    transferDetailsSection.classList.remove('hidden');
                    creditDetailsSection.classList.add('hidden');
                    creditDueDate.required = false;
                    customerLabel.textContent = 'Nama Pembeli *';
                    transferDestination.required = true;
                    transferInputs.forEach((input) => { input.required = true; });
                    transferConfirmation.required = true;
                    hiddenNominalInput.value = currentTotal;
                    const transferReady = transferDestination.value
                        && [...transferInputs].every((input) => input.value.trim() !== '')
                        && transferConfirmation.checked;
                    checkoutButton.disabled = cart.size === 0 || !unitSelect.value || !customerInput.value.trim() || !transferReady;
                    checkoutButtonLabel.textContent = `Bayar & Simpan (Rp ${formatNumber(currentTotal)})`;
                    return;
                }

                if (selectedMethod === 'kredit') {
                    cashCalcSection.style.display = 'none';
                    transferDetailsSection.classList.add('hidden');
                    transferDestination.required = false;
                    transferInputs.forEach((input) => { input.required = false; });
                    transferConfirmation.required = false;
                    creditDetailsSection.classList.remove('hidden');
                    creditDueDate.required = true;
                    customerLabel.textContent = 'Nama Pembeli / Debitur *';
                    hiddenNominalInput.value = '0';
                    checkoutButton.disabled = cart.size === 0 || !unitSelect.value || !customerInput.value.trim() || !creditDueDate.value;
                    checkoutButtonLabel.textContent = `Catat Kredit (Rp ${formatNumber(currentTotal)})`;
                    return;
                }

                cashCalcSection.style.display = 'block';
                transferDetailsSection.classList.add('hidden');
                creditDetailsSection.classList.add('hidden');
                creditDueDate.required = false;
                transferDestination.required = false;
                transferInputs.forEach((input) => { input.required = false; });
                transferConfirmation.required = false;
                customerLabel.textContent = 'Nama Pembeli *';
                currentCash = parseCleanNumber(cashInput.value);
                hiddenNominalInput.value = currentCash;

                const displayBadge = document.getElementById('display-uang-diterima');
                if (displayBadge) {
                    displayBadge.textContent = `Rp ${formatNumber(currentCash)}`;
                }
                const summaryCash = document.getElementById('summary-cash-received');
                if (summaryCash) {
                    summaryCash.textContent = `Rp ${formatNumber(currentCash)}`;
                }

                const diff = currentCash - currentTotal;

                if (currentTotal === 0) {
                    changeDisplayBox.className = 'rounded-sm border border-paper-300 bg-paper-100 p-3 transition-colors';
                    changeStatusLabel.textContent = 'UANG KEMBALI';
                    changeStatusLabel.className = 'text-ink-700';
                    changeAmountDisplay.textContent = 'Rp 0';
                    changeAmountDisplay.className = 'mt-1 text-right font-mono text-2xl font-extrabold text-ink-600';
                    changeBadge.textContent = 'Belum Ada Belanja';
                    changeBadge.className = 'rounded bg-paper-300 px-1.5 py-0.5 text-[10px] font-bold text-ink-700';
                    checkoutButton.disabled = true;
                    checkoutButtonLabel.textContent = 'Simpan & Cetak Struk';
                    return;
                }

                if (diff >= 0) {
                    // Lunas / Ada Kembalian
                    changeDisplayBox.className = 'rounded-sm border border-emerald-300 bg-emerald-50/90 p-3 transition-colors';
                    changeStatusLabel.textContent = 'UANG KEMBALI (KEMBALIAN)';
                    changeStatusLabel.className = 'text-emerald-900';
                    changeAmountDisplay.textContent = `Rp ${formatNumber(diff)}`;
                    changeAmountDisplay.className = 'mt-1 text-right font-mono text-2xl font-extrabold text-emerald-800';
                    changeBadge.textContent = diff === 0 ? 'Uang Pas' : 'Lunas';
                    changeBadge.className = 'rounded bg-emerald-600 px-1.5 py-0.5 text-[10px] font-bold text-white';
                    
                    checkoutButton.disabled = cart.size === 0 || !unitSelect.value || !customerInput.value.trim();
                    checkoutButtonLabel.textContent = `Bayar & Cetak Struk (Kembali Rp ${formatNumber(diff)})`;
                } else {
                    // Kurang
                    const shortage = Math.abs(diff);
                    changeDisplayBox.className = 'rounded-sm border border-rose-300 bg-rose-50/90 p-3 transition-colors';
                    changeStatusLabel.textContent = 'UANG MASIH KURANG';
                    changeStatusLabel.className = 'text-rose-900';
                    changeAmountDisplay.textContent = `Kurang Rp ${formatNumber(shortage)}`;
                    changeAmountDisplay.className = 'mt-1 text-right font-mono text-2xl font-extrabold text-rose-700';
                    changeBadge.textContent = 'Kurang';
                    changeBadge.className = 'rounded bg-rose-600 px-1.5 py-0.5 text-[10px] font-bold text-white';

                    checkoutButton.disabled = true;
                    checkoutButtonLabel.textContent = `Uang Kurang Rp ${formatNumber(shortage)}`;
                }
            }

            // Render daftar keranjang belanja
            function renderCart() {
                cartItems.replaceChildren();
                cartInputs.replaceChildren();
                currentTotal = 0;
                let count = 0;
                let index = 0;

                cart.forEach((item, id) => {
                    const itemSubtotal = item.price * item.quantity;
                    currentTotal += itemSubtotal;
                    count += item.quantity;

                    const row = document.createElement('div');
                    row.className = 'flex items-center justify-between gap-3 rounded-sm border border-paper-200 bg-white p-2.5 shadow-2xs';

                    // Info Produk
                    const details = document.createElement('div');
                    details.className = 'min-w-0 flex-1';
                    const name = document.createElement('p');
                    name.className = 'truncate text-xs font-bold text-ink-900';
                    name.textContent = item.name;
                    const priceSub = document.createElement('p');
                    priceSub.className = 'text-[11px] text-ink-500';
                    priceSub.textContent = `Rp ${formatNumber(item.price)} / ${item.unit}`;
                    details.append(name, priceSub);

                    // Kontrol Kuantitas Modern (+ / - / input)
                    const controls = document.createElement('div');
                    controls.className = 'flex shrink-0 items-center gap-1.5';

                    const decBtn = document.createElement('button');
                    decBtn.type = 'button';
                    decBtn.className = 'grid size-6 place-items-center rounded border border-paper-300 bg-paper-50 text-xs font-bold text-ink-700 hover:border-rose-400 hover:bg-rose-50 hover:text-rose-700';
                    decBtn.textContent = '−';
                    decBtn.title = 'Kurangi satu';
                    decBtn.addEventListener('click', () => {
                        if (item.quantity <= 1) cart.delete(id);
                        else item.quantity -= 1;
                        renderCart();
                    });

                    const qtyInput = document.createElement('input');
                    qtyInput.type = 'number';
                    qtyInput.min = '1';
                    qtyInput.max = String(item.available);
                    qtyInput.value = item.quantity;
                    qtyInput.className = 'w-10 rounded border border-paper-300 py-0.5 text-center font-mono text-xs font-bold text-ink-900 focus:border-rose-500 focus:outline-none';
                    qtyInput.addEventListener('change', (e) => {
                        let val = Math.floor(Number(e.target.value));
                        if (isNaN(val) || val <= 0) val = 1;
                        if (val > item.available) val = item.available;
                        item.quantity = val;
                        renderCart();
                    });

                    const incBtn = document.createElement('button');
                    incBtn.type = 'button';
                    incBtn.className = 'grid size-6 place-items-center rounded border border-paper-300 bg-paper-50 text-xs font-bold text-ink-700 hover:border-rose-400 hover:bg-rose-50 hover:text-rose-700 disabled:opacity-30';
                    incBtn.textContent = '+';
                    incBtn.title = 'Tambah satu';
                    incBtn.disabled = item.quantity >= item.available;
                    incBtn.addEventListener('click', () => {
                        if (item.quantity < item.available) {
                            item.quantity += 1;
                            renderCart();
                        }
                    });

                    // Subtotal Display
                    const subtotalElem = document.createElement('span');
                    subtotalElem.className = 'min-w-20 text-right font-mono text-xs font-bold text-rose-700';
                    subtotalElem.textContent = `Rp ${formatNumber(itemSubtotal)}`;

                    // Tombol Hapus
                    const delBtn = document.createElement('button');
                    delBtn.type = 'button';
                    delBtn.className = 'ml-1 text-xs text-ink-400 hover:text-merah-600';
                    delBtn.textContent = '✕';
                    delBtn.title = 'Hapus barang ini';
                    delBtn.addEventListener('click', () => {
                        cart.delete(id);
                        renderCart();
                    });

                    controls.append(decBtn, qtyInput, incBtn, subtotalElem, delBtn);
                    row.append(details, controls);
                    cartItems.append(row);

                    // Input form hidden untuk POST
                    [
                        ['id_barang', id],
                        ['qty_dasar', item.quantity],
                    ].forEach(([field, value]) => {
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = `items[${index}][${field}]`;
                        input.value = value;
                        cartInputs.append(input);
                    });
                    index += 1;
                });

                emptyState.classList.toggle('hidden', cart.size > 0);
                totalDisplay.textContent = `Rp ${formatNumber(currentTotal)}`;
                itemSummaryDisplay.textContent = `${count} Total Item`;
                cartCountBadge.textContent = count;

                syncProducts();
                calculateChange();
            }

            // Tambah barang ke keranjang
            document.querySelectorAll('[data-add-product]').forEach((button) => {
                button.addEventListener('click', () => {
                    const id = button.dataset.addProduct;
                    if (!unitSelect.value) {
                        unitSelect.value = button.dataset.unitId;
                    }
                    if (unitSelect.value !== button.dataset.unitId) return;

                    const item = cart.get(id) ?? {
                        name: button.dataset.name,
                        price: Number(button.dataset.price),
                        unit: button.dataset.unit,
                        unitId: button.dataset.unitId,
                        available: Number(button.dataset.stock),
                        quantity: 0,
                    };

                    if (item.quantity >= item.available) return;
                    item.quantity += 1;
                    cart.set(id, item);
                    renderCart();
                });
            });

            // Kosongkan keranjang
            document.getElementById('clear-cart').addEventListener('click', () => {
                cart.clear();
                cashInput.value = '0';
                renderCart();
            });

            // Ganti Unit Usaha
            unitSelect.addEventListener('change', () => {
                for (const [id, item] of cart) {
                    if (unitSelect.value && item.unitId !== unitSelect.value) {
                        cart.delete(id);
                    }
                }
                renderCart();
            });

            // Pilihan Metode Pembayaran
            paymentRadios.forEach((radio) => {
                radio.addEventListener('change', () => {
                    document.querySelectorAll('.payment-method-card').forEach((card) => {
                        const input = card.querySelector('input');
                        if (input.checked) {
                            card.className = 'payment-method-card flex cursor-pointer items-center justify-center gap-2 rounded-sm border border-rose-600 bg-rose-600 p-2 text-xs font-bold text-white shadow-2xs';
                        } else {
                            card.className = 'payment-method-card flex cursor-pointer items-center justify-center gap-2 rounded-sm border border-paper-300 bg-white p-2 text-xs font-semibold text-ink-700 hover:border-paper-400';
                        }
                    });
                    calculateChange();
                });
            });

            [transferDestination, ...transferInputs, transferConfirmation].forEach((input) => {
                input.addEventListener('input', calculateChange);
                input.addEventListener('change', calculateChange);
            });
            creditDueDate.addEventListener('input', calculateChange);
            creditDueDate.addEventListener('change', calculateChange);

            // Input Uang Tunai Manual dengan formatting kalkulator realtime
            cashInput.addEventListener('input', (e) => {
                const raw = e.target.value;
                const clean = parseCleanNumber(raw);
                e.target.value = clean > 0 ? formatNumber(clean) : (raw === '' ? '' : '0');
                calculateChange();
            });

            cashInput.addEventListener('focus', () => {
                if (cashInput.value === '0') {
                    cashInput.select();
                }
            });

            cashInput.addEventListener('blur', () => {
                if (!cashInput.value || cashInput.value === '') {
                    cashInput.value = '0';
                    calculateChange();
                }
            });

            // Tombol Uang Pas (F4)
            btnUangPas.addEventListener('click', () => {
                if (currentTotal > 0) {
                    cashInput.value = formatNumber(currentTotal);
                } else {
                    cashInput.value = '0';
                }
                calculateChange();
            });

            // Tombol Pecahan Cepat
            document.querySelectorAll('[data-cash-preset]').forEach((btn) => {
                btn.addEventListener('click', () => {
                    const presetVal = Number(btn.dataset.cashPreset);
                    cashInput.value = formatNumber(presetVal);
                    calculateChange();
                });
            });

            // Numpad Touch Cepat Kalkulator: Urut 1 s/d 0
            document.querySelectorAll('[data-numpad]').forEach((btn) => {
                btn.addEventListener('click', () => {
                    const action = btn.dataset.numpad;
                    let currentValStr = String(parseCleanNumber(cashInput.value) || '');

                    if (action === 'clear') {
                        cashInput.value = '0';
                    } else if (action === 'backspace') {
                        currentValStr = currentValStr.slice(0, -1);
                        cashInput.value = currentValStr ? formatNumber(Number(currentValStr)) : '0';
                    } else {
                        // Angka 0 - 9, 00, 000
                        if (currentValStr === '0' || currentValStr === '') {
                            if (action === '0' || action === '00' || action === '000') {
                                cashInput.value = '0';
                                calculateChange();
                                return;
                            }
                            currentValStr = '';
                        }
                        currentValStr += action;
                        // Batasi max 11 digit (hingga puluhan miliar)
                        if (currentValStr.length > 11) currentValStr = currentValStr.slice(0, 11);
                        cashInput.value = formatNumber(Number(currentValStr));
                    }
                    calculateChange();
                });
            });

            // Checkbox Cetak Otomatis
            autoPrintCheckbox.addEventListener('change', (e) => {
                hiddenAutoPrint.value = e.target.checked ? '1' : '0';
            });

            // Pencarian Produk
            searchInput.addEventListener('input', (e) => {
                const query = e.target.value.trim().toLocaleLowerCase('id-ID');
                clearSearchBtn.classList.toggle('hidden', query === '');
                
                document.querySelectorAll('[data-product-card]').forEach((card) => {
                    const matchName = card.dataset.name.toLocaleLowerCase('id-ID').includes(query);
                    const matchUnit = !unitSelect.value || card.dataset.unitId === unitSelect.value;
                    card.classList.toggle('hidden', !matchName || !matchUnit);
                });
            });

            clearSearchBtn.addEventListener('click', () => {
                searchInput.value = '';
                clearSearchBtn.classList.add('hidden');
                searchInput.dispatchEvent(new Event('input'));
                searchInput.focus();
            });

            // Keyboard Shortcuts Listener (F2, F4, Esc)
            window.addEventListener('keydown', (e) => {
                if (e.key === 'F2') {
                    e.preventDefault();
                    searchInput.focus();
                } else if (e.key === 'F4') {
                    e.preventDefault();
                    btnUangPas.click();
                } else if (e.key === 'Escape') {
                    if (!receiptModal.classList.contains('hidden')) {
                        receiptModal.classList.add('hidden');
                    }
                }
            });

            // Modal Pratinjau Struk Kasir
            function updateReceiptModal() {
                const customerName = customerInput.value.trim() || 'Pembeli Umum';
                const method = document.querySelector('input[name="metode_bayar"]:checked')?.value || 'tunai';
                
                document.getElementById('preview-customer').textContent = customerName;
                document.getElementById('preview-method').textContent = method.toUpperCase();
                document.getElementById('preview-total').textContent = `Rp ${formatNumber(currentTotal)}`;
                
                const paidVal = method === 'transfer' ? currentTotal : (method === 'kredit' ? 0 : currentCash);
                const changeVal = Math.max(0, paidVal - currentTotal);
                
                document.getElementById('preview-paid').textContent = `Rp ${formatNumber(paidVal)}`;
                document.getElementById('preview-change').textContent = `Rp ${formatNumber(changeVal)}`;
                const dueRow = document.getElementById('preview-credit-due-row');
                dueRow.classList.toggle('hidden', method !== 'kredit');
                document.getElementById('preview-credit-due').textContent = creditDueDate.value
                    ? new Date(`${creditDueDate.value}T00:00:00`).toLocaleDateString('id-ID')
                    : '-';

                const itemsContainer = document.getElementById('preview-items');
                itemsContainer.replaceChildren();

                cart.forEach((item) => {
                    const itemRow = document.createElement('div');
                    itemRow.innerHTML = `
                        <div class="font-bold text-gray-900">${item.name}</div>
                        <div class="flex justify-between text-gray-600">
                            <span>${item.quantity} x Rp ${formatNumber(item.price)}</span>
                            <span class="font-bold text-gray-900">Rp ${formatNumber(item.price * item.quantity)}</span>
                        </div>
                    `;
                    itemsContainer.appendChild(itemRow);
                });

                if (cart.size === 0) {
                    itemsContainer.innerHTML = '<p class="text-center text-gray-400 italic">Belum ada item belanja.</p>';
                }
            }

            btnPreviewReceipt.addEventListener('click', () => {
                updateReceiptModal();
                receiptModal.classList.remove('hidden');
                receiptModal.classList.add('flex');
            });

            closeReceiptModal.addEventListener('click', () => {
                receiptModal.classList.add('hidden');
                receiptModal.classList.remove('flex');
            });

            btnDismissModal.addEventListener('click', () => {
                receiptModal.classList.add('hidden');
                receiptModal.classList.remove('flex');
            });

            btnPrintFromModal.addEventListener('click', () => {
                window.print();
            });

            customerInput.addEventListener('input', calculateChange);

            // Inisialisasi awal
            renderCart();
        })();
    </script>
</x-layouts.app>

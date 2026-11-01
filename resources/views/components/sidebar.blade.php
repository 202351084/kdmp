@php
    $sidebarGroups = [
        [
            'title' => 'Master Data',
            'icon' => '<svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" /></svg>',
            'match' => 'master.*',
            'items' => [
                ['label' => 'Entitas', 'route' => 'master.entitas.index', 'match' => 'master.entitas.*', 'super_admin_only' => true],
                ['label' => 'Komoditas', 'route' => 'master.komoditas.index', 'match' => 'master.komoditas.*'],
                ['label' => 'Satuan Ukur', 'route' => 'master.satuan.index', 'match' => 'master.satuan.*'],
                ['label' => 'Barang', 'route' => 'master.barang.index', 'match' => 'master.barang.*'],
                ['label' => 'Pihak/Mitra', 'route' => 'master.pihak.index', 'match' => 'master.pihak.*'],
                ['label' => 'Gudang', 'route' => 'master.gudang.index', 'match' => 'master.gudang.*'],
                ['label' => 'Kas & Bank', 'route' => 'master.kas-bank.index', 'match' => 'master.kas-bank.*'],
                ['label' => 'Periode Akuntansi', 'route' => 'master.periode.index', 'match' => 'master.periode.*'],
                ['label' => 'Chart of Accounts', 'route' => 'master.coa.index', 'match' => 'master.coa.*'],
            ]
        ],
        [
            'title' => 'Survei & Pemetaan',
            'icon' => '<svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" /></svg>',
            'match' => 'survei.*',
            'items' => [
                ['label' => 'Dashboard Survei', 'route' => 'survei.dashboard.index', 'match' => 'survei.dashboard.*'],
                ['label' => 'Sesi Survei', 'route' => 'survei.sesi.index', 'match' => 'survei.sesi.*'],
                ['label' => 'Data RT', 'route' => 'survei.rt.index', 'match' => 'survei.rt.*'],
                ['label' => 'Produsen Desa', 'route' => 'survei.produsen.index', 'match' => 'survei.produsen.*'],
                ['label' => 'Validasi Survei', 'route' => 'survei.validasi.index', 'match' => 'survei.validasi.*'],
                ['label' => 'Bank Pertanyaan', 'route' => 'survei.pertanyaan.index', 'match' => 'survei.pertanyaan.*'],
            ]
        ],
        [
            'title' => 'Kalkulasi & Perencanaan',
            'icon' => '<svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" /></svg>',
            'match' => 'perencanaan.*',
            'items' => [
                ['label' => 'Dashboard Kalkulasi dan Perencanaan', 'route' => 'perencanaan.dashboard', 'match' => 'perencanaan.dashboard'],
                ['label' => 'Data & Parameter', 'route' => 'perencanaan.demografi.index', 'match' => 'perencanaan.demografi.*,perencanaan.potensi-produksi.*,perencanaan.standar-kebutuhan.*'],
                ['label' => 'Kalkulasi Kebutuhan', 'route' => 'perencanaan.kebutuhan-komoditas.index', 'match' => 'perencanaan.kebutuhan-komoditas.*'],
                ['label' => 'Surplus Defisit', 'route' => 'perencanaan.neraca-komoditas.index', 'match' => 'perencanaan.neraca-komoditas.*'],
                ['label' => 'Laporan Kalkulasi', 'route' => 'perencanaan.laporan.index', 'match' => 'perencanaan.laporan.*'],
                ['label' => 'Permintaan Pengadaan', 'route' => 'perencanaan.permintaan-pengadaan.index', 'match' => 'perencanaan.permintaan-pengadaan.*'],
                ['label' => 'Perbandingan Harga', 'route' => 'perencanaan.perbandingan-harga.index', 'match' => 'perencanaan.perbandingan-harga.*'],
            ]
        ],
        [
            'title' => 'Manajemen Gudang',
            'icon' => '<svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" /></svg>',
            'match' => 'gudang.*',
            'items' => [
                ['label' => 'Dashboard Gudang', 'route' => 'gudang.index', 'match' => 'gudang.index'],
                ['label' => 'Pencocokan Surplus-Defisit', 'route' => 'gudang.pencocokan-surplus-defisit.index', 'match' => 'gudang.pencocokan-surplus-defisit.*'],
                ['label' => 'Posisi Stok', 'route' => 'gudang.stok.index', 'match' => 'gudang.stok.*'],
                ['label' => 'Penerimaan', 'route' => 'gudang.penerimaan.index', 'match' => 'gudang.penerimaan.*'],
                ['label' => 'Batch & Kedaluwarsa', 'route' => 'gudang.batch.index', 'match' => 'gudang.batch.*'],
                ['label' => 'Kerusakan', 'route' => 'gudang.kerusakan.index', 'match' => 'gudang.kerusakan.*'],
                ['label' => 'Stock Opname', 'route' => 'gudang.opname.index', 'match' => 'gudang.opname.*'],
                ['label' => 'Kartu Stok', 'route' => 'gudang.kartu-stok.index', 'match' => 'gudang.kartu-stok.*'],
            ]
        ],
        [
            'title' => 'Pembelian & Penjualan',
            'icon' => '<svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" /></svg>',
            'match' => 'pembelian.*,penjualan.*',
            'items' => [
                ['label' => 'Order Pembelian', 'route' => 'pembelian.pembelian.index', 'match' => 'pembelian.pembelian.*'],
                ['label' => 'Retur Pembelian', 'route' => 'pembelian.retur.index', 'match' => 'pembelian.retur.*'],
                ['label' => 'Order Penjualan', 'route' => 'penjualan.penjualan.index', 'match' => 'penjualan.penjualan.*'],
                ['label' => 'Retur Penjualan', 'route' => 'penjualan.retur.index', 'match' => 'penjualan.retur.*'],
            ]
        ],
        [
            'title' => 'Konsinyasi & Titipan',
            'icon' => '<svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" /></svg>',
            'match' => 'konsinyasi.*',
            'items' => [
                ['label' => 'Pengiriman', 'route' => 'konsinyasi.pengiriman.index', 'match' => 'konsinyasi.pengiriman.*'],
                ['label' => 'Stok Konsinyasi', 'route' => 'konsinyasi.stok.index', 'match' => 'konsinyasi.stok.*'],
                ['label' => 'Retur Konsinyasi', 'route' => 'konsinyasi.retur.index', 'match' => 'konsinyasi.retur.*'],
                ['label' => 'Rekonsiliasi', 'route' => 'konsinyasi.rekonsiliasi.index', 'match' => 'konsinyasi.rekonsiliasi.*'],
                ['label' => 'Setoran', 'route' => 'konsinyasi.setoran.index', 'match' => 'konsinyasi.setoran.*'],
            ]
        ],
        [
            'title' => 'Buku Keuangan',
            'icon' => '<svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z" /></svg>',
            'match' => 'keuangan.*',
            'items' => [
                ['label' => 'Piutang', 'route' => 'keuangan.piutang.index', 'match' => 'keuangan.piutang.*'],
                ['label' => 'Hutang', 'route' => 'keuangan.hutang.index', 'match' => 'keuangan.hutang.*'],
                ['label' => 'Pelunasan', 'route' => 'keuangan.pelunasan.index', 'match' => 'keuangan.pelunasan.*'],
                ['label' => 'Kas & Transaksi', 'route' => 'keuangan.kas-transaksi.index', 'match' => 'keuangan.kas-transaksi.*'],
            ]
        ],
        [
            'title' => 'Akuntansi & Laporan',
            'icon' => '<svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>',
            'match' => 'akuntansi.*',
            'items' => [
                ['label' => 'Saldo Awal', 'route' => 'akuntansi.saldo-awal.index', 'match' => 'akuntansi.saldo-awal.*'],
                ['label' => 'Jurnal', 'route' => 'akuntansi.jurnal.index', 'match' => 'akuntansi.jurnal.*'],
                ['label' => 'Aset Tetap', 'route' => 'akuntansi.aset-tetap.index', 'match' => 'akuntansi.aset-tetap.*'],
                ['label' => 'Tutup Bulan', 'route' => 'akuntansi.tutup-bulan.index', 'match' => 'akuntansi.tutup-bulan.*'],
                ['label' => 'Tutup Tahun', 'route' => 'akuntansi.tutup-tahun.index', 'match' => 'akuntansi.tutup-tahun.*'],
                ['label' => 'Neraca Saldo', 'route' => 'akuntansi.laporan.neraca-saldo', 'match' => 'akuntansi.laporan.neraca-saldo'],
                ['label' => 'Buku Besar', 'route' => 'akuntansi.laporan.buku-besar', 'match' => 'akuntansi.laporan.buku-besar'],
                ['label' => 'Neraca', 'route' => 'akuntansi.laporan.neraca', 'match' => 'akuntansi.laporan.neraca'],
                ['label' => 'Laba Rugi', 'route' => 'akuntansi.laporan.laba-rugi', 'match' => 'akuntansi.laporan.laba-rugi'],
            ]
        ]
    ];
@endphp

<aside class="flex h-screen w-72 shrink-0 flex-col bg-ink-950 text-paper-100 shadow-xl z-50">

    {{-- Logo --}}
    <a href="{{ route('dashboard') }}" class="flex items-center gap-3 border-b border-ink-800 px-6 py-5 hover:bg-ink-900 transition-colors">
        <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-padi-500 font-display text-lg font-bold text-ink-950 shadow-sm">
            N
        </span>
        <div class="leading-tight">
            <p class="font-display text-base font-semibold tracking-tight text-white">
                Ngobar
            </p>
            <p class="text-[10px] text-paper-400 uppercase tracking-widest mt-0.5">
                Barter & Pemetaan
            </p>
        </div>
    </a>

    {{-- Navigation --}}
    <nav class="flex-1 overflow-y-auto px-4 py-6 custom-scrollbar">

        @if(auth()->check() && auth()->user()->id_entitas === null)
            <div class="mb-6">
                <p class="px-2 pb-2 font-mono text-[10px] uppercase tracking-widest text-paper-400">
                    Super Admin
                </p>
                <a
                    href="{{ route('admin.dashboard') }}"
                    class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm transition-colors
                    {{ request()->routeIs('admin.*') ? 'bg-padi-500/10 text-padi-400' : 'text-paper-300 hover:bg-ink-800 hover:text-white' }}"
                >
                    <span class="text-padi-500">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" /></svg>
                    </span>
                    <span class="font-medium">Persetujuan Entitas</span>
                </a>
            </div>
        @endif

        <p class="px-2 pb-3 font-mono text-[10px] uppercase tracking-widest text-paper-400">
            Modul Sistem
        </p>

        <ul class="space-y-2">
            @foreach ($sidebarGroups as $group)
                @php
                    $isGroupActive = collect(explode(',', $group['match']))->contains(fn ($p) => request()->routeIs($p));
                @endphp
                
                <li x-data="{ expanded: {{ $isGroupActive ? 'true' : 'false' }} }">
                    {{-- Accordion Header --}}
                    <button 
                        @click="expanded = !expanded" 
                        class="w-full flex items-center justify-between rounded-xl px-3 py-2.5 text-sm transition-colors focus:outline-none focus:ring-2 focus:ring-padi-500/50
                        {{ $isGroupActive ? 'bg-ink-800 text-white shadow-sm' : 'text-paper-300 hover:bg-ink-800 hover:text-white' }}"
                    >
                        <div class="flex items-center gap-3">
                            <span class="{{ $isGroupActive ? 'text-padi-400' : 'text-paper-500 group-hover:text-paper-300' }}">
                                {!! $group['icon'] !!}
                            </span>
                            <span class="font-medium">{{ $group['title'] }}</span>
                        </div>
                        <svg 
                            class="w-4 h-4 text-paper-500 transition-transform duration-200" 
                            :class="expanded ? 'rotate-180' : ''"
                            fill="none" viewBox="0 0 24 24" stroke="currentColor"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>

                    {{-- Accordion Body --}}
                    <div 
                        x-show="expanded" 
                        x-transition
                        x-cloak
                        class="mt-1"
                    >
                        <ul class="space-y-1 py-1 pl-11 pr-2 relative before:absolute before:left-[1.375rem] before:top-2 before:bottom-2 before:w-px before:bg-ink-800">
                            @foreach($group['items'] as $item)
                                @continue(! empty($item['super_admin_only']) && auth()->user()?->id_entitas !== null)
                                @php
                                    $hasChildren = ! empty($item['children']);
                                    $isItemActive = collect(explode(',', $item['match']))->contains(fn ($p) => request()->routeIs($p));
                                    $isChildActive = $hasChildren && collect($item['children'])->contains(function ($child) {
                                        return collect(explode(',', $child['match']))->contains(fn ($p) => request()->routeIs($p))
                                            || (! empty($child['children']) && collect($child['children'])->contains(fn ($grandchild) => collect(explode(',', $grandchild['match']))->contains(fn ($p) => request()->routeIs($p))));
                                    });
                                @endphp
                                <li>
                                    @if ($hasChildren)
                                        <div x-data="{ expanded: {{ ($isChildActive || $isItemActive) ? 'true' : 'false' }} }">
                                            <div class="flex items-center rounded-lg text-xs transition-colors
                                                {{ ($isChildActive || $isItemActive) ? 'bg-padi-500/10 text-padi-400 font-medium' : 'text-paper-400 hover:bg-ink-800 hover:text-white' }}">
                                                <a href="{{ route($item['route']) }}" class="min-w-0 flex-1 px-3 py-2">
                                                    {{ $item['label'] }}
                                                </a>
                                                <button type="button" @click="expanded = !expanded" class="px-3 py-2 text-paper-500 hover:text-white" aria-label="Buka submenu {{ $item['label'] }}">
                                                    <svg class="h-3.5 w-3.5 transition-transform" :class="expanded ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 9-7 7-7-7" />
                                                    </svg>
                                                </button>
                                            </div>

                                            <div x-show="expanded" x-transition x-cloak class="ml-3 mt-1 border-l border-ink-800 pl-2">
                                                <ul class="space-y-1">
                                                    @foreach ($item['children'] as $child)
                                                        @php
                                                            $childHasChildren = ! empty($child['children']);
                                                            $childActive = collect(explode(',', $child['match']))->contains(fn ($p) => request()->routeIs($p));
                                                            $grandchildActive = $childHasChildren && collect($child['children'])->contains(fn ($grandchild) => collect(explode(',', $grandchild['match']))->contains(fn ($p) => request()->routeIs($p)));
                                                        @endphp
                                                        <li>
                                                            @if ($childHasChildren)
                                                                <div x-data="{ expanded: {{ ($childActive || $grandchildActive) ? 'true' : 'false' }} }">
                                                                    <div class="flex items-center rounded-md {{ ($childActive || $grandchildActive) ? 'bg-ink-800 text-padi-400 font-medium' : 'text-paper-400 hover:bg-ink-800 hover:text-white' }}">
                                                                        <a href="{{ route($child['route']) }}" class="min-w-0 flex-1 px-2.5 py-1.5">{{ $child['label'] }}</a>
                                                                        <button type="button" @click="expanded = !expanded" class="px-2 py-1.5 text-paper-500 hover:text-white" aria-label="Buka submenu {{ $child['label'] }}">
                                                                            <svg class="h-3 w-3 transition-transform" :class="expanded ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 9-7 7-7-7" /></svg>
                                                                        </button>
                                                                    </div>
                                                                    <div x-show="expanded" x-transition x-cloak class="ml-3 mt-1 border-l border-ink-800 pl-2">
                                                                        @foreach ($child['children'] as $grandchild)
                                                                            <a href="{{ route($grandchild['route']) }}" class="block rounded-md px-2.5 py-1.5 text-[11px] transition-colors {{ request()->routeIs($grandchild['match']) ? 'bg-padi-500/10 text-padi-400 font-medium' : 'text-paper-500 hover:bg-ink-800 hover:text-white' }}">
                                                                                {{ $grandchild['label'] }}
                                                                            </a>
                                                                        @endforeach
                                                                    </div>
                                                                </div>
                                                            @else
                                                                <a href="{{ route($child['route']) }}" class="block rounded-md px-2.5 py-1.5 text-[11px] transition-colors {{ $childActive ? 'bg-ink-800 text-padi-400 font-medium' : 'text-paper-400 hover:bg-ink-800 hover:text-white' }}">
                                                                    {{ $child['label'] }}
                                                                </a>
                                                            @endif
                                                        </li>
                                                    @endforeach
                                                </ul>
                                            </div>
                                        </div>
                                    @else
                                        <a href="{{ route($item['route']) }}" class="block rounded-lg px-3 py-2 text-xs transition-colors {{ $isItemActive ? 'bg-padi-500/10 text-padi-400 font-medium relative before:absolute before:-left-[0.8125rem] before:top-1/2 before:-translate-y-1/2 before:w-[5px] before:h-[5px] before:rounded-full before:bg-padi-400' : 'text-paper-400 hover:bg-ink-800 hover:text-white' }}">
                                            {{ $item['label'] }}
                                        </a>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </li>
            @endforeach
        </ul>
    </nav>

    {{-- Footer Sidebar --}}
    <div class="border-t border-ink-800 px-6 py-4 bg-ink-950/50">
        <div class="flex items-center gap-3">
            <div class="w-8 h-8 rounded-full bg-ink-800 flex items-center justify-center text-xs font-bold text-padi-400">
                {{ substr(auth()->user()->nama ?? 'U', 0, 1) }}
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-xs font-medium text-white truncate">{{ auth()->user()->nama ?? 'User' }}</p>
                <p class="text-[10px] text-paper-400 truncate">Ngobar v1.0</p>
            </div>
        </div>
    </div>
</aside>

<style>
    /* Custom Scrollbar for Sidebar */
    .custom-scrollbar::-webkit-scrollbar {
        width: 4px;
    }
    .custom-scrollbar::-webkit-scrollbar-track {
        background: transparent;
    }
    .custom-scrollbar::-webkit-scrollbar-thumb {
        background-color: #3f3f46;
        border-radius: 20px;
    }
</style>

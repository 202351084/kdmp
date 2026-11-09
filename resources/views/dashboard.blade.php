<x-layouts.app title="Dashboard Ngobar" eyebrow="Daftar Menu">
    
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h2 class="font-display text-2xl font-semibold text-paper-900">Daftar Menu Akses</h2>
            <p class="text-sm text-paper-500 mt-1">Pilih fitur untuk mengelola data entitas Anda.</p>
        </div>
        <div class="text-sm font-medium text-paper-500 bg-white px-4 py-2 rounded-lg border border-paper-200 shadow-sm">
            Tahun Buku: {{ date('Y') }}
        </div>
    </div>

    @php
        $menuCards = [
            // MASTER (Blue)
            ['group' => 'Master', 'title' => 'Entitas', 'route' => route('master.entitas.index'), 'color' => 'bg-blue-600', 'icon' => '🏢', 'super_admin_only' => true],
            ['group' => 'Master', 'title' => 'Komoditas', 'route' => route('master.komoditas.index'), 'color' => 'bg-blue-600', 'icon' => '🌾'],
            ['group' => 'Master', 'title' => 'Barang', 'route' => route('master.barang.index'), 'color' => 'bg-blue-600', 'icon' => '📦'],
            ['group' => 'Master', 'title' => 'Pihak / Mitra', 'route' => route('master.pihak.index'), 'color' => 'bg-blue-600', 'icon' => '🤝'],
            ['group' => 'Master', 'title' => 'Kas & Bank', 'route' => route('master.kas-bank.index'), 'color' => 'bg-blue-600', 'icon' => '🏦'],
            ['group' => 'Master', 'title' => 'Chart of Accounts', 'route' => route('master.coa.index'), 'color' => 'bg-blue-600', 'icon' => '📜'],
            
            // SURVEI (Indigo)
            ['group' => 'Survei', 'title' => 'Sesi Survei', 'route' => route('survei.sesi.index'), 'color' => 'bg-indigo-600', 'icon' => '📋'],
            ['group' => 'Survei', 'title' => 'Bank Pertanyaan', 'route' => route('survei.pertanyaan.index'), 'color' => 'bg-indigo-600', 'icon' => '❓'],

            // PERENCANAAN (Cyan)
            ['group' => 'Perencanaan', 'title' => 'Demografi', 'route' => route('perencanaan.demografi.index'), 'color' => 'bg-cyan-600', 'icon' => '👥'],
            ['group' => 'Perencanaan', 'title' => 'Potensi Produksi', 'route' => route('perencanaan.potensi-produksi.index'), 'color' => 'bg-cyan-600', 'icon' => '📈'],
            ['group' => 'Perencanaan', 'title' => 'Standar Kebutuhan', 'route' => route('perencanaan.standar-kebutuhan.index'), 'color' => 'bg-cyan-600', 'icon' => '⚖️'],
            ['group' => 'Perencanaan', 'title' => 'Neraca Komoditas', 'route' => route('perencanaan.neraca-komoditas.index'), 'color' => 'bg-cyan-600', 'icon' => '📊'],

            // GUDANG (Amber)
            ['group' => 'Gudang', 'title' => 'Posisi Stok', 'route' => route('gudang.stok.index'), 'color' => 'bg-amber-500', 'icon' => '🏭'],
            ['group' => 'Gudang', 'title' => 'Penerimaan', 'route' => route('gudang.penerimaan.index'), 'color' => 'bg-amber-500', 'icon' => '📥'],
            ['group' => 'Gudang', 'title' => 'Stock Opname', 'route' => route('gudang.opname.index'), 'color' => 'bg-amber-500', 'icon' => '🔍'],
            ['group' => 'Gudang', 'title' => 'Kerusakan', 'route' => route('gudang.kerusakan.index'), 'color' => 'bg-amber-500', 'icon' => '⚠️'],
            
            // PEMBELIAN & PENJUALAN (Emerald & Rose)
            ['group' => 'Pembelian', 'title' => 'Order Pembelian', 'route' => route('pembelian.pembelian.index'), 'color' => 'bg-emerald-600', 'icon' => '🛒'],
            ['group' => 'Penjualan', 'title' => 'Kasir / POS', 'route' => route('penjualan.penjualan.index'), 'color' => 'bg-rose-600', 'icon' => '🧾'],
            
            // KONSINYASI (Orange)
            ['group' => 'Konsinyasi', 'title' => 'Pengiriman', 'route' => route('konsinyasi.pengiriman.index'), 'color' => 'bg-orange-600', 'icon' => '🚚'],
            ['group' => 'Konsinyasi', 'title' => 'Stok Konsinyasi', 'route' => route('konsinyasi.stok.index'), 'color' => 'bg-orange-600', 'icon' => '🏪'],
            ['group' => 'Konsinyasi', 'title' => 'Rekonsiliasi', 'route' => route('konsinyasi.rekonsiliasi.index'), 'color' => 'bg-orange-600', 'icon' => '🔄'],

            // KEUANGAN (Violet)
            ['group' => 'Keuangan', 'title' => 'Kas & Transaksi', 'route' => route('keuangan.kas-transaksi.index'), 'color' => 'bg-violet-600', 'icon' => '💰'],
            ['group' => 'Keuangan', 'title' => 'Piutang', 'route' => route('keuangan.piutang.index'), 'color' => 'bg-violet-600', 'icon' => '📤'],
            ['group' => 'Keuangan', 'title' => 'Hutang', 'route' => route('keuangan.hutang.index'), 'color' => 'bg-violet-600', 'icon' => '📥'],
            ['group' => 'Keuangan', 'title' => 'Pelunasan', 'route' => route('keuangan.pelunasan.index'), 'color' => 'bg-violet-600', 'icon' => '✅'],

            // AKUNTANSI & LAPORAN (Fuchsia & Slate)
            ['group' => 'Akuntansi', 'title' => 'Saldo Awal', 'route' => route('akuntansi.saldo-awal.index'), 'color' => 'bg-fuchsia-700', 'icon' => '💵'],
            ['group' => 'Akuntansi', 'title' => 'Jurnal Umum', 'route' => route('akuntansi.jurnal.index'), 'color' => 'bg-fuchsia-700', 'icon' => '📒'],
            ['group' => 'Akuntansi', 'title' => 'Tutup Bulan', 'route' => route('akuntansi.tutup-bulan.index'), 'color' => 'bg-fuchsia-700', 'icon' => '🔒'],
            ['group' => 'Akuntansi', 'title' => 'Tutup Tahun', 'route' => route('akuntansi.tutup-tahun.index'), 'color' => 'bg-fuchsia-700', 'icon' => '🔐'],
            
            // LAPORAN KEUANGAN (Slate)
            ['group' => 'Laporan', 'title' => 'Neraca Saldo', 'route' => route('akuntansi.laporan.neraca-saldo'), 'color' => 'bg-slate-700', 'icon' => '📑'],
            ['group' => 'Laporan', 'title' => 'Buku Besar', 'route' => route('akuntansi.laporan.buku-besar'), 'color' => 'bg-slate-700', 'icon' => '📖'],
            ['group' => 'Laporan', 'title' => 'Neraca (Posisi)', 'route' => route('akuntansi.laporan.neraca'), 'color' => 'bg-slate-700', 'icon' => '⚖️'],
            ['group' => 'Laporan', 'title' => 'Laba Rugi', 'route' => route('akuntansi.laporan.laba-rugi'), 'color' => 'bg-slate-700', 'icon' => '📉'],
        ];
    @endphp

    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 xl:grid-cols-7 gap-4">
        @foreach($menuCards as $menu)
            @continue(! empty($menu['super_admin_only']) && auth()->user()?->id_entitas !== null)
            <a href="{{ $menu['route'] }}" class="group relative flex flex-col items-center justify-center rounded-[14px] {{ $menu['color'] }} p-4 text-center shadow-sm transition-all hover:-translate-y-1 hover:shadow-lg hover:brightness-110 min-h-[140px]">
                <div class="mb-3 flex items-center justify-center text-3xl opacity-90 group-hover:scale-110 transition-transform">
                    {{ $menu['icon'] }}
                </div>
                <h4 class="font-display text-[10px] uppercase tracking-wider text-white/80 font-medium mb-0.5">{{ $menu['group'] }}</h4>
                <h3 class="font-display text-[13px] font-semibold text-white leading-tight">{{ $menu['title'] }}</h3>
            </a>
        @endforeach
    </div>

</x-layouts.app>

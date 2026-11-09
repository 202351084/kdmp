<x-layouts.app title="Gudang" eyebrow="Modul Operasional">
    @if ($jumlahBarangMenipis > 0)
        <a href="{{ route('gudang.stok.index', ['status' => 'menipis']) }}" class="mb-5 flex items-center justify-between gap-4 rounded-sm border border-merah-300 bg-merah-50 p-4 transition hover:bg-merah-100">
            <div>
                <p class="font-mono text-[11px] uppercase tracking-wide text-merah-700">Peringatan Stok Minimum</p>
                <p class="mt-1 text-sm font-medium text-merah-800">{{ $jumlahBarangMenipis }} barang perlu ditinjau untuk pengadaan.</p>
            </div>
            <span class="text-sm font-medium text-merah-700">Lihat daftar &rarr;</span>
        </a>
    @else
        <div class="mb-5 rounded-sm border border-paper-300 bg-paper-50 p-4">
            <p class="font-mono text-[11px] uppercase tracking-wide text-ink-600/70">Peringatan Stok Minimum</p>
            <p class="mt-1 text-sm text-ink-700">Tidak ada barang yang berada di bawah ambang minimum yang sudah diatur.</p>
        </div>
    @endif
    @if ($pelacakanBatchTersedia && $jumlahBatchMendekatiKedaluwarsa > 0)
        <a href="{{ route('gudang.batch.index', ['status' => 'segera']) }}" class="mb-5 flex items-center justify-between gap-4 rounded-sm border border-amber-300 bg-amber-50 p-4 transition hover:bg-amber-100">
            <div>
                <p class="font-mono text-[11px] uppercase tracking-wide text-amber-800">Peringatan Kedaluwarsa</p>
                <p class="mt-1 text-sm font-medium text-amber-900">{{ $jumlahBatchMendekatiKedaluwarsa }} batch akan kedaluwarsa dalam 30 hari.</p>
            </div>
            <span class="text-sm font-medium text-amber-800">Tinjau persediaan</span>
        </a>
    @endif

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <a href="{{ route('gudang.penerimaan.index') }}" class="rounded-sm border border-paper-300 bg-paper-50 p-5 transition hover:border-merah-400 hover:bg-merah-50">
            <p class="font-mono text-[11px] uppercase tracking-wide text-ink-600/60">Barang Masuk</p>
            <h3 class="mt-3 font-display text-xl font-semibold text-ink-900">Penerimaan Barang</h3>
            <p class="mt-2 text-sm text-ink-700">Catat barang yang masuk ke gudang dan update stok.</p>
        </a>

        <a href="{{ route('gudang.kartu-stok.index') }}" class="rounded-sm border border-paper-300 bg-paper-50 p-5 transition hover:border-merah-400 hover:bg-merah-50">
            <p class="font-mono text-[11px] uppercase tracking-wide text-ink-600/60">Stok</p>
            <h3 class="mt-3 font-display text-xl font-semibold text-ink-900">Kartu Stok</h3>
            <p class="mt-2 text-sm text-ink-700">Lihat riwayat barang masuk, keluar, dan saldo stok.</p>
        </a>

        <a href="{{ route('gudang.stok.index') }}" class="rounded-sm border border-paper-300 bg-paper-50 p-5 transition hover:border-merah-400 hover:bg-merah-50">
            <p class="font-mono text-[11px] uppercase tracking-wide text-ink-600/60">Stok Saat Ini</p>
            <h3 class="mt-3 font-display text-xl font-semibold text-ink-900">Posisi Stok</h3>
            <p class="mt-2 text-sm text-ink-700">Lihat jumlah dan nilai persediaan yang tersedia sekarang.</p>
        </a>

        @if ($pelacakanBatchTersedia)
            <a href="{{ route('gudang.batch.index') }}" class="rounded-sm border border-paper-300 bg-paper-50 p-5 transition hover:border-merah-400 hover:bg-merah-50">
                <p class="font-mono text-[11px] uppercase tracking-wide text-ink-600/60">Pelacakan</p>
                <h3 class="mt-3 font-display text-xl font-semibold text-ink-900">Batch &amp; Kedaluwarsa</h3>
                <p class="mt-2 text-sm text-ink-700">Tinjau saldo batch dan tanggal kedaluwarsa persediaan.</p>
            </a>
        @endif

        <a href="{{ route('gudang.opname.index') }}" class="rounded-sm border border-paper-300 bg-paper-50 p-5 transition hover:border-merah-400 hover:bg-merah-50">
            <p class="font-mono text-[11px] uppercase tracking-wide text-ink-600/60">Audit</p>
            <h3 class="mt-3 font-display text-xl font-semibold text-ink-900">Opname</h3>
            <p class="mt-2 text-sm text-ink-700">Hitung fisik barang dan selesaikan selisih stok.</p>
        </a>

        <a href="{{ route('gudang.kerusakan.index') }}" class="rounded-sm border border-paper-300 bg-paper-50 p-5 transition hover:border-merah-400 hover:bg-merah-50">
            <p class="font-mono text-[11px] uppercase tracking-wide text-ink-600/60">Kerusakan</p>
            <h3 class="mt-3 font-display text-xl font-semibold text-ink-900">Barang Rusak</h3>
            <p class="mt-2 text-sm text-ink-700">Catat barang rusak, tidak layak, atau perlu tindakan.</p>
        </a>
    </div>
</x-layouts.app>

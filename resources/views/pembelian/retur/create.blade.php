<x-layouts.app :title="$title" eyebrow="M5 Pembelian / Retur">
    @if (session('error'))
        <div class="mb-4 rounded-sm border border-merah-300 bg-merah-50 px-4 py-3 text-sm text-merah-800">{{ session('error') }}</div>
    @endif
    @if ($errors->any())
        <div class="mb-4 rounded-sm border border-merah-300 bg-merah-50 p-4 text-sm text-merah-800">
            <ul class="list-inside list-disc space-y-1">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="mb-5 flex flex-wrap items-end justify-between gap-3">
        <div>
            <p class="text-sm text-ink-600">Admin Pembelian hanya memproses permintaan retur yang dikirim oleh gudang.</p>
            <h1 class="mt-1 text-2xl font-semibold text-ink-900">Permintaan Retur Gudang</h1>
        </div>
        <a href="{{ route("$routeBase.index") }}" class="rounded-sm border border-emerald-200 px-3 py-2 text-sm font-medium text-emerald-800 hover:bg-emerald-50">Kembali ke Retur</a>
    </div>

    <section class="mb-5 rounded-sm border border-amber-300 bg-amber-50 p-4">
        <h2 class="font-semibold text-amber-950">Notifikasi Retur dari Gudang (Data Contoh)</h2>
        <p class="mt-1 text-sm text-amber-900">Data ini simulasi untuk alur yang akan dikelola admin gudang. Admin Pembelian tidak dapat membuat atau mengubah permintaan, barang, maupun kuantitas retur.</p>
        @if ($pendingReturnRequests->isEmpty())
            <p class="mt-3 text-sm text-amber-900">Belum ada permintaan retur gudang yang menunggu diproses.</p>
        @else
            <form method="GET" action="{{ route("$routeBase.create") }}" class="mt-3 flex flex-col gap-2 sm:flex-row">
                <label class="sr-only" for="return_request_select">Pilih permintaan retur</label>
                <select id="return_request_select" name="permintaan_retur" required class="min-w-0 flex-1 rounded-sm border border-amber-300 bg-white px-3 py-2 text-sm">
                    <option value="">-- Pilih permintaan retur --</option>
                    @foreach ($pendingReturnRequests as $returnRequest)
                        <option value="{{ $returnRequest->id_permintaan_retur }}" @selected($selectedReturnRequest?->id_permintaan_retur === $returnRequest->id_permintaan_retur)>
                            {{ $returnRequest->kode_permintaan }} — {{ $returnRequest->barang?->nama_barang }} — {{ number_format((float) $returnRequest->qty_diminta, 2, ',', '.') }} KG rusak — Pembelian {{ $returnRequest->pembelian->kode_pembelian }}
                        </option>
                    @endforeach
                </select>
                <button class="rounded-sm bg-amber-700 px-4 py-2 text-sm font-medium text-white hover:bg-amber-800">Pilih Permintaan</button>
            </form>
        @endif
    </section>

    @if ($selectedReturnRequest)
        <section class="mb-5 grid gap-3 rounded-sm border border-paper-300 bg-paper-50 p-4 sm:grid-cols-2">
            <div>
                <p class="text-xs font-medium uppercase text-ink-600">Nomor Permintaan Gudang</p>
                <p class="mt-1 font-semibold">{{ $selectedReturnRequest->kode_permintaan }}</p>
            </div>
            <div>
                <p class="text-xs font-medium uppercase text-ink-600">Pembelian Asal / Supplier</p>
                <p class="mt-1 font-semibold">{{ $pembelian->kode_pembelian }} / {{ $pembelian->pihak?->nama_pihak ?? $pembelian->pihak?->nama ?? '-' }}</p>
            </div>
            <div>
                <p class="text-xs font-medium uppercase text-ink-600">Komoditas Rusak</p>
                <p class="mt-1 font-semibold">{{ $selectedReturnRequest->barang?->nama_barang ?? '-' }}</p>
            </div>
            <div>
                <p class="text-xs font-medium uppercase text-ink-600">Kuantitas yang diminta gudang</p>
                <p class="mt-1 font-semibold">{{ number_format((float) $selectedReturnRequest->qty_diminta, 2, ',', '.') }} KG</p>
            </div>
            <div class="sm:col-span-2">
                <p class="text-xs font-medium uppercase text-ink-600">Catatan Gudang</p>
                <p class="mt-1 text-sm">{{ $selectedReturnRequest->alasan }}</p>
            </div>
        </section>

        <form method="POST" action="{{ route("$routeBase.store") }}" class="space-y-5">
            @csrf
            <input type="hidden" name="id_permintaan_retur" value="{{ $selectedReturnRequest->id_permintaan_retur }}">
            <section class="overflow-hidden rounded-sm border border-paper-300 bg-paper-50">
                <div class="border-b border-paper-300 px-4 py-3">
                    <h2 class="font-semibold text-ink-900">Detail Retur Sesuai Permintaan Gudang</h2>
                    <p class="mt-1 text-sm text-ink-600">Barang dan jumlah dikunci sesuai permintaan gudang.</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-emerald-50 text-left text-emerald-950">
                            <tr>
                                <th class="px-4 py-3">Barang</th>
                                <th class="px-4 py-3 text-right">Jumlah Dibeli (KG)</th>
                                <th class="px-4 py-3 text-right">Sisa Belum Diretur (KG)</th>
                                <th class="px-4 py-3 text-right">Jumlah Retur (KG)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="border-t border-paper-200">
                                <td class="px-4 py-3 font-medium">{{ $selectedReturnRequest->barang?->nama_barang ?? '-' }}</td>
                                <td class="px-4 py-3 text-right">{{ number_format((float) $selectedReturnRequest->line->qty_dasar, 2, ',', '.') }}</td>
                                <td class="px-4 py-3 text-right">{{ number_format((float) $selectedReturnRequest->line->qty_sisa_retur, 2, ',', '.') }}</td>
                                <td class="px-4 py-3 text-right font-semibold">{{ number_format((float) $selectedReturnRequest->qty_diminta, 2, ',', '.') }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="max-w-md rounded-sm border border-paper-300 bg-paper-50 p-4">
                <label class="block text-sm">
                    <span class="mb-1 block font-medium text-ink-700">Penyelesaian dengan Supplier</span>
                    <select name="jenis_penyelesaian" required class="w-full rounded-sm border border-paper-300 bg-paper-50 px-3 py-2">
                        <option value="">Pilih penyelesaian</option>
                        <option value="uang" @selected(old('jenis_penyelesaian') === 'uang')>Pengembalian uang</option>
                        <option value="potong_hutang" @selected(old('jenis_penyelesaian') === 'potong_hutang')>Potong hutang</option>
                        <option value="ganti_barang" @selected(old('jenis_penyelesaian') === 'ganti_barang')>Ganti barang</option>
                    </select>
                </label>
                <button class="mt-4 w-full rounded-sm bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700">Proses Permintaan Retur</button>
            </section>
        </form>
    @endif
</x-layouts.app>

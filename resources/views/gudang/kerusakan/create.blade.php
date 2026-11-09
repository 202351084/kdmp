<x-layouts.app title="Tambah Kerusakan Barang" eyebrow="Gudang">
    <div class="mb-4">
        <a href="{{ route('gudang.index') }}" class="text-sm font-medium text-ink-700 hover:text-merah-600">
            &larr; Kembali ke Gudang
        </a>
    </div>

    <form action="{{ route('gudang.kerusakan.store') }}" method="POST" class="space-y-6">
        @csrf

        @if ($errors->any())
            <div class="rounded-sm border border-merah-300 bg-merah-50 px-4 py-3 text-sm text-merah-800">
                {{ $errors->first() }}
            </div>
        @endif

        <div class="grid gap-4 md:grid-cols-2">
            <div>
                <label class="mb-1 block text-sm font-medium text-ink-700">Gudang</label>
                <select name="id_gudang" class="w-full rounded-sm border border-paper-300 bg-paper-50 px-3 py-2 text-sm focus:border-merah-400 focus:outline-none" required>
                    @foreach ($gudang as $item)
                        <option value="{{ $item->id_gudang }}">{{ $item->nama_gudang }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-ink-700">Tanggal</label>
                <input type="date" name="tanggal" value="{{ now()->toDateString() }}" class="w-full rounded-sm border border-paper-300 bg-paper-50 px-3 py-2 text-sm focus:border-merah-400 focus:outline-none" required>
            </div>
        </div>

        @if ($batchTrackingAvailable)
            <div id="batchField" hidden>
                <label class="mb-1 block text-sm font-medium text-ink-700">Batch sumber</label>
                <select name="id_batch" id="id_batch" disabled class="w-full rounded-sm border border-paper-300 bg-paper-50 px-3 py-2 text-sm focus:border-merah-400 focus:outline-none">
                    <option value="">Pilih batch</option>
                </select>
                <p id="batchHelp" class="mt-1 text-xs text-ink-600">
                    Batch kedaluwarsa hanya dapat dikeluarkan dengan jenis kejadian “Kadaluarsa”.
                </p>
            </div>
        @endif

        <div class="grid gap-4 md:grid-cols-2">
            <div>
                <label class="mb-1 block text-sm font-medium text-ink-700">Barang</label>
                <select name="id_barang" class="w-full rounded-sm border border-paper-300 bg-paper-50 px-3 py-2 text-sm focus:border-merah-400 focus:outline-none" required>
                    @foreach ($barang as $item)
                        <option value="{{ $item->id_barang }}">{{ $item->nama_barang }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-ink-700">Qty Rusak / Hilang / Susut</label>
                <input type="number" min="0.0001" step="0.0001" name="qty" value="{{ old('qty', '0') }}" class="w-full rounded-sm border border-paper-300 bg-paper-50 px-3 py-2 text-sm focus:border-merah-400 focus:outline-none" required>
            </div>
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium text-ink-700">Jenis Kejadian</label>
            <select name="jenis_kejadian" class="w-full rounded-sm border border-paper-300 bg-paper-50 px-3 py-2 text-sm focus:border-merah-400 focus:outline-none" required>
                <option value="rusak">Rusak</option>
                <option value="susut">Susut</option>
                <option value="hilang">Hilang</option>
                <option value="kadaluarsa">Kadaluarsa</option>
            </select>
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium text-ink-700">Keterangan</label>
            <input type="text" name="keterangan" placeholder="mis. hilang 2 karung / rusak karena benturan / kadaluarsa" class="w-full rounded-sm border border-paper-300 bg-paper-50 px-3 py-2 text-sm focus:border-merah-400 focus:outline-none">
        </div>

        <div class="flex gap-3">
            <button type="submit" class="rounded-sm bg-merah-500 px-4 py-2 text-sm font-medium text-paper-50 hover:bg-merah-600">
                Simpan Kerusakan
            </button>
            <a href="{{ route('gudang.kerusakan.index') }}" class="rounded-sm border border-paper-300 px-4 py-2 text-sm text-ink-700 hover:border-merah-400">
                Batal
            </a>
        </div>
    </form>

    @if ($batchTrackingAvailable)
        <script>
            const batchData = {{ Illuminate\Support\Js::from($batchData) }};
            const pelacakanBarang = {{ Illuminate\Support\Js::from($pelacakanBarang) }};
            const batchField = document.getElementById('batchField');
            const batchSelect = document.getElementById('id_batch');
            const batchHelp = document.getElementById('batchHelp');
            const qtyInput = document.querySelector('input[name="qty"]');
            const expiryEvent = document.querySelector('select[name="jenis_kejadian"]');
            const hariIni = {{ Illuminate\Support\Js::from(now()->toDateString()) }};

            function perbaruiBatchPilihan() {
                const barangId = document.querySelector('select[name="id_barang"]').value;
                const gudangId = document.querySelector('select[name="id_gudang"]').value;
                const lacakBatch = Boolean(pelacakanBarang[barangId]);
                const butuhBatchKedaluwarsa = expiryEvent.value === 'kadaluarsa';
                batchField.hidden = !lacakBatch;
                batchSelect.disabled = !lacakBatch;
                batchSelect.required = lacakBatch;
                batchSelect.replaceChildren(new Option('Pilih batch', ''));

                if (!lacakBatch) {
                    qtyInput.removeAttribute('max');
                    return;
                }

                const tersedia = batchData.filter(batch => {
                    const sudahKedaluwarsa = batch.tanggal_kedaluwarsa
                        && batch.tanggal_kedaluwarsa < hariIni;
                    return String(batch.id_barang) === barangId
                        && String(batch.id_gudang) === gudangId
                        && Number(batch.qty_on_hand) > 0
                        && Boolean(sudahKedaluwarsa) === butuhBatchKedaluwarsa;
                });
                tersedia.forEach(batch => {
                    const expiry = batch.tanggal_kedaluwarsa
                        ? ` · Exp ${batch.tanggal_kedaluwarsa}`
                        : ' · tanpa tanggal kedaluwarsa';
                    batchSelect.add(new Option(
                        `${batch.kode_batch} · saldo ${batch.qty_on_hand}${expiry}`,
                        batch.id_batch,
                    ));
                    batchSelect.options[batchSelect.options.length - 1].dataset.qty = batch.qty_on_hand;
                });
                batchHelp.textContent = tersedia.length
                    ? (butuhBatchKedaluwarsa
                        ? 'Pilih batch yang sudah kedaluwarsa untuk mencatat pengeluaran.'
                        : 'Batch kedaluwarsa tidak tersedia untuk kerusakan, susut, atau kehilangan biasa.')
                    : 'Tidak ada batch yang sesuai dengan gudang, barang, dan jenis kejadian ini.';
            }

            document.querySelector('select[name="id_barang"]').addEventListener('change', perbaruiBatchPilihan);
            document.querySelector('select[name="id_gudang"]').addEventListener('change', perbaruiBatchPilihan);
            expiryEvent.addEventListener('change', perbaruiBatchPilihan);
            batchSelect.addEventListener('change', () => {
                const qty = batchSelect.selectedOptions[0]?.dataset.qty;
                if (qty) qtyInput.max = qty;
                else qtyInput.removeAttribute('max');
            });
            perbaruiBatchPilihan();
        </script>
    @endif
</x-layouts.app>

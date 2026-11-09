<x-layouts.app :title="$title" eyebrow="Gudang">
    <div class="mb-4">
        <a href="{{ route('gudang.index') }}" class="text-sm font-medium text-ink-700 hover:text-merah-600">
            &larr; Kembali ke Gudang
        </a>
    </div>

    <div class="mx-auto max-w-5xl rounded-sm border border-paper-300 bg-paper-50 p-6">
        <form method="POST" action="{{ route('gudang.penerimaan.store') }}" class="space-y-5" id="formPenerimaan">
            @csrf

            <!-- Header Section -->
            <div>
                <h3 class="mb-3 text-sm font-semibold text-ink-700">Informasi Penerimaan</h3>
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-ink-700">Kode Penerimaan</label>
                        <input type="text" name="kode_penerimaan" value="{{ old('kode_penerimaan', 'PNR-' . now()->format('ymdHis')) }}" required class="w-full rounded-sm border border-paper-300 bg-paper-50 px-3 py-2 text-sm focus:border-merah-400 focus:outline-none">
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-ink-700">Tanggal Terima</label>
                        <input type="date" name="tanggal_terima" value="{{ old('tanggal_terima', now()->format('Y-m-d')) }}" required class="w-full rounded-sm border border-paper-300 bg-paper-50 px-3 py-2 text-sm focus:border-merah-400 focus:outline-none">
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-ink-700">Gudang</label>
                        <select name="id_gudang" required class="w-full rounded-sm border border-paper-300 bg-paper-50 px-3 py-2 text-sm focus:border-merah-400 focus:outline-none">
                            <option value="">Pilih gudang</option>
                            @foreach ($gudang as $item)
                                <option value="{{ $item->id_gudang }}" {{ old('id_gudang') == $item->id_gudang ? 'selected' : '' }}>
                                    {{ $item->nama_gudang }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-ink-700">Pihak</label>
                        <select name="id_pihak" required class="w-full rounded-sm border border-paper-300 bg-paper-50 px-3 py-2 text-sm focus:border-merah-400 focus:outline-none">
                            <option value="">Pilih pihak</option>
                            @foreach ($pihak as $item)
                                <option value="{{ $item->id_pihak }}" {{ old('id_pihak') == $item->id_pihak ? 'selected' : '' }}>
                                    {{ $item->nama }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="md:col-span-2">
                        <label class="mb-1 block text-sm font-medium text-ink-700">Catatan</label>
                        <textarea name="catatan" rows="2" class="w-full rounded-sm border border-paper-300 bg-paper-50 px-3 py-2 text-sm focus:border-merah-400 focus:outline-none">{{ old('catatan') }}</textarea>
                    </div>
                </div>
            </div>

            <div>
                <div class="mb-3 flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-ink-700">Daftar Barang Masuk</h3>
                    <button type="button" id="tambahBarang" class="rounded-sm bg-sawah-500 px-3 py-1.5 text-xs font-medium text-paper-50 hover:bg-sawah-600">
                        + Tambah Barang
                    </button>
                </div>

                <p class="mb-3 text-xs text-ink-600">
                    Stok dan HPP disimpan dalam satuan dasar barang. Harga yang diisi adalah harga per satuan yang diterima.
                    Contoh: 1 karung = 25 kg; 2 karung menambah stok 50 kg.
                    Isi jumlah layak dalam satuan dasar; sisanya dihitung sebagai tidak layak dan wajib diberi alasan.
                    Hanya jumlah layak yang menambah stok. Proses retur tetap ditangani modul Pembelian/Penjualan.
                </p>

                <div id="itemRows" class="space-y-4"></div>
                <div class="mt-4 grid gap-2 rounded-sm border border-paper-300 bg-paper-100 p-4 text-right text-sm sm:grid-cols-3">
                    <p><span class="block text-xs text-ink-600">Layak masuk stok</span><span class="font-mono font-semibold text-sawah-600" id="totalNilaiLayak">Rp 0</span></p>
                    <p><span class="block text-xs text-ink-600">Tidak layak</span><span class="font-mono font-semibold text-merah-600" id="totalNilaiTidakLayak">Rp 0</span></p>
                    <p><span class="block text-xs text-ink-600">Penerimaan bruto</span><span class="font-mono font-semibold text-ink-800" id="totalNilaiBruto">Rp 0</span></p>
                </div>
            </div>

            @if ($errors->any())
                <div class="rounded-sm border border-merah-500/40 bg-merah-50 px-3 py-2 text-sm text-merah-700">
                    <ul class="list-disc pl-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="flex items-center justify-end gap-3">
                <a href="{{ route('gudang.penerimaan.index') }}" class="rounded-sm border border-paper-300 px-4 py-2 text-sm text-ink-700 hover:border-merah-400">
                    Batal
                </a>
                <button type="submit" class="rounded-sm bg-merah-500 px-4 py-2 text-sm font-medium text-paper-50 hover:bg-merah-600">
                    Simpan Penerimaan
                </button>
            </div>
        </form>
    </div>

    <script>
        const barangData = {{ Illuminate\Support\Js::from($barangData) }};
        const satuanData = {{ Illuminate\Support\Js::from($satuanData) }};
        const oldItems = {{ Illuminate\Support\Js::from(old('items', [])) }};
        const batchTrackingAvailable = {{ Illuminate\Support\Js::from($batchTrackingAvailable) }};
        let rowCount = 0;

        function buatBaris(item = {}) {
            const index = rowCount++;
            const row = document.createElement('div');
            row.className = 'barangRow overflow-hidden rounded-sm border border-paper-300 bg-paper-50 shadow-sm';
            row.innerHTML = `
                <div class="flex flex-col gap-3 border-b border-paper-300 bg-paper-100/70 p-4 sm:flex-row sm:items-end">
                    <div class="flex items-center gap-2 sm:w-24 sm:shrink-0">
                        <span class="flex h-7 w-7 items-center justify-center rounded-full bg-merah-500 font-mono text-xs font-semibold text-paper-50">${index + 1}</span>
                        <p class="text-xs font-semibold text-ink-800">Barang</p>
                    </div>
                    <div class="min-w-0 flex-1">
                        <label class="mb-1 block text-xs font-semibold text-ink-700">Barang diterima</label>
                        <select name="items[${index}][id_barang]" required class="w-full rounded-sm border border-paper-300 bg-paper-50 px-3 py-2 text-sm focus:border-merah-400 focus:outline-none barangSelect"></select>
                    </div>
                    <button type="button" class="shrink-0 rounded-sm border border-merah-100 bg-paper-50 px-3 py-2 text-xs font-medium text-merah-600 transition hover:border-merah-300 hover:bg-merah-50 hapusBaris">Hapus barang</button>
                </div>
                <div class="space-y-5 p-5">
                <section>
                <p class="mb-2 font-mono text-[10px] font-semibold uppercase tracking-wide text-ink-600/70">Jumlah dan satuan</p>
                <div class="grid gap-4 rounded-sm border border-paper-200 bg-paper-50 p-4 sm:grid-cols-2 xl:grid-cols-2">
                    <label class="block text-xs font-medium text-ink-700">Qty diterima
                        <input type="number" step="0.0001" name="items[${index}][qty]" value="1" required class="mt-1 w-full rounded-sm border border-paper-300 bg-paper-50 px-3 py-2 text-sm focus:border-merah-400 focus:outline-none qtyInput" min="0.0001">
                    </label>
                    <div class="text-xs font-medium text-ink-700">Satuan
                        <select name="items[${index}][id_satuan_input]" required class="mt-1 w-full rounded-sm border border-paper-300 bg-paper-50 px-3 py-2 text-sm focus:border-merah-400 focus:outline-none satuanSelect" disabled></select>
                        <p class="mt-1 text-[11px] text-ink-600 satuanDasarHint"></p>
                        <button type="button" class="mt-1 text-[11px] font-medium text-merah-600 hover:text-merah-700 tambahKonversi" hidden>+ Atur satuan lain</button>
                        <select class="mt-1 w-full rounded-sm border border-paper-300 bg-paper-50 px-2 py-1.5 text-xs focus:border-merah-400 focus:outline-none satuanBaru" hidden>
                            <option value="">Pilih satuan aktif</option>
                        </select>
                    </div>
                    <label class="block text-xs font-medium text-ink-700">Konversi ke dasar
                        <input type="number" step="0.000001" name="items[${index}][faktor_konversi]" required min="0.000001" class="mt-1 w-full rounded-sm border border-paper-300 bg-paper-50 px-3 py-2 text-sm focus:border-merah-400 focus:outline-none faktorInput" disabled>
                        <span class="mt-1 block text-[11px] text-ink-600 ringkasanKonversi"></span>
                        <span class="mt-1 flex items-center gap-1 text-[11px] text-ink-600 aktualWrap" hidden>
                            <input type="checkbox" name="items[${index}][gunakan_faktor_aktual]" value="1" class="faktorAktual"> Pakai faktor aktual
                        </span>
                        <span class="mt-1 flex items-center gap-1 text-[11px] text-ink-600 simpanWrap" hidden>
                            <input type="checkbox" name="items[${index}][simpan_konversi]" value="1" class="simpanKonversi"> Simpan sebagai standar
                        </span>
                    </label>
                    <div class="rounded-sm bg-paper-100 px-3 py-2 text-xs text-ink-600">
                        Qty dasar
                        <p class="mt-1 text-base font-semibold text-ink-900"><span class="qtyDasar">0</span> <span class="satuanDasarRingkasan"></span></p>
                    </div>
                </div>
                </section>
                <section class="batchlessSortir rounded-sm border border-paper-200 bg-paper-100/60 p-4">
                <p class="mb-2 font-mono text-[10px] font-semibold uppercase tracking-wide text-ink-600/70">Pemeriksaan kelayakan</p>
                <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-2">
                    <label class="block text-xs font-medium text-ink-700">Jumlah layak (satuan dasar)
                        <input type="number" step="0.0001" name="items[${index}][qty_layak]" value="0" required min="0" class="mt-1 w-full rounded-sm border border-paper-300 bg-paper-50 px-3 py-2 text-sm focus:border-merah-400 focus:outline-none qtyLayakInput">
                    </label>
                    <div class="rounded-sm bg-paper-50 px-3 py-2 text-xs text-ink-600">
                        Tidak layak
                        <p class="mt-1 text-base font-semibold text-merah-600"><span class="qtyTidakLayak">0</span> <span class="satuanDasarRingkasan"></span></p>
                    </div>
                    <label class="block text-xs font-medium text-ink-700 sm:col-span-2">Alasan tidak layak
                        <textarea name="items[${index}][alasan_tidak_layak]" rows="2" class="mt-1 w-full rounded-sm border border-paper-300 bg-paper-50 px-3 py-2 text-sm focus:border-merah-400 focus:outline-none alasanTidakLayakInput" placeholder="Wajib diisi jika ada barang tidak layak"></textarea>
                    </label>
                </div>
                </section>
                <div class="batchTrackingPanel rounded-sm border border-padi-200 bg-padi-50/50 p-4" hidden>
                    <div class="mb-3 flex items-center justify-between gap-3">
                        <div>
                            <p class="text-xs font-semibold text-ink-800">Rincian Batch dan Kedaluwarsa</p>
                            <p class="mt-1 text-[11px] text-ink-600">Jumlah dasar seluruh batch harus sama dengan qty dasar penerimaan. Sortir dicatat per batch.</p>
                        </div>
                        <button type="button" class="tambahBatch shrink-0 rounded-sm border border-paper-300 bg-paper-50 px-3 py-1.5 text-xs font-medium text-ink-700 hover:border-merah-400">
                            + Batch
                        </button>
                    </div>
                    <div class="batchRows space-y-4"></div>
                    <p class="batchSummary mt-3 rounded-sm bg-paper-50 px-3 py-2 text-xs font-semibold text-ink-800"></p>
                </div>
                <section class="border-t border-paper-200 pt-4">
                <p class="mb-2 font-mono text-[10px] font-semibold uppercase tracking-wide text-ink-600/70">Nilai penerimaan</p>
                <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-2">
                    <label class="block text-xs font-medium text-ink-700">Harga per satuan diterima
                        <input type="number" step="0.0001" name="items[${index}][harga_satuan]" value="0" required class="mt-1 w-full rounded-sm border border-paper-300 bg-paper-50 px-3 py-2 text-sm focus:border-merah-400 focus:outline-none hargaInput" min="0">
                    </label>
                    <div class="rounded-sm border border-sawah-100 bg-sawah-100/50 px-3 py-2 text-xs text-ink-700">
                        Nilai layak masuk stok
                        <p class="mt-1 font-mono text-sm font-semibold text-sawah-600 nilaiLayak">Rp 0</p>
                    </div>
                    <div class="rounded-sm border border-merah-100 bg-merah-50 px-3 py-2 text-xs text-ink-700">
                        Nilai tidak layak
                        <p class="mt-1 font-mono text-sm font-semibold text-merah-600 nilaiTidakLayak">Rp 0</p>
                    </div>
                    <div class="rounded-sm border border-paper-300 bg-paper-100 px-3 py-2 text-xs text-ink-700">
                        Nilai penerimaan bruto
                        <p class="mt-1 font-mono text-sm font-semibold text-ink-800 nilaiBruto">Rp 0</p>
                    </div>
                </div>
                </section>
                </div>
            `;

            const barangSelect = row.querySelector('.barangSelect');
            barangSelect.add(new Option('Pilih barang', ''));
            barangData.forEach(barang => barangSelect.add(new Option(`${barang.kode} - ${barang.nama}`, barang.id)));

            const faktorInput = row.querySelector('.faktorInput');
            const faktorAktual = row.querySelector('.faktorAktual');
            const simpanKonversi = row.querySelector('.simpanKonversi');
            const satuanSelect = row.querySelector('.satuanSelect');
            const satuanBaru = row.querySelector('.satuanBaru');
            const tambahKonversi = row.querySelector('.tambahKonversi');
            const qtyLayakInput = row.querySelector('.qtyLayakInput');
            const alasanTidakLayakInput = row.querySelector('.alasanTidakLayakInput');
            const batchPanel = row.querySelector('.batchTrackingPanel');
            const batchRows = row.querySelector('.batchRows');
            let batchRowCount = 0;

            function tambahBarisBatch(batch = {}) {
                const batchIndex = batchRowCount++;
                const batchRow = document.createElement('div');
                batchRow.className = 'batchRow grid gap-4 rounded-sm border border-padi-200 bg-paper-50 p-4 shadow-sm sm:grid-cols-2 xl:grid-cols-3';
                batchRow.innerHTML = `
                    <label class="block text-[11px] font-medium text-ink-700">Kode batch supplier
                        <input type="text" name="items[${index}][batches][${batchIndex}][kode_batch]" maxlength="100" class="mt-1 w-full rounded-sm border border-paper-300 px-2 py-1.5 text-xs batchKode">
                    </label>
                    <label class="block text-[11px] font-medium text-ink-700">Tanggal kedaluwarsa
                        <input type="date" name="items[${index}][batches][${batchIndex}][tanggal_kedaluwarsa]" class="mt-1 w-full rounded-sm border border-paper-300 px-2 py-1.5 text-xs batchExpiry">
                    </label>
                    <label class="block text-[11px] font-medium text-ink-700">Qty batch (satuan dasar)
                        <input type="number" min="0.0001" step="0.0001" name="items[${index}][batches][${batchIndex}][qty_dasar]" required class="mt-1 w-full rounded-sm border border-paper-300 px-2 py-1.5 text-xs batchQty">
                    </label>
                    <label class="block text-[11px] font-medium text-ink-700">Qty layak
                        <input type="number" min="0" step="0.0001" name="items[${index}][batches][${batchIndex}][qty_layak]" required class="mt-1 w-full rounded-sm border border-paper-300 px-2 py-1.5 text-xs batchGood">
                    </label>
                    <div class="flex items-end gap-2">
                        <label class="block min-w-0 flex-1 text-[11px] font-medium text-ink-700">Alasan tidak layak
                            <input type="text" name="items[${index}][batches][${batchIndex}][alasan_tidak_layak]" class="mt-1 w-full rounded-sm border border-paper-300 px-2 py-1.5 text-xs batchReason">
                        </label>
                        <button type="button" class="hapusBatch rounded-sm border border-merah-100 px-2 py-1.5 text-xs text-merah-600 hover:bg-merah-50">Hapus</button>
                    </div>
                `;
                batchRows.appendChild(batchRow);
                batchRow.querySelector('.batchKode').value = batch.kode_batch ?? '';
                batchRow.querySelector('.batchExpiry').value = batch.tanggal_kedaluwarsa ?? '';
                batchRow.querySelector('.batchQty').value = batch.qty_dasar ?? '';
                batchRow.querySelector('.batchGood').value = batch.qty_layak ?? '';
                batchRow.querySelector('.batchReason').value = batch.alasan_tidak_layak ?? '';
                batchRow.querySelector('.batchQty').addEventListener('input', perbaruiTotalBatch);
                batchRow.querySelector('.batchGood').addEventListener('input', perbaruiTotalBatch);
                batchRow.querySelector('.batchReason').addEventListener('input', perbaruiTotalBatch);
                batchRow.querySelector('.hapusBatch').addEventListener('click', () => {
                    batchRow.remove();
                    perbaruiTotalBatch();
                });
                perbaruiTotalBatch();
            }

            function perbaruiTotalBatch() {
                const rows = [...batchRows.querySelectorAll('.batchRow')];
                let totalDasar = 0;
                let totalLayak = 0;
                rows.forEach(batchRow => {
                    const qtyDasarBatch = Number(batchRow.querySelector('.batchQty').value) || 0;
                    const qtyLayakBatch = Number(batchRow.querySelector('.batchGood').value) || 0;
                    const alasanBatch = batchRow.querySelector('.batchReason');
                    totalDasar += qtyDasarBatch;
                    totalLayak += qtyLayakBatch;
                    batchRow.querySelector('.batchGood').max = qtyDasarBatch.toFixed(4);
                    batchRow.querySelector('.batchGood').setCustomValidity(
                        qtyLayakBatch > qtyDasarBatch ? 'Qty layak tidak boleh melebihi qty batch.' : ''
                    );
                    alasanBatch.setCustomValidity(
                        qtyDasarBatch > qtyLayakBatch && alasanBatch.value.trim() === ''
                            ? 'Alasan wajib diisi jika batch tidak layak.'
                            : ''
                    );
                });
                const qtyDasarTarget = (Number(row.querySelector('.qtyInput').value) || 0)
                    * (Number(row.querySelector('.faktorInput').value) || 0);
                row.querySelector('.batchSummary').textContent =
                    `Total batch: ${totalDasar.toLocaleString('id-ID', { maximumFractionDigits: 4 })} / ${qtyDasarTarget.toLocaleString('id-ID', { maximumFractionDigits: 4 })} satuan dasar • Layak: ${totalLayak.toLocaleString('id-ID', { maximumFractionDigits: 4 })}`;
                row.querySelector('.qtyInput').setCustomValidity(
                    batchPanel.hidden || Math.abs(totalDasar - qtyDasarTarget) < 0.00005
                        ? ''
                        : 'Total qty batch harus sama dengan qty dasar penerimaan.'
                );
                qtyLayakInput.value = totalLayak.toFixed(4);
                qtyLayakInput.dataset.edited = 'true';
                alasanTidakLayakInput.value = totalDasar > totalLayak ? 'Sortir tercatat per batch.' : '';
                row.querySelector('.qtyTidakLayak').textContent =
                    Math.max(0, totalDasar - totalLayak).toLocaleString('id-ID', { maximumFractionDigits: 4 });
                hitungBaris(row);
            }

            function perbaruiModeBatch() {
                const barang = barangData.find(entry => String(entry.id) === barangSelect.value);
                const lacakBatch = batchTrackingAvailable && Boolean(barang?.lacakBatch);
                batchPanel.hidden = !lacakBatch;
                row.querySelector('.batchlessSortir').hidden = lacakBatch;
                qtyLayakInput.required = !lacakBatch;
                qtyLayakInput.readOnly = lacakBatch;
                alasanTidakLayakInput.readOnly = lacakBatch;
                batchRows.querySelectorAll('input').forEach(input => {
                    input.disabled = !lacakBatch;
                });
                row.querySelector('.tambahBatch').disabled = !lacakBatch;
                if (lacakBatch && batchRows.children.length === 0) {
                    const qtyDefault = (Number(row.querySelector('.qtyInput').value) || 0)
                        * (Number(row.querySelector('.faktorInput').value) || 0);
                    tambahBarisBatch({
                        qty_dasar: qtyDefault.toFixed(4),
                        qty_layak: qtyDefault.toFixed(4),
                    });
                } else if (lacakBatch) {
                    perbaruiTotalBatch();
                } else {
                    row.querySelector('.qtyInput').setCustomValidity('');
                }
            }

            function isiSatuan(preferredId = null, allowUnconfigured = false) {
                const barang = barangData.find(item => String(item.id) === barangSelect.value);
                satuanSelect.replaceChildren();

                if (!barang) {
                    satuanSelect.add(new Option('Pilih barang dahulu', ''));
                    satuanSelect.disabled = true;
                    tambahKonversi.hidden = true;
                    satuanBaru.hidden = true;
                    return;
                }

                satuanSelect.disabled = false;
                satuanSelect.add(new Option(barang.satuanDasar, barang.satuanDasarId));
                barang.konversi
                    .filter(konversi => String(konversi.idSatuan) !== String(barang.satuanDasarId))
                    .forEach(konversi => {
                        const satuan = satuanData.find(item => String(item.id) === String(konversi.idSatuan));
                        if (satuan) satuanSelect.add(new Option(satuan.kode, satuan.id));
                    });

                if (allowUnconfigured && preferredId && !Array.from(satuanSelect.options).some(option => option.value === String(preferredId))) {
                    const satuan = satuanData.find(item => String(item.id) === String(preferredId));
                    if (satuan) satuanSelect.add(new Option(satuan.kode, satuan.id));
                }

                const defaultBeli = barang.konversi.find(konversi => konversi.defaultBeli);
                const selectedId = preferredId
                    ?? defaultBeli?.idSatuan
                    ?? barang.satuanDasarId;
                satuanSelect.value = String(selectedId);
                if (!satuanSelect.value) satuanSelect.value = String(barang.satuanDasarId);
            }

            function perbaruiKonversi(savedItem = null) {
                const barang = barangData.find(item => String(item.id) === barangSelect.value);
                if (!barang) return;

                const idSatuan = satuanSelect.value;
                const isSatuanDasar = String(idSatuan) === String(barang.satuanDasarId);
                const konversi = barang.konversi.find(item => String(item.idSatuan) === String(idSatuan));
                const actual = Boolean(savedItem?.gunakan_faktor_aktual);
                const faktor = konversi && !actual
                    ? konversi.faktor
                    : savedItem?.faktor_konversi ?? '';

                row.querySelector('.satuanDasarHint').textContent = `Satuan dasar: ${barang.satuanDasar}`;
                row.querySelector('.aktualWrap').hidden = !konversi || isSatuanDasar;
                row.querySelector('.simpanWrap').hidden = isSatuanDasar;
                faktorAktual.checked = Boolean(konversi && actual);
                faktorAktual.disabled = !konversi || isSatuanDasar;
                simpanKonversi.checked = savedItem
                    ? Boolean(savedItem.simpan_konversi)
                    : !konversi && !isSatuanDasar;
                simpanKonversi.required = !konversi && !isSatuanDasar;
                simpanKonversi.disabled = isSatuanDasar || (Boolean(konversi) && !faktorAktual.checked);
                faktorInput.value = isSatuanDasar ? '1' : faktor;
                faktorInput.readOnly = isSatuanDasar || (Boolean(konversi) && !faktorAktual.checked);
                faktorInput.disabled = false;

                const faktorRingkas = faktorInput.value || '?';
                row.querySelector('.ringkasanKonversi').textContent =
                    `1 ${satuanSelect.options[satuanSelect.selectedIndex]?.text || ''} = ${faktorRingkas} ${barang.satuanDasar}`;
                perbaruiPilihanSatuanBaru(barang);
                perbaruiSortir(row);
                hitungBaris(row);
            }

            barangSelect.value = item.id_barang ? String(item.id_barang) : '';
            isiSatuan(item.id_satuan_input ? String(item.id_satuan_input) : null, Boolean(item.simpan_konversi));
            row.querySelector('.qtyInput').value = item.qty ?? 1;
            row.querySelector('.hargaInput').value = item.harga_satuan ?? 0;
            qtyLayakInput.value = item.qty_layak ?? 0;
            qtyLayakInput.dataset.edited = item.qty_layak !== undefined ? 'true' : 'false';
            alasanTidakLayakInput.value = item.alasan_tidak_layak ?? '';
            perbaruiKonversi(item.id_barang ? item : null);
            (Array.isArray(item.batches) ? item.batches : []).forEach(tambahBarisBatch);
            perbaruiModeBatch();

            barangSelect.addEventListener('change', () => {
                qtyLayakInput.dataset.edited = 'false';
                batchRows.replaceChildren();
                batchRowCount = 0;
                isiSatuan();
                perbaruiKonversi();
                perbaruiModeBatch();
            });
            satuanSelect.addEventListener('change', () => perbaruiKonversi());
            tambahKonversi.addEventListener('click', () => {
                perbaruiPilihanSatuanBaru();
                satuanBaru.hidden = false;
                satuanBaru.focus();
            });
            satuanBaru.addEventListener('change', () => {
                if (!satuanBaru.value) return;
                if (!Array.from(satuanSelect.options).some(option => option.value === satuanBaru.value)) {
                    const satuan = satuanData.find(item => String(item.id) === satuanBaru.value);
                    if (satuan) satuanSelect.add(new Option(satuan.kode, satuan.id));
                }
                satuanSelect.value = satuanBaru.value;
                satuanBaru.value = '';
                satuanBaru.hidden = true;
                perbaruiKonversi({ faktor_konversi: '', simpan_konversi: true });
            });
            faktorAktual.addEventListener('change', () => {
                if (!faktorAktual.checked) {
                    const barang = barangData.find(entry => String(entry.id) === barangSelect.value);
                    const konversi = barang?.konversi.find(entry => String(entry.idSatuan) === satuanSelect.value);
                    if (konversi) faktorInput.value = konversi.faktor;
                }
                simpanKonversi.disabled = !faktorAktual.checked;
                if (!faktorAktual.checked) simpanKonversi.checked = false;
                faktorInput.readOnly = !faktorAktual.checked;
                perbaruiKonversi({ faktor_konversi: faktorInput.value, gunakan_faktor_aktual: faktorAktual.checked });
            });
            simpanKonversi.addEventListener('change', () => {
                if (simpanKonversi.checked) {
                    faktorAktual.checked = true;
                    faktorAktual.dispatchEvent(new Event('change'));
                    simpanKonversi.checked = true;
                    simpanKonversi.disabled = false;
                }
            });
            row.querySelector('.qtyInput').addEventListener('input', () => {
                perbaruiSortir(row);
                perbaruiTotalBatch();
                hitungBaris(row);
            });
            row.querySelector('.hargaInput').addEventListener('input', () => hitungBaris(row));
            qtyLayakInput.addEventListener('input', () => {
                qtyLayakInput.dataset.edited = 'true';
                perbaruiSortir(row);
            });
            alasanTidakLayakInput.addEventListener('input', () => perbaruiSortir(row));
            faktorInput.addEventListener('input', () => {
                const barang = barangData.find(entry => String(entry.id) === barangSelect.value);
                if (barang) {
                    const satuan = satuanSelect.options[satuanSelect.selectedIndex]?.text || '';
                    row.querySelector('.ringkasanKonversi').textContent =
                        `1 ${satuan} = ${faktorInput.value || '?'} ${barang.satuanDasar}`;
                }
                perbaruiSortir(row);
                perbaruiTotalBatch();
                hitungBaris(row);
            });
            row.querySelector('.tambahBatch').addEventListener('click', () => tambahBarisBatch());
            row.querySelector('.hapusBaris').addEventListener('click', () => {
                row.remove();
                hitungTotal();
            });

            document.getElementById('itemRows').appendChild(row);
            hitungBaris(row);

            function perbaruiPilihanSatuanBaru(barang = barangData.find(item => String(item.id) === barangSelect.value)) {
                satuanBaru.replaceChildren(new Option('Pilih satuan aktif', ''));
                satuanBaru.hidden = true;
                if (!barang) {
                    tambahKonversi.hidden = true;
                    return;
                }

                const satuanTersedia = satuanData.filter(satuan =>
                    String(satuan.id) !== String(barang.satuanDasarId)
                    && !barang.konversi.some(konversi => String(konversi.idSatuan) === String(satuan.id))
                    && !Array.from(satuanSelect.options).some(option => option.value === String(satuan.id))
                );
                satuanTersedia.forEach(satuan => satuanBaru.add(new Option(satuan.kode, satuan.id)));
                tambahKonversi.hidden = satuanTersedia.length === 0;
                if (satuanBaru.options.length <= 1) satuanBaru.hidden = true;
            }
        }

        function hitungBaris(row) {
            const qty = Number(row.querySelector('.qtyInput').value) || 0;
            const harga = Number(row.querySelector('.hargaInput').value) || 0;
            const faktor = Number(row.querySelector('.faktorInput').value) || 0;
            const qtyDasar = qty * faktor;
            const qtyLayak = Math.min(Number(row.querySelector('.qtyLayakInput').value) || 0, qtyDasar);
            const hargaDasar = faktor > 0 ? harga / faktor : 0;
            const nilaiLayak = qtyLayak * hargaDasar;
            const nilaiTidakLayak = Math.max(0, qtyDasar - qtyLayak) * hargaDasar;
            const nilaiBruto = qty * harga;
            const formatRupiah = nilai => `Rp ${nilai.toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;

            row.querySelector('.nilaiLayak').textContent = formatRupiah(nilaiLayak);
            row.querySelector('.nilaiTidakLayak').textContent = formatRupiah(nilaiTidakLayak);
            row.querySelector('.nilaiBruto').textContent = formatRupiah(nilaiBruto);
            hitungTotal();
        }

        function perbaruiSortir(row) {
            const qty = Number(row.querySelector('.qtyInput').value) || 0;
            const faktor = Number(row.querySelector('.faktorInput').value) || 0;
            const qtyDasar = Math.round(qty * faktor * 10000) / 10000;
            const qtyLayakInput = row.querySelector('.qtyLayakInput');
            const alasanInput = row.querySelector('.alasanTidakLayakInput');
            if (!row.querySelector('.batchTrackingPanel').hidden) {
                row.querySelector('.qtyDasar').textContent =
                    qtyDasar.toLocaleString('id-ID', { maximumFractionDigits: 4 });
                row.querySelectorAll('.satuanDasarRingkasan').forEach(element => {
                    const barang = barangData.find(item => String(item.id) === row.querySelector('.barangSelect').value);
                    element.textContent = barang?.satuanDasar ?? '';
                });
                return;
            }

            row.querySelector('.qtyDasar').textContent =
                qtyDasar.toLocaleString('id-ID', { maximumFractionDigits: 4 });
            row.querySelectorAll('.satuanDasarRingkasan').forEach(element => {
                const barang = barangData.find(item => String(item.id) === row.querySelector('.barangSelect').value);
                element.textContent = barang?.satuanDasar ?? '';
            });
            if (qtyLayakInput.dataset.edited !== 'true') {
                qtyLayakInput.value = qtyDasar.toFixed(4);
            }
            const qtyLayak = Number(qtyLayakInput.value) || 0;
            const qtyTidakLayak = Math.max(0, Math.round((qtyDasar - qtyLayak) * 10000) / 10000);
            qtyLayakInput.max = qtyDasar.toFixed(4);
            row.querySelector('.qtyTidakLayak').textContent =
                qtyTidakLayak.toLocaleString('id-ID', { maximumFractionDigits: 4 });
            alasanInput.setCustomValidity(
                qtyTidakLayak > 0 && alasanInput.value.trim() === ''
                    ? 'Alasan wajib diisi jika ada barang tidak layak.'
                    : ''
            );
            qtyLayakInput.setCustomValidity(
                qtyLayak > qtyDasar
                    ? 'Jumlah layak tidak boleh melebihi jumlah dasar yang diterima.'
                    : ''
            );
            hitungBaris(row);
        }

        function hitungTotal() {
            let totalLayak = 0;
            let totalTidakLayak = 0;
            let totalBruto = 0;
            document.querySelectorAll('.barangRow').forEach(row => {
                const qty = Number(row.querySelector('.qtyInput').value) || 0;
                const harga = Number(row.querySelector('.hargaInput').value) || 0;
                const faktor = Number(row.querySelector('.faktorInput').value) || 0;
                const qtyDasar = qty * faktor;
                const qtyLayak = Math.min(Number(row.querySelector('.qtyLayakInput').value) || 0, qtyDasar);
                const hargaDasar = faktor > 0 ? harga / faktor : 0;
                totalLayak += qtyLayak * hargaDasar;
                totalTidakLayak += Math.max(0, qtyDasar - qtyLayak) * hargaDasar;
                totalBruto += qty * harga;
            });
            const formatRupiah = nilai => `Rp ${nilai.toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;

            document.getElementById('totalNilaiLayak').textContent = formatRupiah(totalLayak);
            document.getElementById('totalNilaiTidakLayak').textContent = formatRupiah(totalTidakLayak);
            document.getElementById('totalNilaiBruto').textContent = formatRupiah(totalBruto);
        }

        document.getElementById('tambahBarang').addEventListener('click', () => buatBaris());
        (Array.isArray(oldItems) && oldItems.length ? oldItems : [{}]).forEach(item => buatBaris(item));
    </script>
</x-layouts.app>

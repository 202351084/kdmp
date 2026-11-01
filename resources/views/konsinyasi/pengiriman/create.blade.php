<x-layouts.app :title="$title" eyebrow="Konsinyasi">
    @php
        $shipment = $item ?? null;
        $isEdit = $shipment !== null;
        $formItems = old('items', $isEdit
            ? $shipment->detail->map(fn ($detail) => [
                'id_barang' => $detail->id_barang,
                'qty_dasar' => $detail->qty_dasar,
                'harga_titip_satuan' => $detail->harga_titip_satuan,
                'harga_jual_saran' => $detail->harga_jual_saran,
            ])->all()
            : [['qty_dasar' => 1, 'harga_titip_satuan' => 0, 'harga_jual_saran' => 0]]);
    @endphp
    <div class="mb-4">
        <a href="{{ route('konsinyasi.pengiriman.index') }}" class="text-sm font-medium text-ink-700 hover:text-merah-600">
            &larr; Kembali ke Pengiriman Konsinyasi
        </a>
    </div>

    <div class="mx-auto max-w-6xl rounded-sm border border-paper-300 bg-paper-50 p-6">
        <form method="POST" action="{{ $isEdit ? route('konsinyasi.pengiriman.update', $shipment->id_kiriman) : route('konsinyasi.pengiriman.store') }}" class="space-y-6">
            @csrf
            @if ($isEdit) @method('PUT') @endif

            <div>
                <h2 class="mb-3 font-display text-base font-semibold text-ink-900">Informasi Pengiriman</h2>
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div class="md:col-span-2">
                        <label for="jenisPemilik" class="mb-1 block text-sm font-medium text-ink-700">Jenis penitipan</label>
                        <select id="jenisPemilik" name="jenis_pemilik" required class="w-full rounded-sm border border-paper-300 bg-paper-50 px-3 py-2 text-sm focus:border-merah-400 focus:outline-none">
                            <option value="entitas" @selected(old('jenis_pemilik', $shipment?->jenis_pemilik ?? 'entitas') === 'entitas')>Desa ke desa</option>
                            <option value="pihak" @selected(old('jenis_pemilik', $shipment?->jenis_pemilik ?? 'entitas') === 'pihak')>Orang ke desa</option>
                        </select>
                        <p class="mt-1 text-xs text-ink-600">Barang titipan perorangan dicatat terpisah dari stok milik desa penerima.</p>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-ink-700">Kode Kiriman</label>
                        <input type="text" name="kode_kiriman" value="{{ old('kode_kiriman', $shipment?->kode_kiriman ?? 'KRM-' . now()->format('ymdHis')) }}" required class="w-full rounded-sm border border-paper-300 bg-paper-50 px-3 py-2 text-sm focus:border-merah-400 focus:outline-none">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-ink-700">Tanggal Kirim</label>
                        <input type="date" name="tgl_kirim" value="{{ old('tgl_kirim', $shipment?->tgl_kirim?->format('Y-m-d') ?? now()->format('Y-m-d')) }}" required class="w-full rounded-sm border border-paper-300 bg-paper-50 px-3 py-2 text-sm focus:border-merah-400 focus:outline-none">
                    </div>
                    <div id="entitasPemilikFields">
                        <label class="mb-1 block text-sm font-medium text-ink-700">Desa Pemilik</label>
                        <select id="koperasiPemilik" name="id_entitas_pemilik" class="w-full rounded-sm border border-paper-300 bg-paper-50 px-3 py-2 text-sm focus:border-merah-400 focus:outline-none">
                            <option value="">Pilih desa pemilik</option>
                            @foreach ($entitas as $entity)
                                <option value="{{ $entity->id_entitas }}" @selected(old('id_entitas_pemilik', $shipment?->id_entitas_pemilik) == $entity->id_entitas)>{{ $entity->nama_entitas }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div id="pihakPemilikFields">
                        <label class="mb-1 block text-sm font-medium text-ink-700">Penitip (Master Pihak)</label>
                        <select id="pihakPemilik" name="id_pihak_pemilik" class="w-full rounded-sm border border-paper-300 bg-paper-50 px-3 py-2 text-sm focus:border-merah-400 focus:outline-none">
                            <option value="">Pilih warga atau petani</option>
                            @foreach ($pihak as $person)
                                <option value="{{ $person->id_pihak }}" data-entitas="{{ $person->id_entitas }}" @selected(old('id_pihak_pemilik', $shipment?->id_pihak_pemilik) == $person->id_pihak)>{{ $person->nama }} ({{ $person->jenis_pihak }})</option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-ink-600">Penitip harus terdaftar pada desa penerima.</p>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-ink-700">Desa Penerima</label>
                        <select id="koperasiPenerima" name="id_entitas_penerima" required class="w-full rounded-sm border border-paper-300 bg-paper-50 px-3 py-2 text-sm focus:border-merah-400 focus:outline-none">
                            <option value="">Pilih desa penerima</option>
                            @foreach ($entitas as $entity)
                                <option value="{{ $entity->id_entitas }}" @selected(old('id_entitas_penerima', $shipment?->id_entitas_penerima) == $entity->id_entitas)>{{ $entity->nama_entitas }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div id="gudangAsalFields">
                        <label class="mb-1 block text-sm font-medium text-ink-700">Gudang Asal</label>
                        <select id="gudangAsal" name="id_gudang_asal" disabled class="w-full rounded-sm border border-paper-300 bg-paper-50 px-3 py-2 text-sm focus:border-merah-400 focus:outline-none">
                            <option value="">Pilih desa pemilik terlebih dahulu</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-ink-700">Gudang Tujuan</label>
                        <select id="gudangTujuan" name="id_gudang_tujuan" required disabled class="w-full rounded-sm border border-paper-300 bg-paper-50 px-3 py-2 text-sm focus:border-merah-400 focus:outline-none">
                            <option value="">Pilih desa penerima terlebih dahulu</option>
                        </select>
                    </div>
                    <div id="alamatAsalFields" class="md:col-span-2">
                        <label class="mb-1 block text-sm font-medium text-ink-700">Alamat asal / pengambilan (opsional)</label>
                        <textarea name="alamat_asal" rows="2" class="w-full rounded-sm border border-paper-300 bg-paper-50 px-3 py-2 text-sm focus:border-merah-400 focus:outline-none">{{ old('alamat_asal', $shipment?->alamat_asal ?? '') }}</textarea>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-ink-700">Model Imbalan</label>
                        <select name="model_imbalan" required class="w-full rounded-sm border border-paper-300 bg-paper-50 px-3 py-2 text-sm focus:border-merah-400 focus:outline-none">
                            <option value="selisih_harga" @selected(old('model_imbalan', $shipment?->model_imbalan ?? 'selisih_harga') === 'selisih_harga')>Selisih harga</option>
                            <option value="komisi_persen" @selected(old('model_imbalan', $shipment?->model_imbalan) === 'komisi_persen')>Komisi persen</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-ink-700">Persen Komisi</label>
                        <input type="number" name="persen_komisi" value="{{ old('persen_komisi', $shipment?->persen_komisi ?? 0) }}" min="0" max="100" step="0.01" class="w-full rounded-sm border border-paper-300 bg-paper-50 px-3 py-2 text-sm focus:border-merah-400 focus:outline-none">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-ink-700">Batas Waktu Titipan</label>
                        <input type="date" name="tgl_batas_titip" value="{{ old('tgl_batas_titip', $shipment?->tgl_batas_titip?->format('Y-m-d') ?? '') }}" class="w-full rounded-sm border border-paper-300 bg-paper-50 px-3 py-2 text-sm focus:border-merah-400 focus:outline-none">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-ink-700">Penanggung Susut</label>
                        <select name="penanggung_susut" required class="w-full rounded-sm border border-paper-300 bg-paper-50 px-3 py-2 text-sm focus:border-merah-400 focus:outline-none">
                            <option value="pemilik" @selected(old('penanggung_susut', $shipment?->penanggung_susut ?? 'pemilik') === 'pemilik')>Pemilik</option>
                            <option value="penerima" @selected(old('penanggung_susut', $shipment?->penanggung_susut) === 'penerima')>Penerima</option>
                        </select>
                    </div>
                    <div class="md:col-span-2">
                        <label class="mb-1 block text-sm font-medium text-ink-700">Catatan</label>
                        <textarea name="catatan_pengiriman" rows="2" class="w-full rounded-sm border border-paper-300 bg-paper-50 px-3 py-2 text-sm focus:border-merah-400 focus:outline-none">{{ old('catatan_pengiriman', $shipment?->catatan_pengiriman ?? '') }}</textarea>
                    </div>
                </div>
            </div>

            <div>
                <div class="mb-3 flex items-center justify-between">
                    <h2 class="font-display text-base font-semibold text-ink-900">Barang Titipan</h2>
                    <button id="tambahBaris" type="button" class="rounded-sm bg-sawah-500 px-3 py-1.5 text-xs font-medium text-paper-50 hover:bg-sawah-600">+ Tambah Barang</button>
                </div>
                <div class="overflow-x-auto rounded-sm border border-paper-300">
                    <table class="w-full text-left text-sm">
                        <thead><tr class="border-b border-paper-300 bg-paper-200/60 font-mono text-[11px] uppercase tracking-wide text-ink-600/70">
                            <th class="px-3 py-2 font-medium">Barang</th><th class="px-3 py-2 font-medium">Qty Dasar</th><th class="px-3 py-2 font-medium">Harga Titip</th><th class="px-3 py-2 font-medium">Harga Jual Saran</th><th class="px-3 py-2 text-center font-medium">Aksi</th>
                        </tr></thead>
                        <tbody id="itemRows"></tbody>
                    </table>
                </div>
            </div>

            @if ($errors->any())<div class="rounded-sm border border-merah-500/40 bg-merah-50 px-3 py-2 text-sm text-merah-700"><ul class="list-disc pl-5">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
            <div class="flex items-center justify-end gap-3"><a href="{{ route('konsinyasi.pengiriman.index') }}" class="rounded-sm border border-paper-300 px-4 py-2 text-sm text-ink-700 hover:border-merah-400">Batal</a><button type="submit" class="rounded-sm bg-merah-500 px-4 py-2 text-sm font-medium text-paper-50 hover:bg-merah-600">{{ $isEdit ? 'Perbarui Draft' : 'Simpan Draft' }}</button></div>
        </form>
    </div>

    <script>
        let rowCount = 0;
        const formItems = @json($formItems);
        const barangData = @json($barang->map(fn ($item) => [
            'id' => $item->id_barang,
            'label' => $item->kode_barang . ' - ' . $item->nama_barang,
        ])->all());

        function tambahBaris(item = {}) {
            const row = document.createElement('tr');
            row.className = 'barangRow border-b border-paper-200 last:border-0';
            const cell = () => {
                const td = document.createElement('td');
                td.className = 'px-3 py-2';
                row.appendChild(td);
                return td;
            };
            const makeInput = (name, value, min, step, required = true) => {
                const input = document.createElement('input');
                input.type = 'number';
                input.name = `items[${rowCount}][${name}]`;
                input.value = value ?? '';
                input.min = min;
                input.step = step;
                input.required = required;
                input.className = 'w-full rounded-sm border border-paper-300 bg-paper-50 px-2 py-1.5 text-xs';
                return input;
            };
            const barangCell = cell();
            const barangSelect = document.createElement('select');
            barangSelect.name = `items[${rowCount}][id_barang]`;
            barangSelect.required = true;
            barangSelect.className = 'w-full rounded-sm border border-paper-300 bg-paper-50 px-2 py-1.5 text-xs';
            barangSelect.add(new Option('Pilih barang', ''));
            barangData.forEach((barang) => barangSelect.add(new Option(barang.label, barang.id)));
            barangSelect.value = item.id_barang ?? '';
            barangCell.appendChild(barangSelect);
            cell().appendChild(makeInput('qty_dasar', item.qty_dasar ?? 1, '0.0001', '0.0001'));
            cell().appendChild(makeInput('harga_titip_satuan', item.harga_titip_satuan ?? 0, '0', '0.01'));
            cell().appendChild(makeInput('harga_jual_saran', item.harga_jual_saran ?? 0, '0', '0.01', false));
            const actionCell = cell();
            actionCell.className += ' text-center';
            const deleteButton = document.createElement('button');
            deleteButton.type = 'button';
            deleteButton.className = 'text-xs text-merah-600 hover:text-merah-700';
            deleteButton.textContent = 'Hapus';
            deleteButton.addEventListener('click', () => {
                if (document.querySelectorAll('.barangRow').length > 1) row.remove();
            });
            actionCell.appendChild(deleteButton);
            document.getElementById('itemRows').appendChild(row);
            rowCount++;
        }

        const gudangData = @json($gudangData);
        const oldGudangAsal = @json(old('id_gudang_asal', $shipment?->id_gudang_asal));
        const oldGudangTujuan = @json(old('id_gudang_tujuan', $shipment?->id_gudang_tujuan));

        function isiGudang(select, entitasId, placeholder, excludedId = null, selectedId = null) {
            select.innerHTML = `<option value="">${placeholder}</option>`;
            const pilihan = gudangData.filter(gudang => String(gudang.koperasi) === String(entitasId) && String(gudang.id) !== String(excludedId));

            pilihan.forEach(gudang => {
                const option = new Option(gudang.nama, gudang.id);
                if (String(gudang.id) === String(selectedId)) option.selected = true;
                select.add(option);
            });

            select.disabled = pilihan.length === 0;
        }

        function filterForm() {
            const jenisPemilik = document.getElementById('jenisPemilik').value;
            const entitasMode = jenisPemilik === 'entitas';
            document.getElementById('entitasPemilikFields').hidden = !entitasMode;
            document.getElementById('gudangAsalFields').hidden = !entitasMode;
            document.getElementById('pihakPemilikFields').hidden = entitasMode;
            document.getElementById('alamatAsalFields').hidden = entitasMode;
            document.getElementById('koperasiPemilik').disabled = !entitasMode;
            document.getElementById('gudangAsal').disabled = !entitasMode;
            document.getElementById('pihakPemilik').disabled = entitasMode;
            document.querySelector('[name="alamat_asal"]').disabled = entitasMode;

            const pemilik = document.getElementById('koperasiPemilik').value;
            const penerima = document.getElementById('koperasiPenerima').value;
            const asal = document.getElementById('gudangAsal');
            const tujuan = document.getElementById('gudangTujuan');
            const selectedAsal = asal.value || oldGudangAsal;
            const selectedTujuan = tujuan.value || oldGudangTujuan;

            if (entitasMode) {
                isiGudang(asal, pemilik, 'Pilih gudang asal', null, selectedAsal);
            } else {
                asal.innerHTML = '<option value="">Tidak diperlukan</option>';
                asal.disabled = true;
            }
            isiGudang(tujuan, penerima, 'Pilih gudang tujuan', null, selectedTujuan);

            const pihakSelect = document.getElementById('pihakPemilik');
            Array.from(pihakSelect.options).forEach((option) => {
                if (option.value) option.hidden = option.dataset.entitas !== penerima;
            });
            if (pihakSelect.selectedOptions.length && pihakSelect.selectedOptions[0].hidden) {
                pihakSelect.value = '';
            }
        }

        document.getElementById('tambahBaris').addEventListener('click', () => tambahBaris());
        document.getElementById('jenisPemilik').addEventListener('change', filterForm);
        document.getElementById('koperasiPemilik').addEventListener('change', filterForm);
        document.getElementById('koperasiPenerima').addEventListener('change', filterForm);
        formItems.forEach((item) => tambahBaris(item));
        filterForm();
    </script>
</x-layouts.app>

<x-layouts.app :title="$title" eyebrow="Edit Pembelian">
    @if (session('error'))
        <div class="mb-4 rounded-sm border border-merah-300 bg-merah-50 p-4 text-sm text-merah-800">
            {{ session('error') }}
        </div>
    @endif
    <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-3">
        <div class="md:col-span-2 xl:col-span-2">
            @if ($errors->any())
                <div class="mb-4 rounded-sm border border-merah-300 bg-merah-50 p-4">
                    <h4 class="font-medium text-merah-900">Validasi Gagal:</h4>
                    <ul class="mt-2 list-inside list-disc text-sm text-merah-800">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route("$routeBase.update", $item) }}" method="POST" class="space-y-4" data-source="{{ $item->sumber_pembelian }}">
                @csrf
                @method('PUT')
                <input type="hidden" name="jenis_pembayaran" value="kredit" id="consignment_payment_value" @disabled($item->sumber_pembelian !== 'pengambilan_konsinyasi')>

                <!-- Data Dokumen -->
                <div class="rounded-sm border border-paper-300 bg-paper-50 p-4">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-sm font-semibold text-ink-900">Informasi Dokumen Draft</h3>
                        <span class="rounded-sm bg-amber-100 px-2 py-0.5 text-xs font-semibold text-amber-800 uppercase">Draft</span>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <span class="text-xs font-medium uppercase text-ink-600">Nomor Dokumen</span>
                            <p class="font-semibold text-ink-900">{{ $item->kode_pembelian }}</p>
                        </div>
                        <div>
                            <span class="text-xs font-medium uppercase text-ink-600">Tanggal Transaksi</span>
                            <p class="font-semibold text-ink-900">{{ $item->tanggal_transaksi?->format('d M Y') ?? '—' }}</p>
                        </div>
                    </div>
                    @if ($item->sumber_pembelian === 'pengambilan_konsinyasi')
                        <p class="mt-3 text-sm text-sky-900">Pengambilan konsinyasi dicatat sebagai hutang kepada desa pengirim. Pembayaran kas/bank tidak digunakan.</p>
                    @endif
                </div>

                <!-- Unit Usaha & Gudang -->
                <div class="rounded-sm border border-paper-300 bg-paper-50 p-4">
                    <h3 class="mb-3 text-sm font-semibold text-ink-900">Unit Usaha & Gudang Tujuan</h3>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <label class="block text-sm">
                            <span class="text-ink-700 font-medium">Unit Usaha *</span>
                            <select name="id_unit_usaha" required class="mt-1 w-full rounded-sm border border-paper-300 bg-paper-50 px-3 py-2">
                                <option value="">-- Pilih Unit --</option>
                                @foreach ($barangs->groupBy('id_unit_usaha') as $idUnit => $unitBarang)
                                    <option value="{{ $idUnit }}" @selected(old('id_unit_usaha', $item->id_unit_usaha) == $idUnit)>
                                        {{ $unitBarang->first()->unitUsaha->nama_unit_usaha ?? 'N/A' }}
                                    </option>
                                @endforeach
                            </select>
                        </label>
                        <label class="block text-sm">
                            <span class="text-ink-700 font-medium">Gudang *</span>
                            <select name="id_gudang" required class="mt-1 w-full rounded-sm border border-paper-300 bg-paper-50 px-3 py-2">
                                <option value="">-- Pilih Gudang --</option>
                                @foreach (\App\Models\Master\Gudang::all() as $gudang)
                                    <option value="{{ $gudang->id_gudang }}" @selected(old('id_gudang', $item->id_gudang) == $gudang->id_gudang)>
                                        {{ $gudang->nama_gudang }}
                                    </option>
                                @endforeach
                            </select>
                        </label>
                    </div>
                </div>

                <!-- Daftar Barang -->
                <div class="rounded-sm border border-paper-300 bg-paper-50 p-4">
                    <div class="mb-3 flex items-center justify-between">
                        <h3 class="text-sm font-semibold text-ink-900">Daftar Barang yang Dipesan</h3>
                        <button type="button" onclick="addPurchaseRow()" class="rounded-sm bg-emerald-600 px-2.5 py-1 text-xs font-medium text-white hover:bg-emerald-700">
                            + Tambah Baris
                        </button>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="bg-paper-100 text-left text-ink-700">
                                <tr>
                                    <th class="px-3 py-2">Barang</th>
                                    <th class="px-3 py-2">Qty (KG)</th>
                                    <th class="px-3 py-2">Harga per KG</th>
                                    <th class="px-3 py-2 text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="purchase_rows">
                                @foreach (old('items', $editItems) as $index => $row)
                                    <tr class="purchase-row">
                                        <td class="px-3 py-2">
                                            <select name="items[{{ $index }}][id_barang]" onchange="updatePurchaseUnits(this)" class="purchase-barang w-full rounded-sm border border-paper-300 bg-paper-50 px-2 py-1.5" required>
                                                <option value="">-- Pilih Barang --</option>
                                                @foreach ($barangs as $barang)
                                                    <option value="{{ $barang->id_barang }}" @disabled(!in_array((int) $barang->id_barang, $barangKgIds, true)) @selected($row['id_barang'] == $barang->id_barang) data-base-unit="{{ $barang->id_satuan_dasar }}" data-base-code="{{ $barang->satuanDasar?->kode_satuan }}" data-conversions="{{ $barang->konversiSatuan->filter(fn ($c) => strtoupper($c->satuan?->kode_satuan ?? '') === 'KG')->map(fn ($c) => ['id' => (int) $c->id_satuan, 'code' => 'KG', 'factor' => (float) $c->faktor_ke_dasar, 'default' => (bool) $c->is_default_beli])->values()->toJson() }}">{{ $barang->nama_barang }}</option>
                                                @endforeach
                                            </select>
                                            <input type="hidden" name="items[{{ $index }}][id_satuan]" class="purchase-unit">
                                        </td>
                                        <td class="px-3 py-2">
                                            <input type="number" step="0.01" min="0.01" name="items[{{ $index }}][qty_dasar]" value="{{ $row['qty_dasar'] }}" required class="w-full rounded-sm border border-paper-300 bg-paper-50 px-2 py-1.5 text-right">
                                        </td>
                                        <td class="px-3 py-2">
                                            <input type="number" step="0.01" min="{{ $item->sumber_pembelian === 'pengambilan_konsinyasi' ? '0.01' : '0' }}" name="items[{{ $index }}][harga_satuan]" value="{{ $row['harga_satuan'] }}" required class="w-full rounded-sm border border-paper-300 bg-paper-50 px-2 py-1.5 text-right">
                                        </td>
                                        <td class="px-3 py-2 text-center">
                                            <button type="button" onclick="removePurchaseRow(this)" class="text-xs text-merah-600 hover:text-merah-700">Hapus</button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <p class="mt-2 text-xs text-ink-600">Jumlah dan harga wajib menggunakan KG dan harga per KG. Baris lama yang barangnya tidak memiliki konversi KG harus dihapus atau diganti.</p>
                </div>

                <!-- Supplier dan Pembayaran -->
                <div class="rounded-sm border border-paper-300 bg-paper-50 p-4">
                    <h3 class="mb-3 text-sm font-semibold text-ink-900">Data Supplier dan Pembayaran</h3>
                    <div class="space-y-3">
                        <label class="block text-sm">
                            <span class="text-ink-700 font-medium">{{ $item->sumber_pembelian === 'pengambilan_konsinyasi' ? 'Desa Pengirim *' : 'Supplier *' }}</span>
                            <select name="id_pihak" required class="mt-1 w-full rounded-sm border border-paper-300 bg-paper-50 px-3 py-2">
                                <option value="">-- Pilih Supplier --</option>
                                @foreach ($pihaks as $pihak)
                                    <option value="{{ $pihak->id_pihak }}" @selected(old('id_pihak', $item->id_pihak) == $pihak->id_pihak)>
                                        {{ $pihak->nama_pihak }}
                                    </option>
                                @endforeach
                            </select>
                        </label>

                        <label id="payment_method_field" class="block text-sm" @if ($item->sumber_pembelian === 'pengambilan_konsinyasi') style="display: none;" @endif>
                            <span class="text-ink-700 font-medium">Jenis Pembayaran *</span>
                            <select name="jenis_pembayaran" required onchange="togglePaymentFields()" @disabled($item->sumber_pembelian === 'pengambilan_konsinyasi') class="mt-1 w-full rounded-sm border border-paper-300 bg-paper-50 px-3 py-2">
                                <option value="">-- Pilih Jenis --</option>
                                <option value="tunai" @selected(old('jenis_pembayaran', $item->jenis_pembayaran) === 'tunai')>Tunai</option>
                                <option value="transfer" @selected(old('jenis_pembayaran', $item->jenis_pembayaran) === 'transfer')>Transfer</option>
                                <option value="kredit" @selected(old('jenis_pembayaran', $item->jenis_pembayaran) === 'kredit')>Kredit</option>
                            </select>
                        </label>

                        <div id="sumber_uang_section" style="display: none;">
                            <label class="block text-sm">
                                <span class="text-ink-700 font-medium">Sumber Uang (Kas / Bank) *</span>
                                <select name="id_kas_bank" id="id_kas_bank" class="mt-1 w-full rounded-sm border border-paper-300 bg-paper-50 px-3 py-2">
                                    <option value="">-- Pilih Sumber Kas / Bank --</option>
                                    @foreach ($kasBanks as $kasBank)
                                        <option value="{{ $kasBank->id_kas_bank }}" data-jenis="{{ $kasBank->jenis }}" @selected(old('id_kas_bank', $item->id_kas_bank) == $kasBank->id_kas_bank)>
                                            {{ $kasBank->nama }} ({{ ucfirst($kasBank->jenis) }}{{ $kasBank->no_rekening ? ' - ' . $kasBank->no_rekening : '' }})
                                        </option>
                                    @endforeach
                                </select>
                                <p class="mt-1 text-xs text-ink-500">Pilih akun kas atau rekening bank yang digunakan untuk mendanai pembayaran.</p>
                            </label>
                        </div>

                        <div id="jatuh_tempo_section" style="display: none;">
                            <label class="block text-sm">
                                <span class="text-ink-700 font-medium">Tanggal Jatuh Tempo *</span>
                                <input type="date" name="tgl_jatuh_tempo" min="{{ now()->format('Y-m-d') }}" value="{{ old('tgl_jatuh_tempo', $item->tgl_jatuh_tempo?->format('Y-m-d')) }}" class="mt-1 w-full rounded-sm border border-paper-300 bg-paper-50 px-3 py-2">
                                <p class="mt-1 text-xs text-ink-500">Wajib diisi untuk pembelian dengan tempo/kredit.</p>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="flex gap-2">
                    <button type="submit" class="rounded-sm bg-emerald-600 px-4 py-2 font-medium text-white hover:bg-emerald-700">
                        Simpan Perubahan
                    </button>
                    <a href="{{ route("$routeBase.show", $item) }}" class="rounded-sm border border-paper-300 px-4 py-2 text-ink-700 hover:bg-paper-100">
                        Batal
                    </a>
                </div>
            </form>
        </div>

        <div class="md:col-span-2 xl:col-span-1">
            <div class="rounded-sm border border-paper-300 bg-paper-50 p-4">
                <h3 class="mb-3 font-semibold text-ink-900">Catatan Edit Pembelian</h3>
                <ul class="list-inside list-disc space-y-1 text-sm text-ink-700">
                    <li>Edit hanya diizinkan untuk dokumen berstatus <strong>Draft</strong>.</li>
                    <li>Setelah pembelian disetujui (<strong>Disetujui</strong>), data transaksi tidak dapat diedit kembali.</li>
                </ul>
            </div>
        </div>
    </div>

    <script>
        function togglePaymentFields() {
            const jenisSelect = document.querySelector('select[name="jenis_pembayaran"]');
            const form = document.querySelector('form[data-source]');
            const isConsignment = form.dataset.source === 'pengambilan_konsinyasi';
            const paymentField = document.getElementById('payment_method_field');
            const consignmentPaymentValue = document.getElementById('consignment_payment_value');
            const jenis = isConsignment ? 'kredit' : (jenisSelect ? jenisSelect.value : '');
            const isCredit = jenis === 'kredit';
            const isCashOrBank = !isConsignment && (jenis === 'tunai' || jenis === 'transfer');

            paymentField.style.display = isConsignment ? 'none' : 'block';
            jenisSelect.disabled = isConsignment;
            jenisSelect.required = !isConsignment;
            consignmentPaymentValue.disabled = !isConsignment;
            if (isConsignment) {
                jenisSelect.value = 'kredit';
            }

            const jatuhTempoSection = document.getElementById('jatuh_tempo_section');
            const jatuhTempoInput = document.querySelector('input[name="tgl_jatuh_tempo"]');
            const sumberUangSection = document.getElementById('sumber_uang_section');
            const sumberUangSelect = document.getElementById('id_kas_bank');

            // Tanggal Jatuh Tempo (Hanya saat Kredit)
            if (jatuhTempoSection && jatuhTempoInput) {
                jatuhTempoSection.style.display = isCredit ? 'block' : 'none';
                jatuhTempoInput.disabled = !isCredit;
                jatuhTempoInput.required = isCredit;
                if (!isCredit) {
                    jatuhTempoInput.value = '';
                }
            }

            // Sumber Uang (Kas / Bank)
            if (sumberUangSection && sumberUangSelect) {
                if (isCashOrBank) {
                    sumberUangSection.style.display = 'block';
                    sumberUangSelect.disabled = false;
                    sumberUangSelect.required = true;

                    let hasMatchingSelected = false;
                    const options = sumberUangSelect.querySelectorAll('option');
                    options.forEach((opt, idx) => {
                        if (idx === 0) return;
                        const optJenis = opt.dataset.jenis;
                        const match = (jenis === 'tunai' && optJenis === 'kas') || (jenis === 'transfer' && optJenis === 'bank');
                        if (match) {
                            opt.style.display = '';
                            opt.disabled = false;
                            if (opt.value === sumberUangSelect.value) {
                                hasMatchingSelected = true;
                            }
                        } else {
                            opt.style.display = 'none';
                            opt.disabled = true;
                        }
                    });

                    if (!hasMatchingSelected) {
                        const firstValid = Array.from(options).find((opt, idx) => idx > 0 && !opt.disabled);
                        if (firstValid) {
                            sumberUangSelect.value = firstValid.value;
                        }
                    }
                } else {
                    sumberUangSection.style.display = 'none';
                    sumberUangSelect.disabled = true;
                    sumberUangSelect.required = false;
                    sumberUangSelect.value = '';
                }
            }
        }

        function updatePurchaseUnits(barangSelect) {
            const row = barangSelect.closest('.purchase-row');
            const unitInput = row.querySelector('.purchase-unit');
            const selectedBarang = barangSelect.selectedOptions[0];
            unitInput.value = '';

            if (!barangSelect.value) {
                return;
            }

            let conversions = JSON.parse(selectedBarang.dataset.conversions || '[]');
            const baseUnitId = Number(selectedBarang.dataset.baseUnit);
            if (selectedBarang.dataset.baseCode?.toUpperCase() === 'KG' && !conversions.some((conversion) => Number(conversion.id) === baseUnitId)) {
                conversions.push({
                    id: baseUnitId,
                    code: 'KG',
                    factor: 1,
                    default: true,
                });
            }

            if (conversions.length === 0) {
                return;
            }

            conversions.sort((left, right) => Number(right.default) - Number(left.default));
            const preferred = conversions.find((conversion) => conversion.default) || conversions[0];
            unitInput.value = String(preferred?.id ?? '');
        }

        function addPurchaseRow() {
            const tableBody = document.getElementById('purchase_rows');
            const rows = tableBody.querySelectorAll('.purchase-row');
            const index = rows.length;

            const row = document.createElement('tr');
            row.className = 'purchase-row';
            row.innerHTML = `
                <td class="px-3 py-2">
                    <select name="items[${index}][id_barang]" onchange="updatePurchaseUnits(this)" class="purchase-barang w-full rounded-sm border border-paper-300 bg-paper-50 px-2 py-1.5" required>
                        <option value="">-- Pilih Barang --</option>
                        @foreach ($barangs as $barang)
                            <option value="{{ $barang->id_barang }}" @disabled(!in_array((int) $barang->id_barang, $barangKgIds, true)) data-base-unit="{{ $barang->id_satuan_dasar }}" data-base-code="{{ $barang->satuanDasar?->kode_satuan }}" data-conversions="{{ $barang->konversiSatuan->filter(fn ($c) => strtoupper($c->satuan?->kode_satuan ?? '') === 'KG')->map(fn ($c) => ['id' => (int) $c->id_satuan, 'code' => 'KG', 'factor' => (float) $c->faktor_ke_dasar, 'default' => (bool) $c->is_default_beli])->values()->toJson() }}">{{ $barang->nama_barang }}</option>
                        @endforeach
                    </select>
                    <input type="hidden" name="items[${index}][id_satuan]" class="purchase-unit">
                </td>
                <td class="px-3 py-2">
                    <input type="number" step="0.01" min="0.01" name="items[${index}][qty_dasar]" required class="w-full rounded-sm border border-paper-300 bg-paper-50 px-2 py-1.5 text-right">
                </td>
                <td class="px-3 py-2">
                    <input type="number" step="0.01" min="0" name="items[${index}][harga_satuan]" required class="w-full rounded-sm border border-paper-300 bg-paper-50 px-2 py-1.5 text-right">
                </td>
                <td class="px-3 py-2 text-center">
                    <button type="button" onclick="removePurchaseRow(this)" class="text-xs text-merah-600 hover:text-merah-700">Hapus</button>
                </td>
            `;
            tableBody.appendChild(row);
        }

        function removePurchaseRow(button) {
            const row = button.closest('.purchase-row');
            if (document.querySelectorAll('.purchase-row').length > 1) {
                row.remove();
            }
        }

        document.addEventListener('DOMContentLoaded', function () {
            togglePaymentFields();
            document.querySelectorAll('.purchase-barang').forEach(updatePurchaseUnits);
        });
    </script>
</x-layouts.app>

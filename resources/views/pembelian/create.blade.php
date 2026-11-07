<x-layouts.app :title="$title" eyebrow="Tambah Data">
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

            <section class="mb-4 rounded-sm border border-amber-300 bg-amber-50 p-4">
                <div class="mb-3 flex flex-wrap items-start justify-between gap-2">
                    <div>
                        <h3 class="font-semibold text-amber-950">Notifikasi Kebutuhan Gudang (Data Contoh)</h3>
                        <p class="mt-1 text-sm text-amber-900">
                            {{ $permintaanPembelianPending->count() }} kebutuhan menunggu pembelian. Data sementara ini hanya simulasi; permintaan sebenarnya dikelola admin gudang.
                        </p>
                    </div>
                </div>
                @if ($permintaanPembelianPending->isEmpty())
                    <p class="text-sm text-amber-900">Belum ada permintaan komoditas dari gudang.</p>
                @else
                    <form action="{{ route("$routeBase.create") }}" method="GET" class="flex flex-col gap-2 sm:flex-row">
                        <label class="sr-only" for="permintaan_pembelian_select">Pilih permintaan gudang</label>
                        <select id="permintaan_pembelian_select" name="permintaan" required class="min-w-0 flex-1 rounded-sm border border-amber-300 bg-white px-3 py-2 text-sm">
                            <option value="">-- Pilih permintaan --</option>
                            @foreach ($permintaanPembelianPending as $requestItem)
                                <option value="{{ $requestItem->id_permintaan_pembelian }}" @selected($permintaanPembelian?->id_permintaan_pembelian === $requestItem->id_permintaan_pembelian)>
                                    {{ $requestItem->kode_permintaan }} — {{ $requestItem->barang?->nama_barang }} ({{ number_format((float) $requestItem->qty_diminta, 2, ',', '.') }} KG) — {{ $requestItem->sumber_pembelian === 'desa_lain' ? 'Desa lain' : 'Warga lokal' }} — Rekomendasi: {{ $requestItem->supplier_recommendations->pluck('nama')->join(', ') ?: 'Belum ada pemasok yang cocok di data master' }}
                                </option>
                            @endforeach
                        </select>
                        <button type="submit" class="rounded-sm bg-amber-700 px-4 py-2 text-sm font-medium text-white hover:bg-amber-800">Pilih Permintaan</button>
                    </form>
                @endif
            </section>

            <form action="{{ route("$routeBase.store") }}" method="POST" class="space-y-4">
                @csrf
                <input type="hidden" name="jenis_pembayaran" value="kredit" id="consignment_payment_value" disabled>

                <!-- Pilih Sumber -->
                <div class="rounded-sm border border-paper-300 bg-paper-50 p-4">
                    <h3 class="mb-3 text-sm font-semibold text-ink-900">Pilih Sumber Pembelian</h3>
                    @if ($permintaanPembelian)
                        <input type="hidden" name="id_permintaan_pembelian" value="{{ $permintaanPembelian->id_permintaan_pembelian }}">
                        <input type="hidden" name="source" value="{{ $permintaanPembelian->sumber_pembelian }}">
                        <p class="text-sm text-ink-700">
                            Kebutuhan yang dipilih: <strong>{{ $permintaanPembelian->barang->nama_barang }}</strong>,
                            {{ number_format((float) $permintaanPembelian->qty_diminta, 2, ',', '.') }} KG,
                            gudang {{ $permintaanPembelian->gudang->nama_gudang }}. Sumber pemasok:
                            <strong>{{ $permintaanPembelian->sumber_pembelian === 'desa_lain' ? 'Pembelian dari Desa Lain' : 'Pembelian dari Warga Lokal' }}</strong>.
                            Admin pembelian hanya memilih salah satu pemasok rekomendasi; permintaan tidak dapat dibuat dari halaman ini.
                        </p>
                    @else
                        <label class="flex items-center gap-2 mb-2">
                            <input type="radio" name="source" value="desa_lain" required @checked(old('source', 'warga_lokal') === 'desa_lain') onchange="toggleForm()" class="rounded">
                            Pembelian dari Desa Lain
                        </label>
                        <label class="mb-2 flex items-center gap-2">
                            <input type="radio" name="source" value="warga_lokal" @checked(old('source', 'warga_lokal') === 'warga_lokal') onchange="toggleForm()" class="rounded">
                            Pembelian dari Warga Lokal
                        </label>
                        <label class="flex items-center gap-2">
                            <input type="radio" name="source" value="pengambilan_konsinyasi" @checked(old('source') === 'pengambilan_konsinyasi') onchange="toggleForm()" class="rounded">
                            Pengambilan Konsinyasi
                        </label>
                    @endif
                </div>

                <!-- Form Pembelian -->
                <div id="quick_purchase_section" class="rounded-sm border border-paper-300 bg-paper-50 p-4" style="display: none;">
                    <h3 id="purchase_heading" class="mb-3 text-sm font-semibold text-ink-900">Data Pembelian</h3>
                    <p class="mb-3 text-xs text-ink-600">Jumlah dan harga pembelian diisi dalam KG dan harga per KG. Barang tanpa konversi KG tidak dapat dipilih.</p>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <label class="block text-sm">
                            <span class="text-ink-700 font-medium">Unit Usaha *</span>
                            <select @unless ($permintaanPembelian) name="id_unit_usaha" @endunless @disabled($permintaanPembelian) data-locked="{{ $permintaanPembelian ? 'true' : 'false' }}" class="mt-1 w-full rounded-sm border border-paper-300 bg-paper-50 px-3 py-2">
                                <option value="">-- Pilih Unit --</option>
                                @foreach ($barangs->groupBy('id_unit_usaha') as $idUnit => $unitBarang)
                                    <option value="{{ $idUnit }}" @selected($permintaanPembelian && (int) $unitBarang->first()->id_unit_usaha === (int) $permintaanPembelian->barang->id_unit_usaha)>
                                        {{ $unitBarang->first()->unitUsaha->nama_unit_usaha ?? 'N/A' }}
                                    </option>
                                @endforeach
                            </select>
                            @if ($permintaanPembelian)
                                <input type="hidden" name="id_unit_usaha" value="{{ $permintaanPembelian->barang->id_unit_usaha }}">
                            @endif
                        </label>
                        <label class="block text-sm">
                            <span class="text-ink-700 font-medium">Gudang *</span>
                            <select @unless ($permintaanPembelian) name="id_gudang" @endunless @disabled($permintaanPembelian) data-locked="{{ $permintaanPembelian ? 'true' : 'false' }}" class="mt-1 w-full rounded-sm border border-paper-300 bg-paper-50 px-3 py-2">
                                <option value="">-- Pilih Gudang --</option>
                                @foreach ($gudangs as $gudang)
                                    <option value="{{ $gudang->id_gudang }}" @selected($permintaanPembelian && (int) $gudang->id_gudang === (int) $permintaanPembelian->gudang->id_gudang)>{{ $gudang->nama_gudang }}</option>
                                @endforeach
                            </select>
                            @if ($permintaanPembelian)
                                <input type="hidden" name="id_gudang" value="{{ $permintaanPembelian->gudang->id_gudang }}">
                            @endif
                        </label>
                    </div>

                    <div class="mt-4 overflow-hidden rounded-sm border border-paper-300">
                        <div class="flex items-center justify-between bg-paper-100 px-3 py-2">
                            <h4 class="text-sm font-semibold text-ink-900">Daftar Barang</h4>
                            <button type="button" onclick="addQuickPurchaseRow()" @if ($permintaanPembelian) hidden @endif class="rounded-sm bg-emerald-600 px-2 py-1 text-xs font-medium text-white hover:bg-emerald-700">
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
                                        <th class="px-3 py-2">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody id="quick_purchase_rows">
                                    <tr class="quick-purchase-row">
                                        <td class="px-3 py-2">
                                            <select @unless ($permintaanPembelian) name="items[0][id_barang]" @endunless @disabled($permintaanPembelian) data-locked="{{ $permintaanPembelian ? 'true' : 'false' }}" onchange="updateQuickPurchaseUnits(this)" class="quick-purchase-barang w-full rounded-sm border border-paper-300 bg-paper-50 px-2 py-1.5">
                                                <option value="">-- Pilih Barang --</option>
                                                @foreach ($barangs as $barang)
                                                    <option value="{{ $barang->id_barang }}" @disabled(!in_array((int) $barang->id_barang, $barangKgIds, true)) data-base-unit="{{ $barang->id_satuan_dasar }}" data-base-code="{{ $barang->satuanDasar?->kode_satuan }}" data-conversions="{{ $barang->konversiSatuan->filter(fn ($conversion) => strtoupper($conversion->satuan?->kode_satuan ?? '') === 'KG')->map(fn ($conversion) => ['id' => (int) $conversion->id_satuan, 'code' => 'KG', 'factor' => (float) $conversion->faktor_ke_dasar, 'default' => (bool) $conversion->is_default_beli])->values()->toJson() }}" @selected($permintaanPembelian && (int) $barang->id_barang === (int) $permintaanPembelian->id_barang)>{{ $barang->nama_barang }}</option>
                                                @endforeach
                                            </select>
                                            @if ($permintaanPembelian)
                                                <input type="hidden" name="items[0][id_barang]" value="{{ $permintaanPembelian->id_barang }}">
                                            @endif
                                            <input type="hidden" name="items[0][id_satuan]" value="{{ $permintaanPembelian?->id_satuan }}" class="quick-purchase-unit">
                                        </td>
                                        <td class="px-3 py-2">
                                            <input type="number" step="0.0001" min="0" name="items[0][qty_dasar]" value="{{ $permintaanPembelian?->qty_diminta ?? old('items.0.qty_dasar') }}" @readonly($permintaanPembelian) class="w-full rounded-sm border border-paper-300 bg-paper-50 px-2 py-1.5">
                                        </td>
                                        <td class="px-3 py-2">
                                            <input type="number" step="0.01" min="0" name="items[0][harga_satuan]" class="quick-purchase-price w-full rounded-sm border border-paper-300 bg-paper-50 px-2 py-1.5">
                                        </td>
                                        <td class="px-3 py-2">
                                            @unless ($permintaanPembelian)
                                                <button type="button" onclick="removeQuickPurchaseRow(this)" class="text-xs text-merah-600 hover:text-merah-700">Hapus</button>
                                            @endunless
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Umum -->
                <div id="purchase_details_section" class="rounded-sm border border-paper-300 bg-paper-50 p-4">
                    <h3 class="mb-3 text-sm font-semibold text-ink-900">Data Pihak Sumber dan Pembayaran</h3>
                    <div class="space-y-3">
                        <label class="block text-sm">
                            <span id="supplier-label" class="text-ink-700 font-medium">Pihak Sumber Pembelian *</span>
                            <select id="id_pihak" name="id_pihak" required @disabled($permintaanPembelian && $permintaanPembelian->supplier_recommendations->isEmpty()) class="mt-1 w-full rounded-sm border border-paper-300 bg-paper-50 px-3 py-2">
                                <option value="">-- Pilih Pihak --</option>
                                @foreach ($pihaks as $pihak)
                                    <option value="{{ $pihak->id_pihak }}" data-source="{{ $pihak->jenis_pihak === 'entitas_lain' ? 'desa_lain' : 'warga_lokal' }}" data-is-village="{{ $pihak->id_entitas_mitra ? 'true' : 'false' }}" data-request-recommended="{{ $permintaanPembelian?->supplier_recommendations->contains('id_pihak', $pihak->id_pihak) ? 'true' : 'false' }}" @selected(old('id_pihak') == $pihak->id_pihak)>
                                        {{ $pihak->nama_pihak }}{{ $pihak->jenis_pihak === 'petani' ? ' (Petani)' : '' }}
                                    </option>
                                @endforeach
                            </select>
                            @if ($permintaanPembelian)
                                <p class="mt-1 text-xs text-ink-500">
                                    Rekomendasi: {{ $permintaanPembelian->supplier_recommendations->pluck('nama')->join(', ') ?: 'Belum ada pemasok yang cocok di data master.' }}
                                </p>
                            @endif
                        </label>
                        <label id="payment_method_field" class="block text-sm">
                            <span class="text-ink-700 font-medium">Jenis Pembayaran *</span>
                            <select name="jenis_pembayaran" data-previous-value="{{ old('jenis_pembayaran') }}" required onchange="togglePaymentFields()" class="mt-1 w-full rounded-sm border border-paper-300 bg-paper-50 px-3 py-2">
                                <option value="">-- Pilih Jenis --</option>
                                <option value="tunai" @selected(old('jenis_pembayaran') === 'tunai')>Tunai</option>
                                <option value="transfer" @selected(old('jenis_pembayaran') === 'transfer')>Transfer</option>
                                <option value="kredit" @selected(old('jenis_pembayaran') === 'kredit')>Kredit</option>
                            </select>
                        </label>
                        <div id="sumber_uang_section" style="display: none;">
                            <label class="block text-sm">
                                <span class="text-ink-700 font-medium">Sumber Uang (Kas / Bank) *</span>
                                <select name="id_kas_bank" id="id_kas_bank" class="mt-1 w-full rounded-sm border border-paper-300 bg-paper-50 px-3 py-2">
                                    <option value="">-- Pilih Sumber Kas / Bank --</option>
                                    @foreach ($kasBanks as $kasBank)
                                        <option value="{{ $kasBank->id_kas_bank }}" data-jenis="{{ $kasBank->jenis }}" @selected(old('id_kas_bank') == $kasBank->id_kas_bank)>
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
                                <input type="date" name="tgl_jatuh_tempo" min="{{ now()->format('Y-m-d') }}" value="{{ old('tgl_jatuh_tempo') }}" class="mt-1 w-full rounded-sm border border-paper-300 bg-paper-50 px-3 py-2">
                                <p class="mt-1 text-xs text-ink-500">Wajib diisi untuk pembelian dengan tempo/kredit.</p>
                            </label>
                        </div>
                    </div>
                </div>

                <div id="consignment_section" class="rounded-sm border border-sky-300 bg-sky-50 p-4" style="display: none;">
                    <h3 class="mb-2 text-sm font-semibold text-sky-950">Pencatatan Pengambilan Konsinyasi</h3>
                    <p class="text-sm text-sky-900">
                        Catat desa pengirim, komoditas, kuantitas, dan harga per KG yang ditetapkan desa pengirim. Nilai total menjadi hutang konsinyasi saat transaksi disetujui. Pencatatan ini tidak memilih sumber kas/bank dan belum mengubah stok Gudang atau Penjualan.
                    </p>
                </div>

                <div id="purchase_actions" class="flex gap-2">
                    <button type="submit" class="rounded-sm bg-emerald-600 px-4 py-2 font-medium text-white hover:bg-emerald-700">
                        Lanjutkan
                    </button>
                    <a href="{{ route("$routeBase.index") }}" class="rounded-sm border border-paper-300 px-4 py-2 text-ink-700 hover:bg-paper-100">
                        Batal
                    </a>
                </div>
            </form>
        </div>

        <div class="md:col-span-2 xl:col-span-1">
            <div class="rounded-sm border border-paper-300 bg-paper-50 p-4">
                <h3 class="mb-3 font-semibold text-ink-900">Alur Pembelian</h3>
                <ol class="list-inside list-decimal space-y-1 text-sm text-ink-700">
                    <li><strong>Draft:</strong> Pembelian dicatat</li>
                    <li><strong>Disetujui:</strong> Status diperbarui di modul Pembelian</li>
                    <li><strong>Retur:</strong> Dicatat terpisah di menu Retur Pembelian</li>
                </ol>
            </div>
        </div>
    </div>

    <script>
        function selectedPurchaseSource() {
            const selectedSource = document.querySelector('input[name="source"]:checked, input[name="source"][type="hidden"]');
            return selectedSource ? selectedSource.value : 'warga_lokal';
        }

        function toggleForm() {
            const source = selectedPurchaseSource();
            const isPurchase = source === 'desa_lain' || source === 'warga_lokal';
            const isConsignment = source === 'pengambilan_konsinyasi';
            const isTransaction = isPurchase || isConsignment;
            const purchaseSection = document.getElementById('quick_purchase_section');
            const purchaseDetails = document.getElementById('purchase_details_section');
            const consignmentSection = document.getElementById('consignment_section');
            const supplierSelect = document.getElementById('id_pihak');

            purchaseSection.style.display = isTransaction ? 'block' : 'none';
            purchaseDetails.style.display = isTransaction ? 'block' : 'none';
            consignmentSection.style.display = isConsignment ? 'block' : 'none';
            document.getElementById('purchase_actions').style.display = isTransaction ? 'flex' : 'none';
            document.querySelectorAll('#quick_purchase_section select, #quick_purchase_section input').forEach((field) => field.disabled = !isTransaction || field.dataset.locked === 'true');
            document.querySelectorAll('#purchase_details_section select, #purchase_details_section input').forEach((field) => field.disabled = !isTransaction);
            document.querySelectorAll('#quick_purchase_rows input[name$="[qty_dasar]"], #quick_purchase_rows input[name$="[harga_satuan]"]').forEach((field) => {
                field.required = isTransaction;
                if (field.name.endsWith('[harga_satuan]')) {
                    field.min = isConsignment ? '0.01' : '0';
                }
            });
            document.getElementById('purchase_heading').textContent = isConsignment
                ? 'Data Pengambilan Konsinyasi'
                : (source === 'desa_lain' ? 'Data Pembelian dari Desa Lain' : 'Data Pembelian dari Warga Lokal');
            document.getElementById('supplier-label').textContent = isConsignment
                ? 'Desa Pengirim *'
                : 'Pihak Sumber Pembelian *';

            let supplierIsAvailable = false;
            const hasPurchaseRequest = Boolean(document.getElementById('id_permintaan_pembelian'));
            const supplierSource = isConsignment ? 'desa_lain' : source;
            Array.from(supplierSelect.options).forEach((option, index) => {
                if (index === 0) return;
                const isRecommended = !hasPurchaseRequest || option.dataset.requestRecommended === 'true';
                const matchesSource = option.dataset.source === supplierSource
                    && isRecommended
                    && (!isConsignment || option.dataset.isVillage === 'true');
                option.hidden = !matchesSource;
                option.disabled = !matchesSource;
                if (matchesSource && option.value === supplierSelect.value) {
                    supplierIsAvailable = true;
                }
            });
            if (!supplierIsAvailable) {
                supplierSelect.value = '';
            }

            togglePaymentFields();
        }

        function togglePaymentFields() {
            const jenisSelect = document.querySelector('select[name="jenis_pembayaran"]');
            const source = selectedPurchaseSource();
            const isConsignment = source === 'pengambilan_konsinyasi';
            const paymentField = document.getElementById('payment_method_field');
            const consignmentPaymentValue = document.getElementById('consignment_payment_value');
            const jenis = isConsignment ? 'kredit' : (jenisSelect ? jenisSelect.value : '');
            const isPurchase = source === 'desa_lain' || source === 'warga_lokal';
            const isCredit = (isPurchase && jenis === 'kredit') || isConsignment;
            const isCashOrBank = isPurchase && (jenis === 'tunai' || jenis === 'transfer');

            const jatuhTempoSection = document.getElementById('jatuh_tempo_section');
            const jatuhTempoInput = document.querySelector('input[name="tgl_jatuh_tempo"]');
            const sumberUangSection = document.getElementById('sumber_uang_section');
            const sumberUangSelect = document.getElementById('id_kas_bank');

            paymentField.style.display = isConsignment ? 'none' : 'block';
            if (isConsignment && !jenisSelect.disabled) {
                jenisSelect.dataset.previousValue = jenisSelect.value;
            } else if (!isConsignment && jenisSelect.disabled) {
                jenisSelect.value = jenisSelect.dataset.previousValue || '';
            }
            jenisSelect.disabled = isConsignment;
            jenisSelect.required = !isConsignment;
            consignmentPaymentValue.disabled = !isConsignment;
            if (isConsignment) {
                jenisSelect.value = 'kredit';
            }

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

                    // Filter opsi kas vs bank sesuai jenis pembayaran
                    let hasMatchingSelected = false;
                    const options = sumberUangSelect.querySelectorAll('option');
                    options.forEach((opt, idx) => {
                        if (idx === 0) return; // placeholder
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

                    // Jika belum ada yang cocok atau baru berganti jenis, pilih opsi pertama yang valid
                    if (!hasMatchingSelected) {
                        const firstValid = Array.from(options).find((opt, idx) => idx > 0 && !opt.disabled);
                        if (firstValid) {
                            sumberUangSelect.value = firstValid.value;
                        }
                    }
                } else {
                    // Jika kredit atau belum dipilih
                    sumberUangSection.style.display = 'none';
                    sumberUangSelect.disabled = true;
                    sumberUangSelect.required = false;
                    sumberUangSelect.value = '';
                }
            }
        }

        function updateQuickPurchaseUnits(barangSelect) {
            const row = barangSelect.closest('.quick-purchase-row');
            const unitInput = row.querySelector('.quick-purchase-unit');
            const selectedBarang = barangSelect.selectedOptions[0];
            const requestedUnitId = unitInput.value;
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
            const preferred = conversions.find((conversion) => conversion.default)
                ?? conversions.find((conversion) => Number(conversion.id) === baseUnitId);
            const hasPurchaseRequest = Boolean(document.getElementById('id_permintaan_pembelian'));
            const requestedUnit = conversions.find((conversion) => String(conversion.id) === requestedUnitId);
            unitInput.value = String(hasPurchaseRequest && requestedUnit ? requestedUnit.id : (preferred?.id ?? ''));
        }

        function addQuickPurchaseRow() {
            const tableBody = document.getElementById('quick_purchase_rows');
            const rows = tableBody.querySelectorAll('.quick-purchase-row');
            const index = rows.length;

            const row = document.createElement('tr');
            row.className = 'quick-purchase-row';
            row.innerHTML = `
                <td class="px-3 py-2">
                    <select name="items[${index}][id_barang]" onchange="updateQuickPurchaseUnits(this)" class="quick-purchase-barang w-full rounded-sm border border-paper-300 bg-paper-50 px-2 py-1.5">
                        <option value="">-- Pilih Barang --</option>
                        @foreach ($barangs as $barang)
                            <option value="{{ $barang->id_barang }}" @disabled(!in_array((int) $barang->id_barang, $barangKgIds, true)) data-base-unit="{{ $barang->id_satuan_dasar }}" data-base-code="{{ $barang->satuanDasar?->kode_satuan }}" data-conversions="{{ $barang->konversiSatuan->filter(fn ($conversion) => strtoupper($conversion->satuan?->kode_satuan ?? '') === 'KG')->map(fn ($conversion) => ['id' => (int) $conversion->id_satuan, 'code' => 'KG', 'factor' => (float) $conversion->faktor_ke_dasar, 'default' => (bool) $conversion->is_default_beli])->values()->toJson() }}">{{ $barang->nama_barang }}                            </option>
                            @endforeach
                    </select>
                    <input type="hidden" name="items[${index}][id_satuan]" class="quick-purchase-unit">
                </td>
                <td class="px-3 py-2">
                    <input type="number" step="0.01" min="0" name="items[${index}][qty_dasar]" class="w-full rounded-sm border border-paper-300 bg-paper-50 px-2 py-1.5">
                </td>
                <td class="px-3 py-2">
                    <input type="number" step="0.01" min="0" name="items[${index}][harga_satuan]" class="quick-purchase-price w-full rounded-sm border border-paper-300 bg-paper-50 px-2 py-1.5">
                </td>
                <td class="px-3 py-2">
                    <button type="button" onclick="removeQuickPurchaseRow(this)" class="text-xs text-merah-600 hover:text-merah-700">Hapus</button>
                </td>
            `;
            tableBody.appendChild(row);
        }

        function removeQuickPurchaseRow(button) {
            const row = button.closest('.quick-purchase-row');
            if (document.querySelectorAll('.quick-purchase-row').length > 1) {
                row.remove();
            }
        }

        document.addEventListener('DOMContentLoaded', function () {
            toggleForm();
            document.querySelectorAll('.quick-purchase-barang').forEach(updateQuickPurchaseUnits);
        });
    </script>
</x-layouts.app>

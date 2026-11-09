<x-layouts.app title="Tambah Stock Opname" eyebrow="Gudang">
    <div class="mb-4">
        <a href="{{ route('gudang.index') }}" class="text-sm font-medium text-ink-700 hover:text-merah-600">
            &larr; Kembali ke Gudang
        </a>
    </div>

    <form action="{{ route('gudang.opname.store') }}" method="POST" class="space-y-6">
        @csrf

        <div class="grid gap-4 md:grid-cols-2">
            <div>
                <label class="mb-1 block text-sm font-medium text-ink-700">Gudang</label>
                <select name="id_gudang" class="w-full rounded-sm border border-paper-300 bg-paper-50 px-3 py-2 text-sm focus:border-merah-400 focus:outline-none">
                    @foreach ($gudang as $item)
                        <option value="{{ $item->id_gudang }}">{{ $item->nama_gudang }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-ink-700">Kode Opname</label>
                <input type="text" name="kode_opname" value="OPN-{{ now()->format('YmdHis') }}" class="w-full rounded-sm border border-paper-300 bg-paper-50 px-3 py-2 text-sm focus:border-merah-400 focus:outline-none" required>
            </div>
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium text-ink-700">Tanggal</label>
            <input type="date" name="tanggal" value="{{ now()->toDateString() }}" class="w-full rounded-sm border border-paper-300 bg-paper-50 px-3 py-2 text-sm focus:border-merah-400 focus:outline-none" required>
        </div>

        <div class="rounded-sm border border-paper-300 bg-paper-50 p-4">
            <div class="mb-3 flex items-center justify-between">
                <h3 class="font-display text-base font-semibold">Daftar Barang</h3>
                <button type="button" id="tambah-barang" class="rounded-sm border border-paper-300 px-3 py-2 text-xs font-medium text-ink-700 hover:border-merah-400">
                    + Tambah Item
                </button>
            </div>

            <p class="mb-3 rounded-sm border border-padi-100 bg-padi-100/50 px-3 py-2 text-xs text-ink-700">
                Saat disimpan, jumlah stok akan langsung disesuaikan. Jika ada selisih, sistem mencatat mutasi penyesuaian pada kartu stok. Jika tidak ada selisih, hasil hitung tetap tercatat tanpa mutasi nol.
                Untuk barang dengan pelacakan batch, opname tetap dihitung sederhana berdasarkan total barang. Selisih kurang dialokasikan otomatis dengan FEFO; selisih lebih dicatat sebagai batch baru dengan tanggal kedaluwarsa tidak diketahui.
            </p>

            <div class="overflow-x-auto">
                <table class="w-full border-separate border-spacing-y-2 text-left text-sm">
                    <thead>
                        <tr class="text-xs uppercase tracking-wide text-ink-600/70">
                            <th class="px-2 py-2 font-medium">No</th>
                            <th class="px-2 py-2 font-medium">Barang</th>
                            <th class="px-2 py-2 font-medium">Qty Fisik</th>
                            <th class="px-2 py-2 font-medium">Keterangan</th>
                            <th class="px-2 py-2 font-medium text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="barang-wrapper">
                        <tr class="item-row align-top">
                            <td class="px-2 py-2 text-ink-700">1</td>
                            <td class="px-2 py-2">
                                <select name="items[0][id_barang]" class="w-full rounded-sm border border-paper-300 bg-paper-50 px-3 py-2 text-sm focus:border-merah-400 focus:outline-none" required>
                                    @foreach ($barang as $item)
                                        <option value="{{ $item->id_barang }}">{{ $item->nama_barang }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td class="px-2 py-2">
                                <input type="number" min="0" step="0.0001" name="items[0][qty_fisik]" value="0" class="w-full rounded-sm border border-paper-300 bg-paper-50 px-3 py-2 text-sm focus:border-merah-400 focus:outline-none" required>
                            </td>
                            <td class="px-2 py-2">
                                <input type="text" name="items[0][keterangan]" placeholder="mis. hilang 2 karung / rusak 3 karung" class="w-full rounded-sm border border-paper-300 bg-paper-50 px-3 py-2 text-sm focus:border-merah-400 focus:outline-none">
                            </td>
                            <td class="px-2 py-2 text-right">
                                <button type="button" class="hapus-item rounded-sm border border-paper-300 px-2 py-1 text-xs text-ink-700 hover:border-merah-400">
                                    Hapus
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="flex gap-3">
            <button type="submit" class="rounded-sm bg-merah-500 px-4 py-2 text-sm font-medium text-paper-50 hover:bg-merah-600">
                Simpan Opname
            </button>
            <a href="{{ route('gudang.opname.index') }}" class="rounded-sm border border-paper-300 px-4 py-2 text-sm text-ink-700 hover:border-merah-400">
                Batal
            </a>
        </div>
    </form>

    <script>
        let itemIndex = 1;

        function updateRowNumbers() {
            document.querySelectorAll('#barang-wrapper .item-row').forEach((row, index) => {
                row.querySelector('td:first-child').textContent = index + 1;
            });
        }

        document.getElementById('tambah-barang').addEventListener('click', function () {
            const wrapper = document.getElementById('barang-wrapper');
            const row = document.createElement('tr');
            row.className = 'item-row align-top';
            row.innerHTML = `
                <td class="px-2 py-2 text-ink-700">${itemIndex + 1}</td>
                <td class="px-2 py-2">
                    <select name="items[${itemIndex}][id_barang]" class="w-full rounded-sm border border-paper-300 bg-paper-50 px-3 py-2 text-sm focus:border-merah-400 focus:outline-none" required>
                        @foreach ($barang as $item)
                            <option value="{{ $item->id_barang }}">{{ $item->nama_barang }}</option>
                        @endforeach
                    </select>
                </td>
                <td class="px-2 py-2">
                    <input type="number" min="0" step="0.0001" name="items[${itemIndex}][qty_fisik]" value="0" class="w-full rounded-sm border border-paper-300 bg-paper-50 px-3 py-2 text-sm focus:border-merah-400 focus:outline-none" required>
                </td>
                <td class="px-2 py-2">
                    <input type="text" name="items[${itemIndex}][keterangan]" placeholder="mis. hilang 2 karung / rusak 3 karung" class="w-full rounded-sm border border-paper-300 bg-paper-50 px-3 py-2 text-sm focus:border-merah-400 focus:outline-none">
                </td>
                <td class="px-2 py-2 text-right">
                    <button type="button" class="hapus-item rounded-sm border border-paper-300 px-2 py-1 text-xs text-ink-700 hover:border-merah-400">
                        Hapus
                    </button>
                </td>
            `;
            wrapper.appendChild(row);
            itemIndex += 1;
            updateRowNumbers();
        });

        document.addEventListener('click', function (event) {
            if (event.target && event.target.classList.contains('hapus-item')) {
                const row = event.target.closest('.item-row');
                if (row) {
                    row.remove();
                    updateRowNumbers();
                }
            }
        });
    </script>
</x-layouts.app>

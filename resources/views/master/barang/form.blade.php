<x-layouts.app :title="$title" eyebrow="M0 Master Data">
    <div class="mb-5">
        <a href="{{ route('master.barang.index') }}" class="inline-flex items-center gap-1 text-sm text-ink-600 hover:text-ink-900">
            &larr; Kembali ke Daftar Barang
        </a>
    </div>

    @if ($errors->any())
        <div class="mb-5 rounded-sm border border-merah-300 bg-merah-50 px-4 py-3 text-sm text-merah-800">
            <ul class="list-inside list-disc">
                @foreach ($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @php
        $initialKonversi = old('konversi', $konversiTambahan ?: []);
    @endphp

    <form method="POST"
          action="{{ $mode === 'edit' ? route('master.barang.update', $item->id_barang) : route('master.barang.store') }}"
          class="mx-auto max-w-3xl rounded-sm border border-paper-300 bg-paper-50 p-6 shadow-2xs"
          x-data="{
              rows: {{ json_encode(array_values($initialKonversi)) }},
              addRow() { this.rows.push({ id_satuan: '', faktor_ke_dasar: '' }); },
              removeRow(idx) { this.rows.splice(idx, 1); }
          }">
        @csrf
        @if($mode === 'edit')
            @method('PUT')
        @endif

        <div class="mb-5 border-b border-paper-200 pb-3">
            <h2 class="font-display text-lg font-semibold text-ink-900">{{ $title }}</h2>
            <p class="text-xs text-ink-600">
                Entitas: <strong>{{ $entitas?->nama_entitas }}</strong> (Barang, harga jual, dan batas stok dikelola terpisah per entitas)
            </p>
        </div>

        <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
            <div>
                <label class="block text-xs font-medium text-ink-800 mb-1">Kode Barang (SKU) <span class="text-merah-600">*</span></label>
                <input type="text" name="kode_barang" value="{{ old('kode_barang', $item->kode_barang) }}" required maxlength="50"
                       placeholder="Contoh: BRG-TELUR-01"
                       class="w-full rounded-sm border border-paper-300 bg-white px-3 py-2 font-mono text-sm uppercase text-ink-900 focus:border-merah-400 focus:outline-none">
            </div>

            <div>
                <label class="block text-xs font-medium text-ink-800 mb-1">Nama Barang <span class="text-merah-600">*</span></label>
                <input type="text" name="nama_barang" value="{{ old('nama_barang', $item->nama_barang) }}" required maxlength="150"
                       placeholder="Contoh: Telur Ayam Ras Segar"
                       class="w-full rounded-sm border border-paper-300 bg-white px-3 py-2 text-sm text-ink-900 focus:border-merah-400 focus:outline-none">
            </div>

            <div>
                <label class="block text-xs font-medium text-ink-800 mb-1">Komoditas Terkait (M2/M3)</label>
                <select name="id_komoditas"
                        class="w-full rounded-sm border border-paper-300 bg-white px-3 py-2 text-sm text-ink-900 focus:border-merah-400 focus:outline-none">
                    <option value="">-- Pilih Komoditas --</option>
                    @foreach($komoditasList as $k)
                        <option value="{{ $k->id }}" @selected(old('id_komoditas', $item->id_komoditas) == $k->id)>
                            {{ $k->nama }} ({{ $k->kategori }})
                        </option>
                    @endforeach
                </select>
                <p class="mt-1 text-[11px] text-ink-500">Hubungkan ke Komoditas agar PR Defisit (M3) otomatis memilih barang ini.</p>
            </div>

            <div>
                <label class="block text-xs font-medium text-ink-800 mb-1">Unit Usaha Akuntansi <span class="text-merah-600">*</span></label>
                <select name="id_unit_usaha" required
                        class="w-full rounded-sm border border-paper-300 bg-white px-3 py-2 text-sm text-ink-900 focus:border-merah-400 focus:outline-none">
                    @foreach($unitUsahaList as $u)
                        <option value="{{ $u->id_unit_usaha }}" @selected(old('id_unit_usaha', $item->id_unit_usaha) == $u->id_unit_usaha)>
                            {{ $u->nama_unit_usaha }} (Persediaan: {{ $u->kode_akun_persediaan }}, HPP: {{ $u->kode_akun_hpp }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-medium text-ink-800 mb-1">Satuan Dasar Stok <span class="text-merah-600">*</span></label>
                <select name="id_satuan_dasar" required
                        class="w-full rounded-sm border border-paper-300 bg-white px-3 py-2 font-mono text-sm text-ink-900 focus:border-merah-400 focus:outline-none">
                    <option value="">-- Pilih Satuan Dasar --</option>
                    @foreach($satuanList as $s)
                        <option value="{{ $s->id }}" @selected(old('id_satuan_dasar', $item->id_satuan_dasar) == $s->id)>
                            {{ $s->kode_satuan }}
                        </option>
                    @endforeach
                </select>
                <p class="mt-1 text-[11px] text-ink-500">Kartu stok gudang dan HPP dihitung menggunakan satuan dasar ini.</p>
            </div>

            <div>
                <label class="block text-xs font-medium text-ink-800 mb-1">Harga Jual Standar Kasir POS (Rp / Satuan Dasar) <span class="text-merah-600">*</span></label>
                <input type="number" step="1" min="0" name="harga_jual_standar"
                       value="{{ old('harga_jual_standar', (float) ($setting->harga_jual_standar ?? 0)) }}" required
                       class="w-full rounded-sm border border-paper-300 bg-white px-3 py-2 font-mono text-sm font-semibold text-ink-900 focus:border-merah-400 focus:outline-none">
            </div>

            <div>
                <label class="block text-xs font-medium text-ink-800 mb-1">Batas Stok Minimum (Peringatan Reorder)</label>
                <input type="number" step="0.01" min="0" name="stok_minimum"
                       value="{{ old('stok_minimum', (float) ($setting->stok_minimum ?? 10)) }}"
                       class="w-full rounded-sm border border-paper-300 bg-white px-3 py-2 font-mono text-sm text-ink-900 focus:border-merah-400 focus:outline-none">
            </div>

            <div>
                <label class="block text-xs font-medium text-ink-800 mb-1">Batas Stok Maksimum Gudang</label>
                <input type="number" step="0.01" min="0" name="stok_maksimum"
                       value="{{ old('stok_maksimum', (float) ($setting->stok_maksimum ?? 1000)) }}"
                       class="w-full rounded-sm border border-paper-300 bg-white px-3 py-2 font-mono text-sm text-ink-900 focus:border-merah-400 focus:outline-none">
            </div>

            {{-- Konversi Satuan Tambahan --}}
            <div class="md:col-span-2 rounded-sm border border-paper-200 bg-white p-4">
                <div class="flex items-center justify-between mb-3">
                    <div>
                        <h3 class="text-xs font-bold uppercase tracking-wider text-ink-800">Konversi Multi-Satuan (Opsional)</h3>
                        <p class="text-[11px] text-ink-500">Contoh: Jika satuan dasar adalah <strong>KG</strong>, Anda bisa menambahkan <strong>1 KARUNG = 25 KG</strong> atau <strong>1 KRAT = 15 KG</strong>.</p>
                    </div>
                    <button type="button" @click="addRow()"
                            class="rounded-sm border border-paper-300 bg-paper-100 px-3 py-1 text-xs font-medium text-ink-800 hover:border-merah-400">
                        + Tambah Konversi
                    </button>
                </div>

                <template x-if="rows.length === 0">
                    <p class="text-xs text-ink-400 italic">Hanya menggunakan satuan dasar (1 : 1).</p>
                </template>

                <div class="space-y-2">
                    <template x-for="(row, index) in rows" :key="index">
                        <div class="flex flex-wrap items-center gap-3 rounded-sm border border-paper-200 bg-paper-50 p-2.5">
                            <span class="font-mono text-xs font-semibold text-ink-700">1</span>
                            <select :name="'konversi[' + index + '][id_satuan]'" x-model="row.id_satuan"
                                    class="rounded-sm border border-paper-300 bg-white px-3 py-1.5 font-mono text-xs text-ink-900">
                                <option value="">-- Pilih Satuan Kemasan --</option>
                                @foreach($satuanList as $s)
                                    <option value="{{ $s->id }}">{{ $s->kode_satuan }}</option>
                                @endforeach
                            </select>
                            <span class="font-mono text-xs text-ink-700">=</span>
                            <input type="number" step="0.0001" min="0.0001"
                                   :name="'konversi[' + index + '][faktor_ke_dasar]'"
                                   x-model="row.faktor_ke_dasar"
                                   placeholder="Faktor (mis: 25)"
                                   class="w-36 rounded-sm border border-paper-300 bg-white px-3 py-1.5 font-mono text-xs text-ink-900">
                            <span class="text-xs text-ink-600">Satuan Dasar</span>
                            <button type="button" @click="removeRow(index)"
                                    class="ml-auto text-xs font-medium text-merah-600 hover:underline">
                                Hapus
                            </button>
                        </div>
                    </template>
                </div>
            </div>

            <div class="md:col-span-2 flex flex-wrap items-center gap-6 pt-1">
                <label class="inline-flex items-center gap-2 text-sm text-ink-800">
                    <input type="hidden" name="is_dijual" value="0">
                    <input type="checkbox" name="is_dijual" value="1"
                           @checked(old('is_dijual', $setting->is_dijual ?? true))
                           class="rounded border-paper-300 text-merah-600 focus:ring-merah-500">
                    <span>Tersedia untuk Dijual di Kasir POS Entitas Ini</span>
                </label>

                <label class="inline-flex items-center gap-2 text-sm text-ink-800">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1"
                           @checked(old('is_active', $item->is_active ?? true))
                           class="rounded border-paper-300 text-merah-600 focus:ring-merah-500">
                    <span>Status Barang Aktif</span>
                </label>
            </div>
        </div>

        <div class="mt-6 flex items-center justify-end gap-3 border-t border-paper-200 pt-4">
            <a href="{{ route('master.barang.index') }}"
               class="rounded-sm border border-paper-300 px-4 py-2 text-xs font-medium text-ink-700 hover:bg-paper-100">
                Batal
            </a>
            <button type="submit"
                    class="rounded-sm bg-merah-500 px-5 py-2 text-xs font-semibold text-paper-50 hover:bg-merah-600">
                {{ $mode === 'edit' ? 'Simpan Perubahan' : 'Simpan Barang' }}
            </button>
        </div>
    </form>
</x-layouts.app>


<x-layouts.app :title="$title" eyebrow="M0 Master Data">
    <div class="mb-5">
        <a href="{{ route('master.pihak.index') }}" class="inline-flex items-center gap-1 text-sm text-ink-600 hover:text-ink-900">
            &larr; Kembali ke Daftar Pihak / Mitra
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

    <form method="POST"
          action="{{ $mode === 'edit' ? route('master.pihak.update', $item->id_pihak) : route('master.pihak.store') }}"
          class="mx-auto max-w-3xl rounded-sm border border-paper-300 bg-paper-50 p-6 shadow-2xs"
          x-data="{
              jenisPihak: '{{ old('jenis_pihak', $item->jenis_pihak ?? 'warga') }}',
              isAnggota: {{ old('is_anggota', $item->is_anggota ?? false) ? 'true' : 'false' }}
          }">
        @csrf
        @if($mode === 'edit')
            @method('PUT')
        @endif

        <div class="mb-5 border-b border-paper-200 pb-3">
            <h2 class="font-display text-lg font-semibold text-ink-900">{{ $title }}</h2>
            <p class="text-xs text-ink-600">
                Entitas: <strong>{{ $entitas?->nama_entitas }}</strong> (Data pihak ini eksklusif untuk entitas Anda)
            </p>
        </div>

        <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
            <div>
                <label class="block text-xs font-medium text-ink-800 mb-1">Jenis Pihak <span class="text-merah-600">*</span></label>
                <select name="jenis_pihak" x-model="jenisPihak" required
                        class="w-full rounded-sm border border-paper-300 bg-white px-3 py-2 text-sm text-ink-900 focus:border-merah-400 focus:outline-none">
                    <option value="warga">Warga / Konsumen</option>
                    <option value="petani">Petani / Kelompok Tani (Poktan)</option>
                    <option value="supplier">Supplier / Vendor Eksternal</option>
                    <option value="entitas_lain">Entitas / Koperasi Desa Mitra (Konsinyasi)</option>
                </select>
            </div>

            <div x-show="jenisPihak === 'entitas_lain'">
                <label class="block text-xs font-medium text-ink-800 mb-1">Pilih Entitas Desa Mitra <span class="text-merah-600">*</span></label>
                <select name="id_entitas_mitra"
                        class="w-full rounded-sm border border-paper-300 bg-white px-3 py-2 text-sm text-ink-900 focus:border-merah-400 focus:outline-none">
                    <option value="">-- Pilih Koperasi / Entitas Mitra --</option>
                    @foreach($entitasMitraList as $mitra)
                        <option value="{{ $mitra->id_entitas }}"
                            @selected(old('id_entitas_mitra', $item->id_entitas_mitra) == $mitra->id_entitas)>
                            {{ $mitra->kode_entitas }} &mdash; {{ $mitra->nama_entitas }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="md:col-span-2">
                <label class="block text-xs font-medium text-ink-800 mb-1">Nama Lengkap / Nama Kelompok / Perusahaan <span class="text-merah-600">*</span></label>
                <input type="text" name="nama" value="{{ old('nama', $item->nama) }}" required maxlength="150"
                       placeholder="Contoh: Ibu Ani / Kelompok Tani Subur Jaya / CV Pangan Nusantara"
                       class="w-full rounded-sm border border-paper-300 bg-white px-3 py-2 text-sm text-ink-900 focus:border-merah-400 focus:outline-none">
            </div>

            <div>
                <label class="block text-xs font-medium text-ink-800 mb-1">NIK / NPWP (Opsional)</label>
                <input type="text" name="nik" value="{{ old('nik', $item->nik) }}" maxlength="25"
                       placeholder="16 digit NIK"
                       class="w-full rounded-sm border border-paper-300 bg-white px-3 py-2 font-mono text-sm text-ink-900 focus:border-merah-400 focus:outline-none">
            </div>

            <div>
                <label class="block text-xs font-medium text-ink-800 mb-1">No. HP / WhatsApp</label>
                <input type="text" name="no_hp" value="{{ old('no_hp', $item->no_hp) }}" maxlength="30"
                       placeholder="Contoh: 0812-3456-7890"
                       class="w-full rounded-sm border border-paper-300 bg-white px-3 py-2 text-sm text-ink-900 focus:border-merah-400 focus:outline-none">
            </div>

            <div>
                <label class="block text-xs font-medium text-ink-800 mb-1">Wilayah / Desa</label>
                <select name="id_wilayah"
                        class="w-full rounded-sm border border-paper-300 bg-white px-3 py-2 text-sm text-ink-900 focus:border-merah-400 focus:outline-none">
                    <option value="">-- Pilih Wilayah --</option>
                    @foreach($wilayahList as $w)
                        <option value="{{ $w->id }}" @selected(old('id_wilayah', $item->id_wilayah) == $w->id)>
                            {{ $w->nama }} ({{ strtoupper($w->tingkat) }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-medium text-ink-800 mb-1">Alamat Lengkap</label>
                <input type="text" name="alamat" value="{{ old('alamat', $item->alamat) }}" maxlength="500"
                       placeholder="RT/RW, Dusun, Desa"
                       class="w-full rounded-sm border border-paper-300 bg-white px-3 py-2 text-sm text-ink-900 focus:border-merah-400 focus:outline-none">
            </div>

            {{-- Pengaturan Keanggotaan Koperasi --}}
            <div class="md:col-span-2 rounded-sm border border-paper-200 bg-paper-100/60 p-4 space-y-4">
                <label class="inline-flex items-center gap-2 text-sm font-medium text-ink-900">
                    <input type="checkbox" name="is_anggota" value="1" x-model="isAnggota"
                           class="rounded border-paper-300 text-merah-600 focus:ring-merah-500">
                    <span>Terdaftar sebagai Anggota Koperasi (Berhak atas Simpanan &amp; SHU Anggota)</span>
                </label>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2" x-show="isAnggota">
                    <div>
                        <label class="block text-xs font-medium text-ink-800 mb-1">Nomor Anggota</label>
                        <input type="text" name="no_anggota" value="{{ old('no_anggota', $item->no_anggota) }}" maxlength="50"
                               placeholder="Contoh: ANG-2026-001"
                               class="w-full rounded-sm border border-paper-300 bg-white px-3 py-2 font-mono text-sm text-ink-900 focus:border-merah-400 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-ink-800 mb-1">Tanggal Bergabung Anggota</label>
                        <input type="date" name="tgl_jadi_anggota"
                               value="{{ old('tgl_jadi_anggota', $item->tgl_jadi_anggota?->format('Y-m-d') ?? date('Y-m-d')) }}"
                               class="w-full rounded-sm border border-paper-300 bg-white px-3 py-2 text-sm text-ink-900 focus:border-merah-400 focus:outline-none">
                    </div>
                </div>
            </div>

            {{-- Parameter Evaluasi Supplier / Petani (Opsional untuk M3) --}}
            <div class="md:col-span-2 grid grid-cols-1 gap-4 sm:grid-cols-2 rounded-sm border border-paper-200 bg-white p-4"
                 x-show="jenisPihak === 'petani' || jenisPihak === 'supplier'">
                <div>
                    <label class="block text-xs font-medium text-ink-800 mb-1">Rating Kualitas Pasokan (0.0 – 5.0)</label>
                    <input type="number" step="0.1" min="0" max="5" name="kualitas_rating"
                           value="{{ old('kualitas_rating', $item->kualitas_rating ?? '4.5') }}"
                           class="w-full rounded-sm border border-paper-300 bg-white px-3 py-2 font-mono text-sm text-ink-900 focus:border-merah-400 focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-medium text-ink-800 mb-1">Estimasi Waktu Pengiriman</label>
                    <input type="text" name="estimasi_pengiriman"
                           value="{{ old('estimasi_pengiriman', $item->estimasi_pengiriman ?? '1 hari') }}"
                           placeholder="Contoh: 1 hari / 1-2 hari"
                           class="w-full rounded-sm border border-paper-300 bg-white px-3 py-2 text-sm text-ink-900 focus:border-merah-400 focus:outline-none">
                </div>
            </div>

            <div class="md:col-span-2">
                <label class="inline-flex items-center gap-2 text-sm text-ink-800">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1"
                           @checked(old('is_active', $item->is_active ?? true))
                           class="rounded border-paper-300 text-merah-600 focus:ring-merah-500">
                    <span>Status Pihak Aktif</span>
                </label>
            </div>
        </div>

        <div class="mt-6 flex items-center justify-end gap-3 border-t border-paper-200 pt-4">
            <a href="{{ route('master.pihak.index') }}"
               class="rounded-sm border border-paper-300 px-4 py-2 text-xs font-medium text-ink-700 hover:bg-paper-100">
                Batal
            </a>
            <button type="submit"
                    class="rounded-sm bg-merah-500 px-5 py-2 text-xs font-semibold text-paper-50 hover:bg-merah-600">
                {{ $mode === 'edit' ? 'Simpan Perubahan' : 'Simpan Pihak' }}
            </button>
        </div>
    </form>
</x-layouts.app>


<x-layouts.app :title="$title" eyebrow="M0 Master Data (Super Admin)">
    <div class="mb-5">
        <a href="{{ route('master.entitas.index') }}" class="inline-flex items-center gap-1 text-sm text-ink-600 hover:text-ink-900">
            &larr; Kembali ke Daftar Entitas
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
          action="{{ $mode === 'edit' ? route('master.entitas.update', $item->id_entitas) : route('master.entitas.store') }}"
          class="mx-auto max-w-2xl rounded-sm border border-paper-300 bg-paper-50 p-6 shadow-2xs">
        @csrf
        @if($mode === 'edit')
            @method('PUT')
        @endif

        <div class="mb-5 border-b border-paper-200 pb-3">
            <h2 class="font-display text-lg font-semibold text-ink-900">{{ $title }}</h2>
            <p class="text-xs text-ink-600">Setiap entitas memiliki isolasi data master dan pembukuan tersendiri.</p>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <label class="block text-xs font-medium text-ink-800 mb-1">Kode Entitas <span class="text-merah-600">*</span></label>
                <input type="text" name="kode_entitas" value="{{ old('kode_entitas', $item->kode_entitas) }}" required maxlength="30"
                       placeholder="Contoh: KDMP-CIBIRU"
                       class="w-full rounded-sm border border-paper-300 bg-white px-3 py-2 font-mono text-sm uppercase text-ink-900">
            </div>

            <div>
                <label class="block text-xs font-medium text-ink-800 mb-1">Jenis Organisasi <span class="text-merah-600">*</span></label>
                <select name="jenis_entitas" required
                        class="w-full rounded-sm border border-paper-300 bg-white px-3 py-2 text-sm text-ink-900">
                    @foreach($jenisList as $key => $label)
                        <option value="{{ $key }}" @selected(old('jenis_entitas', $item->jenis_entitas ?? 'koperasi') === $key)>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="sm:col-span-2">
                <label class="block text-xs font-medium text-ink-800 mb-1">Nama Lengkap Entitas / Koperasi <span class="text-merah-600">*</span></label>
                <input type="text" name="nama_entitas" value="{{ old('nama_entitas', $item->nama_entitas) }}" required maxlength="150"
                       placeholder="Contoh: Koperasi Desa Merah Putih Tani Makmur"
                       class="w-full rounded-sm border border-paper-300 bg-white px-3 py-2 text-sm text-ink-900">
            </div>

            <div>
                <label class="block text-xs font-medium text-ink-800 mb-1">Pilih Wilayah Desa Terdaftar</label>
                <select name="id_wilayah"
                        class="w-full rounded-sm border border-paper-300 bg-white px-3 py-2 text-sm text-ink-900">
                    <option value="">-- Pilih Wilayah --</option>
                    @foreach($wilayahList as $w)
                        <option value="{{ $w->id }}" @selected(old('id_wilayah', $item->id_wilayah) == $w->id)>
                            {{ $w->nama }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-medium text-ink-800 mb-1">Atau Ketik Nama Desa Baru</label>
                <input type="text" name="nama_wilayah_baru" value="{{ old('nama_wilayah_baru') }}" maxlength="150"
                       placeholder="Contoh: Desa Cibiru"
                       class="w-full rounded-sm border border-paper-300 bg-white px-3 py-2 text-sm text-ink-900">
            </div>

            <div>
                <label class="block text-xs font-medium text-ink-800 mb-1">Nomor Badan Hukum / SK</label>
                <input type="text" name="badan_hukum_no" value="{{ old('badan_hukum_no', $item->badan_hukum_no) }}" maxlength="100"
                       class="w-full rounded-sm border border-paper-300 bg-white px-3 py-2 font-mono text-sm text-ink-900">
            </div>

            <div>
                <label class="block text-xs font-medium text-ink-800 mb-1">Tanggal Berdiri</label>
                <input type="date" name="tgl_berdiri" value="{{ old('tgl_berdiri', $item->tgl_berdiri?->format('Y-m-d')) }}"
                       class="w-full rounded-sm border border-paper-300 bg-white px-3 py-2 text-sm text-ink-900">
            </div>

            <div>
                <label class="block text-xs font-medium text-ink-800 mb-1">Tahun Buku Awal <span class="text-merah-600">*</span></label>
                <input type="number" name="tahun_buku_awal" value="{{ old('tahun_buku_awal', $item->tahun_buku_awal ?? date('Y')) }}" required min="2020" max="2100"
                       class="w-full rounded-sm border border-paper-300 bg-white px-3 py-2 font-mono text-sm text-ink-900">
            </div>

            <div>
                <label class="block text-xs font-medium text-ink-800 mb-1">Status Verifikasi <span class="text-merah-600">*</span></label>
                <select name="status" required
                        class="w-full rounded-sm border border-paper-300 bg-white px-3 py-2 font-mono text-sm text-ink-900">
                    <option value="APPROVED" @selected(old('status', $item->status) === 'APPROVED')>APPROVED</option>
                    <option value="PENDING" @selected(old('status', $item->status) === 'PENDING')>PENDING</option>
                    <option value="REJECTED" @selected(old('status', $item->status) === 'REJECTED')>REJECTED</option>
                </select>
            </div>

            <div class="sm:col-span-2 pt-2">
                <label class="inline-flex items-center gap-2 text-sm text-ink-800">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1"
                           @checked(old('is_active', $item->is_active ?? true))
                           class="rounded border-paper-300 text-merah-600 focus:ring-merah-500">
                    <span>Entitas Aktif</span>
                </label>
            </div>
        </div>

        <div class="mt-6 flex items-center justify-end gap-3 border-t border-paper-200 pt-4">
            <a href="{{ route('master.entitas.index') }}"
               class="rounded-sm border border-paper-300 px-4 py-2 text-xs font-medium text-ink-700 hover:bg-paper-100">
                Batal
            </a>
            <button type="submit"
                    class="rounded-sm bg-merah-500 px-5 py-2 text-xs font-semibold text-paper-50 hover:bg-merah-600">
                {{ $mode === 'edit' ? 'Simpan Perubahan' : 'Simpan Entitas' }}
            </button>
        </div>
    </form>
</x-layouts.app>


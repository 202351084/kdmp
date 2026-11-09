<x-layouts.app :title="$title" eyebrow="M0 Master Data">
    <div class="mb-5">
        <a href="{{ route('master.komoditas.index') }}" class="inline-flex items-center gap-1 text-sm text-ink-600 hover:text-ink-900">
            &larr; Kembali ke Daftar Komoditas
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
          action="{{ $mode === 'edit' ? route('master.komoditas.update', $item->id) : route('master.komoditas.store') }}"
          class="mx-auto max-w-2xl rounded-sm border border-paper-300 bg-paper-50 p-6 shadow-2xs">
        @csrf
        @if($mode === 'edit')
            @method('PUT')
        @endif

        <div class="mb-5 border-b border-paper-200 pb-3">
            <h2 class="font-display text-lg font-semibold text-ink-900">{{ $title }}</h2>
            <p class="text-xs text-ink-600">
                Entitas: <strong>{{ $entitas?->nama_entitas }}</strong> (Komoditas yang didaftarkan terpisah untuk entitas ini)
            </p>
        </div>

        <div class="space-y-4">
            <div>
                <label class="block text-xs font-medium text-ink-800 mb-1">Nama Komoditas <span class="text-merah-600">*</span></label>
                <input type="text" name="nama" value="{{ old('nama', $item->nama) }}" required maxlength="100"
                       placeholder="Contoh: Telur Ayam / Cabai Merah / Beras"
                       class="w-full rounded-sm border border-paper-300 bg-white px-3 py-2 text-sm text-ink-900 focus:border-merah-400 focus:outline-none">
            </div>

            <div>
                <label class="block text-xs font-medium text-ink-800 mb-1">Kategori Komoditas <span class="text-merah-600">*</span></label>
                <select name="kategori" required
                        class="w-full rounded-sm border border-paper-300 bg-white px-3 py-2 text-sm text-ink-900 focus:border-merah-400 focus:outline-none">
                    @foreach($kategoriList as $key => $label)
                        <option value="{{ $key }}" @selected(old('kategori', $item->kategori) === $key)>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-medium text-ink-800 mb-1">Alias / Sinonim Pencocokan Survei (Pisahkan dengan koma)</label>
                <input type="text" name="alias_input"
                       value="{{ old('alias_input', is_array($item->alias_json) ? implode(', ', $item->alias_json) : '') }}"
                       placeholder="Contoh: cabe merah, lombok merah, cabai keriting"
                       class="w-full rounded-sm border border-paper-300 bg-white px-3 py-2 text-sm text-ink-900 focus:border-merah-400 focus:outline-none">
                <p class="mt-1 text-[11px] text-ink-500">
                    Membantu jembatan sinkronisasi M1 &rarr; M2 mengenali variasi penulisan nama komoditas saat survei lapangan.
                </p>
            </div>

            <div class="pt-2">
                <label class="inline-flex items-center gap-2 text-sm text-ink-800">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1"
                           @checked(old('is_active', $item->is_active ?? true))
                           class="rounded border-paper-300 text-merah-600 focus:ring-merah-500">
                    <span>Komoditas Aktif (Dihitung dalam Kalkulasi Neraca Komoditas M2)</span>
                </label>
            </div>
        </div>

        <div class="mt-6 flex items-center justify-end gap-3 border-t border-paper-200 pt-4">
            <a href="{{ route('master.komoditas.index') }}"
               class="rounded-sm border border-paper-300 px-4 py-2 text-xs font-medium text-ink-700 hover:bg-paper-100">
                Batal
            </a>
            <button type="submit"
                    class="rounded-sm bg-merah-500 px-5 py-2 text-xs font-semibold text-paper-50 hover:bg-merah-600">
                {{ $mode === 'edit' ? 'Simpan Perubahan' : 'Simpan Komoditas' }}
            </button>
        </div>
    </form>
</x-layouts.app>


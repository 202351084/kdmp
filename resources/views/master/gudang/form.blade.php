<x-layouts.app :title="$title" eyebrow="M0 Master Data">
    <div class="mb-5">
        <a href="{{ route('master.gudang.index') }}" class="inline-flex items-center gap-1 text-sm text-ink-600 hover:text-ink-900">
            &larr; Kembali ke Daftar Gudang
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
          action="{{ $mode === 'edit' ? route('master.gudang.update', $item->id_gudang) : route('master.gudang.store') }}"
          class="mx-auto max-w-xl rounded-sm border border-paper-300 bg-paper-50 p-6 shadow-2xs">
        @csrf
        @if($mode === 'edit')
            @method('PUT')
        @endif

        <div class="mb-5 border-b border-paper-200 pb-3">
            <h2 class="font-display text-lg font-semibold text-ink-900">{{ $title }}</h2>
            <p class="text-xs text-ink-600">
                Entitas: <strong>{{ $entitas?->nama_entitas }}</strong>
            </p>
        </div>

        <div class="space-y-4">
            <div>
                <label class="block text-xs font-medium text-ink-800 mb-1">Kode Gudang <span class="text-merah-600">*</span></label>
                <input type="text" name="kode_gudang" value="{{ old('kode_gudang', $item->kode_gudang ?? 'UTAMA') }}" required maxlength="30"
                       placeholder="Contoh: UTAMA / GDG-01"
                       class="w-full rounded-sm border border-paper-300 bg-white px-3 py-2 font-mono text-sm uppercase text-ink-900 focus:border-merah-400 focus:outline-none">
            </div>

            <div>
                <label class="block text-xs font-medium text-ink-800 mb-1">Nama Gudang <span class="text-merah-600">*</span></label>
                <input type="text" name="nama_gudang" value="{{ old('nama_gudang', $item->nama_gudang) }}" required maxlength="150"
                       placeholder="Contoh: Gudang Utama Koperasi Desa"
                       class="w-full rounded-sm border border-paper-300 bg-white px-3 py-2 text-sm text-ink-900 focus:border-merah-400 focus:outline-none">
            </div>

            <div>
                <label class="block text-xs font-medium text-ink-800 mb-1">Alamat / Lokasi Gudang</label>
                <input type="text" name="alamat" value="{{ old('alamat', $item->alamat) }}" maxlength="500"
                       placeholder="Contoh: Jl. Balai Desa No. 1"
                       class="w-full rounded-sm border border-paper-300 bg-white px-3 py-2 text-sm text-ink-900 focus:border-merah-400 focus:outline-none">
            </div>

            <div class="pt-2">
                <label class="inline-flex items-center gap-2 text-sm text-ink-800">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1"
                           @checked(old('is_active', $item->is_active ?? true))
                           class="rounded border-paper-300 text-merah-600 focus:ring-merah-500">
                    <span>Gudang Aktif</span>
                </label>
            </div>
        </div>

        <div class="mt-6 flex items-center justify-end gap-3 border-t border-paper-200 pt-4">
            <a href="{{ route('master.gudang.index') }}"
               class="rounded-sm border border-paper-300 px-4 py-2 text-xs font-medium text-ink-700 hover:bg-paper-100">
                Batal
            </a>
            <button type="submit"
                    class="rounded-sm bg-merah-500 px-5 py-2 text-xs font-semibold text-paper-50 hover:bg-merah-600">
                {{ $mode === 'edit' ? 'Simpan Perubahan' : 'Simpan Gudang' }}
            </button>
        </div>
    </form>
</x-layouts.app>


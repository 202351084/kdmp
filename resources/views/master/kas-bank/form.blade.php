<x-layouts.app :title="$title" eyebrow="M0 Master Data">
    <div class="mb-5">
        <a href="{{ route('master.kas-bank.index') }}" class="inline-flex items-center gap-1 text-sm text-ink-600 hover:text-ink-900">
            &larr; Kembali ke Daftar Kas &amp; Bank
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
          action="{{ $mode === 'edit' ? route('master.kas-bank.update', $item->id_kas_bank) : route('master.kas-bank.store') }}"
          class="mx-auto max-w-2xl rounded-sm border border-paper-300 bg-paper-50 p-6 shadow-2xs"
          x-data="{ jenis: '{{ old('jenis', $item->jenis ?? 'bank') }}' }">
        @csrf
        @if($mode === 'edit')
            @method('PUT')
        @endif

        <div class="mb-5 border-b border-paper-200 pb-3">
            <h2 class="font-display text-lg font-semibold text-ink-900">{{ $title }}</h2>
            <p class="text-xs text-ink-600">
                Entitas: <strong>{{ $entitas?->nama_entitas }}</strong> (Data Kas &amp; Bank ini terpisah dari entitas lain)
            </p>
        </div>

        <div class="space-y-4">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="block text-xs font-medium text-ink-800 mb-1">Jenis Akun <span class="text-merah-600">*</span></label>
                    <select name="jenis" x-model="jenis" required
                            class="w-full rounded-sm border border-paper-300 bg-white px-3 py-2 text-sm text-ink-900 focus:border-merah-400 focus:outline-none">
                        <option value="kas">Kas Tunai</option>
                        <option value="bank">Rekening Bank</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-medium text-ink-800 mb-1">Akun COA Terhubung <span class="text-merah-600">*</span></label>
                    <select name="kode_akun" required
                            class="w-full rounded-sm border border-paper-300 bg-white px-3 py-2 font-mono text-sm text-ink-900 focus:border-merah-400 focus:outline-none">
                        @foreach($akunKasBank as $akun)
                            <option value="{{ $akun->kode_anak }}"
                                @selected(old('kode_akun', $item->kode_akun) == $akun->kode_anak)>
                                {{ $akun->kode_anak }} &mdash; {{ $akun->nama_rekening }}
                            </option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-[11px] text-ink-500">
                        Pilih <code class="font-mono">1111</code> untuk Kas Tunai, atau <code class="font-mono">11121–11123</code> untuk Rekening Bank.
                    </p>
                </div>
            </div>

            <div>
                <label class="block text-xs font-medium text-ink-800 mb-1">Nama Kas / Bank <span class="text-merah-600">*</span></label>
                <input type="text" name="nama" value="{{ old('nama', $item->nama) }}" required maxlength="100"
                       placeholder="Contoh: Kas Tunai Koperasi / Bank BRI Unit Desa"
                       class="w-full rounded-sm border border-paper-300 bg-white px-3 py-2 text-sm text-ink-900 focus:border-merah-400 focus:outline-none">
                <p class="mt-1 text-[11px] text-ink-500" x-show="jenis === 'bank'">
                    Nama bank yang Anda simpan di sini otomatis tampil pada Chart of Accounts (COA) milik entitas Anda.
                </p>
            </div>

            <div x-show="jenis === 'bank'">
                <label class="block text-xs font-medium text-ink-800 mb-1">Nomor Rekening Bank</label>
                <input type="text" name="no_rekening" value="{{ old('no_rekening', $item->no_rekening) }}" maxlength="50"
                       placeholder="Contoh: 4123-01-009281-53-8"
                       class="w-full rounded-sm border border-paper-300 bg-white px-3 py-2 font-mono text-sm text-ink-900 focus:border-merah-400 focus:outline-none">
            </div>

            <div class="flex flex-wrap items-center gap-6 pt-2">
                <label class="inline-flex items-center gap-2 text-sm text-ink-800">
                    <input type="checkbox" name="is_default" value="1"
                           @checked(old('is_default', $item->is_default))
                           class="rounded border-paper-300 text-merah-600 focus:ring-merah-500">
                    <span>Jadikan akun utama (default) untuk jenis ini</span>
                </label>

                <label class="inline-flex items-center gap-2 text-sm text-ink-800">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1"
                           @checked(old('is_active', $item->is_active ?? true))
                           class="rounded border-paper-300 text-merah-600 focus:ring-merah-500">
                    <span>Status Aktif</span>
                </label>
            </div>
        </div>

        <div class="mt-6 flex items-center justify-end gap-3 border-t border-paper-200 pt-4">
            <a href="{{ route('master.kas-bank.index') }}"
               class="rounded-sm border border-paper-300 px-4 py-2 text-xs font-medium text-ink-700 hover:bg-paper-100">
                Batal
            </a>
            <button type="submit"
                    class="rounded-sm bg-merah-500 px-5 py-2 text-xs font-semibold text-paper-50 hover:bg-merah-600">
                {{ $mode === 'edit' ? 'Simpan Perubahan' : 'Simpan Kas / Bank' }}
            </button>
        </div>
    </form>
</x-layouts.app>


<x-layouts.app title="Edit Master Data RT" eyebrow="Master Data RT">
    <div class="mb-5 flex items-center justify-between">
        <div>
            <h1 class="text-xl font-bold text-ink-900">Edit Master Data RT</h1>
            <p class="text-xs text-ink-500">Perubahan data RT ini akan berlaku secara otomatis sebagai baseline di semua periode survei desa.</p>
        </div>
        <a href="{{ route('survei.rt.index') }}" class="text-sm font-medium text-ink-500 hover:text-ink-900">Batal</a>
    </div>

    <div class="rounded-sm border border-paper-300 bg-white p-6 shadow-sm">
        <form action="{{ route('survei.rt.update', $rt->id) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="mb-6">
                <label class="mb-2 block text-sm font-semibold text-ink-700">Wilayah / Desa</label>
                <select name="id_wilayah" required class="w-full rounded-sm border-paper-300 shadow-sm focus:border-merah-500 focus:ring-merah-500" {{ $wilayahs->count() == 1 ? 'readonly style="pointer-events: none; background-color: #f3f4f6;"' : '' }}>
                    @if($wilayahs->count() > 1)
                        <option value="">-- Pilih Desa --</option>
                    @endif
                    @foreach ($wilayahs as $wilayah)
                        <option value="{{ $wilayah->id }}" {{ (old('id_wilayah', $rt->id_wilayah) == $wilayah->id || $wilayahs->count() == 1) ? 'selected' : '' }}>{{ $wilayah->nama }}</option>
                    @endforeach
                </select>
            </div>

            <div class="mb-6 grid grid-cols-1 gap-6 md:grid-cols-3">
                <div>
                    <label class="mb-2 block text-sm font-semibold text-ink-700">No RT</label>
                    <input type="text" name="nama_rt" required value="{{ old('nama_rt', $rt->nama_rt) }}" placeholder="Contoh: 01 atau RT 01" class="w-full rounded-sm border-paper-300 shadow-sm focus:border-merah-500 focus:ring-merah-500">
                    @error('nama_rt')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="mb-2 block text-sm font-semibold text-ink-700">No RW (Opsional)</label>
                    <input type="text" name="rw" value="{{ old('rw', $rt->rw) }}" placeholder="Contoh: 02" class="w-full rounded-sm border-paper-300 shadow-sm focus:border-merah-500 focus:ring-merah-500">
                </div>

                <div>
                    <label class="mb-2 block text-sm font-semibold text-ink-700">Dusun / Dukuh (Opsional)</label>
                    <input type="text" name="dusun" value="{{ old('dusun', $rt->dusun) }}" placeholder="Contoh: Dusun Krajan" class="w-full rounded-sm border-paper-300 shadow-sm focus:border-merah-500 focus:ring-merah-500">
                </div>
            </div>

            <div class="mb-6 grid grid-cols-1 gap-6 md:grid-cols-2">
                <div>
                    <label class="mb-2 block text-sm font-semibold text-ink-700">Nama Ketua RT (Opsional)</label>
                    <input type="text" name="nama_ketua_rt" value="{{ old('nama_ketua_rt', $rt->nama_ketua_rt) }}" placeholder="Contoh: Budi Santoso" class="w-full rounded-sm border-paper-300 shadow-sm focus:border-merah-500 focus:ring-merah-500">
                </div>

                <div>
                    <label class="mb-2 block text-sm font-semibold text-ink-700">No. HP / WhatsApp (Opsional)</label>
                    <input type="text" name="no_hp" value="{{ old('no_hp', $rt->no_hp) }}" placeholder="Contoh: 08123456789" class="w-full rounded-sm border-paper-300 shadow-sm focus:border-merah-500 focus:ring-merah-500">
                </div>
            </div>

            <div class="mb-6 grid grid-cols-1 gap-6 md:grid-cols-2">
                <div>
                    <label class="mb-2 block text-sm font-semibold text-ink-700">Total KK Baseline</label>
                    <input type="number" name="total_kk_baseline" min="0" value="{{ old('total_kk_baseline', $rt->total_kk_baseline ?? 0) }}" oninput="if(this.value !== '') this.value = Number(this.value)" class="w-full rounded-sm border-paper-300 shadow-sm focus:border-merah-500 focus:ring-merah-500">
                    <p class="mt-1 text-xs text-ink-500">Patokan resmi jumlah KK di RT ini.</p>
                </div>

                <div>
                    <label class="mb-2 block text-sm font-semibold text-ink-700">Total Jiwa Baseline</label>
                    <input type="number" name="total_jiwa_baseline" min="0" value="{{ old('total_jiwa_baseline', $rt->total_jiwa_baseline ?? 0) }}" oninput="if(this.value !== '') this.value = Number(this.value)" class="w-full rounded-sm border-paper-300 shadow-sm focus:border-merah-500 focus:ring-merah-500">
                    <p class="mt-1 text-xs text-ink-500">Patokan resmi jumlah Jiwa di RT ini.</p>
                </div>
            </div>

            <div class="mt-8 flex justify-end">
                <button type="submit" class="rounded-sm bg-blue-600 px-6 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-700">
                    Perbarui Master Data RT
                </button>
            </div>
        </form>
    </div>
</x-layouts.app>

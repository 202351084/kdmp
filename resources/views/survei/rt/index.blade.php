<x-layouts.app title="Data RT" eyebrow="Manajemen Survei">
    <div class="mb-5 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="text-sm text-ink-500">Kelola daftar RT untuk kebutuhan data baseline kependudukan desa.</p>
        </div>

        <div class="flex items-center gap-3">
            <a href="{{ route('survei.dashboard.index') }}"
               class="flex items-center gap-1.5 rounded-sm border border-paper-300 bg-white px-4 py-2 text-sm font-semibold text-ink-700 shadow-sm hover:bg-paper-100 whitespace-nowrap">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Kembali
            </a>
            <a href="{{ route('survei.rt.create') }}"
               class="flex items-center gap-1.5 rounded-sm bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-700 whitespace-nowrap">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Tambah RT Baru
            </a>
        </div>
    </div>

    <form action="{{ route('survei.rt.index') }}" method="GET" class="flex flex-wrap items-center gap-2 mb-6">
        <select name="id_sesi" onchange="this.form.submit()" class="rounded-sm border-paper-300 py-2 pl-3 pr-10 text-sm shadow-sm focus:border-merah-500 focus:outline-none focus:ring-1 focus:ring-merah-500 min-w-[200px]">
            <option value="">-- Semua Sesi/Periode --</option>
            @foreach($sesis as $sesi)
                <option value="{{ $sesi->id }}" {{ request('id_sesi') == $sesi->id ? 'selected' : '' }}>
                    Periode {{ $sesi->bulan }}/{{ $sesi->tahun }} - {{ $sesi->wilayah->nama ?? '' }}
                </option>
            @endforeach
        </select>

        <div class="relative flex-1 min-w-[200px]">
            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                <svg class="h-4 w-4 text-ink-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </div>
            <input type="text" name="search" value="{{ request('search') }}"
                   class="block w-full rounded-sm border-paper-300 py-2 pl-10 pr-3 text-sm placeholder-ink-400 shadow-sm focus:border-merah-500 focus:outline-none focus:ring-1 focus:ring-merah-500"
                   placeholder="Cari berdasarkan no RT, dusun, atau ketua RT...">
        </div>
        
        <button type="submit"
                class="rounded-sm bg-merah-500 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-merah-600">
            Cari
        </button>
        @if(request('search') || request('id_sesi'))
            <a href="{{ route('survei.rt.index') }}"
               class="rounded-sm border border-paper-300 bg-white px-4 py-2 text-sm font-semibold text-ink-700 shadow-sm hover:bg-paper-100">
                Reset
            </a>
        @endif
    </form>

    @if(isset($selectedSesi) && $selectedSesi && $selectedSesi->token_rt)
        <div class="mb-6 rounded-sm border border-blue-200 bg-blue-50 p-5 flex flex-col md:flex-row items-center justify-between gap-4">
            <div>
                <h2 class="font-display text-base font-semibold mb-1 text-blue-800">Tautan Survei RT (Periode {{ $selectedSesi->bulan }}/{{ $selectedSesi->tahun }})</h2>
                <p class="text-xs text-blue-700">Bagikan tautan ini khusus untuk pengurus RT di {{ $selectedSesi->wilayah->nama ?? 'Desa ini' }} untuk pengisian data baseline keluarga.</p>
            </div>
            <div class="flex gap-2 items-center w-full md:w-auto">
                <div class="flex-1 md:flex-none rounded-sm border border-blue-300 bg-white p-2.5 font-mono text-xs break-all text-ink-800 w-full md:w-64 truncate">
                    {{ url('/survei/isi/rt/' . $selectedSesi->token_rt) }}
                </div>
                <button type="button" onclick="copyAndShowModal('{{ url('/survei/isi/rt/' . $selectedSesi->token_rt) }}')" class="rounded-sm bg-blue-600 px-4 py-2.5 text-xs font-medium text-white hover:bg-blue-700 shadow-sm shrink-0 whitespace-nowrap">
                    Salin Tautan
                </button>
            </div>
        </div>
    @endif

    <!-- Data Summary Cards -->
    <div class="mb-6 grid grid-cols-2 gap-4">
        <div class="rounded-sm border border-paper-300 bg-white p-4 shadow-sm flex items-center gap-4">
            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-sawah-100 text-sawah-600">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
            </div>
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-ink-500">Total KK</p>
                <p class="font-display text-2xl font-bold text-ink-900">{{ number_format($totalKk ?? 0, 0, ',', '.') }}</p>
            </div>
        </div>
        <div class="rounded-sm border border-paper-300 bg-white p-4 shadow-sm flex items-center gap-4">
            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-merah-100 text-merah-600">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
            </div>
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-ink-500">Total Jiwa</p>
                <p class="font-display text-2xl font-bold text-ink-900">{{ number_format($totalJiwa ?? 0, 0, ',', '.') }}</p>
            </div>
        </div>
    </div>

    <div class="mt-8 overflow-hidden rounded-sm border border-paper-300 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-ink-500">
                <thead class="border-b border-paper-300 bg-paper-50 text-xs uppercase text-ink-700">
                    <tr>
                        <th class="px-6 py-4 font-medium">No</th>
                        <th class="px-6 py-4 font-medium">Desa / Wilayah</th>
                        <th class="px-6 py-4 font-medium">No RT / RW</th>
                        <th class="px-6 py-4 font-medium">Ketua RT</th>
                        <th class="px-6 py-4 font-medium text-right">KK</th>
                        <th class="px-6 py-4 font-medium text-right">Jiwa</th>
                        <th class="px-6 py-4 font-medium text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-paper-200">
                    @forelse ($rts as $index => $rt)
                        <tr class="hover:bg-paper-50">
                            <td class="px-6 py-4">{{ $rts->firstItem() + $index }}</td>
                            <td class="px-6 py-4">
                                <div class="font-medium text-ink-900">{{ $rt->wilayah->nama ?? '-' }}</div>
                                <div class="text-xs text-ink-500">{{ $rt->sesi ? $rt->sesi->bulan . '/' . $rt->sesi->tahun : '-' }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="font-medium text-ink-900">
                                    {{ $rt->nama_rt }} @if($rt->rw) / {{ $rt->rw }} @endif
                                </div>
                                @if($rt->dusun)
                                    <span class="text-xs text-ink-400">({{ $rt->dusun }})</span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                <div>{{ $rt->nama_ketua_rt ?? '-' }}</div>
                                @if($rt->no_hp)
                                    @php
                                        $wa = preg_replace('/[^0-9]/', '', $rt->no_hp);
                                        if (str_starts_with($wa, '0')) $wa = '62' . substr($wa, 1);
                                    @endphp
                                    <a href="https://wa.me/{{ $wa }}" target="_blank" class="text-xs text-sawah-600 hover:text-sawah-700 hover:underline flex items-center gap-1 mt-0.5">
                                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 24 24"><path d="M12.031 0C5.385 0 0 5.385 0 12.031c0 2.127.548 4.195 1.59 6.012L.15 23.36l5.485-1.439A11.968 11.968 0 0 0 12.031 24c6.647 0 12.031-5.385 12.031-12.031C24.062 5.385 18.678 0 12.031 0zm0 22.016c-1.802 0-3.565-.483-5.111-1.4l-.367-.217-3.799 1 .996-3.704-.239-.379A10.027 10.027 0 0 1 1.984 12.03c0-5.541 4.505-10.046 10.047-10.046 5.541 0 10.046 4.505 10.046 10.046s-4.505 10.046-10.046 10.046zm5.518-7.534c-.302-.151-1.785-.882-2.062-.983-.277-.101-.479-.151-.68.151-.202.302-.781.983-.957 1.184-.176.201-.353.226-.655.075-1.505-.753-2.615-1.583-3.606-3.32-.239-.42-.03-.647.121-.798.136-.136.302-.352.453-.528.151-.176.202-.301.302-.503.101-.201.05-.377-.025-.528-.076-.151-.68-1.642-.932-2.247-.245-.59-.494-.51-.68-.519-.176-.008-.378-.01-.58-.01-.201 0-.528.075-.805.377-.277.302-1.057 1.031-1.057 2.515 0 1.484 1.082 2.918 1.233 3.12.151.201 2.128 3.249 5.155 4.556.72.311 1.28.497 1.716.636.721.229 1.378.196 1.897.119.58-.087 1.785-.73 2.037-1.434.252-.704.252-1.308.176-1.434-.075-.126-.277-.201-.58-.352z"/></svg>
                                        {{ $rt->no_hp }}
                                    </a>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right">{{ $rt->total_kk_baseline }}</td>
                            <td class="px-6 py-4 text-right">{{ $rt->total_jiwa_baseline }}</td>
                            <td class="px-6 py-4 text-center">
                                <div class="flex justify-center gap-2">
                                    <a href="{{ route('survei.rt.edit', $rt->id) }}" class="rounded-sm bg-blue-50 px-2 py-1 text-xs font-semibold text-blue-600 hover:bg-blue-100">Edit</a>
                                    <form action="{{ route('survei.rt.destroy', $rt->id) }}" method="POST" class="inline-block" onsubmit="return confirm('Yakin ingin menghapus data ini?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="rounded-sm bg-merah-50 px-2 py-1 text-xs font-semibold text-merah-600 hover:bg-merah-100">Hapus</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-8 text-center text-ink-500">Belum ada data RT.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($rts->hasPages())
            <div class="border-t border-paper-300 bg-white px-6 py-4">
                {{ $rts->links() }}
            </div>
        @endif
    </div>

    <!-- Simple Success Modal -->
    <div id="successModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-ink-900/50 backdrop-blur-sm">
        <div class="w-full max-w-sm rounded-sm border border-paper-300 bg-paper-50 p-6 shadow-xl text-center">
            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-sawah-100 mb-4">
                <svg class="h-6 w-6 text-sawah-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
            </div>
            <h3 class="mb-2 font-display text-lg font-semibold text-ink-900">Berhasil Disalin!</h3>
            <p class="mb-6 text-sm text-ink-600">Tautan pengisian survei telah disalin ke clipboard Anda. Silakan bagikan ke Ketua RT yang bersangkutan.</p>
            <button type="button" onclick="closeSuccessModal()" class="w-full rounded-sm bg-merah-500 px-4 py-2 text-sm font-medium text-white hover:bg-merah-600">Tutup</button>
        </div>
    </div>

    <script>
        function copyAndShowModal(text) {
            navigator.clipboard.writeText(text).then(() => {
                document.getElementById('successModal').classList.remove('hidden');
                document.getElementById('successModal').classList.add('flex');
            });
        }
        function closeSuccessModal() {
            document.getElementById('successModal').classList.add('hidden');
            document.getElementById('successModal').classList.remove('flex');
        }
    </script>
</x-layouts.app>

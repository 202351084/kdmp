<x-layouts.app :title="$title" eyebrow="Survei — Sesi 1">

    {{-- ═══════════════════════════════════════════════════════════════════
         HEADER HALAMAN
    ════════════════════════════════════════════════════════════════════ --}}
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <a href="{{ route('survei.sesi.show', $sesi->id) }}"
                   class="text-xs font-medium text-ink-500 hover:text-ink-800 hover:underline">
                    ← Kembali ke Detail Sesi
                </a>
            </div>
            <h1 class="font-display text-2xl font-semibold text-ink-900">Sesi 1 — Data Demografi Desa</h1>
            <p class="mt-1 text-sm text-ink-500">Pendataan jumlah kepala keluarga dan penduduk berdasarkan kelompok umur dan gender.</p>
        </div>

        {{-- Badge Status Sesi 1 --}}
        @php
            $st = $sesi->status_sesi1 ?? 'belum_diisi';
            $stCls = match($st) {
                'sedang_diisi'  => 'bg-yellow-100 text-yellow-800 border-yellow-300',
                'selesai'       => 'bg-sawah-100 text-sawah-700 border-sawah-300',
                'terverifikasi' => 'bg-blue-100 text-blue-800 border-blue-300',
                default         => 'bg-ink-100 text-ink-600 border-ink-300',
            };
            $stLabel = $labelStatusSesi1[$st] ?? 'Belum Diisi';
        @endphp
        <span class="inline-flex items-center self-start rounded-full border px-3 py-1 text-xs font-semibold {{ $stCls }}">
            {{ $stLabel }}
        </span>
    </div>

    {{-- Flash Messages --}}
    @if(session('success'))
        <div class="mb-5 flex items-start gap-3 rounded-sm border border-sawah-300 bg-sawah-50 px-4 py-3 text-sm text-sawah-800">
            <svg class="mt-0.5 h-4 w-4 flex-shrink-0 text-sawah-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif
    @if(session('error'))
        <div class="mb-5 flex items-start gap-3 rounded-sm border border-merah-300 bg-merah-50 px-4 py-3 text-sm text-merah-800">
            <svg class="mt-0.5 h-4 w-4 flex-shrink-0 text-merah-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    {{-- ═══════════════════════════════════════════════════════════════════
         INFO SESI (Identitas Survei)
    ════════════════════════════════════════════════════════════════════ --}}
    @php
        $wilayah  = $sesi->wilayah;
        $kec      = $wilayah?->parent;
        $kab      = $kec?->parent;
    @endphp
    <div class="mb-6 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">
        @foreach([
            ['label' => 'Desa',         'nilai' => $wilayah?->nama ?? '-'],
            ['label' => 'Kecamatan',    'nilai' => $kec?->nama ?? '-'],
            ['label' => 'Kabupaten',    'nilai' => $kab?->nama ?? '-'],
            ['label' => 'Periode',      'nilai' => $sesi->tahun],
            ['label' => 'Kode Survei',  'nilai' => 'SRV-' . $sesi->tahun . '-' . str_pad($sesi->id, 3, '0', STR_PAD_LEFT)],
            ['label' => 'Status Sesi 1','nilai' => $stLabel],
        ] as $inf)
            <div class="rounded-sm border border-paper-300 bg-paper-50 px-3 py-2.5">
                <p class="text-[10px] font-medium uppercase tracking-wider text-ink-400">{{ $inf['label'] }}</p>
                <p class="mt-0.5 text-sm font-semibold text-ink-800 truncate" title="{{ $inf['nilai'] }}">{{ $inf['nilai'] }}</p>
            </div>
        @endforeach
    </div>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">

        {{-- ═══════════════════════════════════════════════════════════════
             KOLOM KIRI-TENGAH: Narasumber + Rekap
        ════════════════════════════════════════════════════════════════ --}}
        <div class="xl:col-span-2 space-y-6">

            {{-- ─── TABEL NARASUMBER ─────────────────────────────────────── --}}
            <div class="rounded-sm border border-paper-300 bg-paper-50">
                <div class="flex items-center justify-between border-b border-paper-200 px-5 py-4">
                    <div>
                        <h2 class="font-display text-base font-semibold text-ink-900">Daftar Narasumber</h2>
                    </div>
                    <div class="flex items-center gap-2">
                        <button type="button"
                                onclick="document.getElementById('modal-tambah-narasumber').classList.remove('hidden')"
                                id="btn-tambah-narasumber"
                                class="flex items-center gap-1.5 rounded-sm bg-merah-500 px-3 py-2 text-xs font-semibold text-white hover:bg-merah-600 shadow-sm whitespace-nowrap">
                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            Tambah Narasumber
                        </button>
                    </div>
                </div>

                @if($narasumbers->isEmpty())
                    <div class="px-5 py-10 text-center">
                        <svg class="mx-auto mb-3 h-10 w-10 text-ink-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                  d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0"/>
                        </svg>
                        <p class="text-sm font-medium text-ink-800">Belum ada narasumber untuk periode ini.</p>
                        <p class="text-xs text-ink-500 mt-1 mb-4">Silakan tambahkan narasumber secara manual.</p>
                        <div class="flex items-center justify-center gap-3">
                            <button type="button"
                                    onclick="document.getElementById('modal-tambah-narasumber').classList.remove('hidden')"
                                    class="inline-flex items-center gap-1.5 rounded-sm bg-merah-500 px-4 py-2 text-xs font-semibold text-white hover:bg-merah-600 shadow-sm transition-colors">
                                + Tambah Narasumber
                            </button>
                        </div>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="border-b border-paper-200 bg-paper-100">
                                <tr>
                                    <th class="px-4 py-2.5 text-left text-xs font-semibold uppercase tracking-wider text-ink-500 w-8">No</th>
                                    <th class="px-4 py-2.5 text-left text-xs font-semibold uppercase tracking-wider text-ink-500">Nama Narasumber</th>
                                    <th class="px-4 py-2.5 text-left text-xs font-semibold uppercase tracking-wider text-ink-500">Kategori</th>
                                    <th class="px-4 py-2.5 text-left text-xs font-semibold uppercase tracking-wider text-ink-500">Kontak</th>
                                    <th class="px-4 py-2.5 text-left text-xs font-semibold uppercase tracking-wider text-ink-500">Jml KK</th>
                                    <th class="px-4 py-2.5 text-left text-xs font-semibold uppercase tracking-wider text-ink-500">Status Data</th>
                                    <th class="px-4 py-2.5 text-right text-xs font-semibold uppercase tracking-wider text-ink-500">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-paper-200">
                                @foreach($narasumbers as $i => $ns)
                                    @php
                                        $nsSt = $ns->status;
                                        $nsStCls = match($nsSt) {
                                            'draft'         => 'bg-yellow-100 text-yellow-800',
                                            'lengkap'       => 'bg-sawah-100 text-sawah-700',
                                            'terverifikasi' => 'bg-blue-100 text-blue-800',
                                            default         => 'bg-ink-100 text-ink-500',
                                        };
                                        $nsStLabel = \App\Models\Survei\NarasumberSesi::LABEL_STATUS[$nsSt] ?? $nsSt;
                                        $nsKK = $kkPerNarasumber[$ns->id_narasumber] ?? 0;
                                    @endphp
                                    <tr class="hover:bg-paper-100 transition-colors">
                                        <td class="px-4 py-3 text-ink-400 text-xs">{{ $i + 1 }}</td>
                                        <td class="px-4 py-3">
                                            <span class="font-medium text-ink-900">{{ $ns->nama_narasumber }}</span>
                                            @if($ns->keterangan)
                                                <p class="text-xs text-ink-400 mt-0.5 truncate max-w-[180px]">{{ $ns->keterangan }}</p>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-ink-600 text-xs">{{ $ns->kategori }}</td>
                                        <td class="px-4 py-3 text-ink-500 text-xs font-mono">{{ $ns->nomor_kontak ?? '—' }}</td>
                                        <td class="px-4 py-3 text-ink-700 font-semibold text-sm tabular-nums">
                                            {{ $nsSt !== 'belum_diisi' ? number_format($nsKK) : '—' }}
                                        </td>
                                        <td class="px-4 py-3">
                                            <span class="inline-block rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $nsStCls }}">
                                                {{ $nsStLabel }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-right">
                                            <div class="flex items-center justify-end gap-2">
                                                <button type="button"
                                                        onclick="bukaModalEditNarasumber({{ json_encode($ns) }}, '{{ route('survei.sesi1.narasumber.update', [$sesi->id, $ns->id_narasumber]) }}')"
                                                        title="Edit Info Narasumber"
                                                        class="inline-flex items-center gap-1 rounded-sm border border-paper-300 px-2 py-1.5 text-xs font-medium text-ink-700 hover:border-merah-400 hover:text-merah-600 transition-colors">
                                                    <svg style="width:13px;height:13px" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                                                    </svg>
                                                    Edit Info
                                                </button>
                                                <a href="{{ route('survei.sesi1.narasumber.show', [$sesi->id, $ns->id_narasumber]) }}"
                                                   class="inline-flex items-center gap-1 rounded-sm border border-paper-300 px-3 py-1.5 text-xs font-medium text-ink-700 hover:border-merah-400 hover:text-merah-600 transition-colors">
                                                    @if($nsSt === 'belum_diisi') Isi Data @else Ubah Data @endif
                                                </a>
                                                <form method="POST"
                                                      action="{{ route('survei.sesi1.narasumber.destroy', [$sesi->id, $ns->id_narasumber]) }}"
                                                      onsubmit="return confirm('Hapus narasumber \"{{ addslashes($ns->nama_narasumber) }}\" beserta data demografinya?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit"
                                                            class="inline-flex items-center rounded-sm border border-merah-200 px-2 py-1.5 text-xs font-medium text-merah-600 hover:bg-merah-50 hover:border-merah-400 transition-colors">
                                                        <svg style="width:14px;height:14px;flex-shrink:0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                        </svg>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            {{-- ─── REKAP DEMOGRAFI DESA ─────────────────────────────────── --}}
            <div class="rounded-sm border border-paper-300 bg-paper-50">
                <div class="border-b border-paper-200 px-5 py-4">
                    <h2 class="font-display text-base font-semibold text-ink-900">Rekap Demografi Desa</h2>
                    <p class="mt-0.5 text-xs text-ink-500">
                        Rekapitulasi dari seluruh narasumber. Data berasal dari
                        <strong>{{ $narasumbers->whereNotIn('status', ['belum_diisi'])->count() }}</strong>
                        narasumber yang sudah mengisi.
                    </p>
                </div>

                @php
                    $totalLaki = collect($rekap)->sum('laki');
                    $totalPerempuan = collect($rekap)->sum('perempuan');
                    $totalPenduduk = $totalLaki + $totalPerempuan;
                @endphp

                @if($totalPenduduk === 0)
                    <div class="px-5 py-8 text-center text-sm text-ink-400">
                        Belum ada data demografi. Isi data dari setiap narasumber terlebih dahulu.
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="border-b border-paper-200 bg-paper-100">
                                <tr>
                                    <th class="px-4 py-2.5 text-left text-xs font-semibold uppercase tracking-wider text-ink-500">Kelompok Umur</th>
                                    <th class="px-4 py-2.5 text-right text-xs font-semibold uppercase tracking-wider text-ink-500">Laki-laki</th>
                                    <th class="px-4 py-2.5 text-right text-xs font-semibold uppercase tracking-wider text-ink-500">Perempuan</th>
                                    <th class="px-4 py-2.5 text-right text-xs font-semibold uppercase tracking-wider text-ink-500">Total</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-paper-200">
                                @foreach($kelompokUmur as $ku)
                                    @php $r = $rekap[$ku] @endphp
                                    <tr class="hover:bg-paper-100">
                                        <td class="px-4 py-3 font-medium text-ink-800">{{ $ku }}</td>
                                        <td class="px-4 py-3 text-right tabular-nums text-ink-700">{{ number_format($r['laki']) }}</td>
                                        <td class="px-4 py-3 text-right tabular-nums text-ink-700">{{ number_format($r['perempuan']) }}</td>
                                        <td class="px-4 py-3 text-right tabular-nums font-semibold text-ink-900">{{ number_format($r['total']) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="border-t-2 border-paper-300 bg-paper-100">
                                <tr>
                                    <td class="px-4 py-3 font-bold text-ink-900">TOTAL</td>
                                    <td class="px-4 py-3 text-right tabular-nums font-bold text-ink-900">{{ number_format($totalLaki) }}</td>
                                    <td class="px-4 py-3 text-right tabular-nums font-bold text-ink-900">{{ number_format($totalPerempuan) }}</td>
                                    <td class="px-4 py-3 text-right tabular-nums font-bold text-merah-600 text-base">{{ number_format($totalPenduduk) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    {{-- Total KK --}}
                    <div class="border-t border-paper-200 px-5 py-3 flex items-center justify-between">
                        <div>
                            <p class="text-xs text-ink-500">Total KK (dari narasumber)</p>
                            <p class="text-lg font-bold text-ink-900 tabular-nums">{{ number_format($totalKK) }} KK</p>
                        </div>
                    </div>
                @endif
            </div>

        </div>{{-- end kolom kiri-tengah --}}

        {{-- ═══════════════════════════════════════════════════════════════
             KOLOM KANAN: Summary Card + Verifikasi
        ════════════════════════════════════════════════════════════════ --}}
        <div class="space-y-4">

            {{-- Summary Cards --}}
            @php $nsLengkap = $narasumbers->whereIn('status', ['lengkap','terverifikasi'])->count(); @endphp
            @foreach([
                ['label' => 'Jumlah Narasumber',  'nilai' => $narasumbers->count(),  'icon' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0', 'cls' => 'text-ink-600'],
                ['label' => 'Data Lengkap',        'nilai' => $nsLengkap . ' / ' . $narasumbers->count(), 'icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0', 'cls' => 'text-sawah-600'],
                ['label' => 'Total Penduduk',      'nilai' => number_format($totalPenduduk), 'icon' => 'M16 7a4 4 0 11-8 0 4 4 0 018 0M12 14a7 7 0 00-7 7h14a7 7 0 00-7-7', 'cls' => 'text-merah-600'],
                ['label' => 'Total KK',            'nilai' => number_format($totalKK),       'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6', 'cls' => 'text-padi-600'],
            ] as $card)
                <div class="rounded-sm border border-paper-300 bg-paper-50 px-4 py-4 flex items-center gap-3">
                    <div class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-full bg-paper-200">
                        <svg class="h-4 w-4 {{ $card['cls'] }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $card['icon'] }}"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs text-ink-500">{{ $card['label'] }}</p>
                        <p class="text-xl font-bold tabular-nums text-ink-900">{{ $card['nilai'] }}</p>
                    </div>
                </div>
            @endforeach

            {{-- ─── PENYELESAIAN SESI 1 ─────────────────────────────────── --}}
            <div class="rounded-sm border border-paper-300 bg-paper-50">
                <div class="border-b border-paper-200 px-5 py-4">
                    <h2 class="font-display text-base font-semibold text-ink-900">Penyelesaian Sesi 1</h2>
                    <p class="mt-0.5 text-xs text-ink-500">Simpan status draft atau selesaikan sesi ini.</p>
                </div>
                <div class="px-5 py-5 space-y-4">

                    {{-- Form Simpan Draft --}}
                    <form method="POST" action="{{ route('survei.sesi1.draft', $sesi->id) }}">
                        @csrf
                        <button type="submit"
                                class="w-full rounded-sm border border-paper-300 bg-white px-4 py-2 text-sm font-medium text-ink-700 hover:border-merah-400 hover:text-merah-600 transition-colors">
                            Simpan Draft
                        </button>
                    </form>

                    <hr class="border-paper-200">

                    {{-- Selesaikan Sesi 1 --}}
                    <div>
                        <p class="text-xs text-ink-500 mb-3">
                            Setelah menekan <strong>Selesaikan Sesi 1</strong>, status sesi akan berubah menjadi
                            <strong>Selesai</strong>. Data masih dapat diubah oleh admin.
                        </p>

                        @if($narasumbers->where('status', 'belum_diisi')->count() > 0)
                            <div class="mb-3 rounded-sm border border-yellow-200 bg-yellow-50 px-3 py-2 text-xs text-yellow-800">
                                ⚠ Ada <strong>{{ $narasumbers->where('status', 'belum_diisi')->count() }}</strong>
                                narasumber yang belum mengisi data demografi.
                            </div>
                        @endif

                        <button type="button"
                                id="btn-selesaikan"
                                onclick="document.getElementById('modal-selesaikan').classList.remove('hidden')"
                                @if($narasumbers->isEmpty()) disabled @endif
                                class="w-full rounded-sm px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors
                                       {{ $narasumbers->isEmpty() ? 'bg-ink-300 cursor-not-allowed' : 'bg-merah-500 hover:bg-merah-600' }}">
                            Selesaikan Sesi 1
                        </button>

                        @if($sesi->selesai_sesi1_at)
                            <p class="mt-2 text-center text-xs text-ink-400">
                                Diselesaikan: {{ $sesi->selesai_sesi1_at->format('d M Y, H:i') }}
                            </p>
                        @endif
                    </div>
                </div>
            </div>

        </div>{{-- end kolom kanan --}}

    </div>{{-- end grid --}}

    {{-- ═══════════════════════════════════════════════════════════════════
         MODAL: Tambah Narasumber
    ════════════════════════════════════════════════════════════════════ --}}
    <div id="modal-tambah-narasumber"
         class="fixed inset-0 z-50 hidden items-center justify-center bg-ink-900/50 backdrop-blur-sm p-4">
        <div class="w-full max-w-md rounded-sm border border-paper-300 bg-paper-50 shadow-xl">
            <div class="flex items-center justify-between border-b border-paper-200 px-6 py-4">
                <h3 class="font-display text-lg font-semibold text-ink-900">Tambah Narasumber</h3>
                <button type="button"
                        onclick="document.getElementById('modal-tambah-narasumber').classList.add('hidden')"
                        class="text-ink-400 hover:text-ink-700">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <form method="POST" action="{{ route('survei.sesi1.narasumber.store', $sesi->id) }}"
                  class="px-6 py-5 space-y-4">
                @csrf

                <div>
                    <label for="nama_narasumber" class="mb-1 block text-sm font-medium text-ink-800">
                        Nama Narasumber <span class="text-merah-500">*</span>
                    </label>
                    <input type="text" id="nama_narasumber" name="nama_narasumber"
                           required maxlength="100"
                           placeholder="Contoh: Budi Santoso"
                           class="w-full rounded-sm border border-paper-300 bg-white px-3 py-2 text-sm focus:border-merah-400 focus:outline-none">
                </div>

                <div>
                    <label for="kategori_modal" class="mb-1 block text-sm font-medium text-ink-800">
                        Kategori Narasumber <span class="text-merah-500">*</span>
                    </label>
                    <select id="kategori_modal" name="kategori" required
                            class="w-full rounded-sm border border-paper-300 bg-white px-3 py-2 text-sm focus:border-merah-400 focus:outline-none">
                        <option value="">-- Pilih Kategori --</option>
                        @foreach($kategoriList as $kat)
                            <option value="{{ $kat }}">{{ $kat }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="nomor_kontak" class="mb-1 block text-sm font-medium text-ink-800">
                        Nomor Kontak
                        <span class="text-xs font-normal text-ink-400">(opsional)</span>
                    </label>
                    <input type="text" id="nomor_kontak" name="nomor_kontak"
                           maxlength="30"
                           placeholder="Contoh: 0812-3456-7890"
                           class="w-full rounded-sm border border-paper-300 bg-white px-3 py-2 text-sm focus:border-merah-400 focus:outline-none">
                </div>

                <div>
                    <label for="keterangan_modal" class="mb-1 block text-sm font-medium text-ink-800">
                        Keterangan
                        <span class="text-xs font-normal text-ink-400">(opsional)</span>
                    </label>
                    <input type="text" id="keterangan_modal" name="keterangan"
                           maxlength="500"
                           placeholder="Contoh: Ketua RT 01 Dusun Ngroto"
                           class="w-full rounded-sm border border-paper-300 bg-white px-3 py-2 text-sm focus:border-merah-400 focus:outline-none">
                </div>

                <div class="flex items-center justify-end gap-3 border-t border-paper-200 pt-4">
                    <button type="button"
                            onclick="document.getElementById('modal-tambah-narasumber').classList.add('hidden')"
                            class="text-sm font-medium text-ink-600 hover:text-ink-800 hover:underline">
                        Batal
                    </button>
                    <button type="submit"
                            class="rounded-sm bg-merah-500 px-5 py-2 text-sm font-semibold text-white hover:bg-merah-600 shadow-sm">
                        Simpan Narasumber
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════════════
         MODAL: Edit Info Narasumber
    ════════════════════════════════════════════════════════════════════ --}}
    <div id="modal-edit-narasumber"
         class="fixed inset-0 z-50 hidden items-center justify-center bg-ink-900/50 backdrop-blur-sm p-4">
        <div class="w-full max-w-md rounded-sm border border-paper-300 bg-paper-50 shadow-xl">
            <div class="flex items-center justify-between border-b border-paper-200 px-6 py-4">
                <h3 class="font-display text-lg font-semibold text-ink-900">Edit Info Narasumber</h3>
                <button type="button"
                        onclick="document.getElementById('modal-edit-narasumber').classList.add('hidden')"
                        class="text-ink-400 hover:text-ink-700">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <form id="form-edit-narasumber" method="POST" action="" class="px-6 py-5 space-y-4">
                @csrf
                @method('PUT')

                <div>
                    <label for="edit_nama_narasumber" class="mb-1 block text-sm font-medium text-ink-800">
                        Nama Narasumber <span class="text-merah-500">*</span>
                    </label>
                    <input type="text" id="edit_nama_narasumber" name="nama_narasumber"
                           required maxlength="100"
                           class="w-full rounded-sm border border-paper-300 bg-white px-3 py-2 text-sm focus:border-merah-400 focus:outline-none">
                </div>

                <div>
                    <label for="edit_kategori" class="mb-1 block text-sm font-medium text-ink-800">
                        Kategori Narasumber <span class="text-merah-500">*</span>
                    </label>
                    <select id="edit_kategori" name="kategori" required
                            class="w-full rounded-sm border border-paper-300 bg-white px-3 py-2 text-sm focus:border-merah-400 focus:outline-none">
                        @foreach($kategoriList as $kat)
                            <option value="{{ $kat }}">{{ $kat }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="edit_nomor_kontak" class="mb-1 block text-sm font-medium text-ink-800">
                        Nomor Kontak
                        <span class="text-xs font-normal text-ink-400">(opsional)</span>
                    </label>
                    <input type="text" id="edit_nomor_kontak" name="nomor_kontak"
                           maxlength="30"
                           class="w-full rounded-sm border border-paper-300 bg-white px-3 py-2 text-sm focus:border-merah-400 focus:outline-none">
                </div>

                <div>
                    <label for="edit_keterangan" class="mb-1 block text-sm font-medium text-ink-800">
                        Keterangan
                        <span class="text-xs font-normal text-ink-400">(opsional)</span>
                    </label>
                    <input type="text" id="edit_keterangan" name="keterangan"
                           maxlength="500"
                           class="w-full rounded-sm border border-paper-300 bg-white px-3 py-2 text-sm focus:border-merah-400 focus:outline-none">
                </div>

                <div class="flex items-center justify-end gap-3 border-t border-paper-200 pt-4">
                    <button type="button"
                            onclick="document.getElementById('modal-edit-narasumber').classList.add('hidden')"
                            class="text-sm font-medium text-ink-600 hover:text-ink-800 hover:underline">
                        Batal
                    </button>
                    <button type="submit"
                            class="rounded-sm bg-merah-500 px-5 py-2 text-sm font-semibold text-white hover:bg-merah-600 shadow-sm">
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════════════
         MODAL: Konfirmasi Selesaikan Sesi 1
    ════════════════════════════════════════════════════════════════════ --}}
    <div id="modal-selesaikan"
         class="fixed inset-0 z-50 hidden items-center justify-center bg-ink-900/50 backdrop-blur-sm p-4">
        <div class="w-full max-w-sm rounded-sm border border-paper-300 bg-paper-50 shadow-xl">
            <div class="px-6 py-6 text-center">
                <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full" style="background:#f0fdf4">
                    <svg style="width:24px;height:24px;color:#16a34a" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0"/>
                    </svg>
                </div>
                <h3 class="mb-2 font-display text-lg font-semibold text-ink-900">Selesaikan Sesi 1?</h3>
                <p class="mb-6 text-sm text-ink-600 leading-relaxed">
                    Sesi 1 Data Demografi Desa akan ditandai sebagai <strong>Selesai</strong>.
                    Data narasumber tetap dapat dilihat dan diubah oleh admin bila diperlukan.
                </p>

                <form method="POST" action="{{ route('survei.sesi1.selesaikan', $sesi->id) }}">
                    @csrf
                    <input type="hidden" name="konfirmasi" value="1">
                    <input type="hidden" name="jumlah_kk_verifikasi" value="{{ $sesi->jumlah_kk_verifikasi }}">

                    <div class="flex gap-3">
                        <button type="button"
                                onclick="document.getElementById('modal-selesaikan').classList.add('hidden')"
                                class="flex-1 rounded-sm border border-paper-300 bg-white px-4 py-2 text-sm font-medium text-ink-700 hover:bg-paper-100">
                            Batal
                        </button>
                        <button type="submit"
                                style="background:#16a34a"
                                class="flex-1 rounded-sm px-4 py-2 text-sm font-semibold text-white hover:opacity-90">
                            Ya, Selesaikan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function bukaModalEditNarasumber(ns, updateUrl) {
            document.getElementById('form-edit-narasumber').action = updateUrl;
            document.getElementById('edit_nama_narasumber').value = ns.nama_narasumber || '';
            document.getElementById('edit_kategori').value = ns.kategori || '';
            document.getElementById('edit_nomor_kontak').value = ns.nomor_kontak || '';
            document.getElementById('edit_keterangan').value = ns.keterangan || '';
            document.getElementById('modal-edit-narasumber').classList.remove('hidden');
        }

        // Tutup modal saat klik backdrop
        ['modal-tambah-narasumber', 'modal-edit-narasumber', 'modal-selesaikan'].forEach(function(id) {
            var el = document.getElementById(id);
            if (!el) return;
            el.addEventListener('click', function(e) {
                if (e.target === el) el.classList.add('hidden');
            });
            // Tampilkan sebagai flex saat visible
            var observer = new MutationObserver(function() {
                if (!el.classList.contains('hidden')) {
                    el.style.display = 'flex';
                } else {
                    el.style.display = '';
                }
            });
            observer.observe(el, { attributes: true, attributeFilter: ['class'] });
        });
    </script>

</x-layouts.app>

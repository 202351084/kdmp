<x-layouts.app :title="$title" eyebrow="M0 Master Data">
    @if (session('success'))
        <div class="mb-4 rounded-sm border border-sawah-500/40 bg-sawah-100 px-4 py-3 text-sm text-ink-900">
            {{ session('success') }}
        </div>
    @endif
    @if (session('error'))
        <div class="mb-4 rounded-sm border border-merah-300 bg-merah-50 px-4 py-3 text-sm text-merah-800">
            {{ session('error') }}
        </div>
    @endif

    {{-- Banner Informasi Template Baku COA vs Kustomisasi Nama Bank per Entitas --}}
    <div class="mb-5 flex flex-col justify-between gap-3 rounded-sm border border-paper-300 bg-paper-50 p-4 sm:flex-row sm:items-center">
        <div>
            <div class="flex items-center gap-2">
                <span class="rounded bg-padi-100 px-2 py-0.5 font-mono text-[11px] font-semibold text-padi-700">TEMPLATE BAKU KDMP</span>
                <span class="text-xs font-medium text-ink-700">
                    Entitas Aktif: <strong>{{ $entitas?->nama_entitas ?? 'Mode Global (Semua Entitas)' }}</strong>
                </span>
            </div>
            <p class="mt-1 text-xs text-ink-600">
                Struktur kode dan nama akun COA berlaku seragam di seluruh entitas. Khusus akun kelompok <strong>Bank (11121–11123)</strong>, setiap entitas dapat menyesuaikan <strong>Nama Bank &amp; No. Rekening</strong> masing-masing tanpa memengaruhi entitas lain.
            </p>
        </div>
        <a href="{{ route('master.kas-bank.index') }}"
           class="inline-flex shrink-0 items-center gap-1.5 rounded-sm border border-paper-300 bg-white px-3 py-2 text-xs font-medium text-ink-700 hover:border-merah-400 hover:text-merah-600">
            <span>Kelola Kas &amp; Bank Entitas &rarr;</span>
        </a>
    </div>

    <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <form method="GET" class="flex w-full gap-2 sm:max-w-md">
            <input name="q" value="{{ request('q') }}" placeholder="Cari kode atau nama rekening..."
                   class="min-w-0 flex-1 rounded-sm border border-paper-300 bg-paper-50 px-3 py-2 text-sm focus:border-merah-400 focus:outline-none">
            <button class="rounded-sm border border-paper-300 bg-white px-4 py-2 text-sm font-medium text-ink-700 hover:border-merah-400">
                Cari
            </button>
            @if(request('q'))
                <a href="{{ route('master.coa.index') }}" class="rounded-sm border border-paper-300 bg-paper-100 px-3 py-2 text-sm text-ink-600 hover:bg-paper-200">
                    Reset
                </a>
            @endif
        </form>
    </div>

    <div class="overflow-hidden rounded-sm border border-paper-300 bg-paper-50">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-paper-200/60 text-left font-mono text-[11px] uppercase tracking-wide text-ink-600/80">
                    <tr>
                        <th class="border-b border-paper-300 px-4 py-3 font-medium">Kode Rekening</th>
                        <th class="border-b border-paper-300 px-4 py-3 font-medium">Nama Rekening</th>
                        <th class="border-b border-paper-300 px-4 py-3 font-medium">Kelompok</th>
                        <th class="border-b border-paper-300 px-4 py-3 font-medium">Tipe</th>
                        <th class="border-b border-paper-300 px-4 py-3 font-medium">Saldo Normal</th>
                        <th class="border-b border-paper-300 px-4 py-3 text-right font-medium">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-paper-200">
                    @forelse($items as $item)
                        <tr class="hover:bg-paper-100/60 {{ $item->is_bank_editable ? 'bg-sawah-50/30' : '' }}">
                            <td class="px-4 py-3 font-mono text-ink-900 {{ $item->is_transaction === 'F' ? 'font-bold' : '' }}">
                                {{ $item->kode_anak }}
                            </td>
                            <td class="px-4 py-3 text-ink-900 {{ $item->is_transaction === 'F' ? 'font-bold' : '' }}"
                                style="padding-left: {{ ($item->level - 1) * 1.5 + 1 }}rem;">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span>{{ $item->nama_tampil }}</span>

                                    @if($item->is_bank_editable)
                                        @if($item->nama_tampil !== $item->nama_template)
                                            <span class="rounded bg-paper-200 px-1.5 py-0.5 font-mono text-[10px] font-normal text-ink-600">
                                                Template: {{ $item->nama_template }}
                                            </span>
                                        @endif
                                        @if($item->no_rekening_entitas)
                                            <span class="rounded bg-sawah-100 px-2 py-0.5 font-mono text-[11px] font-normal text-sawah-700">
                                                Rek: {{ $item->no_rekening_entitas }}
                                            </span>
                                        @endif
                                    @endif
                                </div>
                            </td>
                            <td class="px-4 py-3 text-ink-700">{{ $item->kelompok }}</td>
                            <td class="px-4 py-3">
                                @if($item->is_transaction === 'F')
                                    <span class="inline-flex items-center rounded-sm bg-paper-200 px-2 py-0.5 text-xs font-medium text-ink-800">
                                        Header
                                    </span>
                                @else
                                    <span class="inline-flex items-center rounded-sm bg-sawah-100 px-2 py-0.5 text-xs font-medium text-sawah-800">
                                        Transaksi
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if($item->posisi_normal === 'D')
                                    <span class="inline-flex items-center rounded-sm border border-merah-200 bg-merah-50 px-2 py-0.5 text-xs font-medium text-merah-700">
                                        Debet
                                    </span>
                                @elseif($item->posisi_normal === 'K')
                                    <span class="inline-flex items-center rounded-sm border border-amber-200 bg-amber-50 px-2 py-0.5 text-xs font-medium text-amber-700">
                                        Kredit
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">
                                @if($item->is_bank_editable)
                                    @if($idEntitas)
                                        <button type="button"
                                                onclick="openEditBankModal('{{ $item->kode_anak }}', '{{ addslashes($item->nama_template) }}', '{{ addslashes($item->nama_tampil) }}', '{{ addslashes((string) $item->no_rekening_entitas) }}')"
                                                class="inline-flex items-center gap-1 rounded-sm border border-merah-300 bg-white px-2.5 py-1 text-xs font-medium text-merah-600 hover:bg-merah-50">
                                            ✎ Ubah Nama Bank
                                        </button>
                                    @else
                                        <span class="text-[11px] text-ink-500">Pilih entitas untuk edit</span>
                                    @endif
                                @else
                                    <span class="font-mono text-[11px] text-ink-400">Terkunci (Baku)</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-ink-500">
                                @if(request('q'))
                                    Tidak ada rekening yang cocok dengan pencarian "{{ request('q') }}".
                                @else
                                    Belum ada data Chart of Accounts (COA).
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Modal Ubah Nama Bank per Entitas --}}
    @if($idEntitas)
        <div id="modal-edit-bank" class="fixed inset-0 z-50 hidden items-center justify-center bg-ink-950/70 p-4 backdrop-blur-xs">
            <div class="w-full max-w-md rounded-sm border border-paper-300 bg-paper-50 p-6 shadow-2xl">
                <div class="flex items-center justify-between border-b border-paper-200 pb-3">
                    <div>
                        <h3 class="font-display text-base font-semibold text-ink-900">Ubah Nama Akun Bank Entitas</h3>
                        <p class="text-xs text-ink-600">Entitas: <strong>{{ $entitas?->nama_entitas }}</strong></p>
                    </div>
                    <button type="button" onclick="closeEditBankModal()" class="text-ink-600 hover:text-ink-950">✕</button>
                </div>

                <form id="form-edit-bank" method="POST" class="mt-4 space-y-4">
                    @csrf
                    @method('PUT')

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-mono text-xs text-ink-700">Kode Akun COA</label>
                            <input type="text" id="bank-kode-akun" readonly
                                   class="mt-1 w-full rounded-sm border border-paper-300 bg-paper-200 px-3 py-2 font-mono text-xs font-bold text-ink-800">
                        </div>
                        <div>
                            <label class="block font-mono text-xs text-ink-700">Template Dasar</label>
                            <input type="text" id="bank-nama-template" readonly
                                   class="mt-1 w-full rounded-sm border border-paper-300 bg-paper-200 px-3 py-2 text-xs text-ink-700">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-ink-800">Nama Bank untuk Entitas Ini <span class="text-merah-600">*</span></label>
                        <input type="text" name="nama_bank" id="bank-nama-input" required maxlength="100"
                               placeholder="Contoh: Bank BRI Unit Desa / Bank BJB Syariah"
                               class="mt-1 w-full rounded-sm border border-paper-300 bg-white px-3 py-2 text-sm text-ink-900 focus:border-merah-400 focus:outline-none">
                        <p class="mt-1 text-[11px] text-ink-500">
                            Perubahan nama ini hanya berlaku untuk <strong>{{ $entitas?->nama_entitas }}</strong> dan tersinkron dengan menu Kas &amp; Bank.
                        </p>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-ink-800">Nomor Rekening (Opsional)</label>
                        <input type="text" name="no_rekening" id="bank-norek-input" maxlength="50"
                               placeholder="Contoh: 4123-01-002918-53-1"
                               class="mt-1 w-full rounded-sm border border-paper-300 bg-white px-3 py-2 font-mono text-sm text-ink-900 focus:border-merah-400 focus:outline-none">
                    </div>

                    <div class="flex items-center justify-end gap-2 border-t border-paper-200 pt-3">
                        <button type="button" onclick="closeEditBankModal()"
                                class="rounded-sm border border-paper-300 px-4 py-2 text-xs font-medium text-ink-700 hover:bg-paper-100">
                            Batal
                        </button>
                        <button type="submit"
                                class="rounded-sm bg-merah-500 px-5 py-2 text-xs font-semibold text-paper-50 hover:bg-merah-600">
                            Simpan Nama Bank
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <script>
            function openEditBankModal(kodeAkun, namaTemplate, namaTampil, noRekening) {
                const modal = document.getElementById('modal-edit-bank');
                const form = document.getElementById('form-edit-bank');
                form.action = "{{ url('master/coa') }}/" + encodeURIComponent(kodeAkun);
                document.getElementById('bank-kode-akun').value = kodeAkun;
                document.getElementById('bank-nama-template').value = namaTemplate;
                document.getElementById('bank-nama-input').value = namaTampil;
                document.getElementById('bank-norek-input').value = noRekening || '';
                modal.classList.remove('hidden');
                modal.classList.add('flex');
            }

            function closeEditBankModal() {
                const modal = document.getElementById('modal-edit-bank');
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            }
        </script>
    @endif
</x-layouts.app>

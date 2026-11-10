<x-layouts.app :title="$title" eyebrow="Manajemen Survei">
    @php
        $bulans = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];
        $namaBulan = $bulans[$item->bulan] ?? ('Bulan ' . $item->bulan);

        $badgeCls = function($status) {
            return match($status) {
                'terverifikasi' => 'bg-emerald-100 text-emerald-800 border-emerald-300',
                'selesai'       => 'bg-blue-100 text-blue-800 border-blue-300',
                'sedang_diisi'  => 'bg-amber-100 text-amber-800 border-amber-300',
                default         => 'bg-paper-200 text-ink-600 border-paper-300',
            };
        };
    @endphp

    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between min-w-0">
        <div class="min-w-0">
            <div class="flex items-center gap-2 text-xs text-ink-500 mb-1">
                <a href="{{ route($routeBase . '.index') }}" class="hover:underline">Daftar Sesi</a>
                <span>&rsaquo;</span>
                <span class="text-ink-800 font-medium">Detail Sesi</span>
            </div>
            <div class="flex items-center gap-3 flex-wrap">
                <h1 class="font-display text-2xl font-semibold text-ink-900">{{ $title }}</h1>
                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $item->status === 'DRAFT' ? 'bg-amber-100 text-amber-800 border border-amber-200' : 'bg-emerald-100 text-emerald-800 border border-emerald-200' }}">
                    {{ $item->status }}
                </span>
            </div>
            <p class="mt-1 text-sm text-ink-600 truncate">
                {{ $item->wilayah->nama ?? 'Wilayah Desa' }} &bull; Periode {{ $namaBulan }} {{ $item->tahun }}
            </p>
        </div>
        <div class="shrink-0 flex items-center gap-2">
            <a href="{{ route($routeBase . '.index') }}"
               class="inline-flex items-center gap-1.5 rounded-sm border border-paper-300 bg-white px-3.5 py-2 text-xs font-semibold text-ink-700 shadow-sm hover:bg-paper-100 transition-colors">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Kembali ke Daftar Sesi
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="mb-6 flex items-start gap-3 rounded-sm border border-sawah-300 bg-sawah-50 px-4 py-3 text-sm text-sawah-800 shadow-sm">
            <svg class="mt-0.5 h-4 w-4 shrink-0 text-sawah-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    {{-- Main Top Grid: Informasi Sesi & Tautan Publik --}}
    <div class="grid grid-cols-1 xl:grid-cols-12 gap-6 min-w-0 w-full mb-8">
        
        <!-- Informasi Sesi (Left Column) -->
        <div class="xl:col-span-5 min-w-0">
            <div class="rounded-sm border border-paper-300 bg-white p-6 shadow-sm min-w-0">
                <div class="flex items-center justify-between border-b border-paper-200 pb-3 mb-4">
                    <div class="flex items-center gap-2">
                        <span class="flex h-7 w-7 items-center justify-center rounded-md bg-paper-100 text-ink-700">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </span>
                        <h2 class="font-display text-base font-semibold text-ink-900">Informasi Sesi</h2>
                    </div>
                </div>
                
                <dl class="divide-y divide-paper-100 text-sm">
                    <div class="py-3 flex items-start justify-between gap-4">
                        <dt class="text-ink-500 font-medium shrink-0">Desa / Wilayah</dt>
                        <dd class="text-ink-900 font-semibold text-right break-words">{{ $item->wilayah->nama ?? '-' }}</dd>
                    </div>
                    <div class="py-3 flex items-start justify-between gap-4">
                        <dt class="text-ink-500 font-medium shrink-0">Bulan & Tahun</dt>
                        <dd class="text-ink-800 font-medium text-right">{{ $namaBulan }} {{ $item->tahun }}</dd>
                    </div>
                    <div class="py-3 flex items-start justify-between gap-4">
                        <dt class="text-ink-500 font-medium shrink-0">Tanggal Pelaksanaan</dt>
                        <dd class="text-ink-800 font-mono text-xs text-right">{{ $item->tanggal_survei ? $item->tanggal_survei->format('d M Y') : '-' }}</dd>
                    </div>
                    <div class="py-3 flex items-start justify-between gap-4">
                        <dt class="text-ink-500 font-medium shrink-0">Penanggung Jawab</dt>
                        <dd class="text-ink-800 font-medium text-right break-words">{{ $item->petugas->nama ?? 'Sistem' }}</dd>
                    </div>
                </dl>
            </div>
        </div>

        <!-- Pusat Distribusi Tautan Publik (Right Column) -->
        <div class="xl:col-span-7 min-w-0 flex flex-col gap-4">
            
            <!-- 1. Tautan RT -->
            <div class="rounded-sm border border-blue-200 bg-blue-50/70 p-5 shadow-sm min-w-0">
                <div class="flex items-start justify-between gap-2 mb-1.5">
                    <div class="min-w-0">
                        <div class="flex items-center gap-2">
                            <span class="inline-flex h-2 w-2 rounded-full bg-blue-600"></span>
                            <h3 class="font-display text-sm font-semibold text-blue-900 truncate">1. Tautan Survei RT (Data Baseline)</h3>
                        </div>
                        <p class="text-xs text-blue-700 mt-0.5">Khusus Ketua RT/Pengurus. Digunakan untuk input jumlah KK & jiwa di wilayahnya.</p>
                    </div>
                </div>

                <div class="mt-3 flex items-center gap-2 min-w-0 w-full">
                    <div class="relative min-w-0 flex-1">
                        <input type="text"
                               readonly
                               value="{{ url('/survei/isi/rt/' . $item->token_rt) }}"
                               class="w-full min-w-0 rounded-sm border border-blue-300 bg-white px-3 py-2 text-xs font-mono text-ink-800 truncate focus:outline-none focus:ring-1 focus:ring-blue-500 select-all"
                               title="{{ url('/survei/isi/rt/' . $item->token_rt) }}">
                    </div>
                    <button type="button"
                            onclick="copyAndRedirect('{{ url('/survei/isi/rt/' . $item->token_rt) }}', '{{ route('survei.rt.index') }}', 'Tautan Survei RT (Data Baseline)', 'Data RT', this)"
                            title="Salin Tautan & Buka Data RT"
                            aria-label="Salin Tautan & Buka Data RT"
                            class="shrink-0 inline-flex items-center justify-center rounded-sm bg-blue-600 p-2 text-white shadow-sm hover:bg-blue-700 active:scale-95 transition-all">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                    </button>
                    <a href="{{ url('/survei/isi/rt/' . $item->token_rt) }}"
                       target="_blank"
                       rel="noopener"
                       title="Buka Formulir di Tab Baru"
                       class="shrink-0 inline-flex items-center justify-center rounded-sm border border-blue-300 bg-white p-2 text-blue-700 hover:bg-blue-100 transition-colors">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                    </a>
                </div>
            </div>

            <!-- 2. Tautan Masyarakat -->
            <div class="rounded-sm border border-merah-200 bg-merah-50/70 p-5 shadow-sm min-w-0">
                <div class="flex items-start justify-between gap-2 mb-1.5">
                    <div class="min-w-0">
                        <div class="flex items-center gap-2">
                            <span class="inline-flex h-2 w-2 rounded-full bg-merah-600"></span>
                            <h3 class="font-display text-sm font-semibold text-merah-900 truncate">2. Tautan Survei Masyarakat (Keluarga)</h3>
                        </div>
                        <p class="text-xs text-merah-700 mt-0.5">Untuk warga umum. Formulir pendataan keluarga (biodata serta konsumsi pangan).</p>
                    </div>
                </div>

                <div class="mt-3 flex items-center gap-2 min-w-0 w-full">
                    <div class="relative min-w-0 flex-1">
                        <input type="text"
                               readonly
                               value="{{ url('/survei/isi/masyarakat/' . $item->token_masyarakat) }}"
                               class="w-full min-w-0 rounded-sm border border-merah-300 bg-white px-3 py-2 text-xs font-mono text-ink-800 truncate focus:outline-none focus:ring-1 focus:ring-merah-500 select-all"
                               title="{{ url('/survei/isi/masyarakat/' . $item->token_masyarakat) }}">
                    </div>
                    <button type="button"
                            onclick="copyAndRedirect('{{ url('/survei/isi/masyarakat/' . $item->token_masyarakat) }}', '{{ route('survei.validasi.index', ['bulan' => $item->bulan, 'tahun' => $item->tahun]) }}', 'Tautan Survei Masyarakat (Keluarga)', 'Validasi & Laporan Survei', this)"
                            title="Salin Tautan & Buka Validasi Survei"
                            aria-label="Salin Tautan & Buka Validasi Survei"
                            class="shrink-0 inline-flex items-center justify-center rounded-sm bg-merah-500 p-2 text-white shadow-sm hover:bg-merah-600 active:scale-95 transition-all">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                    </button>
                    <a href="{{ url('/survei/isi/masyarakat/' . $item->token_masyarakat) }}"
                       target="_blank"
                       rel="noopener"
                       title="Buka Formulir di Tab Baru"
                       class="shrink-0 inline-flex items-center justify-center rounded-sm border border-merah-300 bg-white p-2 text-merah-700 hover:bg-merah-100 transition-colors">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                    </a>
                </div>
            </div>

            <!-- 3. Tautan Produsen -->
            <div class="rounded-sm border border-emerald-200 bg-emerald-50/70 p-5 shadow-sm min-w-0">
                <div class="flex items-start justify-between gap-2 mb-1.5">
                    <div class="min-w-0">
                        <div class="flex items-center gap-2">
                            <span class="inline-flex h-2 w-2 rounded-full bg-emerald-600"></span>
                            <h3 class="font-display text-sm font-semibold text-emerald-900 truncate">3. Tautan Survei Produsen (Tani & Ekraf)</h3>
                        </div>
                        <p class="text-xs text-emerald-700 mt-0.5">Khusus kelompok tani & pelaku ekonomi kreatif. Pendataan komoditas & hasil panen.</p>
                    </div>
                </div>

                <div class="mt-3 flex items-center gap-2 min-w-0 w-full">
                    <div class="relative min-w-0 flex-1">
                        <input type="text"
                               readonly
                               value="{{ url('/survei/isi/produsen/' . $item->token_produsen) }}"
                               class="w-full min-w-0 rounded-sm border border-emerald-300 bg-white px-3 py-2 text-xs font-mono text-ink-800 truncate focus:outline-none focus:ring-1 focus:ring-emerald-500 select-all"
                               title="{{ url('/survei/isi/produsen/' . $item->token_produsen) }}">
                    </div>
                    <button type="button"
                            onclick="copyAndRedirect('{{ url('/survei/isi/produsen/' . $item->token_produsen) }}', '{{ route('survei.produsen.index', ['sesi_id' => $item->id]) }}', 'Tautan Survei Produsen (Tani & Ekraf)', 'Produsen Desa', this)"
                            title="Salin Tautan & Buka Produsen Desa"
                            aria-label="Salin Tautan & Buka Produsen Desa"
                            class="shrink-0 inline-flex items-center justify-center rounded-sm bg-emerald-600 p-2 text-white shadow-sm hover:bg-emerald-700 active:scale-95 transition-all">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                    </button>
                    <a href="{{ url('/survei/isi/produsen/' . $item->token_produsen) }}"
                       target="_blank"
                       rel="noopener"
                       title="Buka Formulir di Tab Baru"
                       class="shrink-0 inline-flex items-center justify-center rounded-sm border border-emerald-300 bg-white p-2 text-emerald-700 hover:bg-emerald-100 transition-colors">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                    </a>
                </div>
            </div>

        </div>
    </div>

    <!-- Modal Pop-up Notifikasi Salin & Auto Pengalihan (5 Detik) -->
    <div id="copyRedirectModal" class="fixed inset-0 z-50 flex items-center justify-center bg-ink-950/40 backdrop-blur-[2px] hidden transition-opacity duration-200 opacity-0" role="dialog" aria-modal="true">
        <div class="relative bg-white rounded-md border border-paper-300 shadow-2xl p-6 max-w-sm w-full mx-4 text-center transform scale-95 transition-all duration-200" id="copyModalBox">
            <button type="button" onclick="cancelRedirect()" class="absolute top-3 right-3 text-ink-400 hover:text-ink-700 p-1 rounded-sm transition-colors" title="Tutup & Tetap di Sini">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>

            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-emerald-100 text-emerald-600 mb-3 shadow-inner">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                </svg>
            </div>
            <h3 class="text-base font-display font-semibold text-ink-900" id="copyModalTitle">Link Telah Disalin!</h3>
            <p class="text-xs text-ink-600 mt-2 leading-relaxed" id="copyModalDesc">
                Tautan berhasil disalin ke clipboard.
            </p>

            <div class="mt-4 overflow-hidden rounded-sm border border-emerald-200 bg-emerald-50">
                <div class="flex items-center justify-center gap-2 py-2.5 px-3.5 text-xs font-medium text-emerald-800">
                    <svg class="animate-spin h-3.5 w-3.5 text-emerald-600 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                    </svg>
                    <span>Mengalihkan ke <strong id="copyModalTargetPage">...</strong> (<span id="countdownSec" class="font-mono text-emerald-900 font-bold">5</span>s)</span>
                </div>
                <!-- Progress Bar 5 Detik -->
                <div class="h-1 w-full bg-emerald-200 overflow-hidden">
                    <div id="copyProgressBar" class="h-full bg-emerald-600 w-full"></div>
                </div>
            </div>

            <div class="mt-4 flex items-center justify-center gap-2">
                <button type="button" id="btnBukaSekarang" class="flex-1 rounded-sm bg-emerald-600 px-3 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-emerald-700 transition-colors">
                    Buka Sekarang &rarr;
                </button>
                <button type="button" onclick="cancelRedirect()" class="rounded-sm border border-paper-300 bg-white px-3 py-1.5 text-xs font-medium text-ink-600 hover:bg-paper-100 transition-colors">
                    Batal
                </button>
            </div>
        </div>
    </div>

    <!-- Notification Toast Container (Fallback / Standby) -->
    <div id="toastContainer" class="fixed bottom-6 right-6 z-50 flex flex-col gap-2 pointer-events-none"></div>

    <script>
        let redirectTimeout = null;
        let countdownInterval = null;
        let hideModalTimeout = null;
        let activeCopyBtn = null;
        let activeCopyBtnOriginalHtml = null;

        function clearAllTimers() {
            if (redirectTimeout) {
                clearTimeout(redirectTimeout);
                redirectTimeout = null;
            }
            if (countdownInterval) {
                clearInterval(countdownInterval);
                countdownInterval = null;
            }
            if (hideModalTimeout) {
                clearTimeout(hideModalTimeout);
                hideModalTimeout = null;
            }
        }

        function restoreButtonState() {
            if (activeCopyBtn && activeCopyBtnOriginalHtml) {
                activeCopyBtn.innerHTML = activeCopyBtnOriginalHtml;
                activeCopyBtn.classList.remove('ring-2', 'ring-emerald-400');
                activeCopyBtn = null;
                activeCopyBtnOriginalHtml = null;
            }
        }

        function closeModalImmediate() {
            clearAllTimers();
            restoreButtonState();

            const modal = document.getElementById('copyRedirectModal');
            const box = document.getElementById('copyModalBox');
            if (modal && box) {
                modal.classList.add('hidden', 'opacity-0');
                box.classList.remove('scale-100');
                box.classList.add('scale-95');
            }
        }

        function copyAndRedirect(text, redirectUrl, labelTautan, targetPageName, btn) {
            restoreButtonState();
            activeCopyBtn = btn;
            activeCopyBtnOriginalHtml = btn.innerHTML;

            btn.innerHTML = `<svg class="h-4 w-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>`;
            btn.classList.add('ring-2', 'ring-emerald-400');

            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(text).then(() => {
                    showCopyModal(labelTautan, targetPageName, redirectUrl);
                }).catch(() => {
                    fallbackCopy(text);
                    showCopyModal(labelTautan, targetPageName, redirectUrl);
                });
            } else {
                fallbackCopy(text);
                showCopyModal(labelTautan, targetPageName, redirectUrl);
            }
        }

        function fallbackCopy(text) {
            const textArea = document.createElement("textarea");
            textArea.value = text;
            textArea.style.position = "fixed";
            textArea.style.left = "-999999px";
            textArea.style.top = "-999999px";
            document.body.appendChild(textArea);
            textArea.focus();
            textArea.select();
            try {
                document.execCommand('copy');
            } catch (err) {}
            document.body.removeChild(textArea);
        }

        function cancelRedirect() {
            clearAllTimers();
            restoreButtonState();

            const modal = document.getElementById('copyRedirectModal');
            const box = document.getElementById('copyModalBox');
            if (modal && box) {
                modal.classList.add('opacity-0');
                box.classList.remove('scale-100');
                box.classList.add('scale-95');
                hideModalTimeout = setTimeout(() => {
                    modal.classList.add('hidden');
                }, 200);
            }
        }

        function showCopyModal(labelTautan, targetPageName, redirectUrl) {
            clearAllTimers();

            const modal = document.getElementById('copyRedirectModal');
            const box = document.getElementById('copyModalBox');
            const desc = document.getElementById('copyModalDesc');
            const targetPageSpan = document.getElementById('copyModalTargetPage');
            const countdownEl = document.getElementById('countdownSec');
            const progressBar = document.getElementById('copyProgressBar');
            const btnBuka = document.getElementById('btnBukaSekarang');

            desc.innerHTML = `<strong>${labelTautan}</strong> telah disalin ke clipboard.`;
            targetPageSpan.innerText = targetPageName;
            countdownEl.innerText = '5';

            btnBuka.onclick = () => {
                closeModalImmediate();
                window.location.href = redirectUrl;
            };

            // Reset progress bar
            progressBar.style.transition = 'none';
            progressBar.style.width = '100%';

            modal.classList.remove('hidden');
            void modal.offsetWidth; // Force reflow

            modal.classList.remove('opacity-0');
            box.classList.remove('scale-95');
            box.classList.add('scale-100');

            // Mulai progress bar 5 detik
            setTimeout(() => {
                progressBar.style.transition = 'width 5000ms linear';
                progressBar.style.width = '0%';
            }, 50);

            let secondsRemaining = 5;
            countdownInterval = setInterval(() => {
                secondsRemaining -= 1;
                if (secondsRemaining >= 0) {
                    countdownEl.innerText = secondsRemaining;
                }
                if (secondsRemaining <= 0) {
                    clearInterval(countdownInterval);
                    countdownInterval = null;
                }
            }, 1000);

            redirectTimeout = setTimeout(() => {
                closeModalImmediate();
                window.location.href = redirectUrl;
            }, 5000);
        }

        // Tangani navigasi kembali (browser Back button / BFCache)
        window.addEventListener('pageshow', function () {
            closeModalImmediate();
        });

        window.addEventListener('pagehide', function () {
            closeModalImmediate();
        });
    </script>
</x-layouts.app>

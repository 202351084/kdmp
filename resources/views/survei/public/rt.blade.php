<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Survei RT - {{ $sesi->wilayah->nama ?? 'Desa' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .glass-panel {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
        .animate-fade-in {
            animation: fadeIn 0.4s ease-out forwards;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
    <!-- AlpineJS for interactivity -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="bg-gradient-to-br from-blue-50 via-white to-blue-100 min-h-screen text-ink-900 antialiased" x-data="{ 
        showModal: false, 
        isEdit: false, 
        form: { id: null, nama_rt: '', dusun: '', nama_ketua_rt: '', no_hp: '', total_kk_baseline: '', total_jiwa_baseline: '' },
        openAddModal() {
            this.isEdit = false;
            this.form = { id: null, nama_rt: '', dusun: '', nama_ketua_rt: '', no_hp: '', total_kk_baseline: '', total_jiwa_baseline: '' };
            this.showModal = true;
        },
        openEditModal(rt) {
            this.isEdit = true;
            this.form = { ...rt };
            this.showModal = true;
        }
    }">

    <!-- Header / Banner -->
    <div class="relative bg-blue-600 text-white overflow-hidden shadow-lg">
        <div class="absolute inset-0 bg-gradient-to-r from-blue-700 to-blue-500 opacity-90"></div>
        <!-- Decorative pattern -->
        <svg class="absolute right-0 top-0 h-full w-48 text-white opacity-10 transform translate-x-1/3" fill="currentColor" viewBox="0 0 100 100" preserveAspectRatio="none" aria-hidden="true">
            <polygon points="50,0 100,0 50,100 0,100"></polygon>
        </svg>
        <div class="relative max-w-3xl mx-auto px-4 py-8 sm:px-6 lg:px-8 flex flex-col items-center text-center">
            <div class="inline-flex items-center justify-center p-3 bg-white/20 rounded-2xl backdrop-blur-sm mb-4">
                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
            </div>
            <h1 class="text-3xl font-bold tracking-tight mb-2">Data Kependudukan RT</h1>
            <p class="text-blue-100 max-w-lg">
                Desa {{ $sesi->wilayah->nama ?? '-' }} &bull; Periode {{ \Carbon\Carbon::createFromFormat('m', $sesi->bulan)->translatedFormat('F') }} {{ $sesi->tahun }}
            </p>
        </div>
    </div>

    <!-- Main Content -->
    <main class="max-w-xl mx-auto px-4 py-8 sm:px-6 lg:px-8 -mt-10 relative z-10">
        
        @if(session('success'))
            <div class="mb-6 rounded-xl bg-green-50 p-4 border border-green-200 shadow-sm animate-fade-in flex gap-3">
                <svg class="h-5 w-5 text-green-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <div class="text-sm text-green-800 font-medium">{{ session('success') }}</div>
            </div>
        @endif

        @if($errors->any())
            <div class="mb-6 rounded-xl bg-red-50 p-4 border border-red-200 shadow-sm animate-fade-in flex gap-3">
                <svg class="h-5 w-5 text-red-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <ul class="text-sm text-red-800 list-disc pl-4">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="glass-panel rounded-2xl shadow-xl overflow-hidden p-6 sm:p-8 mb-8 animate-fade-in" style="animation-delay: 0.1s;">
            <div class="mb-6 border-b border-paper-200 pb-4">
                <h2 class="text-xl font-bold text-ink-900">Form Pendataan RT</h2>
                <p class="text-sm text-ink-500 mt-1">Silakan isi formulir di bawah ini dengan data kependudukan RT Anda yang paling baru.</p>
            </div>

            <form action="{{ route('survei.public.rt.store', $token) }}" method="POST">
                @csrf
                <div class="space-y-5">
                    
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
                        <x-speech-input name="nama_rt" label="RT" required="true" placeholder="Contoh: 01" autoformat="RT" />
                        <x-speech-input name="rw" label="RW" placeholder="Contoh: 02" autoformat="RW" />
                        <x-speech-input name="dusun" label="Dusun" placeholder="Opsional" />
                    </div>

                    <x-speech-input name="nama_ketua_rt" label="Nama Ketua RT" placeholder="Nama Lengkap" />
                    
                    <x-speech-input name="no_hp" label="Nomor WhatsApp / HP" type="tel" placeholder="08..." />

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 pt-4 mt-2 border-t border-paper-100">
                        <x-speech-input name="total_kk_baseline" label="Total Kepala Keluarga (KK)" type="number" required="true" min="1" placeholder="0" />
                        <x-speech-input name="total_jiwa_baseline" label="Total Jiwa" type="number" required="true" min="1" placeholder="0" />
                    </div>
                </div>

                <div class="mt-8 pt-5 border-t border-paper-200">
                    <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-4 rounded-xl shadow-md hover:shadow-lg transition-all flex items-center justify-center gap-2 transform hover:-translate-y-0.5">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                        Kirim Data RT
                    </button>
                    <p class="text-center text-xs text-ink-500 mt-4">Data yang Anda kirimkan akan dienkripsi dan dijaga kerahasiaannya.</p>
                </div>
            </form>
        </div>
        
        <div class="text-center text-xs text-ink-400 font-medium tracking-wide">
            &copy; {{ date('Y') }} Kader Desa Merdeka Pangan.
        </div>
    </main>

    @stack('scripts')
</body>
</html>

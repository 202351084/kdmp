<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Sesi Akhir: Konsumsi & Pemenuhan Keluarga</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <!-- Tailwind CSS (via CDN for standalone view) -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f8fafc; }
        .stepper {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            margin-bottom: 24px;
            font-size: 0.9rem;
            font-weight: 600;
            flex-wrap: wrap;
        }
        .step-item { color: #64748b; }
        .step-item.active { color: #0284c7; }
        .step-item.done { color: #16a34a; }
    </style>
</head>
<body class="antialiased text-slate-800 pb-20">

<div class="max-w-4xl mx-auto p-4 sm:p-6 lg:p-8">
    <div class="stepper mt-4">
        <div class="step-item done">✓ Data Diri</div>
        <div class="step-item done">✓ Anggota Keluarga</div>
        <div class="step-item active">🛒 Konsumsi & Pemenuhan (Selesai)</div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 sm:p-8 mb-6 relative overflow-hidden">
        <div class="absolute top-0 right-0 p-4 opacity-10">
            <svg class="w-24 h-24 text-blue-600" fill="currentColor" viewBox="0 0 24 24"><path d="M7 18c-1.1 0-1.99.9-1.99 2S5.9 22 7 22s2-.9 2-2-.9-2-2-2zM1 2v2h2l3.6 7.59-1.35 2.45c-.16.28-.25.61-.25.96 0 1.1.9 2 2 2h12v-2H7.42c-.14 0-.25-.11-.25-.25l.03-.12.9-1.63h7.45c.75 0 1.41-.41 1.75-1.03l3.58-6.49c.08-.14.12-.31.12-.48 0-.55-.45-1-1-1H5.21l-.94-2H1zm16 16c-1.1 0-1.99.9-1.99 2s.89 2 1.99 2 2-.9 2-2-.9-2-2-2z"/></svg>
        </div>
        <h1 class="text-2xl font-bold text-slate-900 mb-2 relative z-10">Konsumsi & Pemenuhan Keluarga</h1>
        <p class="text-slate-600 mb-4 relative z-10">Silakan tambahkan daftar komoditas yang keluarga Anda beli/produksi, berapa jumlahnya dalam sebulan, dan dari mana Anda mendapatkannya.</p>
        
        @if ($errors->any())
            <div class="bg-red-50 text-red-700 p-3 rounded-lg border border-red-200 mb-4 text-sm">
                Terdapat kesalahan pengisian, pastikan semua kolom terisi dengan benar.
            </div>
        @endif
    </div>

    <form action="{{ route('survei.public.masyarakat.sesi3.store', ['token' => $token, 'id_masyarakat' => $masyarakat->id]) }}" method="POST" x-data="konsumsiApp()">
        @csrf
        
        <div class="space-y-4">
            <template x-for="(item, index) in items" :key="item.id">
                <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm relative transition-all hover:border-blue-300">
                    <button type="button" @click="removeItem(index)" class="absolute top-4 right-4 text-red-400 hover:text-red-600 hover:bg-red-50 p-1.5 rounded-full transition-colors" title="Hapus baris ini">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                    
                    <h3 class="font-bold text-slate-800 mb-4 text-sm uppercase tracking-wide">Komoditas #<span x-text="index + 1"></span></h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Pilihan Komoditas -->
                        <div class="space-y-1">
                            <x-speech-input 
                                x-bind:name="`items[${index}][nama_komoditas]`"
                                label="Pilih Komoditas"
                                xmodel="item.nama_komoditas"
                                list="komoditas-list"
                                placeholder="Ketik atau sebut komoditas..."
                                required="true"
                                @input="updateSatuan(item)"
                            />
                            
                            <!-- Hanya di-render sekali di luar template tapi karena ini dalam template, taruh di luar saja. 
                                 Sebenarnya lebih baik datalist di luar template x-for agar tidak duplikat. -->
                        </div>
                        
                        <!-- Kuantitas & Satuan -->
                        <div class="space-y-1">
                            <label class="block text-xs font-semibold text-slate-600 uppercase">Jumlah / Bulan</label>
                            <div class="flex gap-2">
                                <div class="flex-1">
                                    <x-speech-input 
                                        type="number"
                                        x-bind:name="`items[${index}][jumlah]`"
                                        xmodel="item.jumlah"
                                        placeholder="0"
                                        min="0.1"
                                        step="0.1"
                                        required="true"
                                    />
                                </div>
                                <input type="text" x-model="item.satuan" :name="`items[${index}][satuan]`" class="w-20 rounded-xl border-slate-300 bg-slate-100 px-3 py-2.5 text-sm text-slate-500 cursor-not-allowed" readonly required>
                            </div>
                        </div>

                        <!-- Sumber Pemenuhan -->
                        <div class="space-y-1">
                            <label class="block text-xs font-semibold text-slate-600 uppercase">Sumber Perolehan</label>
                            <select x-model="item.sumber_pemenuhan" :name="`items[${index}][sumber_pemenuhan]`" class="w-full rounded-xl border-slate-300 bg-slate-50 px-3 py-2.5 text-sm focus:border-blue-500 focus:ring-blue-500" required>
                                <option value="">Pilih Sumber...</option>
                                <option value="Beli di Pasar/Warung">Beli di Pasar / Warung</option>
                                <option value="Panen Sendiri">Panen Sendiri</option>
                                <option value="Bantuan Pemerintah">Bantuan Pemerintah</option>
                                <option value="Lainnya">Lainnya</option>
                            </select>
                        </div>

                        <!-- Lokasi Pemenuhan -->
                        <div class="space-y-1">
                            <label class="block text-xs font-semibold text-slate-600 uppercase">Nama Tempat / Lokasi Spesifik</label>
                            <x-speech-input 
                                x-bind:name="`items[${index}][lokasi_pemenuhan]`"
                                xmodel="item.lokasi_pemenuhan"
                                placeholder="Contoh: Pasar Wage, Kebun Sendiri, dll"
                            />
                        </div>

                        <!-- Harga -->
                        <div class="space-y-1">
                            <label class="block text-xs font-semibold text-slate-600 uppercase">Perkiraan Harga (Rp)</label>
                            <div class="flex gap-2">
                                <div class="flex-1">
                                    <x-speech-input 
                                        type="number"
                                        x-bind:name="`items[${index}][harga]`"
                                        xmodel="item.harga"
                                        placeholder="Contoh: 15000"
                                        min="0"
                                    />
                                </div>
                                <select x-model="item.tipe_harga" :name="`items[${index}][tipe_harga]`" class="w-36 rounded-xl border-slate-300 bg-slate-50 px-2 py-2.5 text-xs font-medium focus:border-blue-500 focus:ring-blue-500" required>
                                    <option value="Total">Total Sebulan</option>
                                    <option value="Satuan">Per Satuan</option>
                                </select>
                            </div>

                            <!-- Kalkulasi Otomatis Total Sebulan -->
                            <div class="pt-1 min-h-[22px]">
                                <template x-if="item.harga && item.jumlah && item.tipe_harga === 'Satuan'">
                                    <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-emerald-50 border border-emerald-200 text-xs font-medium text-emerald-800">
                                        <span>Estimasi sebulan:</span>
                                        <span class="font-bold text-emerald-700 text-sm" x-text="formatRupiah(hitungSubtotal(item))"></span>
                                        <span class="text-emerald-600 text-[11px]" x-text="`(${item.jumlah} ${item.satuan || 'satuan'} × ${formatRupiah(item.harga)})`"></span>
                                    </div>
                                </template>
                                <template x-if="item.harga && item.tipe_harga === 'Total'">
                                    <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-slate-50 border border-slate-200 text-xs text-slate-600">
                                        <span>Total sebulan:</span>
                                        <span class="font-bold text-slate-800" x-text="formatRupiah(item.harga)"></span>
                                        <template x-if="item.jumlah && item.jumlah > 0">
                                            <span class="text-slate-500 text-[11px]" x-text="`(${formatRupiah(Math.round(item.harga / item.jumlah))} / ${item.satuan || 'satuan'})`"></span>
                                        </template>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
            </template>

            <button type="button" @click="addItem()" class="w-full py-4 border-2 border-dashed border-blue-300 rounded-xl text-blue-600 font-bold hover:bg-blue-50 transition-colors flex items-center justify-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                Tambah Komoditas Baru
            </button>
        </div>

        <!-- Datalist untuk Suggestions -->
        <datalist id="komoditas-list">
            @foreach($komoditasList as $k)
                <option value="{{ $k->nama }}"></option>
            @endforeach
        </datalist>

        <!-- Ringkasan Total Pengeluaran Sebulan -->
        <div x-show="hitungTotalSemua() > 0" class="mt-6 bg-white border border-slate-200 rounded-xl p-4 shadow-sm flex flex-col sm:flex-row justify-between items-center gap-2">
            <span class="text-sm font-semibold text-slate-700">Total Estimasi Belanja Pangan Seluruh Komoditas:</span>
            <span class="text-xl font-bold text-blue-600" x-text="formatRupiah(hitungTotalSemua()) + ' / bulan'"></span>
        </div>

        <div class="mt-8 flex justify-end">
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-8 rounded-xl shadow-md transition-colors w-full sm:w-auto text-center text-lg">
                Selesaikan Kuesioner
            </button>
        </div>
    </form>
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('konsumsiApp', () => ({
        komoditasList: @json($komoditasList->map(fn($k) => ['nama' => $k->nama, 'satuan' => $k->satuan])->values()),
        items: [],
        
        init() {
            // Coba ambil data dari session old() (saat error validasi)
            const oldData = @json(old('items', []));
            const existingData = @json($konsumsiTersimpan);

            if (oldData.length > 0) {
                this.items = oldData.map(item => ({
                    id: Date.now() + Math.random(),
                    nama_komoditas: item.nama_komoditas,
                    jumlah: item.jumlah,
                    satuan: item.satuan,
                    sumber_pemenuhan: item.sumber_pemenuhan,
                    lokasi_pemenuhan: item.lokasi_pemenuhan,
                    harga: item.harga,
                    tipe_harga: item.tipe_harga || 'Total'
                }));
            } else if (existingData.length > 0) {
                this.items = existingData.map(item => ({
                    id: item.id,
                    nama_komoditas: item.nama_komoditas,
                    jumlah: item.jumlah,
                    satuan: item.satuan,
                    sumber_pemenuhan: item.sumber_pemenuhan,
                    lokasi_pemenuhan: item.lokasi_pemenuhan,
                    harga: item.harga,
                    tipe_harga: item.tipe_harga || 'Total'
                }));
            } else {
                // Beri 1 baris kosong sebagai default
                this.addItem();
            }
        },
        
        addItem() {
            this.items.push({
                id: Date.now() + Math.random(),
                nama_komoditas: '',
                jumlah: '',
                satuan: 'kg',
                sumber_pemenuhan: '',
                lokasi_pemenuhan: '',
                harga: '',
                tipe_harga: 'Total'
            });
        },
        
        removeItem(index) {
            this.items.splice(index, 1);
            if (this.items.length === 0) {
                this.addItem(); // Pastikan selalu ada minimal 1
            }
        },
        
        updateSatuan(item) {
            const selected = this.komoditasList.find(k => k.nama.toLowerCase() === (item.nama_komoditas || '').toLowerCase());
            if (selected) {
                item.satuan = selected.satuan || 'kg';
            }
        },

        formatRupiah(val) {
            const num = parseFloat(val);
            if (isNaN(num) || num <= 0) return 'Rp 0';
            return 'Rp ' + Math.round(num).toLocaleString('id-ID');
        },

        hitungSubtotal(item) {
            const h = parseFloat(item.harga) || 0;
            const j = parseFloat(item.jumlah) || 0;
            if (item.tipe_harga === 'Satuan') {
                return h * j;
            }
            return h;
        },

        hitungTotalSemua() {
            return this.items.reduce((total, item) => total + this.hitungSubtotal(item), 0);
        }
    }));
});
</script>

@stack('scripts')
</body>
</html>

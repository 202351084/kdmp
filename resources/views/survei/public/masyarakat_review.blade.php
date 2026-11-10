<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Review & Finalisasi Survei</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
        <div class="step-item done">✓ Konsumsi</div>
        <div class="step-item active">📋 Review Final</div>
    </div>

    <div class="bg-gradient-to-br from-blue-600 to-indigo-700 rounded-2xl shadow-lg border border-blue-200 p-6 sm:p-8 mb-6 relative overflow-hidden text-white">
        <div class="absolute top-0 right-0 p-4 opacity-20">
            <svg class="w-32 h-32 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
        </div>
        <h1 class="text-3xl font-bold mb-2 relative z-10">Satu Langkah Lagi!</h1>
        <p class="text-blue-100 mb-0 relative z-10">Silakan tinjau kembali data yang telah Anda masukkan. Jika sudah sesuai, klik tombol Kirim di bawah.</p>
    </div>

    <!-- Panel 1: Data Diri & Alamat -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 mb-6">
        <div class="flex items-center justify-between mb-4 pb-4 border-b border-slate-100">
            <h2 class="text-lg font-bold text-slate-800 flex items-center gap-2">
                <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                Data Responden & Alamat
            </h2>
            <a href="{{ route('survei.public.masyarakat.show', ['token' => $token, 'id_masyarakat' => $masyarakat->id, 'redirect' => 'review']) }}" class="text-sm font-medium text-blue-600 hover:text-blue-800 flex items-center gap-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                Edit
            </a>
        </div>
        
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
            <div>
                <p class="text-slate-500 font-medium">Nama Responden</p>
                <p class="font-bold text-slate-800">{{ $masyarakat->nama_kepala_keluarga }}</p>
            </div>
            <div>
                <p class="text-slate-500 font-medium">Umur / Jenis Kelamin</p>
                <p class="font-bold text-slate-800">{{ $masyarakat->umur ?? '-' }} Tahun / {{ $masyarakat->jenis_kelamin ?? '-' }}</p>
            </div>
            <div>
                <p class="text-slate-500 font-medium">RT / RW</p>
                <p class="font-bold text-slate-800">{{ $masyarakat->rt }} / {{ $masyarakat->rw }}</p>
            </div>
            <div>
                <p class="text-slate-500 font-medium">No. Telepon / WA</p>
                <p class="font-bold text-slate-800">{{ $masyarakat->nomor_hp ?? 'Tidak ada' }}</p>
            </div>
            <div class="sm:col-span-2">
                <p class="text-slate-500 font-medium">Alamat Lengkap</p>
                <p class="font-bold text-slate-800">
                    {{ $masyarakat->nama_jalan ? $masyarakat->nama_jalan . ', ' : '' }}
                    {{ $masyarakat->nomor_rumah ? 'No. ' . $masyarakat->nomor_rumah . ', ' : '' }}
                    {{ $masyarakat->dusun ? $masyarakat->dusun . ', ' : '' }}
                    {{ $masyarakat->desa }}, Kec. {{ $masyarakat->kecamatan }}, {{ $masyarakat->kabupaten }}, {{ $masyarakat->provinsi }}
                    @if($masyarakat->detail_alamat)
                        <br><span class="text-xs font-normal text-slate-500">({{ $masyarakat->detail_alamat }})</span>
                    @endif
                </p>
            </div>
        </div>
    </div>

    <!-- Panel 2: Anggota Keluarga -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 mb-6">
        <div class="flex items-center justify-between mb-4 pb-4 border-b border-slate-100">
            <h2 class="text-lg font-bold text-slate-800 flex items-center gap-2">
                <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                Anggota Keluarga ({{ $masyarakat->anggota->count() }} Orang)
            </h2>
            <a href="{{ route('survei.public.masyarakat.anggota.show', ['token' => $token, 'id_masyarakat' => $masyarakat->id, 'redirect' => 'review']) }}" class="text-sm font-medium text-blue-600 hover:text-blue-800 flex items-center gap-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                Edit
            </a>
        </div>
        
        @if($masyarakat->anggota->count() > 0)
        <div class="overflow-x-auto rounded-xl border border-slate-200">
            <table class="w-full text-sm text-left">
                <thead class="text-xs text-slate-600 bg-slate-50 border-b border-slate-200">
                    <tr>
                        <th class="px-4 py-3">No</th>
                        <th class="px-4 py-3">Nama</th>
                        <th class="px-4 py-3">Umur</th>
                        <th class="px-4 py-3">Jenis Kelamin</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($masyarakat->anggota as $idx => $anggota)
                    <tr class="border-b border-slate-100 last:border-0 hover:bg-slate-50">
                        <td class="px-4 py-3 font-medium">{{ $idx + 1 }}</td>
                        <td class="px-4 py-3 font-bold text-slate-800">{{ $anggota->nama }}</td>
                        <td class="px-4 py-3">{{ $anggota->umur }} Tahun</td>
                        <td class="px-4 py-3">{{ $anggota->jenis_kelamin == 'L' ? 'Laki-laki' : 'Perempuan' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <div class="text-center py-6 bg-slate-50 rounded-xl border border-dashed border-slate-300 text-slate-500">
            Tidak ada anggota keluarga yang diinput.
        </div>
        @endif
    </div>

    <!-- Panel 3: Konsumsi Keluarga -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 mb-8">
        <div class="flex items-center justify-between mb-4 pb-4 border-b border-slate-100">
            <h2 class="text-lg font-bold text-slate-800 flex items-center gap-2">
                <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                Konsumsi & Pemenuhan ({{ $masyarakat->konsumsi->count() }} Komoditas)
            </h2>
            <a href="{{ route('survei.public.masyarakat.sesi3.show', ['token' => $token, 'id_masyarakat' => $masyarakat->id]) }}" class="text-sm font-medium text-blue-600 hover:text-blue-800 flex items-center gap-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                Edit
            </a>
        </div>
        
        @if($masyarakat->konsumsi->count() > 0)
        <div class="space-y-3">
            @foreach($masyarakat->konsumsi as $konsumsi)
            <div class="p-4 rounded-xl border border-slate-200 bg-slate-50 flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h3 class="font-bold text-slate-800 text-base mb-1">{{ $konsumsi->nama_komoditas }}</h3>
                    <div class="flex flex-wrap gap-2 text-xs">
                        <span class="inline-flex items-center px-2 py-1 rounded bg-green-100 text-green-700 font-medium">
                            {{ $konsumsi->jumlah }} {{ $konsumsi->satuan }} / Bulan
                        </span>
                        @if($konsumsi->sumber_pemenuhan)
                        <span class="inline-flex items-center px-2 py-1 rounded bg-purple-100 text-purple-700 font-medium">
                            Sumber: {{ $konsumsi->sumber_pemenuhan }}
                        </span>
                        @endif
                    </div>
                </div>
                <div class="text-left md:text-right">
                    @if($konsumsi->harga)
                        <p class="text-sm font-medium text-slate-500 mb-1">
                            @if($konsumsi->tipe_harga == 'Satuan')
                                Harga / {{ $konsumsi->satuan }}
                            @else
                                Total Harga / Bulan
                            @endif
                        </p>
                        <p class="font-bold text-slate-800">Rp {{ number_format($konsumsi->harga, 0, ',', '.') }}</p>
                        @if($konsumsi->tipe_harga == 'Satuan' && $konsumsi->jumlah)
                            <p class="text-xs text-emerald-600 font-semibold mt-1">
                                Total: Rp {{ number_format($konsumsi->harga * $konsumsi->jumlah, 0, ',', '.') }} / bln
                            </p>
                        @endif
                    @endif
                    @if($konsumsi->lokasi_pemenuhan)
                        <p class="text-xs text-slate-500 mt-1">
                            <span class="font-semibold">Lokasi:</span> {{ $konsumsi->lokasi_pemenuhan }}
                        </p>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
        @else
        <div class="text-center py-6 bg-slate-50 rounded-xl border border-dashed border-slate-300 text-slate-500">
            Tidak ada komoditas yang diinput.
        </div>
        @endif
    </div>

    <!-- Actions -->
    <div class="flex flex-col sm:flex-row gap-4 items-center justify-between" x-data>
        <a href="{{ route('survei.public.masyarakat.sesi3.show', ['token' => $token, 'id_masyarakat' => $masyarakat->id]) }}" class="px-6 py-3 rounded-xl border-2 border-slate-300 text-slate-600 font-bold hover:bg-slate-50 hover:text-slate-800 transition-colors w-full sm:w-auto text-center">
            Kembali
        </a>
        
        <form action="{{ route('survei.public.masyarakat.review.submit', ['token' => $token, 'id_masyarakat' => $masyarakat->id]) }}" method="POST" class="w-full sm:w-auto" id="formFinal">
            @csrf
            <button type="button" onclick="confirmSubmit()" class="w-full sm:w-auto bg-green-600 hover:bg-green-700 text-white font-bold py-3 px-8 rounded-xl shadow-lg hover:shadow-xl transition-all transform hover:-translate-y-1 text-center text-lg flex items-center justify-center gap-2">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                Kirim Data Final
            </button>
        </form>
    </div>
</div>

<script>
    function confirmSubmit() {
        Swal.fire({
            title: 'Apakah Data Sudah Benar?',
            text: "Pastikan semua data dari awal hingga akhir sudah sesuai. Anda tidak dapat mengubahnya lagi setelah ini.",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#16a34a',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Ya, Kirim Sekarang!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                // Tampilkan loading sebelum submit
                Swal.fire({
                    title: 'Memproses Data...',
                    html: 'Mohon tunggu sebentar',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading()
                    }
                });
                document.getElementById('formFinal').submit();
            }
        })
    }
</script>

</body>
</html>

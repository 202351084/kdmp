<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Survei Masyarakat - Pengisian Data</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <style>
        /* Form & Grid Layout */
        .row {
            display: grid !important;
            grid-template-columns: minmax(0, 1fr) minmax(0, 1fr) !important;
            gap: 12px !important;
            width: 100% !important;
            box-sizing: border-box !important;
        }
        .row > .form-group {
            min-width: 0 !important;
            width: 100% !important;
            box-sizing: border-box !important;
        }
        .form-group {
            width: 100% !important;
            max-width: 100% !important;
            box-sizing: border-box !important;
            min-width: 0 !important;
        }
        .form-group input, 
        .form-group select, 
        .form-group textarea {
            width: 100% !important;
            max-width: 100% !important;
            box-sizing: border-box !important;
            min-width: 0 !important;
        }
        .address-card {
            width: 100% !important;
            max-width: 100% !important;
            box-sizing: border-box !important;
            background-color: #ffffff !important;
            border: 1px solid var(--border-color);
            border-radius: 14px;
            padding: 16px;
            opacity: 1 !important;
            transform: none !important;
            visibility: visible !important;
            display: flex !important;
            flex-direction: column !important;
        }

        /* Input dengan Mic Icon */
        .input-mic-group {
            position: relative;
            width: 100%;
            box-sizing: border-box;
            display: flex;
            align-items: center;
        }
        .input-mic-group input {
            width: 100% !important;
            padding-right: 42px !important;
            box-sizing: border-box !important;
        }
        .input-mic-group textarea {
            width: 100% !important;
            padding-right: 42px !important;
            box-sizing: border-box !important;
        }

        /* Posisi Mic Terkunci */
        .mic-btn {
            position: absolute;
            right: 8px;
            top: 50%;
            transform: translateY(-50%) !important;
            width: 30px;
            height: 30px;
            border: none;
            background: transparent;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            color: #64748b;
            padding: 0;
            transition: color 0.15s, background-color 0.15s;
            z-index: 5;
        }
        .mic-btn:hover {
            color: #0f172a;
            background-color: #f1f5f9;
        }

        .input-mic-group textarea + .mic-btn {
            top: 14px !important;
            transform: none !important;
        }

        /* Animasi Mic saat Aktif */
        @keyframes micPulseFixed {
            0% {
                box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.6);
                transform: translateY(-50%) scale(1) !important;
            }
            50% {
                box-shadow: 0 0 0 6px rgba(239, 68, 68, 0);
                transform: translateY(-50%) scale(1.08) !important;
            }
            100% {
                box-shadow: 0 0 0 0 rgba(239, 68, 68, 0);
                transform: translateY(-50%) scale(1) !important;
            }
        }
        @keyframes micPulseTopFixed {
            0% {
                box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.6);
                transform: scale(1) !important;
            }
            50% {
                box-shadow: 0 0 0 6px rgba(239, 68, 68, 0);
                transform: scale(1.08) !important;
            }
            100% {
                box-shadow: 0 0 0 0 rgba(239, 68, 68, 0);
                transform: scale(1) !important;
            }
        }

        .mic-btn.listening {
            color: #ef4444 !important;
            background-color: #fee2e2 !important;
            animation: micPulseFixed 1.3s infinite !important;
        }
        .input-mic-group textarea + .mic-btn.listening {
            animation: micPulseTopFixed 1.3s infinite !important;
        }

        /* Dropdown Rekomendasi Desa Bersih & Profesional */
        .desa-dropdown {
            display: none;
            position: absolute;
            left: 0;
            right: 0;
            top: calc(100% + 4px);
            background: #ffffff !important;
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            box-shadow: 0 10px 25px -4px rgba(0,0,0,0.12);
            z-index: 9999;
            max-height: 220px;
            overflow-y: auto;
        }
        .desa-dropdown-item {
            padding: 10px 14px;
            cursor: pointer;
            border-bottom: 1px solid #f1f5f9;
            background: #ffffff;
            transition: background-color 0.15s ease;
        }
        .desa-dropdown-item:last-child {
            border-bottom: none;
        }
        .desa-dropdown-item:hover {
            background-color: #f8fafc;
        }
        .desa-item-title {
            font-weight: 600;
            font-size: 0.9rem;
            color: #0f172a;
        }
        .desa-item-sub {
            font-size: 0.75rem;
            color: #64748b;
            margin-top: 2px;
        }
        .desa-item-manual {
            padding: 10px 14px;
            cursor: pointer;
            background-color: #f8fafc;
            border-top: 1px solid #e2e8f0;
            font-size: 0.85rem;
            color: #334155;
            font-weight: 500;
        }
        .desa-item-manual:hover {
            background-color: #f1f5f9;
        }
    </style>
    <script>
        window.APP_SUBMIT_URL = "{!! route('survei.public.masyarakat.store', array_merge(['token' => $token], request()->query())) !!}";
        window.KOPERASI_DESA_LIST = [
            @if(isset($daftarKoperasi) && $daftarKoperasi->isNotEmpty())
                @foreach($daftarKoperasi as $kop)
                @php
                    $namaDesaKop = $kop->wilayah?->nama ?? $kop->nama_entitas;
                @endphp
                {
                    desa: @json($namaDesaKop),
                    koperasi: @json($kop->nama_entitas)
                },
                @endforeach
            @endif
            @if(isset($daftarWilayah) && $daftarWilayah->isNotEmpty())
                @foreach($daftarWilayah as $wil)
                {
                    desa: @json($wil->nama),
                    koperasi: @json($wil->nama)
                },
                @endforeach
            @endif
        ];

        // Deduplikasi daftar desa
        (function() {
            const map = new Map();
            window.KOPERASI_DESA_LIST.forEach(item => {
                if (item.desa) {
                    const key = item.desa.toLowerCase().trim();
                    if (!map.has(key)) map.set(key, item);
                }
            });
            window.KOPERASI_DESA_LIST = Array.from(map.values());
        })();
    </script>
</head>
<body>

<div class="container">
    <!-- Stepper 2 Langkah Sederhana -->
    <div class="stepper">
        <div class="step-item active" id="stepIndicator1">1. Data Diri</div>
        <div class="step-arrow">&rarr;</div>
        <div class="step-item" id="stepIndicator2">2. Alamat Rumah</div>
    </div>

    <div class="step-container" id="stepContainer">
        
        <!-- Step 1: Data Diri -->
        <div class="step" id="step1">
            <div class="step-header">
                <h1 class="step-title">Data Diri</h1>
                <p class="step-subtitle">Lengkapi identitas diri responden.</p>
            </div>
            
            <div class="form-group">
                <label for="nama">Nama Responden</label>
                <div class="input-mic-group">
                    <input type="text" id="nama" placeholder="Nama lengkap responden" value="{{ $masyarakat->nama_kepala_keluarga ?? '' }}" required>
                    <button type="button" class="mic-btn" data-target="nama" title="Input dengan Suara">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2a3 3 0 0 0-3 3v7a3 3 0 0 0 6 0V5a3 3 0 0 0-3-3Z"></path><path d="M19 10v2a7 7 0 0 1-14 0v-2"></path><line x1="12" x2="12" y1="19" y2="22"></line></svg>
                    </button>
                </div>
            </div>

            <div class="form-group">
                <label for="nomor_hp">Nomor HP</label>
                <div class="input-mic-group">
                    <input type="tel" id="nomor_hp" placeholder="Contoh: 081234567890" pattern="^08[0-9]{8,11}$" value="{{ $masyarakat->nomor_hp ?? '' }}" required>
                    <button type="button" class="mic-btn" data-target="nomor_hp" data-type="phone" title="Input dengan Suara">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2a3 3 0 0 0-3 3v7a3 3 0 0 0 6 0V5a3 3 0 0 0-3-3Z"></path><path d="M19 10v2a7 7 0 0 1-14 0v-2"></path><line x1="12" x2="12" y1="19" y2="22"></line></svg>
                    </button>
                </div>
            </div>
            
            <div class="row" style="margin-bottom: 16px;">
                <div class="form-group" style="margin-bottom: 0;">
                    <label for="umur">Umur (Tahun)</label>
                    <div class="input-mic-group">
                        <input type="number" id="umur" min="0" max="150" placeholder="Umur" value="{{ $masyarakat->umur ?? '' }}" required>
                        <button type="button" class="mic-btn" data-target="umur" data-type="number" title="Input dengan Suara">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2a3 3 0 0 0-3 3v7a3 3 0 0 0 6 0V5a3 3 0 0 0-3-3Z"></path><path d="M19 10v2a7 7 0 0 1-14 0v-2"></path><line x1="12" x2="12" y1="19" y2="22"></line></svg>
                        </button>
                    </div>
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <label for="jenis_kelamin">Jenis Kelamin</label>
                    <select id="jenis_kelamin" required>
                        <option value="">Pilih...</option>
                        <option value="Laki-laki" {{ ($masyarakat?->jenis_kelamin == 'Laki-laki') ? 'selected' : '' }}>Laki-laki</option>
                        <option value="Perempuan" {{ ($masyarakat?->jenis_kelamin == 'Perempuan') ? 'selected' : '' }}>Perempuan</option>
                    </select>
                </div>
            </div>
            
            <button type="button" class="btn btn-primary" onclick="goToStep2()">Lanjutkan ke Alamat &rarr;</button>
        </div>

        <!-- Step 2: Alamat Tempat Tinggal -->
        <div class="step" id="step2">
            <div class="step-header">
                <h1 class="step-title">Alamat Tempat Tinggal</h1>
                <p class="step-subtitle">Lengkapi detail alamat domisili Anda.</p>
            </div>

            <div class="address-card show" style="display: flex; flex-direction: column; gap: 14px; opacity: 1 !important; visibility: visible !important;">
                
                <!-- Kolom Desa (Searchable + Mic) -->
                <div class="form-group" id="fg-desa" style="position: relative; z-index: 60;">
                    <label for="desa">Desa/Kelurahan</label>
                    <div class="input-mic-group" style="position: relative;">
                        <input type="text" 
                               id="desa" 
                               name="desa" 
                               value="{{ $masyarakat->desa ?? '' }}"
                               placeholder="Ketik atau klik untuk mencari desa..." 
                               autocomplete="off" 
                               required>
                        <button type="button" 
                                id="btn-mic-desa" 
                                class="mic-btn"
                                data-target="desa" 
                                data-is-desa="true" 
                                title="Bicara nama desa">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M12 2a3 3 0 0 0-3 3v7a3 3 0 0 0 6 0V5a3 3 0 0 0-3-3Z"></path>
                                <path d="M19 10v2a7 7 0 0 1-14 0v-2"></path>
                                <line x1="12" x2="12" y1="19" y2="22"></line>
                            </svg>
                        </button>
                        <div id="desa-dropdown-list" class="desa-dropdown"></div>
                    </div>
                </div>

                <!-- Kecamatan & Kabupaten (Pati tetap readonly) -->
                <div class="row">
                    <div class="form-group" style="margin-bottom: 0;">
                        <label for="kecamatan">Kecamatan</label>
                        <div class="input-mic-group">
                            <input type="text" id="kecamatan" value="{{ $masyarakat->kecamatan ?? '' }}" placeholder="Kecamatan">
                            <button type="button" class="mic-btn" data-target="kecamatan" title="Input dengan Suara">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2a3 3 0 0 0-3 3v7a3 3 0 0 0 6 0V5a3 3 0 0 0-3-3Z"></path><path d="M19 10v2a7 7 0 0 1-14 0v-2"></path><line x1="12" x2="12" y1="19" y2="22"></line></svg>
                            </button>
                        </div>
                    </div>

                    <div class="form-group" style="margin-bottom: 0;">
                        <label for="kabupaten">Kabupaten/Kota</label>
                        <input type="text" id="kabupaten" value="Pati" readonly style="background-color: #f8fafc; color: #475569; cursor: not-allowed;">
                    </div>
                </div>

                <!-- Provinsi (Jawa Tengah tetap readonly) -->
                <div class="form-group">
                    <label for="provinsi">Provinsi</label>
                    <input type="text" id="provinsi" value="Jawa Tengah" readonly style="background-color: #f8fafc; color: #475569; cursor: not-allowed;">
                </div>

                <!-- Dusun -->
                <div class="form-group">
                    <label for="dusun">Dusun/Dukuh</label>
                    <div class="input-mic-group">
                        <input type="text" id="dusun" placeholder="Dusun / Dukuh (opsional)" value="{{ $masyarakat->dusun ?? '' }}">
                        <button type="button" class="mic-btn" data-target="dusun" title="Input dengan Suara">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2a3 3 0 0 0-3 3v7a3 3 0 0 0 6 0V5a3 3 0 0 0-3-3Z"></path><path d="M19 10v2a7 7 0 0 1-14 0v-2"></path><line x1="12" x2="12" y1="19" y2="22"></line></svg>
                        </button>
                    </div>
                </div>
                
                <!-- RT & RW -->
                <div class="row">
                    <div class="form-group">
                        <label for="rt">RT</label>
                        <div class="input-mic-group">
                            <input type="text" id="rt" placeholder="01" value="{{ $masyarakat->rt ?? '' }}">
                            <button type="button" class="mic-btn" data-target="rt" data-type="number" title="Input dengan Suara">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2a3 3 0 0 0-3 3v7a3 3 0 0 0 6 0V5a3 3 0 0 0-3-3Z"></path><path d="M19 10v2a7 7 0 0 1-14 0v-2"></path><line x1="12" x2="12" y1="19" y2="22"></line></svg>
                            </button>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="rw">RW</label>
                        <div class="input-mic-group">
                            <input type="text" id="rw" placeholder="02" value="{{ $masyarakat->rw ?? '' }}">
                            <button type="button" class="mic-btn" data-target="rw" data-type="number" title="Input dengan Suara">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2a3 3 0 0 0-3 3v7a3 3 0 0 0 6 0V5a3 3 0 0 0-3-3Z"></path><path d="M19 10v2a7 7 0 0 1-14 0v-2"></path><line x1="12" x2="12" y1="19" y2="22"></line></svg>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Jalan & Nomor Rumah -->
                <div class="row">
                    <div class="form-group">
                        <label for="nama_jalan">Nama Jalan</label>
                        <div class="input-mic-group">
                            <input type="text" id="nama_jalan" placeholder="Nama jalan (opsional)" value="{{ $masyarakat->nama_jalan ?? '' }}">
                            <button type="button" class="mic-btn" data-target="nama_jalan" title="Input dengan Suara">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2a3 3 0 0 0-3 3v7a3 3 0 0 0 6 0V5a3 3 0 0 0-3-3Z"></path><path d="M19 10v2a7 7 0 0 1-14 0v-2"></path><line x1="12" x2="12" y1="19" y2="22"></line></svg>
                            </button>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="nomor_rumah">Nomor Rumah</label>
                        <div class="input-mic-group">
                            <input type="text" id="nomor_rumah" placeholder="Nomor rumah (opsional)" value="{{ $masyarakat->nomor_rumah ?? '' }}">
                            <button type="button" class="mic-btn" data-target="nomor_rumah" data-type="number" title="Input dengan Suara">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2a3 3 0 0 0-3 3v7a3 3 0 0 0 6 0V5a3 3 0 0 0-3-3Z"></path><path d="M19 10v2a7 7 0 0 1-14 0v-2"></path><line x1="12" x2="12" y1="19" y2="22"></line></svg>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Detail Alamat / Patokan -->
                <div class="form-group">
                    <label for="detail_alamat">Detail Alamat / Patokan</label>
                    <div class="input-mic-group">
                        <textarea id="detail_alamat" rows="2" placeholder="Contoh: dekat masjid, sebelah balai desa, rumah warna putih...">{{ $masyarakat->detail_alamat ?? '' }}</textarea>
                        <button type="button" class="mic-btn" data-target="detail_alamat" title="Input dengan Suara">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2a3 3 0 0 0-3 3v7a3 3 0 0 0 6 0V5a3 3 0 0 0-3-3Z"></path><path d="M19 10v2a7 7 0 0 1-14 0v-2"></path><line x1="12" x2="12" y1="19" y2="22"></line></svg>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Tombol Navigasi & Simpan Langsung (Tanpa Verifikasi Lokasi) -->
            <div style="display: flex; gap: 12px; margin-top: 16px;">
                <button type="button" class="btn btn-outline" style="margin-top: 0; width: 35%;" onclick="nextStep(1)">&larr; Kembali</button>
                <button type="button" class="btn btn-primary" id="btn-submit-survei" style="margin-top: 0; width: 65%;" onclick="submitSurvei()">Simpan & Lanjutkan</button>
            </div>
        </div>

    </div>
</div>

<script>
// State Step Sederhana (1: Data Diri, 2: Alamat)
let currentStep = 1;

function nextStep(step) {
    currentStep = step;
    const container = document.getElementById('stepContainer');
    container.style.transform = `translateX(-${(step - 1) * 100}%)`;

    document.querySelectorAll('.step-item').forEach((el, index) => {
        if (index < step) {
            el.classList.add('active');
        } else {
            el.classList.remove('active');
        }
    });

    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function goToStep2() {
    const nama = document.getElementById('nama').value.trim();
    const nomorHp = document.getElementById('nomor_hp').value.trim();
    const umur = document.getElementById('umur').value.trim();
    const jk = document.getElementById('jenis_kelamin').value;

    if (!nama) {
        alert('Harap isi Nama Responden.');
        document.getElementById('nama').focus();
        return;
    }
    if (!nomorHp) {
        alert('Harap isi Nomor HP.');
        document.getElementById('nomor_hp').focus();
        return;
    }
    if (!umur) {
        alert('Harap isi Umur.');
        document.getElementById('umur').focus();
        return;
    }
    if (!jk) {
        alert('Harap pilih Jenis Kelamin.');
        document.getElementById('jenis_kelamin').focus();
        return;
    }

    nextStep(2);
}

// Logika Pengisian Desa & Dropdown
document.addEventListener('DOMContentLoaded', function() {
    const desaInput = document.getElementById('desa');
    const dropdown = document.getElementById('desa-dropdown-list');

    window.selectDesa = function(desaName) {
        desaInput.value = desaName;
        dropdown.style.display = 'none';
    };

    window.renderDesaDropdown = function(filterText = '') {
        const query = (filterText || '').trim().toLowerCase();
        dropdown.innerHTML = '';

        const list = window.KOPERASI_DESA_LIST || [];
        const filtered = list.filter(item => {
            if (!query) return true;
            const d = (item.desa || '').toLowerCase();
            const k = (item.koperasi || '').toLowerCase();
            const cleanD = d.replace(/^desa\s+/, '');
            const cleanQ = query.replace(/^desa\s+/, '');
            return d.includes(query) || k.includes(query) || cleanD.includes(cleanQ) || cleanQ.includes(cleanD);
        });

        if (filtered.length > 0) {
            filtered.forEach(item => {
                const el = document.createElement('div');
                el.className = 'desa-dropdown-item';
                el.innerHTML = `
                    <div class="desa-item-title">${item.desa}</div>
                    <div class="desa-item-sub">${item.koperasi || ''}</div>
                `;
                el.addEventListener('click', () => {
                    selectDesa(item.desa);
                });
                dropdown.appendChild(el);
            });
        }

        // Pilihan input teks manual jika belum ada di database
        if (query) {
            const manualEl = document.createElement('div');
            manualEl.className = 'desa-item-manual';
            manualEl.innerText = `Gunakan: "${filterText.trim()}" (Input manual)`;
            manualEl.addEventListener('click', () => {
                selectDesa(filterText.trim());
            });
            dropdown.appendChild(manualEl);
        }

        dropdown.style.display = 'block';
    };

    desaInput.addEventListener('click', function() {
        renderDesaDropdown(this.value);
    });
    desaInput.addEventListener('focus', function() {
        renderDesaDropdown(this.value);
    });
    desaInput.addEventListener('input', function() {
        renderDesaDropdown(this.value);
    });

    document.addEventListener('click', function(e) {
        if (!e.target.closest('#fg-desa')) {
            dropdown.style.display = 'none';
        }
    });

    // ==========================================
    // Web Speech API: Akurasi Tinggi (Multi-Alternative & Fuzzy Levenshtein)
    // ==========================================
    const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
    let recognition = null;
    let activeBtn = null;
    let activeTarget = null;
    let originalPlaceholder = '';
    let isListening = false;

    // Koreksi Kontekstual Pertukaran Fonetik B dan W pada Istilah Alamat / Nama
    function correctLabialConfusions(text) {
        if (!text) return text;
        const corrections = {
            'walai': 'balai',
            'welakang': 'belakang',
            'warat': 'barat',
            'wawah': 'bawah',
            'wundaran': 'bundaran',
            'wiru': 'biru',
            'barna': 'warna',
            'barga': 'warga',
            'barung': 'warung',
            'betan': 'wetan',
            'baduk': 'waduk',
            'bisma': 'wisma',
            'wudi': 'budi',
            'wambang': 'bambang',
            'wagus': 'bagus'
        };
        return text.replace(/\b[a-zA-Z]+\b/g, function(word) {
            const lower = word.toLowerCase();
            if (corrections[lower]) {
                if (word[0] === word[0].toUpperCase()) {
                    return corrections[lower].charAt(0).toUpperCase() + corrections[lower].slice(1);
                }
                return corrections[lower];
            }
            return word;
        });
    }

    // Algoritma Levenshtein Similarity dengan Bobot Fonetik B <-> W (Skor 0.0 - 1.0)
    function getSimilarity(s1, s2) {
        s1 = (s1 || '').toLowerCase().trim();
        s2 = (s2 || '').toLowerCase().trim();
        if (s1 === s2) return 1.0;
        if (!s1 || !s2) return 0.0;
        
        // Pembersihan awalan desa / kelurahan dan karakter non-alfanumerik
        const c1 = s1.replace(/\b(desa|kelurahan|daerah|wilayah|dukuh|kp|kampung)\b/gi, '').replace(/[^a-z0-9]/gi, '');
        const c2 = s2.replace(/\b(desa|kelurahan|daerah|wilayah|dukuh|kp|kampung)\b/gi, '').replace(/[^a-z0-9]/gi, '');
        
        if (c1 === c2 && c1.length > 0) return 1.0;
        if (c1.includes(c2) || c2.includes(c1)) return 0.95;

        // Penyetaraan fonetik bilabial B, W, dan V
        const p1 = c1.replace(/[bwv]/g, 'b');
        const p2 = c2.replace(/[bwv]/g, 'b');
        if (p1 === p2 && p1.length > 0) return 0.98;

        const m = c1.length, n = c2.length;
        if (m === 0) return n === 0 ? 1.0 : 0.0;
        if (n === 0) return 0.0;
        
        const d = Array(m + 1).fill(null).map(() => Array(n + 1).fill(0));
        for (let i = 0; i <= m; i++) d[i][0] = i;
        for (let j = 0; j <= n; j++) d[0][j] = j;
        
        for (let i = 1; i <= m; i++) {
            for (let j = 1; j <= n; j++) {
                const ch1 = c1[i - 1];
                const ch2 = c2[j - 1];
                let cost = 0;
                if (ch1 === ch2) {
                    cost = 0;
                } else if ((ch1 === 'b' || ch1 === 'w' || ch1 === 'v') && (ch2 === 'b' || ch2 === 'w' || ch2 === 'v')) {
                    cost = 0.05; // Penalti sangat rendah untuk ketertukaran B, W, V
                } else if ((ch1 === 'a' && ch2 === 'o') || (ch1 === 'o' && ch2 === 'a')) {
                    cost = 0.15; // Penalti vokal dialek Jawa A dan O
                } else {
                    cost = 1.0;
                }

                d[i][j] = Math.min(
                    d[i - 1][j] + 1,
                    d[i][j - 1] + 1,
                    d[i - 1][j - 1] + cost
                );
            }
        }
        const maxLen = Math.max(m, n);
        return Math.max(0, (maxLen - d[m][n]) / maxLen);
    }

    // Parsing Ucapan Bahasa Indonesia (Angka, No HP, RT/RW, Umur, dan Teks)
    function parseSpokenWords(text, type) {
        let clean = text.trim();
        if (type === 'phone' || type === 'number') {
            const units = {
                'nol': 0, 'kosong': 0, 'satu': 1, 'dua': 2, 'tiga': 3, 'empat': 4,
                'lima': 5, 'enam': 6, 'tujuh': 7, 'delapan': 8, 'sembilan': 9
            };
            const teens = {
                'sepuluh': 10, 'sebelas': 11, 'dua belas': 12, 'tiga belas': 13,
                'empat belas': 14, 'lima belas': 15, 'enam belas': 16,
                'tujuh belas': 17, 'delapan belas': 18, 'sembilan belas': 19
            };
            const tens = {
                'dua puluh': 20, 'tiga puluh': 30, 'empat puluh': 40, 'lima puluh': 50,
                'enam puluh': 60, 'tujuh puluh': 70, 'delapan puluh': 80, 'sembilan puluh': 90
            };
            
            let lower = clean.toLowerCase();
            for (const [tWord, tVal] of Object.entries(tens)) {
                for (const [uWord, uVal] of Object.entries(units)) {
                    if (uVal > 0) lower = lower.replaceAll(`${tWord} ${uWord}`, String(tVal + uVal));
                }
                lower = lower.replaceAll(tWord, String(tVal));
            }
            for (const [w, val] of Object.entries(teens)) {
                lower = lower.replaceAll(w, String(val));
            }
            for (const [w, val] of Object.entries(units)) {
                lower = lower.replaceAll(w, String(val));
            }
            
            if (type === 'phone') {
                let digits = lower.replace(/\D/g, '');
                if (digits.startsWith('8') && digits.length >= 9) {
                    digits = '0' + digits; // Mengoreksi ucapan "delapan satu..." menjadi "081..."
                }
                return digits || clean;
            } else if (type === 'number') {
                let digits = lower.replace(/\D/g, '');
                if (digits.length === 1 && (activeTarget?.id === 'rt' || activeTarget?.id === 'rw')) {
                    digits = '0' + digits; // Format RT/RW 2 digit "01"
                }
                return digits || clean;
            }
        }
        
        // Bersihkan tanda baca dan koreksi pertukaran B/W
        return correctLabialConfusions(clean.replace(/[.,]$/, ''));
    }

    if (SpeechRecognition) {
        recognition = new SpeechRecognition();
        recognition.lang = 'id-ID'; // Bahasa Indonesia
        recognition.continuous = false;
        recognition.interimResults = true; // Feedback lisan real-time
        recognition.maxAlternatives = 10;   // Evaluasi hingga 10 hipotesis kandidat

        recognition.onstart = function() {
            isListening = true;
            if (activeBtn) activeBtn.classList.add('listening');
            if (activeTarget) {
                originalPlaceholder = activeTarget.placeholder || '';
                activeTarget.placeholder = 'Mendengarkan suara Anda...';
            }
        };

        recognition.onresult = function(event) {
            let interimTranscript = '';
            let finalAlternatives = [];

            for (let i = event.resultIndex; i < event.results.length; ++i) {
                if (event.results[i].isFinal) {
                    for (let j = 0; j < event.results[i].length; j++) {
                        finalAlternatives.push(event.results[i][j].transcript.trim());
                    }
                } else {
                    interimTranscript += event.results[i][0].transcript;
                }
            }

            // Tampilkan progres lisan sementara
            if (interimTranscript && activeTarget) {
                activeTarget.placeholder = 'Mendengar: "' + interimTranscript + '"...';
            }

            if (finalAlternatives.length > 0 && activeTarget) {
                isListening = false;
                const type = activeBtn?.dataset?.type || 'text';
                const isDesa = activeBtn?.dataset?.isDesa === 'true';

                if (isDesa) {
                    const list = window.KOPERASI_DESA_LIST || [];
                    let bestMatch = null;
                    let highestScore = 0;
                    let bestTranscript = finalAlternatives[0];

                    // Evaluasi seluruh alternatif terhadap seluruh daftar desa
                    for (const candidateText of finalAlternatives) {
                        for (const item of list) {
                            const score = getSimilarity(candidateText, item.desa);
                            if (score > highestScore) {
                                highestScore = score;
                                bestMatch = item;
                                bestTranscript = candidateText;
                            }
                        }
                    }

                    // Jika tingkat kemiripan >= 65%, pilih desa tersebut
                    if (bestMatch && highestScore >= 0.65) {
                        selectDesa(bestMatch.desa);
                    } else {
                        // Tampilkan transkrip terbaik dan buka dropdown dengan pencarian
                        activeTarget.value = parseSpokenWords(bestTranscript, 'text');
                        renderDesaDropdown(activeTarget.value);
                    }
                } else {
                    const parsedValue = parseSpokenWords(finalAlternatives[0], type);
                    activeTarget.value = parsedValue;
                    activeTarget.dispatchEvent(new Event('input', { bubbles: true }));
                    activeTarget.dispatchEvent(new Event('change', { bubbles: true }));
                }

                resetActiveMic();
            }
        };

        recognition.onerror = function(event) {
            isListening = false;
            resetActiveMic();
        };

        recognition.onend = function() {
            isListening = false;
            resetActiveMic();
        };

        function resetActiveMic() {
            if (activeBtn) activeBtn.classList.remove('listening');
            if (activeTarget && originalPlaceholder) {
                activeTarget.placeholder = originalPlaceholder;
            }
            activeBtn = null;
            activeTarget = null;
        }

        document.querySelectorAll('.mic-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();

                const targetId = this.dataset.target;
                const targetEl = document.getElementById(targetId);
                if (!targetEl) return;

                if (isListening && activeBtn === this) {
                    recognition.stop();
                    resetActiveMic();
                } else {
                    if (isListening) {
                        recognition.stop();
                        resetActiveMic();
                    }
                    activeBtn = this;
                    activeTarget = targetEl;
                    try {
                        recognition.start();
                    } catch (err) {
                        resetActiveMic();
                    }
                }
            });
        });
    }
});

// Simpan Langsung ke Backend (Tanpa Verifikasi Lokasi)
function submitSurvei() {
    const nama = document.getElementById('nama').value.trim();
    const nomorHp = document.getElementById('nomor_hp').value.trim();
    const umur = document.getElementById('umur').value.trim();
    const jk = document.getElementById('jenis_kelamin').value;
    const desa = document.getElementById('desa').value.trim();

    if (!desa) {
        alert('Harap isi Desa/Kelurahan.');
        document.getElementById('desa').focus();
        return;
    }

    const btn = document.getElementById('btn-submit-survei');
    btn.disabled = true;
    btn.innerText = 'Menyimpan...';

    const payload = {
        nama: nama,
        nomor_hp: nomorHp,
        umur: umur,
        jenis_kelamin: jk,
        desa: desa,
        kecamatan: document.getElementById('kecamatan').value.trim(),
        kabupaten: document.getElementById('kabupaten').value.trim(),
        provinsi: document.getElementById('provinsi').value.trim(),
        dusun: document.getElementById('dusun').value.trim(),
        rt: document.getElementById('rt').value.trim(),
        rw: document.getElementById('rw').value.trim(),
        nama_jalan: document.getElementById('nama_jalan').value.trim(),
        nomor_rumah: document.getElementById('nomor_rumah').value.trim(),
        detail_alamat: document.getElementById('detail_alamat').value.trim()
    };

    fetch(window.APP_SUBMIT_URL, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json'
        },
        body: JSON.stringify(payload)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success && data.redirect_url) {
            window.location.href = data.redirect_url;
        } else {
            alert('Gagal menyimpan: ' + (data.message || 'Terjadi kesalahan'));
            btn.disabled = false;
            btn.innerText = 'Simpan & Lanjutkan';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Terjadi kesalahan jaringan saat menyimpan.');
        btn.disabled = false;
        btn.innerText = 'Simpan & Lanjutkan';
    });
}
</script>
</body>
</html>

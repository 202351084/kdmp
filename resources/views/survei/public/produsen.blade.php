<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Survei Produsen — {{ $sesi->wilayah->nama ?? 'Desa' }}</title>
    <meta name="description" content="Form survei potensi produksi desa untuk Kelompok Tani dan Pelaku Ekraf">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        *, *::before, *::after { box-sizing: border-box; }
        body {
            font-family: 'Inter', ui-sans-serif, system-ui, sans-serif;
            background: linear-gradient(135deg, #f0fdf4 0%, #ffffff 50%, #ecfdf5 100%);
            min-height: 100vh;
            margin: 0;
            color: #1c211a;
            -webkit-font-smoothing: antialiased;
        }

        /* ── Layout ── */
        .page-header {
            background: linear-gradient(135deg, #15803d 0%, #059669 100%);
            color: #fff;
            padding: 2rem 1rem 3rem;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        .page-header::before {
            content: '';
            position: absolute;
            right: -60px; top: -60px;
            width: 200px; height: 200px;
            border-radius: 50%;
            background: rgba(255,255,255,0.06);
        }
        .page-header::after {
            content: '';
            position: absolute;
            left: -40px; bottom: -40px;
            width: 160px; height: 160px;
            border-radius: 50%;
            background: rgba(255,255,255,0.05);
        }
        .header-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 56px; height: 56px;
            border-radius: 16px;
            background: rgba(255,255,255,0.2);
            margin-bottom: 1rem;
        }
        .header-title {
            font-size: clamp(1.25rem, 5vw, 1.75rem);
            font-weight: 700;
            letter-spacing: -0.02em;
            margin: 0 0 0.5rem;
        }
        .header-sub {
            font-size: 0.85rem;
            color: rgba(255,255,255,0.85);
            margin: 0;
        }
        .header-badge {
            display: inline-block;
            margin-top: 0.75rem;
            font-size: 0.7rem;
            color: rgba(255,255,255,0.7);
            border: 1px solid rgba(255,255,255,0.3);
            padding: 0.2rem 0.75rem;
            border-radius: 999px;
        }

        /* ── Cards ── */
        .main-content {
            max-width: 640px;
            margin: -1.5rem auto 0;
            padding: 0 1rem 3rem;
            position: relative;
            z-index: 10;
        }
        .card {
            background: rgba(255,255,255,0.92);
            border: 1px solid rgba(255,255,255,0.4);
            border-radius: 16px;
            box-shadow: 0 4px 24px rgba(0,0,0,0.08);
            margin-bottom: 1rem;
            overflow: hidden;
            backdrop-filter: blur(12px);
        }
        .card-header {
            padding: 1rem 1.25rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        .step-badge {
            width: 28px; height: 28px;
            border-radius: 50%;
            background: rgba(255,255,255,0.25);
            color: #fff;
            font-size: 0.8rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .card-header-text { flex: 1; }
        .card-header-title { font-size: 0.95rem; font-weight: 700; color: #fff; margin: 0; }
        .card-header-sub { font-size: 0.72rem; color: rgba(255,255,255,0.8); margin: 0.1rem 0 0; }
        .card-body { padding: 1.25rem; }
        .card-body + .card-body { border-top: 1px solid #ebe8de; }

        /* ── Form Fields ── */
        .field { margin-bottom: 1rem; }
        .field:last-child { margin-bottom: 0; }
        .field-label {
            display: block;
            font-size: 0.7rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: #454f3d;
            margin-bottom: 0.4rem;
        }
        .required { color: #c1483a; }

        /* Input wrapper: input + mic button */
        .input-wrap {
            position: relative;
            display: flex;
            align-items: stretch;
        }
        .input-wrap input,
        .input-wrap textarea,
        .input-wrap select {
            width: 100%;
            padding: 0.65rem 3rem 0.65rem 0.875rem;
            border: 1.5px solid #ddd9cb;
            border-radius: 10px;
            font-size: 0.9rem;
            font-family: inherit;
            background: #fff;
            color: #1c211a;
            transition: border-color 0.15s;
            appearance: none;
            -webkit-appearance: none;
        }
        .input-wrap select {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%23454f3d'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 2.5rem center;
            background-size: 1rem;
            padding-right: 4rem;
        }
        /* select without mic (no x-data speechInput) */
        .input-wrap.no-mic select,
        .input-wrap.no-mic input {
            padding-right: 0.875rem;
            background-position: right 0.75rem center;
        }
        .input-wrap input:focus,
        .input-wrap textarea:focus,
        .input-wrap select:focus {
            outline: none;
            border-color: #15803d;
            box-shadow: 0 0 0 3px rgba(21,128,61,0.12);
        }
        .input-wrap textarea {
            resize: none;
            min-height: 72px;
        }
        /* Number inputs: bold */
        .input-wrap input[type="number"] {
            font-weight: 700;
            font-size: 1rem;
        }

        /* Mic button — absolute, perfectly centered vertically */
        .mic-btn {
            position: absolute;
            right: 0.4rem;
            top: 50%;
            transform: translateY(-50%);
            width: 36px;
            height: 36px;
            border-radius: 8px;
            border: none;
            background: transparent;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #888;
            transition: background 0.15s, color 0.15s;
            padding: 0;
            flex-shrink: 0;
        }
        .mic-btn:hover { background: #f0fdf4; color: #15803d; }
        .mic-btn.listening {
            background: #fef2f2;
            color: #dc2626;
            animation: pulse-red 1s infinite;
        }
        @keyframes pulse-red {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.6; }
        }
        /* For textarea: mic is top-aligned */
        .input-wrap.textarea-wrap .mic-btn {
            top: 0.4rem;
            transform: none;
        }

        /* Two-column grid */
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; }
        @media (max-width: 400px) { .grid-2 { grid-template-columns: 1fr; } }

        /* ── Suggestion chips ── */
        .chips {
            display: flex;
            flex-wrap: wrap;
            gap: 0.4rem;
            margin-bottom: 0.75rem;
        }
        .chip {
            padding: 0.3rem 0.75rem;
            border-radius: 999px;
            border: 1.5px solid #bbf7d0;
            background: #f0fdf4;
            color: #15803d;
            font-size: 0.75rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.15s;
        }
        .chip:hover { background: #dcfce7; border-color: #4ade80; }
        .chips-label {
            font-size: 0.68rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: #454f3d;
            margin-bottom: 0.4rem;
        }

        /* ── Add-to-cart button ── */
        .btn-add {
            width: 100%;
            padding: 0.8rem 1rem;
            border-radius: 10px;
            border: 2px dashed #4ade80;
            background: #f0fdf4;
            color: #15803d;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.4rem;
            transition: all 0.15s;
            font-family: inherit;
        }
        .btn-add:hover { background: #dcfce7; border-color: #22c55e; }

        /* ── Cart items ── */
        .cart-item {
            padding: 1rem 1.25rem;
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
            border-bottom: 1px solid #f5f4ee;
        }
        .cart-item:last-child { border-bottom: none; }
        .cart-item-info { flex: 1; min-width: 0; }
        .cart-item-name {
            font-size: 0.95rem;
            font-weight: 700;
            color: #1c211a;
            margin: 0 0 0.2rem;
        }
        .cart-item-meta {
            font-size: 0.78rem;
            color: #454f3d;
            margin: 0;
        }
        .cart-badge {
            display: inline-block;
            font-size: 0.65rem;
            font-weight: 600;
            padding: 0.1rem 0.5rem;
            border-radius: 999px;
            margin-left: 0.4rem;
        }
        .cart-badge-tani { background: #dcfce7; color: #15803d; }
        .cart-badge-ekraf { background: #f3e8ff; color: #7c3aed; }
        .btn-del {
            width: 34px;
            height: 34px;
            border-radius: 8px;
            border: none;
            background: #fff1f2;
            color: #dc2626;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            transition: background 0.15s;
        }
        .btn-del:hover { background: #fee2e2; }
        .btn-edit {
            width: 34px;
            height: 34px;
            border-radius: 8px;
            border: none;
            background: #f0fdf4;
            color: #15803d;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            transition: background 0.15s;
        }
        .btn-edit:hover { background: #dcfce7; }

        /* ── Toggle Tipe Harga ── */
        .price-toggle {
            display: flex;
            background: #f1efe7;
            padding: 3px;
            border-radius: 10px;
            gap: 3px;
            margin-bottom: 0.5rem;
        }
        .price-toggle-btn {
            flex: 1;
            padding: 0.45rem 0.5rem;
            border: none;
            background: transparent;
            border-radius: 8px;
            font-size: 0.78rem;
            font-weight: 600;
            color: #555;
            cursor: pointer;
            transition: all 0.15s;
            text-align: center;
            font-family: inherit;
        }
        .price-toggle-btn.active {
            background: #fff;
            color: #15803d;
            box-shadow: 0 1px 4px rgba(0,0,0,0.08);
        }
        .calc-box {
            margin-top: 0.5rem;
            padding: 0.65rem 0.85rem;
            border-radius: 10px;
            font-size: 0.78rem;
            display: flex;
            align-items: flex-start;
            gap: 0.5rem;
            line-height: 1.4;
        }
        .calc-box-green {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            color: #166534;
        }
        .calc-box-blue {
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            color: #1e40af;
        }
        .calc-val {
            font-weight: 700;
            font-size: 0.88rem;
        }

        /* ── Edit Modal ── */
        .modal-overlay {
            position: fixed; inset: 0; z-index: 100;
            background: rgba(0,0,0,0.5);
            display: flex; align-items: flex-end; justify-content: center;
            padding: 0;
        }
        @media (min-width: 480px) {
            .modal-overlay { align-items: center; padding: 1rem; }
        }
        .modal-box {
            background: #fff;
            border-radius: 20px 20px 0 0;
            width: 100%;
            max-width: 600px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 -8px 40px rgba(0,0,0,0.15);
        }
        @media (min-width: 480px) {
            .modal-box { border-radius: 16px; }
        }
        .modal-header {
            display: flex; align-items: center; justify-content: space-between;
            padding: 1.1rem 1.25rem;
            border-bottom: 1px solid #ebe8de;
            position: sticky; top: 0; background: #fff; z-index: 1;
        }
        .modal-title { font-size: 1rem; font-weight: 700; color: #1c211a; margin: 0; }
        .modal-close {
            width: 32px; height: 32px; border-radius: 8px; border: none;
            background: #f5f4ee; color: #454f3d; cursor: pointer;
            display: flex; align-items: center; justify-content: center;
        }
        .modal-body { padding: 1.25rem; }
        .modal-footer {
            padding: 1rem 1.25rem;
            border-top: 1px solid #ebe8de;
            display: flex; gap: 0.75rem;
        }
        .btn-cancel {
            flex: 1; padding: 0.75rem; border-radius: 10px;
            border: 1.5px solid #ddd9cb; background: #fff;
            color: #454f3d; font-size: 0.9rem; font-weight: 600;
            cursor: pointer; font-family: inherit;
        }
        .btn-save {
            flex: 2; padding: 0.75rem; border-radius: 10px;
            border: none; background: linear-gradient(135deg,#15803d,#059669);
            color: #fff; font-size: 0.9rem; font-weight: 700;
            cursor: pointer; font-family: inherit;
            box-shadow: 0 3px 10px rgba(21,128,61,0.3);
        }

        /* ── Submit button ── */
        .btn-submit {
            width: 100%;
            padding: 1rem 1.5rem;
            border-radius: 12px;
            border: none;
            background: linear-gradient(135deg, #15803d 0%, #059669 100%);
            color: #fff;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            font-family: inherit;
            letter-spacing: 0.01em;
            box-shadow: 0 4px 16px rgba(21,128,61,0.35);
            transition: all 0.15s;
        }
        .btn-submit:hover {
            background: linear-gradient(135deg, #166534 0%, #047857 100%);
            box-shadow: 0 6px 20px rgba(21,128,61,0.45);
            transform: translateY(-1px);
        }
        .btn-submit:active { transform: translateY(0); box-shadow: none; }

        /* ── Alerts ── */
        .alert {
            border-radius: 12px;
            padding: 0.875rem 1rem;
            margin-bottom: 1rem;
            display: flex;
            gap: 0.6rem;
            align-items: flex-start;
            font-size: 0.85rem;
        }
        .alert-success { background: #f0fdf4; border: 1.5px solid #86efac; color: #15803d; }
        .alert-error { background: #fff1f2; border: 1.5px solid #fca5a5; color: #b91c1c; }
        .alert svg { flex-shrink: 0; width: 18px; height: 18px; margin-top: 1px; }
        .alert-error-list { list-style: disc; padding-left: 1.2rem; margin: 0.25rem 0 0; }

        /* ── Error msg ── */
        .err-msg {
            font-size: 0.8rem;
            color: #dc2626;
            font-weight: 600;
            text-align: center;
            margin-top: 0.5rem;
        }

        /* ── Footer ── */
        .footer {
            text-align: center;
            font-size: 0.72rem;
            color: #888;
            padding: 0 1rem 2rem;
        }

        /* ── Cart count badge in card header ── */
        .count-badge {
            background: rgba(255,255,255,0.25);
            color: #fff;
            font-size: 0.75rem;
            font-weight: 700;
            padding: 0.1rem 0.6rem;
            border-radius: 999px;
            margin-left: auto;
        }

        [x-cloak] { display: none !important; }
    </style>
</head>
<body x-data="produsenSurvei()">

    {{-- ══ HEADER ══ --}}
    <div class="page-header">
        <div style="position:relative;z-index:1;">
            <div class="header-icon">
                <svg width="28" height="28" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <h1 class="header-title">Survei Potensi Produksi Desa</h1>
            <p class="header-sub">
                Desa <strong>{{ $sesi->wilayah->nama ?? '-' }}</strong>
                &bull; {{ \Carbon\Carbon::createFromFormat('m', $sesi->bulan)->translatedFormat('F') }} {{ $sesi->tahun }}
            </p>

        </div>
    </div>

    {{-- ══ CONTENT ══ --}}
    <div class="main-content">

        {{-- Alerts --}}
        @if(session('success'))
            <div class="alert alert-success">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span>{{ session('success') }}</span>
            </div>
        @endif
        @if($errors->any())
            <div class="alert alert-error">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <div>
                    <div style="font-weight:600;">Ada kesalahan:</div>
                    <ul class="alert-error-list">
                        @foreach($errors->all() as $err)<li>{{ $err }}</li>@endforeach
                    </ul>
                </div>
            </div>
        @endif

        {{-- ══ STEP 1 — Identitas ══ --}}
        <div class="card">
            <div class="card-header" style="background:linear-gradient(135deg,#15803d,#059669);">
                <div class="step-badge">1</div>
                <div class="card-header-text">
                    <p class="card-header-title">Identitas Responden</p>
                </div>
            </div>
            <div class="card-body">
                {{-- Nama --}}
                <div class="field" x-data="micInput(() => namaResponden, v => namaResponden = v, 'name')">
                    <label class="field-label">Nama Lengkap Anda <span class="required">*</span></label>
                    <div class="input-wrap">
                        <input type="text" x-ref="el"
                               :value="namaResponden"
                               @input="namaResponden = $event.target.value"
                               placeholder="Ketik atau ucapkan nama Anda...">
                        <button type="button" class="mic-btn" :class="{ listening: isListening }" @click="toggle()" title="Suara">
                            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"/>
                            </svg>
                        </button>
                    </div>
                </div>

                {{-- Kategori + Sub --}}
                <div class="grid-2">
                    <div class="field">
                        <label class="field-label">Kategori <span class="required">*</span></label>
                        <div class="input-wrap no-mic">
                            <select x-model="kategori" @change="subKategori=''">
                                <option value="">-- Pilih --</option>
                                <option value="Kelompok Tani">🌾 Kelompok Tani</option>
                                <option value="Ekraf">🎨 Ekraf</option>
                            </select>
                        </div>
                    </div>
                    <div class="field" x-show="kategori" x-cloak>
                        <label class="field-label">Sub Kategori</label>
                        <div class="input-wrap no-mic">
                            <select x-model="subKategori">
                                <option value="">-- Pilih --</option>
                                <template x-if="kategori === 'Kelompok Tani'">
                                    <optgroup label="Kelompok Tani">
                                        <option value="Pertanian">🌾 Pertanian</option>
                                        <option value="Perkebunan">🌴 Perkebunan</option>
                                        <option value="Peternakan">🐄 Peternakan</option>
                                        <option value="Kelautan">🌊 Kelautan</option>
                                    </optgroup>
                                </template>
                                <template x-if="kategori === 'Ekraf'">
                                    <optgroup label="Ekonomi Kreatif">
                                        <option value="Pangan">🍱 Pangan</option>
                                        <option value="Non Pangan">🧶 Non Pangan</option>
                                    </optgroup>
                                </template>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ══ STEP 2 — Tambah Komoditas ══ --}}
        <div class="card">
            <div class="card-header" style="background:linear-gradient(135deg,#0d7f54,#047857);">
                <div class="step-badge">2</div>
                <div class="card-header-text">
                    <p class="card-header-title">Tambah Komoditas</p>
                </div>
            </div>
            <div class="card-body">

                {{-- Saran chips --}}
                <div x-show="pilihanSaran.length > 0" x-cloak>
                    <div class="chips-label">Komoditas umum (ketuk untuk pilih):</div>
                    <div class="chips">
                        <template x-for="s in pilihanSaran" :key="s">
                            <button type="button" class="chip" @click="namaKmd = s" x-text="s"></button>
                        </template>
                    </div>
                </div>

                {{-- Nama komoditas --}}
                <div class="field" x-data="micInput(() => namaKmd, v => namaKmd = v, 'komoditas', (qty, unit) => { if(qty) jumlah = qty; if(unit) satuan = unit; })">
                    <label class="field-label">Nama Komoditas / Produk <span class="required">*</span></label>
                    <div class="input-wrap">
                        <input type="text" x-ref="el"
                               :value="namaKmd"
                               @input="namaKmd = $event.target.value"
                               placeholder="Beras, Kopi, Anyaman...">
                        <button type="button" class="mic-btn" :class="{ listening: isListening }" @click="toggle()" title="Suara">
                            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"/>
                            </svg>
                        </button>
                    </div>
                </div>

                {{-- Jumlah + Satuan --}}
                <div class="grid-2">
                    <div class="field" x-data="micInput(() => jumlah, v => jumlah = v, 'number', (unit) => { if(unit) satuan = unit; })">
                        <label class="field-label">Jumlah Produksi</label>
                        <div class="input-wrap">
                            <input type="number" x-ref="el"
                                   :value="jumlah"
                                   @input="jumlah = $event.target.value"
                                   min="0" step="0.01" placeholder="0">
                            <button type="button" class="mic-btn" :class="{ listening: isListening }" @click="toggle()" title="Suara">
                                <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                    <div class="field">
                        <label class="field-label">Satuan</label>
                        <div class="input-wrap no-mic">
                            <select x-model="satuan">
                                <option value="">-- Pilih --</option>
                                @foreach($satuanList as $sat)
                                    <option value="{{ $sat }}">{{ $sat }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                {{-- Frekuensi Panen per Tahun (terisi otomatis dari acuan tetap) --}}
                <div class="field">
                    <label class="field-label">Frekuensi Panen per Tahun (kali)</label>
                    <div class="input-wrap no-mic">
                        <input type="number" min="0" max="365" step="1"
                               x-model="frekuensi"
                               @input="frekManual = true"
                               placeholder="Terisi otomatis sesuai komoditas">
                    </div>
                    <div class="calc-box calc-box-green" x-show="infoFrekuensi" x-cloak>
                        <span style="font-size:1rem;">🌾</span>
                        <div>
                            <div>
                                Acuan <strong x-text="infoFrekuensi && infoFrekuensi.item"></strong>:
                                <span class="calc-val" x-text="infoFrekuensi && (infoFrekuensi.rentang + ' siklus/tahun')"></span>
                            </div>
                            <div style="font-size:0.72rem;margin-top:0.15rem;"
                                 x-text="infoFrekuensi && ('Durasi satu siklus ' + infoFrekuensi.durasi + ' · 365 ÷ durasi · sumber: ' + infoFrekuensi.sumber)"></div>
                            <div style="font-size:0.72rem;margin-top:0.15rem;">Angka otomatis memakai batas bawah rentang, dan bisa Anda ubah sesuai kondisi di lapangan.</div>
                        </div>
                    </div>
                    <p style="font-size:0.72rem;color:#888;margin-top:0.25rem;"
                       x-show="!infoFrekuensi && namaKmd.trim()" x-cloak>
                        Belum ada acuan frekuensi untuk komoditas ini. Isi manual jika Anda tahu berapa kali panen dalam setahun.
                    </p>
                </div>

                {{-- Harga / Nilai Jual --}}
                <div class="field">
                    <label class="field-label">Perkiraan Harga / Nilai Jual (Opsional)</label>

                    {{-- Toggle Pilihan Satuan vs Total --}}
                    <div class="price-toggle">
                        <button type="button" class="price-toggle-btn"
                                :class="{ active: tipeHarga === 'Satuan' }"
                                @click="setTipeHarga('Satuan')">
                            🏷️ Harga per Satuan
                        </button>
                        <button type="button" class="price-toggle-btn"
                                :class="{ active: tipeHarga === 'Total' }"
                                @click="setTipeHarga('Total')">
                            💰 Total Nominal Jual
                        </button>
                    </div>

                    <div class="input-wrap" x-data="micInput(() => harga, v => harga = v, 'number')">
                        <input type="number" x-ref="el"
                               :value="harga"
                               @input="harga = $event.target.value"
                               min="0" step="any"
                               :placeholder="tipeHarga === 'Satuan' ? 'Nominal per ' + (satuan || 'satuan') + ' (cth: 15000)' : 'Total penjualan seluruhnya (cth: 1500000)'">
                        <button type="button" class="mic-btn" :class="{ listening: isListening }" @click="toggle()" title="Suara">
                            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"/>
                            </svg>
                        </button>
                    </div>

                    {{-- Kotak Kalkulasi Otomatis --}}
                    <div x-show="harga && parseFloat(harga) > 0" x-cloak>
                        <template x-if="tipeHarga === 'Satuan'">
                            <div class="calc-box calc-box-green">
                                <span style="font-size:1rem;">🧮</span>
                                <div>
                                    <template x-if="jumlah && parseFloat(jumlah) > 0">
                                        <div>
                                            <span>Estimasi Total Penjualan: </span>
                                            <span class="calc-val" x-text="formatRupiah(parseFloat(harga) * parseFloat(jumlah))"></span>
                                            <div style="font-size:0.72rem;color:#15803d;margin-top:0.15rem;"
                                                 x-text="'(' + jumlah + ' ' + (satuan || 'satuan') + ' × ' + formatRupiah(harga) + ')'"></div>
                                        </div>
                                    </template>
                                    <template x-if="!jumlah || parseFloat(jumlah) <= 0">
                                        <div style="font-size:0.75rem;color:#166534;">
                                            💡 Masukkan <strong>Jumlah Produksi</strong> di atas untuk melihat perkalian total penjualan secara otomatis.
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </template>
                        <template x-if="tipeHarga === 'Total'">
                            <div class="calc-box calc-box-blue">
                                <span style="font-size:1rem;">🧮</span>
                                <div>
                                    <template x-if="jumlah && parseFloat(jumlah) > 0">
                                        <div>
                                            <span>Estimasi Harga per Satuan: </span>
                                            <span class="calc-val" x-text="formatRupiah(Math.round(parseFloat(harga) / parseFloat(jumlah))) + ' / ' + (satuan || 'satuan')"></span>
                                            <div style="font-size:0.72rem;color:#1d4ed8;margin-top:0.15rem;"
                                                 x-text="'(' + formatRupiah(harga) + ' ÷ ' + jumlah + ' ' + (satuan || 'satuan') + ')'"></div>
                                        </div>
                                    </template>
                                    <template x-if="!jumlah || parseFloat(jumlah) <= 0">
                                        <div style="font-size:0.75rem;color:#1e40af;">
                                            💡 Masukkan <strong>Jumlah Produksi</strong> di atas untuk menghitung pembagian harga per satuan.
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                {{-- Bulan --}}
                <div class="field">
                    <label class="field-label">Bulan Produksi <span class="required">*</span></label>
                    <div class="input-wrap no-mic">
                        <select x-model="bulan">
                            <option value="">-- Pilih Bulan --</option>
                            @foreach($bulans as $no => $nama)
                                <option value="{{ $no }}" {{ $sesi->bulan == $no ? 'selected' : '' }}>{{ $nama }} {{ $sesi->tahun }}</option>
                            @endforeach
                        </select>
                    </div>
                    <p style="font-size:0.72rem;color:#888;margin-top:0.25rem;">Pilih bulan panen atau bulan produksi komoditas ini.</p>
                </div>

                {{-- Tombol tambah ke daftar --}}
                <button type="button" class="btn-add" @click="tambah()">
                    <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Tambah ke Daftar
                </button>
                <p class="err-msg" x-show="errMsg" x-text="errMsg" x-cloak></p>
            </div>
        </div>

        {{-- ══ STEP 3 — Daftar & Kirim ══ --}}
        <div class="card" x-show="cart.length > 0" x-cloak>
            <div class="card-header" style="background:linear-gradient(135deg,#064e3b,#065f46);">
                <div class="step-badge">3</div>
                <div class="card-header-text">
                    <p class="card-header-title">Daftar Komoditas</p>
                </div>
                <span class="count-badge" x-text="cart.length + ' item'"></span>
            </div>

            {{-- Cart items --}}
            <template x-for="(item, i) in cart" :key="i">
                <div class="cart-item">
                    <div class="cart-item-info">
                        <p class="cart-item-name">
                            <span x-text="item.nama_komoditas"></span>
                            <span class="cart-badge" :class="item.kategori === 'Kelompok Tani' ? 'cart-badge-tani' : 'cart-badge-ekraf'"
                                  x-show="item.sub_kategori" x-text="item.sub_kategori"></span>
                        </p>
                        <p class="cart-item-meta">
                            <span x-show="item.jumlah_produksi">
                                <span x-text="item.jumlah_produksi"></span> <span x-text="item.satuan"></span> &bull;
                            </span>
                            <span x-show="item.harga && parseFloat(item.harga) > 0">
                                <template x-if="item.tipe_harga === 'Satuan'">
                                    <span>
                                        <strong x-text="formatRupiah(item.harga) + ' / ' + (item.satuan || 'satuan')"></strong>
                                        <template x-if="item.jumlah_produksi && parseFloat(item.jumlah_produksi) > 0">
                                            <span style="color:#15803d;font-weight:600;" x-text="'(Total ' + formatRupiah(parseFloat(item.harga) * parseFloat(item.jumlah_produksi)) + ')'"></span>
                                        </template>
                                        &bull;
                                    </span>
                                </template>
                                <template x-if="item.tipe_harga === 'Total'">
                                    <span>
                                        <strong x-text="'Total ' + formatRupiah(item.harga)"></strong>
                                        <template x-if="item.jumlah_produksi && parseFloat(item.jumlah_produksi) > 0">
                                            <span style="color:#1d4ed8;font-weight:600;" x-text="'(' + formatRupiah(Math.round(parseFloat(item.harga) / parseFloat(item.jumlah_produksi))) + ' / ' + (item.satuan || 'satuan') + ')'"></span>
                                        </template>
                                        &bull;
                                    </span>
                                </template>
                            </span>
                            Bulan <span x-text="namaBulan(item.bulan_produksi)"></span>
                            <span x-show="item.frekuensi"> &bull; <span x-text="item.frekuensi"></span>× panen/tahun</span>
                        </p>
                    </div>
                    <div style="display:flex;gap:0.4rem;flex-shrink:0;">
                        <button type="button" class="btn-edit" @click="editItem(i)" title="Edit">
                            <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                        </button>
                        <button type="button" class="btn-del" @click="cart.splice(i,1)" title="Hapus">
                            <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </template>

            {{-- Submit --}}
            <div style="padding:1.25rem;border-top:1px solid #ebe8de;">
                <button type="button" class="btn-submit" @click="submitAll()">
                    <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                              d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                    Kirim Semua Data
                    <span style="background:rgba(255,255,255,0.2);border-radius:999px;padding:0.1rem 0.5rem;font-size:0.8rem;" x-text="'(' + cart.length + ' Komoditas)'"></span>
                </button>
                <p style="text-align:center;font-size:0.72rem;color:#888;margin-top:0.6rem;">
                    Data akan dicatat ke sistem survei desa.
                </p>
            </div>
        </div>

        <div class="footer">&copy; {{ date('Y') }} Kader Desa Merdeka Pangan</div>
    </div>

    {{-- ══ MODAL EDIT KOMODITAS ══ --}}
    <div class="modal-overlay" x-show="showEditModal" x-cloak @click.self="showEditModal = false"
         style="animation: none;">
        <div class="modal-box">
            <div class="modal-header">
                <p class="modal-title">Edit Komoditas</p>
                <button class="modal-close" @click="showEditModal = false" type="button">
                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <div class="modal-body" style="display:flex;flex-direction:column;gap:0.875rem;">
                {{-- Nama Komoditas --}}
                <div class="field" style="margin-bottom:0;" x-data="micInput(() => editForm.nama_komoditas, v => editForm.nama_komoditas = v, 'komoditas')">
                    <label class="field-label">Nama Komoditas <span class="required">*</span></label>
                    <div class="input-wrap">
                        <input type="text" x-ref="el" x-model="editForm.nama_komoditas" placeholder="Nama komoditas...">
                        <button type="button" class="mic-btn" :class="{ listening: isListening }" @click="toggle()" title="Suara">
                            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"/>
                            </svg>
                        </button>
                    </div>
                </div>
                {{-- Kategori + Sub --}}
                <div class="grid-2">
                    <div class="field" style="margin-bottom:0;">
                        <label class="field-label">Kategori <span class="required">*</span></label>
                        <div class="input-wrap no-mic">
                            <select x-model="editForm.kategori" @change="editForm.sub_kategori=''">
                                <option value="">-- Pilih --</option>
                                <option value="Kelompok Tani">🌾 Kelompok Tani</option>
                                <option value="Ekraf">🎨 Ekraf</option>
                            </select>
                        </div>
                    </div>
                    <div class="field" style="margin-bottom:0;">
                        <label class="field-label">Sub Kategori</label>
                        <div class="input-wrap no-mic">
                            <select x-model="editForm.sub_kategori">
                                <option value="">-- Pilih --</option>
                                <template x-if="editForm.kategori === 'Kelompok Tani'">
                                    <optgroup label="Kelompok Tani">
                                        <option value="Pertanian">🌾 Pertanian</option>
                                        <option value="Perkebunan">🌴 Perkebunan</option>
                                        <option value="Peternakan">🐄 Peternakan</option>
                                        <option value="Kelautan">🌊 Kelautan</option>
                                    </optgroup>
                                </template>
                                <template x-if="editForm.kategori === 'Ekraf'">
                                    <optgroup label="Ekonomi Kreatif">
                                        <option value="Pangan">🍱 Pangan</option>
                                        <option value="Non Pangan">🧶 Non Pangan</option>
                                    </optgroup>
                                </template>
                            </select>
                        </div>
                    </div>
                </div>
                {{-- Jumlah + Satuan --}}
                <div class="grid-2">
                    <div class="field" style="margin-bottom:0;" x-data="micInput(() => editForm.jumlah_produksi, v => editForm.jumlah_produksi = v, 'number', (unit) => { if(unit) editForm.satuan = unit; })">
                        <label class="field-label">Jumlah Produksi</label>
                        <div class="input-wrap">
                            <input type="number" x-ref="el" x-model="editForm.jumlah_produksi"
                                   min="0" step="0.01" placeholder="0">
                            <button type="button" class="mic-btn" :class="{ listening: isListening }" @click="toggle()" title="Suara">
                                <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                    <div class="field" style="margin-bottom:0;">
                        <label class="field-label">Satuan</label>
                        <div class="input-wrap no-mic">
                            <select x-model="editForm.satuan">
                                <option value="">-- Pilih --</option>
                                <template x-for="s in SATUAN_LIST" :key="s">
                                    <option :value="s" x-text="s"></option>
                                </template>
                            </select>
                        </div>
                    </div>
                </div>
                {{-- Frekuensi Panen per Tahun (Modal Edit) --}}
                <div class="field" style="margin-bottom:0;">
                    <label class="field-label">Frekuensi Panen per Tahun (kali)</label>
                    <div class="input-wrap no-mic">
                        <input type="number" min="0" max="365" step="1" x-model="editForm.frekuensi"
                               placeholder="Jumlah panen dalam setahun">
                    </div>
                </div>
                {{-- Harga (Modal Edit) --}}
                <div class="field" style="margin-bottom:0;">
                    <label class="field-label">Harga / Nilai Jual</label>
                    <div class="price-toggle">
                        <button type="button" class="price-toggle-btn"
                                :class="{ active: editForm.tipe_harga === 'Satuan' }"
                                @click="setEditTipeHarga('Satuan')">
                            🏷️ Harga per Satuan
                        </button>
                        <button type="button" class="price-toggle-btn"
                                :class="{ active: editForm.tipe_harga === 'Total' }"
                                @click="setEditTipeHarga('Total')">
                            💰 Total Nominal Jual
                        </button>
                    </div>
                    <div class="input-wrap" x-data="micInput(() => editForm.harga, v => editForm.harga = v, 'number')">
                        <input type="number" x-ref="el" x-model="editForm.harga"
                               min="0" step="any"
                               :placeholder="editForm.tipe_harga === 'Satuan' ? 'Nominal per ' + (editForm.satuan || 'satuan') : 'Total nominal jual'">
                        <button type="button" class="mic-btn" :class="{ listening: isListening }" @click="toggle()" title="Suara">
                            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"/>
                            </svg>
                        </button>
                    </div>
                    {{-- Kotak Kalkulasi Otomatis (Modal Edit) --}}
                    <div x-show="editForm.harga && parseFloat(editForm.harga) > 0" x-cloak>
                        <template x-if="editForm.tipe_harga === 'Satuan'">
                            <div class="calc-box calc-box-green">
                                <span>🧮</span>
                                <div style="flex:1;">
                                    <template x-if="editForm.jumlah_produksi && parseFloat(editForm.jumlah_produksi) > 0">
                                        <div>
                                            <span>Estimasi Total Jual: </span>
                                            <span class="calc-val" x-text="formatRupiah(parseFloat(editForm.harga) * parseFloat(editForm.jumlah_produksi))"></span>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </template>
                        <template x-if="editForm.tipe_harga === 'Total'">
                            <div class="calc-box calc-box-blue">
                                <span>🧮</span>
                                <div style="flex:1;">
                                    <template x-if="editForm.jumlah_produksi && parseFloat(editForm.jumlah_produksi) > 0">
                                        <div>
                                            <span>Estimasi Harga per Satuan: </span>
                                            <span class="calc-val" x-text="formatRupiah(Math.round(parseFloat(editForm.harga) / parseFloat(editForm.jumlah_produksi))) + ' / ' + (editForm.satuan || 'satuan')"></span>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
                {{-- Bulan --}}
                <div class="field" style="margin-bottom:0;">
                    <label class="field-label">Bulan Produksi <span class="required">*</span></label>
                    <div class="input-wrap no-mic">
                        <select x-model="editForm.bulan_produksi">
                            <option value="">-- Pilih Bulan --</option>
                            <template x-for="(nama, no) in BULAN_OPTS" :key="no">
                                <option :value="no" x-text="nama + ' {{ $sesi->tahun }}'"></option>
                            </template>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" @click="showEditModal = false">Batal</button>
                <button type="button" class="btn-save" @click="saveEdit()">
                    ✓ Simpan Perubahan
                </button>
            </div>
        </div>
    </div>

    <script>
        const SARAN = @json($saranKomoditas);
        const BULANS = @json($bulans);
        const ACTION = '{{ route('survei.public.produsen.store', $token) }}';
        const CSRF   = '{{ csrf_token() }}';
        const SATUAN_LIST = @json($satuanList);
        const BULAN_OPTS  = @json($bulans);
        // Frekuensi panen per tahun: nama komoditas (huruf kecil) → data acuan tetap (dari server, tanpa DB)
        const FREKUENSI_PANEN = @json($frekuensiPanen);

        function cariFrekuensi(nama) {
            const key = (nama || '').toLowerCase().replace(/\./g, '').replace(/\s+/g, ' ').trim();
            return key && FREKUENSI_PANEN[key] ? FREKUENSI_PANEN[key] : null;
        }

        function produsenSurvei() {
            return {
                namaResponden: '', kategori: '', subKategori: '',
                namaKmd: '', jumlah: '', satuan: '', bulan: '{{ $sesi->bulan }}',
                harga: '', tipeHarga: 'Satuan',
                frekuensi: '', frekManual: false, infoFrekuensi: null,
                cart: [], errMsg: '',

                init() {
                    // Isi frekuensi otomatis setiap nama komoditas berubah (kecuali sudah diubah manual)
                    this.$watch('namaKmd', (val) => {
                        this.infoFrekuensi = cariFrekuensi(val);
                        if (!this.frekManual || !val.trim()) {
                            this.frekuensi = this.infoFrekuensi ? this.infoFrekuensi.frekuensi : '';
                            this.frekManual = false;
                        }
                    });
                },

                // Edit modal state
                showEditModal: false,
                editIdx: null,
                editForm: { nama_komoditas:'', kategori:'', sub_kategori:'', jumlah_produksi:'', satuan:'', harga:'', tipe_harga:'Satuan', bulan_produksi:'', frekuensi:'' },

                formatRupiah(num) {
                    if (num === null || num === undefined || num === '' || isNaN(num)) return 'Rp 0';
                    return 'Rp ' + Math.round(num).toLocaleString('id-ID');
                },

                setTipeHarga(newTipe) {
                    if (this.tipeHarga === newTipe) return;
                    const j = parseFloat(this.jumlah);
                    const h = parseFloat(this.harga);
                    if (h > 0 && j > 0) {
                        if (newTipe === 'Total' && this.tipeHarga === 'Satuan') {
                            this.harga = Math.round(h * j);
                        } else if (newTipe === 'Satuan' && this.tipeHarga === 'Total') {
                            this.harga = Math.round(h / j);
                        }
                    }
                    this.tipeHarga = newTipe;
                },

                setEditTipeHarga(newTipe) {
                    if (this.editForm.tipe_harga === newTipe) return;
                    const j = parseFloat(this.editForm.jumlah_produksi);
                    const h = parseFloat(this.editForm.harga);
                    if (h > 0 && j > 0) {
                        if (newTipe === 'Total' && this.editForm.tipe_harga === 'Satuan') {
                            this.editForm.harga = Math.round(h * j);
                        } else if (newTipe === 'Satuan' && this.editForm.tipe_harga === 'Total') {
                            this.editForm.harga = Math.round(h / j);
                        }
                    }
                    this.editForm.tipe_harga = newTipe;
                },

                get pilihanSaran() {
                    return this.subKategori && SARAN[this.subKategori] ? SARAN[this.subKategori] : [];
                },
                namaBulan(no) { return BULANS[no] || no; },

                editItem(i) {
                    this.editIdx  = i;
                    this.editForm = {
                        ...this.cart[i],
                        harga: this.cart[i].harga || '',
                        tipe_harga: this.cart[i].tipe_harga || 'Satuan'
                    };
                    this.showEditModal = true;
                },
                saveEdit() {
                    if (!this.editForm.nama_komoditas.trim()) { alert('Nama komoditas wajib diisi!'); return; }
                    if (!this.editForm.bulan_produksi) { alert('Pilih bulan produksi!'); return; }
                    this.editForm.nama_komoditas = cap(this.editForm.nama_komoditas.replace(/\./g,''));
                    this.cart[this.editIdx] = { ...this.editForm };
                    this.showEditModal = false;
                    this.editIdx = null;
                },

                tambah() {
                    this.errMsg = '';
                    if (!this.namaResponden.trim()) { this.errMsg = 'Nama Anda wajib diisi dulu!'; window.scrollTo({top:0,behavior:'smooth'}); return; }
                    if (!this.kategori) { this.errMsg = 'Pilih Kategori terlebih dahulu!'; return; }
                    if (!this.namaKmd.trim()) { this.errMsg = 'Nama Komoditas wajib diisi!'; return; }
                    if (!this.bulan) { this.errMsg = 'Pilih Bulan Produksi!'; return; }
                    this.cart.push({
                        nama_komoditas: cap(this.namaKmd.replace(/\./g,'')),
                        kategori: this.kategori, sub_kategori: this.subKategori,
                        jumlah_produksi: this.jumlah, satuan: this.satuan,
                        harga: this.harga,
                        tipe_harga: this.tipeHarga,
                        bulan_produksi: this.bulan,
                        frekuensi: this.frekuensi,
                    });
                    this.namaKmd = ''; this.jumlah = ''; this.satuan = ''; this.harga = ''; this.tipeHarga = 'Satuan';
                    this.frekuensi = ''; this.frekManual = false; this.infoFrekuensi = null;
                },
                submitAll() {
                    if (!this.cart.length) { this.errMsg = 'Tambah minimal 1 komoditas dulu!'; return; }
                    const form = document.createElement('form');
                    form.method = 'POST'; form.action = ACTION;
                    addF(form, '_token', CSRF);
                    addF(form, 'nama_responden', this.namaResponden.replace(/\./g,'').trim());
                    this.cart.forEach((it,i) => {
                        addF(form, `komoditas[${i}][nama_komoditas]`,  it.nama_komoditas);
                        addF(form, `komoditas[${i}][kategori]`,        it.kategori);
                        addF(form, `komoditas[${i}][sub_kategori]`,    it.sub_kategori);
                        addF(form, `komoditas[${i}][jumlah_produksi]`, it.jumlah_produksi);
                        addF(form, `komoditas[${i}][satuan]`,          it.satuan);
                        addF(form, `komoditas[${i}][harga]`,           it.harga);
                        addF(form, `komoditas[${i}][tipe_harga]`,      it.tipe_harga);
                        addF(form, `komoditas[${i}][bulan_produksi]`,  it.bulan_produksi);
                    });
                    document.body.appendChild(form);
                    form.submit();
                }
            };
        }

        function addF(form, name, value) {
            const f = document.createElement('input');
            f.type = 'hidden'; f.name = name; f.value = value || '';
            form.appendChild(f);
        }

        function cap(s) { return s ? s.charAt(0).toUpperCase() + s.slice(1) : s; }

        // ========================================================
        // ENGINE REKOGNISI SUARA TINGGI AKURASI (WEB SPEECH API)
        // Multi-Hypothesis + Kamus Komoditas Pangan/Ekraf + Parser Angka
        // ========================================================

        // Kamus kanonikal komoditas pangan, pertanian, perkebunan, peternakan, perikanan & ekraf
        const MASTER_KOMODITAS_DICT = [
            'Beras', 'Padi', 'Jagung', 'Kedelai', 'Kacang Tanah', 'Kacang Hijau', 'Singkong', 'Ubi Jalar', 'Ubi Kayu',
            'Kelapa', 'Kelapa Sawit', 'Kopi', 'Kopi Robusta', 'Kopi Arabika', 'Kakao', 'Cengkeh', 'Tebu', 'Tembakau', 'Karet',
            'Sapi Potong', 'Sapi Perah', 'Sapi', 'Kambing', 'Domba', 'Ayam Broiler', 'Ayam Kampung', 'Ayam Petelur', 'Ayam',
            'Bebek', 'Itik', 'Telur Ayam', 'Telur Bebek', 'Telur',
            'Ikan Lele', 'Ikan Nila', 'Ikan Gurame', 'Ikan Bandeng', 'Ikan Patin', 'Ikan Mas', 'Udang', 'Garam', 'Gula Pasir', 'Gula', 'Gula Merah', 'Gula Jawa',
            'Cabai Rawit', 'Cabai Merah', 'Cabai', 'Bawang Merah', 'Bawang Putih', 'Tomat', 'Wortel', 'Kentang', 'Kubis', 'Kol', 'Terong', 'Bayam', 'Kangkung',
            'Semangka', 'Melon', 'Pisang', 'Mangga', 'Jeruk', 'Pepaya', 'Alpukat',
            'Keripik Singkong', 'Keripik Tempe', 'Keripik Pisang', 'Keripik', 'Anyaman Bambu', 'Anyaman', 'Batik', 'Jamu Tradisional', 'Jamu', 'Madu'
        ];

        // Normalisasi kata & bobot kemiripan Levenshtein
        function calcSimilarity(s1, s2) {
            s1 = (s1 || '').toLowerCase().trim();
            s2 = (s2 || '').toLowerCase().trim();
            if (s1 === s2) return 1.0;
            if (!s1 || !s2) return 0.0;

            const c1 = s1.replace(/[^a-z0-9]/gi, '');
            const c2 = s2.replace(/[^a-z0-9]/gi, '');
            if (c1 === c2 && c1.length > 0) return 1.0;
            if (c1.includes(c2) || c2.includes(c1)) return 0.95;

            // Penyetaraan fonetik bilabial/labiodental (B <-> W <-> V <-> P, D <-> T)
            const p1 = c1.replace(/[bwvp]/g, 'b').replace(/[dt]/g, 'd');
            const p2 = c2.replace(/[bwvp]/g, 'b').replace(/[dt]/g, 'd');
            if (p1 === p2 && p1.length > 0) return 0.98;

            const m = c1.length, n = c2.length;
            const d = Array(m + 1).fill(null).map(() => Array(n + 1).fill(0));
            for (let i = 0; i <= m; i++) d[i][0] = i;
            for (let j = 0; j <= n; j++) d[0][j] = j;

            for (let i = 1; i <= m; i++) {
                for (let j = 1; j <= n; j++) {
                    const ch1 = c1[i - 1], ch2 = c2[j - 1];
                    let cost = 0;
                    if (ch1 === ch2) {
                        cost = 0;
                    } else if ((/[bwvp]/.test(ch1) && /[bwvp]/.test(ch2)) || (/[dt]/.test(ch1) && /[dt]/.test(ch2))) {
                        cost = 0.1;
                    } else if ((ch1 === 'a' && ch2 === 'o') || (ch1 === 'o' && ch2 === 'a')) {
                        cost = 0.15;
                    } else {
                        cost = 1.0;
                    }
                    d[i][j] = Math.min(d[i - 1][j] + 1, d[i][j - 1] + 1, d[i - 1][j - 1] + cost);
                }
            }
            return Math.max(0, (Math.max(m, n) - d[m][n]) / Math.max(m, n));
        }

        // Deteksi Satuan dalam ucapan bahasa Indonesia
        function detectUnit(str) {
            const lower = (str || '').toLowerCase();
            const unitPatterns = [
                { regex: /\b(kilogram|kilo|kg)\b/i, unit: 'kg' },
                { regex: /\b(ton)\b/i, unit: 'ton' },
                { regex: /\b(kwintal|kuintal)\b/i, unit: 'kwintal' },
                { regex: /\b(gram|gr)\b/i, unit: 'gram' },
                { regex: /\b(liter|ltr)\b/i, unit: 'liter' },
                { regex: /\b(buah|biji|butir|butir telur)\b/i, unit: 'buah' },
                { regex: /\b(ikat)\b/i, unit: 'ikat' },
                { regex: /\b(pack|pak|bungkus|bks)\b/i, unit: 'pack' },
                { regex: /\b(karung|sak)\b/i, unit: 'karung' },
                { regex: /\b(lusin)\b/i, unit: 'lusin' }
            ];
            for (const item of unitPatterns) {
                if (item.regex.test(lower)) {
                    return {
                        unit: item.unit,
                        cleaned: lower.replace(item.regex, '').trim()
                    };
                }
            }
            return { unit: null, cleaned: str };
        }

        // Konversi kata angka bahasa Indonesia ke angka desimal
        function parseIndoNumber(text) {
            if (!text) return '';
            let s = text.toLowerCase().trim();

            // Desimal & pecahan umum
            s = s.replace(/,/g, '.');
            s = s.replace(/(\d+)\s*koma\s*(\d+)/g, '$1.$2');
            s = s.replace(/\bseperempat\b/g, '0.25');
            s = s.replace(/\bsetengah\b/g, '0.5');
            s = s.replace(/\btiga perempat\b/g, '0.75');
            s = s.replace(/\bdua setengah\b/g, '2.5');
            s = s.replace(/\bsatu setengah\b/g, '1.5');

            const digitsOnly = s.match(/^\d+(\.\d+)?$/);
            if (digitsOnly) return digitsOnly[0];

            const numMap = {
                'nol': 0, 'kosong': 0, 'satu': 1, 'dua': 2, 'tiga': 3, 'empat': 4,
                'lima': 5, 'enam': 6, 'tujuh': 7, 'delapan': 8, 'sembilan': 9
            };

            s = s.replace(/\bkoma\s+([a-z]+)\b/g, (m, d) => {
                return '.' + (numMap[d] !== undefined ? numMap[d] : d);
            });

            const words = s.split(/\s+/);
            let total = 0;
            let current = 0;
            let hasNumberWord = false;

            for (let i = 0; i < words.length; i++) {
                const w = words[i];
                if (numMap[w] !== undefined) {
                    current += numMap[w];
                    hasNumberWord = true;
                } else if (w === 'sepuluh') {
                    current += 10;
                    hasNumberWord = true;
                } else if (w === 'sebelas') {
                    current += 11;
                    hasNumberWord = true;
                } else if (w === 'belas') {
                    if (current === 0) current = 1;
                    current += 10;
                    hasNumberWord = true;
                } else if (w === 'puluh') {
                    if (current === 0) current = 1;
                    current *= 10;
                    hasNumberWord = true;
                } else if (w === 'seratus') {
                    current += 100;
                    hasNumberWord = true;
                } else if (w === 'ratus') {
                    if (current === 0) current = 1;
                    current *= 100;
                    hasNumberWord = true;
                } else if (w === 'seribu') {
                    if (current === 0) current = 1;
                    total += (current || 1) * 1000;
                    current = 0;
                    hasNumberWord = true;
                } else if (w === 'ribu') {
                    if (current === 0) current = 1;
                    total += current * 1000;
                    current = 0;
                    hasNumberWord = true;
                } else if (w === 'juta') {
                    if (current === 0) current = 1;
                    total += current * 1000000;
                    current = 0;
                    hasNumberWord = true;
                } else if (!isNaN(parseFloat(w))) {
                    current += parseFloat(w);
                    hasNumberWord = true;
                }
            }
            total += current;

            if (hasNumberWord && total > 0) return String(total);
            const rawMatch = s.match(/\d+(\.\d+)?/);
            return rawMatch ? rawMatch[0] : '';
        }

        // Parsing Komoditas dengan pencocokan kamus kanonikal
        function parseKomoditas(text) {
            if (!text) return '';
            let raw = text.trim();
            // Bersihkan imbuhan ucapan awal
            raw = raw.replace(/^(komoditas|komoditi|produk|tanaman|ternak|hasil panen|panen|usaha)\s+/i, '');

            // Kumpulkan seluruh kamus kandidat (saran kategori aktif + saran umum + master)
            const activeCategorySaran = (typeof SARAN === 'object' && SARAN) 
                ? Object.values(SARAN).flat() 
                : [];
            const candidates = Array.from(new Set([...activeCategorySaran, ...MASTER_KOMODITAS_DICT]));

            let bestMatch = null;
            let highestScore = 0;

            for (const item of candidates) {
                const score = calcSimilarity(raw, item);
                if (score > highestScore) {
                    highestScore = score;
                    bestMatch = item;
                }
            }

            // Ambang batas akurasi fuzzy match 0.72
            if (highestScore >= 0.72 && bestMatch) {
                return bestMatch;
            }

            // Jika tidak ada di kamus, rapikan format kata (Title Case)
            return raw.split(/\s+/).map(w => cap(w.toLowerCase())).join(' ');
        }

        // Mic component — Web Speech API tingkat akurasi tinggi
        function micInput(getter, setter, type = 'text', extraCallback = null) {
            return {
                isListening: false,
                rec: null,
                originalPlaceholder: '',
                interimText: '',

                init() {
                    const SR = window.SpeechRecognition || window.webkitSpeechRecognition;
                    if (!SR) return;

                    this.rec = new SR();
                    this.rec.lang = 'id-ID';
                    this.rec.continuous = false;
                    this.rec.interimResults = true;
                    this.rec.maxAlternatives = 10;

                    this.rec.onstart = () => {
                        this.isListening = true;
                        this.interimText = '';
                        if (this.$refs.el) {
                            this.originalPlaceholder = this.$refs.el.placeholder || '';
                            this.$refs.el.placeholder = '🎙️ Mendengarkan suara Anda...';
                        }
                    };

                    this.rec.onend = () => {
                        this.isListening = false;
                        if (this.$refs.el && this.originalPlaceholder) {
                            this.$refs.el.placeholder = this.originalPlaceholder;
                        }
                    };

                    this.rec.onerror = (e) => {
                        this.isListening = false;
                        if (this.$refs.el && this.originalPlaceholder) {
                            this.$refs.el.placeholder = this.originalPlaceholder;
                        }
                    };

                    this.rec.onresult = (e) => {
                        let finalAlts = [];
                        let interim = '';

                        for (let i = e.resultIndex; i < e.results.length; ++i) {
                            if (e.results[i].isFinal) {
                                for (let j = 0; j < e.results[i].length; j++) {
                                    finalAlts.push(e.results[i][j].transcript.trim());
                                }
                            } else {
                                interim += e.results[i][0].transcript;
                            }
                        }

                        // Tampilkan live transcript real-time pada placeholder
                        if (interim && this.$refs.el) {
                            this.$refs.el.placeholder = 'Mendengar: "' + interim + '"...';
                            this.interimText = interim;
                        }

                        const candidateList = finalAlts.length > 0 ? finalAlts : (this.interimText ? [this.interimText] : []);
                        if (!candidateList.length) return;

                        if (type === 'number') {
                            let parsedNumber = '';
                            let detectedUnit = null;

                            // Cek seluruh alternatif untuk menemukan angka & satuan terbaik
                            for (const alt of candidateList) {
                                const unitCheck = detectUnit(alt);
                                if (!detectedUnit && unitCheck.unit) detectedUnit = unitCheck.unit;
                                const num = parseIndoNumber(unitCheck.cleaned);
                                if (num) {
                                    parsedNumber = num;
                                    break;
                                }
                            }

                            if (parsedNumber) {
                                setter(parsedNumber);
                                if (this.$refs.el) this.$refs.el.value = parsedNumber;
                                if (detectedUnit && typeof extraCallback === 'function') {
                                    extraCallback(detectedUnit);
                                }
                            }
                        } else if (type === 'komoditas') {
                            // Cek jika pengguna menyebut nama komoditas sekaligus jumlah / satuan
                            let bestKomoditas = '';
                            let detectedUnit = null;
                            let detectedQty = null;

                            for (const alt of candidateList) {
                                const unitCheck = detectUnit(alt);
                                if (unitCheck.unit) detectedUnit = unitCheck.unit;

                                // Cek apakah ada angka di ucapan (misal "Beras 50 kg")
                                const numCandidate = parseIndoNumber(unitCheck.cleaned);
                                if (numCandidate && !detectedQty) {
                                    detectedQty = numCandidate;
                                }

                                const kmd = parseKomoditas(unitCheck.cleaned);
                                if (kmd) {
                                    bestKomoditas = kmd;
                                    break;
                                }
                            }

                            if (!bestKomoditas) bestKomoditas = parseKomoditas(candidateList[0]);
                            setter(bestKomoditas);
                            if (this.$refs.el) this.$refs.el.value = bestKomoditas;

                            if (typeof extraCallback === 'function') {
                                extraCallback(detectedQty, detectedUnit);
                            }
                        } else if (type === 'name') {
                            let nameText = candidateList[0].replace(/[.,]$/g, '').trim();
                            nameText = nameText.replace(/^(nama saya|nama|atas nama)\s+/i, '');
                            const formattedName = nameText.split(/\s+/).map(w => cap(w)).join(' ');
                            setter(formattedName);
                            if (this.$refs.el) this.$refs.el.value = formattedName;
                        } else {
                            // Text / Keterangan
                            let textVal = cap(candidateList[0].replace(/[.]*$/g, '').trim());
                            setter(textVal);
                            if (this.$refs.el) this.$refs.el.value = textVal;
                        }
                    };
                },

                toggle() {
                    if (!this.rec) {
                        alert('Browser Anda tidak mendukung Web Speech API. Silakan gunakan Google Chrome di Laptop / Android.');
                        return;
                    }
                    if (this.isListening) {
                        this.rec.stop();
                    } else {
                        try {
                            this.rec.start();
                        } catch (err) {
                            console.warn('Speech recognition start error:', err);
                        }
                    }
                }
            };
        }
    </script>
</body>
</html>

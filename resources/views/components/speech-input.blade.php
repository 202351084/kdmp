@props([
    'name' => '', 
    'label' => '', 
    'required' => false, 
    'type' => 'text', 
    'placeholder' => '', 
    'min' => null,
    'step' => null,
    'large' => false,
    'autoformat' => '', // e.g., 'RT', 'RW'
    'value' => null,
    'xmodel' => '',
    'list' => ''
])

<div x-data="speechInput('{{ $autoformat }}')" class="relative">
    @if($label)
    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wide mb-1.5">
        {{ $label }} @if($required)<span class="text-red-500">*</span>@endif
    </label>
    @endif
    
    <div class="relative flex items-center">
        <input 
            type="{{ $type }}" 
            name="{{ $name }}" 
            value="{{ $value ?? old($name) }}"
            x-ref="inputField"
            @if($xmodel) x-model="{{ $xmodel }}" @endif
            @if($list) list="{{ $list }}" @endif
            @if($required) required @endif
            @if($min !== null) min="{{ $min }}" @endif
            @if($step !== null) step="{{ $step }}" @endif
            placeholder="{{ $placeholder }}" 
            {{ $attributes->merge(['class' => 'w-full rounded-xl border-slate-300 bg-white pl-10 pr-4 py-2.5 text-sm focus:border-blue-500 focus:ring-blue-500 shadow-sm transition-shadow ' . ($large ? 'text-lg font-bold text-slate-900 shadow-inner py-3' : '')]) }}
        >
        
        <!-- Microphone Button (Left Side) -->
        <div class="absolute inset-y-0 left-0 flex items-center pl-2 z-10">
            <button 
                type="button" 
                @click="toggleSpeech()"
                :class="{'text-red-600 animate-pulse bg-red-100 ring-2 ring-red-400': isListening, 'text-slate-400 hover:text-blue-500 hover:bg-blue-50': !isListening}"
                class="p-2 rounded-full transition-all focus:outline-none flex items-center justify-center cursor-pointer"
                title="Gunakan suara untuk mengetik"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"></path>
                </svg>
            </button>
        </div>
    </div>
    
    <!-- Status indicator -->
    <div x-show="isListening" x-cloak class="absolute -bottom-5 right-0 text-[10px] font-semibold text-red-600 flex items-center gap-1 bg-white px-2 py-0.5 rounded-full shadow-sm border border-red-200 z-20">
        <span class="w-1.5 h-1.5 rounded-full bg-red-500 animate-ping"></span>
        Mendengarkan...
    </div>
</div>

@push('scripts')
@once
<script>
(function() {
    // Shared Singleton Speech Recognition Manager with Auto-Chunking & Phone Dictation Support
    window.__kdmpSpeechManager = window.__kdmpSpeechManager || {
        recognition: null,
        activeComponent: null,
        isManualStop: false,
        silenceTimer: null,
        restartTimer: null,

        getRecognition() {
            if (!this.recognition && ('webkitSpeechRecognition' in window || 'SpeechRecognition' in window)) {
                const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
                this.recognition = new SpeechRecognition();
                this.recognition.continuous = false;
                this.recognition.interimResults = true;
                this.recognition.lang = 'id-ID';
                this.recognition.maxAlternatives = 10;

                this.recognition.onstart = () => {
                    if (this.activeComponent) {
                        this.activeComponent.isListening = true;
                        this.activeComponent.onRecognitionStarted();
                    }
                };

                this.recognition.onresult = (event) => {
                    if (!this.activeComponent) return;

                    let interimTranscript = '';
                    let finalAlternatives = [];

                    for (let i = event.resultIndex; i < event.results.length; ++i) {
                        if (event.results[i].isFinal) {
                            for (let j = 0; j < event.results[i].length; j++) {
                                const t = event.results[i][j].transcript ? event.results[i][j].transcript.trim() : '';
                                if (t && !finalAlternatives.includes(t)) {
                                    finalAlternatives.push(t);
                                }
                            }
                        } else {
                            interimTranscript += event.results[i][0].transcript;
                        }
                    }

                    if (interimTranscript) {
                        this.activeComponent.handleInterim(interimTranscript.trim());
                    }

                    if (finalAlternatives.length > 0) {
                        const shouldStopNow = this.activeComponent.handleFinal(finalAlternatives);
                        if (shouldStopNow) {
                            this.stop();
                        } else {
                            // Reset silence timer: jika hening selama 3 detik, baru otomatis stop
                            this.resetSilenceTimer(3000);
                        }
                    }
                };

                this.recognition.onerror = (event) => {
                    console.warn('Speech recognition status:', event.error);
                    if (event.error === 'not-allowed') {
                        alert('Akses mikrofon tidak diizinkan. Silakan klik ikon gembok/slider di sebelah kiri alamat URL browser untuk mengizinkan mikrofon.');
                        this.stop();
                    }
                };

                this.recognition.onend = () => {
                    clearTimeout(this.restartTimer);
                    // Jika pengguna tidak mematikan mic secara manual dan masih berbicara (misal nomor HP belum selesai):
                    if (!this.isManualStop && this.activeComponent && this.activeComponent.shouldKeepListening()) {
                        this.restartTimer = setTimeout(() => {
                            if (!this.isManualStop && this.activeComponent && this.activeComponent.shouldKeepListening()) {
                                try {
                                    this.recognition.start();
                                } catch (e) {
                                    this.stop();
                                }
                            }
                        }, 150);
                        return;
                    }

                    if (this.activeComponent) {
                        this.activeComponent.onRecognitionEnded();
                        this.activeComponent = null;
                    }
                };
            }
            return this.recognition;
        },

        resetSilenceTimer(delay) {
            clearTimeout(this.silenceTimer);
            this.silenceTimer = setTimeout(() => {
                if (this.activeComponent) {
                    this.stop();
                }
            }, delay || 3000);
        },

        start(component) {
            const rec = this.getRecognition();
            if (!rec) {
                alert('Browser Anda belum mendukung input suara. Gunakan Google Chrome atau Microsoft Edge.');
                return false;
            }

            if (this.activeComponent) {
                this.stop();
            }

            this.isManualStop = false;
            clearTimeout(this.silenceTimer);
            clearTimeout(this.restartTimer);
            this.activeComponent = component;
            this.activeComponent.onSessionStarted();

            try {
                rec.start();
                return true;
            } catch (e) {
                console.warn('Speech recognition start retry:', e);
                try {
                    rec.stop();
                    setTimeout(() => rec.start(), 150);
                    return true;
                } catch (err) {
                    console.error('Failed to start speech recognition:', err);
                    this.stop();
                    return false;
                }
            }
        },

        stop() {
            this.isManualStop = true;
            clearTimeout(this.silenceTimer);
            clearTimeout(this.restartTimer);
            if (this.recognition) {
                try {
                    this.recognition.stop();
                } catch (e) {}
            }
            if (this.activeComponent) {
                this.activeComponent.onRecognitionEnded();
                this.activeComponent = null;
            }
        }
    };

    function registerSpeechInputComponent() {
        if (!window.Alpine || window.__speechInputRegistered) return;
        window.__speechInputRegistered = true;

        Alpine.data('speechInput', (autoFormat) => ({
            autoFormat: autoFormat || '',
            isListening: false,
            originalPlaceholder: '',
            accumulated: '',

            init() {},

            toggleSpeech() {
                const mgr = window.__kdmpSpeechManager;
                if (this.isListening) {
                    mgr.stop();
                } else {
                    mgr.start(this);
                }
            },

            onSessionStarted() {
                this.isListening = true;
                this.accumulated = '';
                const el = this.$refs.inputField;
                if (el) {
                    this.originalPlaceholder = el.placeholder || '';
                    const isPhone = el.type === 'tel' || el.name === 'no_hp';
                    el.placeholder = isPhone 
                        ? '🎙️ Sebut nomor HP (bisa berjeda)...' 
                        : '🎙️ Mendengarkan suara Anda...';
                }
            },

            onRecognitionStarted() {
                this.isListening = true;
            },

            onRecognitionEnded() {
                this.isListening = false;
                const el = this.$refs.inputField;
                if (el && this.originalPlaceholder) {
                    el.placeholder = this.originalPlaceholder;
                }
            },

            shouldKeepListening() {
                if (!this.isListening) return false;
                const el = this.$refs.inputField;
                if (!el) return false;

                const isPhone = el.type === 'tel' || el.name === 'no_hp';
                if (isPhone) {
                    const digits = (el.value || '').replace(/[^0-9]/g, '');
                    // Terus dengarkan sampai nomor HP mencapai minimal 10 digit atau di-stop manual
                    return digits.length < 10;
                }

                return false;
            },

            handleInterim(interim) {
                const el = this.$refs.inputField;
                if (!el) return;

                const isPhone = el.type === 'tel' || el.name === 'no_hp';
                if (isPhone) {
                    const newDigits = this.parseSpokenDigits(interim);
                    if (newDigits) {
                        let preview = this.accumulated;
                        if (!preview.endsWith(newDigits) && !newDigits.startsWith(preview)) {
                            preview += newDigits;
                        } else if (newDigits.length > preview.length) {
                            preview = newDigits;
                        }
                        el.placeholder = '🎙️ "' + preview + '"... (bicara lagi)';
                    }
                } else {
                    el.placeholder = '🎙️ "' + interim + '"...';
                }
            },

            handleFinal(finalAlternatives) {
                const inputElement = this.$refs.inputField;
                if (!inputElement || !finalAlternatives || finalAlternatives.length === 0) return true;

                const autoFormatUpper = (this.autoFormat || '').toUpperCase();
                const isRtRwField = autoFormatUpper === 'RT' || autoFormatUpper === 'RW' || inputElement.name === 'nama_rt' || inputElement.name === 'rw';

                // ==============================================================
                // 1. KHUSUS NOMOR RT & NOMOR RW: Format Wajib Angka 2 Digit (01, 02, ...)
                // ==============================================================
                if (isRtRwField) {
                    let extracted = null;
                    for (const cand of finalAlternatives) {
                        extracted = this.extractRtRwNumber(cand);
                        if (extracted !== null) break;
                    }

                    if (extracted !== null) {
                        inputElement.value = extracted;
                    } else {
                        let raw = finalAlternatives[0].trim().replace(/^(rt|rw)\s+(rt|rw)\s+/i, '$1 ');
                        inputElement.value = raw;
                    }

                    this.notifyModelAndEvents(inputElement);
                    return true; // Langsung selesai untuk RT/RW
                }

                // ==============================================================
                // 2. INPUT TELEPON / HP (misal: no_hp) -> AKUMULASI SAMPAI LENGKAP!
                // ==============================================================
                if (inputElement.type === 'tel' || inputElement.name === 'no_hp') {
                    let digits = '';
                    for (const cand of finalAlternatives) {
                        digits = this.parseSpokenDigits(cand);
                        if (digits) break;
                    }

                    if (digits) {
                        // Jika mesin suara mengirimkan transkrip utuh (sudah mencakup angka sebelumnya)
                        if (digits.startsWith(this.accumulated) && digits.length >= this.accumulated.length) {
                            this.accumulated = digits;
                        } else if (!this.accumulated.endsWith(digits)) {
                            // Mesin suara mengirim potongan pecahan (chunk) baru, sambungkan!
                            this.accumulated += digits;
                        }

                        inputElement.value = this.accumulated;
                        this.notifyModelAndEvents(inputElement);
                        inputElement.placeholder = '🎙️ ' + this.accumulated + '... (sebutkan sisa nomor)';
                    }

                    // Jika nomor sudah mencapai panjang standar HP (>= 10 digit, misal 08123456789)
                    if (this.accumulated.length >= 10) {
                        return false; // Beri jeda 3 detik silence sebelum auto-stop, atau user bisa klik mic
                    } else {
                        return false; // Belum lengkap (< 10 digit), JANGAN STOP!
                    }
                }

                // ==============================================================
                // 3. INPUT NUMBER (misal: Total KK, Total Jiwa)
                // ==============================================================
                if (inputElement.type === 'number') {
                    let numResult = null;
                    for (const cand of finalAlternatives) {
                        const parsed = this.parseIndonesianSpokenNumber(cand);
                        if (parsed !== null) {
                            numResult = parsed;
                            break;
                        }
                    }

                    if (numResult !== null) {
                        inputElement.value = String(numResult);
                    } else {
                        const clean = this.parseCleanNumber(this.wordsToNumbers(finalAlternatives[0]));
                        if (clean !== '') {
                            inputElement.value = clean;
                        }
                    }

                    this.notifyModelAndEvents(inputElement);
                    return true;
                }

                // ==============================================================
                // 4. DATALIST MATCHING (misal Komoditas / Wilayah)
                // ==============================================================
                let chosenText = finalAlternatives[0];
                const listId = inputElement.getAttribute('list');
                let options = [];
                if (listId) {
                    const dl = document.getElementById(listId);
                    if (dl) {
                        options = Array.from(dl.querySelectorAll('option')).map(o => o.value).filter(Boolean);
                    }
                }

                if (options.length > 0) {
                    let bestMatch = null;
                    let highestScore = 0;
                    for (const candidate of finalAlternatives) {
                        for (const opt of options) {
                            const score = this.calculateSimilarity(candidate, opt);
                            if (score > highestScore) {
                                highestScore = score;
                            }
                        }
                    }
                    if (bestMatch && highestScore >= 0.55) {
                        chosenText = bestMatch;
                    }
                }

                // ==============================================================
                // 5. INPUT TEKS BIASA (misal: Nama Ketua RT, Dusun)
                // ==============================================================
                chosenText = this.correctLabialWords(chosenText);
                chosenText = chosenText.replace(/\./g, "").trim();
                chosenText = chosenText.replace(/\b[a-z]/g, c => c.toUpperCase());

                inputElement.value = chosenText;
                this.notifyModelAndEvents(inputElement);
                return true;
            },

            notifyModelAndEvents(el) {
                if (el._x_model) {
                    try { el._x_model.set(el.value); } catch(e) {}
                }
                el.dispatchEvent(new Event('input', { bubbles: true }));
                el.dispatchEvent(new Event('change', { bubbles: true }));
            },

            // Ekstraktor Pintar Khusus Nomor RT / RW: Mengonversi kata atau angka menjadi 2 digit (misal: "satu" -> "01")
            extractRtRwNumber(text) {
                if (!text) return null;
                let s = String(text).toLowerCase().trim();

                // Bersihkan kata awalan "rt", "rw", "nomor", "no", "ke"
                s = s.replace(/\b(rt|rw|erte|er te|erwe|er we|nomor|no|angka|ke)\b/gi, ' ').trim();

                // Jika sudah ada digit eksplisit (contoh: "01", "1", "12")
                const digitMatch = s.match(/\b\d+\b/);
                if (digitMatch) {
                    const val = parseInt(digitMatch[0], 10);
                    return String(val).padStart(2, '0');
                }

                // Kamus koreksi fonetik suara jarak jauh / bergumam
                const phoneticMap = {
                    'ratu': 'satu', 'batu': 'satu', 'sat': 'satu', 'setu': 'satu', 'katu': 'satu', 'atu': 'satu', 'sapu': 'satu',
                    'jua': 'dua', 'tua': 'dua', 'rua': 'dua', 'duo': 'dua', 'gua': 'dua',
                    'iga': 'tiga', 'tiba': 'tiga', 'rika': 'tiga',
                    'ampat': 'empat', 'tempat': 'empat', 'pat': 'empat', 'mpat': 'empat',
                    'gima': 'lima', 'ima': 'lima', 'rima': 'lima',
                    'nam': 'enam', 'nem': 'enam', 'inam': 'enam',
                    'tuju': 'tujuh', 'kujuh': 'tujuh', 'tujo': 'tujuh',
                    'lapan': 'delapan', 'dapan': 'delapan',
                    'sambilan': 'sembilan', 'bilan': 'sembilan', 'milan': 'sembilan',
                    'puluh': 'sepuluh', 'belas': 'sebelas', 'song': 'nol', 'kosong': 'nol'
                };

                let words = s.split(/\s+/).map(w => phoneticMap[w] || w);
                let normalized = words.join(' ');

                const num = this.parseIndonesianSpokenNumber(normalized);
                if (num !== null) {
                    return String(num).padStart(2, '0');
                }

                return null;
            },

            // Parser Angka Lisan Bahasa Indonesia Majemuk & Tunggal
            parseIndonesianSpokenNumber(str) {
                if (!str) return null;
                let s = String(str).toLowerCase().trim();

                // Bersihkan tanda baca
                s = s.replace(/[.,\-]/g, ' ').replace(/\s+/g, ' ').trim();

                // Jika angka murni
                if (/^\d+$/.test(s)) {
                    return parseInt(s, 10);
                }

                // Konversi satuan dasar langsung
                const unitWords = {
                    'nol': 0, 'kosong': 0,
                    'satu': 1, 'se': 1,
                    'dua': 2,
                    'tiga': 3,
                    'empat': 4,
                    'lima': 5,
                    'enam': 6,
                    'tujuh': 7,
                    'delapan': 8,
                    'sembilan': 9,
                    'sepuluh': 10,
                    'sebelas': 11
                };

                if (unitWords[s] !== undefined) {
                    return unitWords[s];
                }

                // Cek awalan "nol satu" / "kosong satu" -> 1
                const zeroPrefixMatch = s.match(/^(?:nol|kosong)\s+(satu|dua|tiga|empat|lima|enam|tujuh|delapan|sembilan|\d+)$/i);
                if (zeroPrefixMatch) {
                    const nextWord = zeroPrefixMatch[1];
                    if (!isNaN(nextWord)) return parseInt(nextWord, 10);
                    if (unitWords[nextWord] !== undefined) return unitWords[nextWord];
                }

                // Tangani belasan: "dua belas" s/d "sembilan belas"
                const belasMatch = s.match(/^(satu|dua|tiga|empat|lima|enam|tujuh|delapan|sembilan)\s+belas$/i);
                if (belasMatch) {
                    return (unitWords[belasMatch[1]] || 0) + 10;
                }

                // Tangani puluhan: "dua puluh", "dua puluh satu", dll
                const puluhMatch = s.match(/^(satu|dua|tiga|empat|lima|enam|tujuh|delapan|sembilan)\s+puluh(?:\s+(satu|dua|tiga|empat|lima|enam|tujuh|delapan|sembilan))?$/i);
                if (puluhMatch) {
                    const puluhan = (unitWords[puluhMatch[1]] || 0) * 10;
                    const satuan = puluhMatch[2] ? (unitWords[puluhMatch[2]] || 0) : 0;
                    return puluhan + satuan;
                }

                // Ratusan & Ribuan umum
                const converted = this.wordsToNumbers(s);
                const parsedVal = parseFloat(converted);
                if (!isNaN(parsedVal)) {
                    return parsedVal;
                }

                return null;
            },

            // Parsing Deretan Digit Lisan untuk Nomor Telepon (contoh: "kosong delapan satu...")
            parseSpokenDigits(str) {
                if (!str) return '';
                let s = String(str).toLowerCase().trim();

                const wordMap = [
                    ['sembilan puluh', '90'], ['delapan puluh', '80'], ['tujuh puluh', '70'],
                    ['enam puluh', '60'], ['lima puluh', '50'], ['empat puluh', '40'],
                    ['tiga puluh', '30'], ['dua puluh', '20'], ['sepuluh', '10'],
                    ['sembilan belas', '19'], ['delapan belas', '18'], ['tujuh belas', '17'],
                    ['enam belas', '16'], ['lima belas', '15'], ['empat belas', '14'],
                    ['tiga belas', '13'], ['dua belas', '12'], ['sebelas', '11'],
                    ['kosong', '0'], ['nol', '0'], ['song', '0'],
                    ['satu', '1'], ['ratu', '1'], ['sat', '1'], ['setu', '1'],
                    ['dua', '2'], ['jua', '2'], ['tua', '2'],
                    ['tiga', '3'], ['iga', '3'],
                    ['empat', '4'], ['ampat', '4'], ['pat', '4'],
                    ['lima', '5'], ['gima', '5'],
                    ['enam', '6'], ['nam', '6'],
                    ['tujuh', '7'], ['tuju', '7'],
                    ['delapan', '8'], ['lapan', '8'],
                    ['sembilan', '9'], ['milan', '9']
                ];

                for (const [w, d] of wordMap) {
                    s = s.replace(new RegExp(`\\b${w}\\b`, 'gi'), d);
                }

                return s.replace(/[^0-9]/g, '');
            },

            // Bersihkan hasil konversi angka menjadi string angka valid untuk HTML5 input number
            parseCleanNumber(str) {
                if (!str) return '';
                let s = String(str).toLowerCase().trim();
                s = s.replace(/^(rp|idr)\.?\s*/i, '').replace(/\s*rupiah$/i, '').trim();
                s = s.replace(/[.,\s]+$/, '').trim();
                if (!s || s === '.') return '';

                if (/^\d{1,3}(\.\d{3})+$/.test(s)) {
                    s = s.replace(/\./g, '');
                } else if (/^\d+,\d+$/.test(s)) {
                    s = s.replace(',', '.');
                } else if (/^\d+(\.\d+)?$/.test(s)) {
                    // Valid
                } else {
                    if (s.includes('.') && s.includes(',')) {
                        s = s.replace(/\./g, '').replace(',', '.');
                    } else if ((s.match(/\./g) || []).length > 1) {
                        s = s.replace(/\./g, '');
                    } else if (s.includes(',')) {
                        s = s.replace(',', '.');
                    }
                    s = s.replace(/[^0-9.]/g, '');
                }

                s = s.replace(/\.+$/, '');
                if (s === '' || isNaN(Number(s))) {
                    return '';
                }
                return s;
            },

            // Koreksi Fonetik B & W Indonesia
            correctLabialWords(text) {
                if (!text) return text;
                const map = {
                    'walai': 'balai', 'welakang': 'belakang', 'warat': 'barat',
                    'wiru': 'biru', 'wudi': 'budi', 'wambang': 'bambang',
                    'wawang': 'bawang', 'weras': 'beras', 'wayam': 'bayam',
                    'wuncis': 'buncis', 'barna': 'warna', 'barga': 'warga',
                    'barung': 'warung', 'betan': 'wetan', 'baduk': 'waduk',
                    'bortel': 'wortel'
                };
                return text.replace(/\b[a-zA-Z]+\b/g, (word) => {
                    const lower = word.toLowerCase();
                    if (map[lower]) {
                        return word[0] === word[0].toUpperCase() 
                            ? map[lower].charAt(0).toUpperCase() + map[lower].slice(1) 
                            : map[lower];
                    }
                    return word;
                });
            },

            normalizeCommodity(text) {
                if (!text) return '';
                let t = text.toLowerCase().trim();
                const bwMap = {
                    'weras': 'beras', 'wawang': 'bawang', 'wayam': 'bayam',
                    'wuncis': 'buncis', 'bortel': 'wortel', 'walai': 'balai',
                    'welakang': 'belakang', 'barung': 'warung'
                };
                for (const [k, v] of Object.entries(bwMap)) {
                    t = t.replace(new RegExp(`\\b${k}\\b`, 'gi'), v);
                }
                const synonyms = {
                    'telor': 'telur ayam',
                    'cabe rawit': 'cabai rawit',
                    'cabe merah': 'cabai merah',
                    'lombok rawit': 'cabai rawit',
                    'lombok merah': 'cabai merah',
                    'lombok': 'cabai rawit',
                    'cabe': 'cabai rawit',
                    'brambang': 'bawang merah',
                    'elpiji': 'gas lpg 3kg',
                    'gas elpiji': 'gas lpg 3kg',
                    'gas melon': 'gas lpg 3kg',
                    'terigu': 'tepung terigu',
                    'minyak kelapa': 'minyak goreng',
                    'minyak sawit': 'minyak goreng'
                };
                for (const [k, v] of Object.entries(synonyms)) {
                    t = t.replace(new RegExp(`\\b${k}\\b`, 'gi'), v);
                }
                t = t.replace(/\b\d+(\.\d+)?\s*(kilo|kg|liter|ltr|gram|gr|ons|bungkus|butir|biji|ikat|papan|potong|tabung|kaleng)?\b/gi, '');
                t = t.replace(/\b(kilo|kg|liter|ltr|gram|gr|ons|bungkus|butir|biji|ikat|papan|potong|tabung|kaleng)\b/gi, '');
                return t.replace(/\s+/g, ' ').trim();
            },

            calculateSimilarity(s1, s2) {
                const norm1 = this.normalizeCommodity(s1);
                const norm2 = this.normalizeCommodity(s2);
                if (norm1 === norm2 && norm1.length > 0) return 1.0;

                const c1 = norm1.replace(/[^a-z0-9]/gi, '');
                const c2 = norm2.replace(/[^a-z0-9]/gi, '');
                if (c1 === c2 && c1.length > 0) return 1.0;

                const words1 = norm1.split(' ').filter(Boolean);
                const words2 = norm2.split(' ').filter(Boolean);
                if (words1.length > 0 && words2.length > 0) {
                    const intersect = words1.filter(w => words2.includes(w));
                    if (intersect.length === words1.length || intersect.length === words2.length) {
                        return 0.95;
                    }
                }

                if (c1.includes(c2) || c2.includes(c1)) return 0.92;

                const p1 = c1.replace(/[bwv]/g, 'b');
                const p2 = c2.replace(/[bwv]/g, 'b');
                if (p1 === p2 && p1.length > 0) return 0.98;

                const m = c1.length, n = c2.length;
                if (m === 0 || n === 0) return 0.0;

                const d = Array(m + 1).fill(null).map(() => Array(n + 1).fill(0));
                for (let i = 0; i <= m; i++) d[i][0] = i;
                for (let j = 0; j <= n; j++) d[0][j] = j;

                for (let i = 1; i <= m; i++) {
                    for (let j = 1; j <= n; j++) {
                        const ch1 = c1[i - 1], ch2 = c2[j - 1];
                        let cost = (ch1 === ch2) ? 0 : 
                            ((ch1 === 'b' || ch1 === 'w' || ch1 === 'v') && (ch2 === 'b' || ch2 === 'w' || ch2 === 'v')) ? 0.05 : 1.0;
                        d[i][j] = Math.min(
                            d[i - 1][j] + 1,
                            d[i][j - 1] + 1,
                            d[i - 1][j - 1] + cost
                        );
                    }
                }
                const maxLen = Math.max(m, n);
                return Math.max(0, (maxLen - d[m][n]) / maxLen);
            },

            // Parsing Angka Lisan Bahasa Indonesia Majemuk (Juta, Ribu, Ratus, Belas, Puluh, Setengah)
            wordsToNumbers(text) {
                if (!text) return '';
                let str = String(text).toLowerCase().trim();
                str = str.replace(/^(rp|idr)\.?\s*/i, '').replace(/\s*rupiah$/i, '').trim();
                str = str.replace(/[.,\s]+$/, '').trim();

                str = str.replace(/(\d+)\s*(rb|ribu)/gi, '$1 ribu');
                str = str.replace(/(\d+)\s*k\b/gi, '$1 ribu');
                str = str.replace(/(\d+)\s*(jt|juta)/gi, '$1 juta');

                if (/^\d{1,3}(\.\d{3})+$/.test(str)) {
                    return str.replace(/\./g, '');
                }
                if (!isNaN(str) && str !== '') {
                    return str;
                }

                str = str.replace(/([a-z\d]+)\s+setengah/gi, (match, p1) => {
                    const units = {'satu':1,'dua':2,'tiga':3,'empat':4,'lima':5,'enam':6,'tujuh':7,'delapan':8,'sembilan':9,'sepuluh':10};
                    const val = !isNaN(p1) ? parseFloat(p1) : (units[p1.toLowerCase()] || 0);
                    return val > 0 ? String(val + 0.5) : '0.5';
                });
                str = str.replace(/\bsetengah\b/gi, '0.5');

                const parseUpTo999 = (s) => {
                    s = s.trim();
                    if (!isNaN(s) && s !== '') return parseFloat(s);
                    let total = 0;

                    if (s.startsWith('seratus')) {
                        total += 100;
                        s = s.substring(7).trim();
                    } else {
                        const ratusMatch = s.match(/^(.*?)\s*ratus\s*(.*)$/i);
                        if (ratusMatch) {
                            const rCount = this.wordsToNumbers(ratusMatch[1]);
                            total += (parseFloat(rCount) || 1) * 100;
                            s = ratusMatch[2].trim();
                        }
                    }

                    const numMap = [
                        ['sembilan puluh', 90], ['delapan puluh', 80], ['tujuh puluh', 70],
                        ['enam puluh', 60], ['lima puluh', 50], ['empat puluh', 40],
                        ['tiga puluh', 30], ['dua puluh', 20], ['sepuluh', 10],
                        ['sembilan belas', 19], ['delapan belas', 18], ['tujuh belas', 17],
                        ['enam belas', 16], ['lima belas', 15], ['empat belas', 14],
                        ['tiga belas', 13], ['dua belas', 12], ['sebelas', 11],
                        ['sembilan', 9], ['delapan', 8], ['tujuh', 7], ['enam', 6],
                        ['lima', 5], ['empat', 4], ['tiga', 3], ['dua', 2], ['satu', 1],
                        ['nol', 0], ['kosong', 0]
                    ];

                    for (const [w, val] of numMap) {
                        if (s === w || s.startsWith(w + ' ') || s.endsWith(' ' + w)) {
                            total += val;
                            s = s.replace(new RegExp(`\\b${w}\\b`, 'i'), '').trim();
                        }
                    }

                    if (!isNaN(s) && s !== '') {
                        total += parseFloat(s);
                    }
                    return total;
                };

                let grandTotal = 0;
                let remaining = str;

                const jutaMatch = remaining.match(/^(.*?)\s*juta\s*(.*)$/i);
                if (jutaMatch) {
                    grandTotal += parseUpTo999(jutaMatch[1]) * 1000000;
                    remaining = (jutaMatch[2] || '').trim();
                }

                const ribuMatch = remaining.match(/^(.*?)\s*ribu\s*(.*)$/i);
                if (ribuMatch) {
                    const rPart = ribuMatch[1].trim();
                    if (rPart === 'se' || rPart === '') {
                        grandTotal += 1000;
                    } else {
                        grandTotal += parseUpTo999(rPart) * 1000;
                    }
                    remaining = (ribuMatch[2] || '').trim();
                }

                if (remaining !== '') {
                    grandTotal += parseUpTo999(remaining);
                }

                return grandTotal > 0 ? String(grandTotal) : str;
            }
        }));
    }

    if (window.Alpine) {
        registerSpeechInputComponent();
    } else {
        document.addEventListener('alpine:init', registerSpeechInputComponent);
    }
})();
</script>
@endonce
@endpush

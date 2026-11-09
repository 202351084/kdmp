@props(['active' => 'dashboard', 'subActive' => null])

@php
    $isParameterActive = in_array($active, ['parameter', 'demografi', 'potensi', 'standar'], true)
        || request()->routeIs('perencanaan.demografi.*')
        || request()->routeIs('perencanaan.potensi-produksi.*')
        || request()->routeIs('perencanaan.standar-kebutuhan.*');

    $activeSub = $subActive ?? (
        request()->routeIs('perencanaan.demografi.*') || $active === 'demografi' ? 'demografi' : (
            request()->routeIs('perencanaan.potensi-produksi.*') || $active === 'potensi' ? 'potensi' : (
                request()->routeIs('perencanaan.standar-kebutuhan.*') || $active === 'standar' ? 'standar' : 'demografi'
            )
        )
    );

    $paramPills = [
        [
            'id' => 'demografi',
            'label' => 'Demografi Penduduk Desa',
            'route' => 'perencanaan.demografi.index',
            'icon' => '👥',
            'info' => 'M1 Sesi 1',
        ],
        [
            'id' => 'potensi',
            'label' => 'Potensi Produksi Panen',
            'route' => 'perencanaan.potensi-produksi.index',
            'icon' => '🌾',
            'info' => 'M1 Sesi 2',
        ],
        [
            'id' => 'standar',
            'label' => 'Standar Kebutuhan Gizi',
            'route' => 'perencanaan.standar-kebutuhan.index',
            'icon' => '⚖️',
            'info' => 'AKG / Standar',
        ],
    ];
@endphp

<div class="mb-6 border-b border-paper-300 bg-paper-50/60 pb-0">
    <div class="flex items-center justify-between px-1 pb-3">
        <div>
            <div class="flex items-center gap-2">
                <span class="rounded bg-merah-500/10 px-2 py-0.5 font-mono text-[11px] font-semibold tracking-wider text-merah-600">
                    KALKULASI & PERENCANAAN
                </span>
                <span class="text-xs text-ink-600/60">•</span>
                <p class="font-mono text-xs uppercase tracking-wide text-ink-600/70">
                    Kalkulasi Kebutuhan &amp; Perencanaan Pengadaan
                </p>
            </div>
            <p class="mt-1 text-xs text-ink-600">
                Penyelarasan kebutuhan konsumsi pangan desa dengan potensi ketersediaan panen lokal.
            </p>
        </div>
    </div>

    {{-- Sub Navigation untuk Data & Parameter --}}
    @if ($isParameterActive)
        <div class="flex flex-wrap items-center gap-2 border-t border-paper-200/80 bg-paper-100/60 px-3 py-2">
            <span class="font-mono text-[10px] uppercase tracking-wider text-ink-600/70 mr-1">Parameter:</span>
            @foreach ($paramPills as $pill)
                @php $isPillActive = $activeSub === $pill['id']; @endphp
                <a href="{{ route($pill['route']) }}"
                   class="inline-flex items-center gap-1.5 rounded-sm px-3 py-1.5 text-xs font-medium transition
                          {{ $isPillActive
                              ? 'bg-ink-900 text-paper-50 shadow-xs'
                              : 'bg-paper-200/70 text-ink-700 hover:bg-paper-200 hover:text-ink-950' }}">
                    <span>{{ $pill['icon'] }}</span>
                    <span>{{ $pill['label'] }}</span>
                    <span class="rounded px-1 py-0.2 font-mono text-[9px] {{ $isPillActive ? 'bg-ink-800 text-paper-200' : 'bg-paper-300/60 text-ink-600' }}">
                        {{ $pill['info'] }}
                    </span>
                </a>
            @endforeach
        </div>
    @endif
</div>

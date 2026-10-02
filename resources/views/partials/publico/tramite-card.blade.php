<article class="flex min-h-[260px] flex-col rounded-2xl border border-neza-line bg-white p-5 shadow-[0_8px_24px_rgba(31,41,55,0.05)]">
    <div class="flex items-start justify-between gap-3">
        <div class="flex min-w-0 items-center gap-3">
            <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-neza-wine-soft text-neza-wine">
                <svg class="size-5" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M6.75 3.75h10.5A2.25 2.25 0 0119.5 6v12a2.25 2.25 0 01-2.25 2.25H6.75A2.25 2.25 0 014.5 18V6a2.25 2.25 0 012.25-2.25z" /></svg>
            </span>
            <span class="truncate text-[10px] font-bold uppercase tracking-wide text-[#76531f]">{{ $tramite['categoria'] }}</span>
        </div>
        <span class="rounded-full px-2.5 py-1 text-[10px] font-bold {{ $tramite['modalidad'] === 'En línea' ? 'bg-[#edf8f1] text-[#287a47]' : ($tramite['modalidad'] === 'Mixto' ? 'bg-[#fbf3e3] text-[#76531f]' : 'bg-[#f1f3f5] text-neza-muted') }}">{{ $tramite['modalidad'] }}</span>
    </div>
    <h3 class="mt-4 text-lg font-bold">{{ $tramite['nombre'] }}</h3>
    <p class="mt-1.5 text-[13px] leading-relaxed text-neza-muted">{{ $tramite['descripcion'] }}</p>
    <p class="mt-3 flex items-center gap-2 text-xs text-neza-muted"><svg class="size-4" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 10.5h15M5.25 10.5v8.25m4.5-8.25v8.25m4.5-8.25v8.25m4.5-8.25v8.25M12 3l8.25 4.5H3.75L12 3z" /></svg>{{ $tramite['dependencia'] }}</p>
    <div class="mt-auto flex items-center justify-between gap-4 border-t border-neza-line pt-5 text-xs">
        <div class="flex flex-wrap gap-4 text-neza-muted"><span>{{ $tramite['costo'] }}</span><span class="flex items-center gap-1"><svg class="size-4" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>{{ $tramite['tiempo'] }}</span></div>
        <a href="{{ $tramite['url'] }}" class="inline-flex h-9 shrink-0 items-center gap-1.5 rounded-xl bg-neza-wine px-4 font-bold text-white hover:bg-[#4c0f26]">Ver trámite <svg class="size-3.5" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" /></svg></a>
    </div>
</article>

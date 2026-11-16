{{-- Identificação do atleta: secretaria (nome/código + foto) ou tablet (rosto ou código). --}}
@php
    $cartaoFoto = fn (array $p, string $tamanho) => $p['foto']
        ? '<img src="'.e($p['foto']).'" alt="'.e($p['nome']).'" class="'.$tamanho.' rounded-full object-cover ring-4 ring-brand/40">'
        : '<span class="'.$tamanho.' flex items-center justify-center rounded-full bg-brand text-3xl font-black text-canvas">'.e($p['iniciais']).'</span>';
@endphp

@if ($modo === 'secretaria')
    <div class="mx-auto max-w-2xl">
        <label class="block">
            <span class="text-lg font-black">Nome ou código do atleta</span>
            <input type="text"
                   wire:model.live.debounce.250ms="buscaNome"
                   wire:keydown.enter.prevent="escolherPrimeiroDaBusca"
                   x-init="$nextTick(() => $el.focus())"
                   autocomplete="off"
                   class="mt-2 w-full rounded-2xl border-line bg-canvas text-xl"
                   placeholder="Digite o nome ou o código">
        </label>
        @error('buscaNome') <p class="mt-3 font-semibold text-red-400">{{ $message }}</p> @enderror
        @error('codigo') <p class="mt-3 font-semibold text-red-400">{{ $message }}</p> @enderror

        @if ($identificado === [])
            @if ($busca)
                <div class="mt-3 space-y-2">
                    @foreach ($busca as $socio)
                        <button type="button" wire:key="busca-{{ $socio['id'] }}" wire:click="escolherSocio({{ $socio['id'] }})"
                                class="fp-btn flex w-full items-center gap-4 rounded-2xl bg-white/5 px-3 py-3 text-left ring-1 ring-white/10 hover:bg-brand/10">
                            @if ($socio['foto'])
                                <img src="{{ $socio['foto'] }}" alt="" class="h-16 w-16 rounded-full object-cover">
                            @else
                                <span class="flex h-16 w-16 items-center justify-center rounded-full bg-brand text-xl font-black text-canvas">{{ $socio['iniciais'] }}</span>
                            @endif
                            <span class="min-w-0">
                                <span class="block truncate text-xl font-black">{{ $socio['nome'] }}</span>
                                <span class="text-sm font-semibold text-muted">Código {{ $socio['matricula'] }}</span>
                                @if ($socio['bloqueado'] ?? false)
                                    <span class="ml-2 rounded-full bg-red-500/20 px-2 py-0.5 text-xs font-black uppercase text-red-300">Bloqueado</span>
                                @elseif ($socio['incompleto'] ?? false)
                                    <span class="ml-2 rounded-full bg-amber-400/20 px-2 py-0.5 text-xs font-black uppercase text-amber-300">Cadastro incompleto</span>
                                @endif
                            </span>
                        </button>
                    @endforeach
                </div>
            @elseif (mb_strlen($buscaNome) >= 2)
                <p class="mt-4 text-center text-muted">Ninguém encontrado. Confira o nome ou cadastre o atleta em Sócios.</p>
            @else
                <p class="mt-4 text-center text-muted">A foto aparece para você conferir e confirmar. Não precisa de reconhecimento facial.</p>
            @endif
        @else
            <div class="mt-5 rounded-3xl bg-brand/10 p-6 text-center ring-1 ring-brand/40">
                <div class="flex justify-center">{!! $cartaoFoto($identificado, 'h-36 w-36') !!}</div>
                <p class="mt-4 text-3xl font-black">{{ $identificado['nome'] }}</p>
                <p class="text-base font-bold text-muted">Código {{ $identificado['matricula'] }}</p>

                @if (! empty($identificado['bloqueio']))
                    <div class="mt-4 rounded-2xl bg-red-500/15 p-4 text-left ring-1 ring-red-400/50">
                        <p class="flex items-center gap-2 text-lg font-black text-red-300">
                            <x-heroicon-o-exclamation-triangle class="h-6 w-6" />
                            Atenção: sócio bloqueado
                        </p>
                        <p class="mt-1 text-base font-semibold text-red-100">{{ $identificado['bloqueio'] }}</p>
                        <p class="mt-1 text-sm text-red-200/80">Você decide: registrar na quadra mesmo assim ou escolher outra pessoa. Fica anotado na linha do tempo.</p>
                    </div>
                    <button type="button" wire:click="cadastrarMesmoAssim" class="fp-btn mt-5 w-full bg-amber-400 text-canvas hover:bg-amber-300">
                        <x-heroicon-o-check class="h-7 w-7" />
                        Cadastrar mesmo assim
                    </button>
                @else
                    @if (! empty($identificado['aviso']))
                        <div class="mt-4 rounded-2xl bg-amber-400/10 p-4 text-left ring-1 ring-amber-300/40">
                            <p class="text-base font-black text-amber-300">Cadastro incompleto</p>
                            <p class="mt-1 text-sm font-semibold text-amber-100/90">{{ $identificado['aviso'] }}</p>
                        </div>
                    @endif
                    <button type="button" wire:click="confirmarJogador" class="fp-btn fp-btn-primary mt-5">
                        <x-heroicon-o-check class="h-7 w-7" />
                        Confirmar atleta
                    </button>
                @endif
                <button type="button" wire:click="naoSouEu" class="fp-btn fp-btn-ghost mt-3 w-full">Escolher outra pessoa</button>
            </div>
        @endif
    </div>
@else
    {{-- TABLET DO ATLETA --}}
    @if ($identificado !== [])
        <div class="mx-auto max-w-xl rounded-3xl bg-brand/10 p-6 text-center ring-1 ring-brand/40">
            <p class="text-xs font-black uppercase tracking-[0.22em] text-brand">{{ $viaRosto ? 'Reconhecido pelo rosto' : 'Código digitado' }}</p>
            <div class="mt-4 flex justify-center">{!! $cartaoFoto($identificado, 'h-36 w-36') !!}</div>
            <p class="mt-4 text-4xl font-black leading-tight">{{ $identificado['nome'] }}</p>
            <p class="mt-1 text-xl font-black tabular-nums text-accent">Código {{ $identificado['matricula'] }}</p>

            @if ($exigirFacial && ! $viaRosto)
                <p class="mt-3 text-sm font-semibold text-accent">Olhe para a câmera — libera sozinho</p>
                @php $urlFace = $identificado['face'] ?? $identificado['foto'] ?? null; @endphp
                @if ($urlFace)
                    <div class="mt-4 text-left">
                        <x-reconhecimento-catraca
                            :referencia-url="$urlFace"
                            :nome="$identificado['primeiro_nome']"
                            wire:key="catraca-{{ $identificado['id'] }}-{{ $passoJogador }}"
                        />
                        @error('foto_facial') <p class="mt-2 text-center text-red-400">{{ $message }}</p> @enderror
                    </div>
                @else
                    <p class="mt-3 text-red-400">Este sócio não tem foto facial cadastrada.</p>
                @endif
                <button type="button" wire:click="naoSouEu" class="fp-btn fp-btn-ghost mt-4 w-full">Não sou eu</button>
            @else
                <button type="button" wire:click="confirmarJogador" class="fp-btn fp-btn-primary mt-6">
                    <x-heroicon-o-check class="h-8 w-8" />
                    OK, sou eu
                </button>
                <button type="button" wire:click="naoSouEu" class="fp-btn fp-btn-ghost mt-3 w-full">Não sou eu</button>
            @endif
        </div>
    @elseif ($metodoId === '')
        <p class="text-center text-2xl font-black">Quem é você?</p>
        <p class="mt-1 text-center text-sm text-muted">Escolha como quer se identificar</p>
        <div class="fp-choice-grid">
            <button type="button" wire:click="definirMetodo('rosto')" class="fp-btn fp-choice">
                <x-heroicon-o-face-smile class="h-14 w-14 text-brand" />
                Pelo rosto
                <span>Olhe para a câmera e toque em OK</span>
            </button>
            <button type="button" wire:click="definirMetodo('codigo')" class="fp-btn fp-choice">
                <x-heroicon-o-hashtag class="h-14 w-14 text-accent" />
                Pelo código
                <span>Digite seu código de sócio</span>
            </button>
        </div>
    @elseif ($metodoId === 'rosto')
        <div class="mx-auto max-w-xl">
            <p class="text-center text-xl font-black">Olhe para a câmera</p>
            <p class="mb-3 mt-1 text-center text-sm text-muted">O sistema descobre quem você é. Depois é só tocar em OK.</p>
            @error('foto_facial') <p class="mb-3 rounded-2xl bg-red-500/10 px-4 py-3 text-center font-semibold text-red-300">{{ $message }}</p> @enderror
            <x-identificar-rosto wire:key="rosto-{{ $passoJogador }}" />
            <button type="button" wire:click="definirMetodo('codigo')" class="fp-btn fp-btn-ghost mt-3 w-full">Prefiro digitar o código</button>
        </div>
    @else
        <div class="fp-reserve-grid"
             x-data="{
                codigo: @entangle('codigo'),
                digitar(n) { if (this.codigo.length < 10) this.codigo += String(n) },
                apagar() { this.codigo = this.codigo.slice(0, -1) },
             }">
            <div>
                <p class="text-lg font-black">Digite o seu código de sócio</p>
                <p class="mt-1 text-sm text-muted">Use o teclado e toque em OK</p>
                <div class="fp-code-box mt-4" x-text="codigo || '—'"></div>
                @error('codigo') <p class="mt-3 font-semibold text-red-400">{{ $message }}</p> @enderror
                <button type="button" wire:click="definirMetodo('rosto')" class="fp-btn fp-btn-ghost mt-4 w-full">Prefiro usar o rosto</button>
            </div>
            <div>
                <div class="fp-pad">
                    @foreach ([1,2,3,4,5,6,7,8,9] as $n)
                        <button type="button" class="fp-btn fp-pad-key" @click="digitar({{ $n }})">{{ $n }}</button>
                    @endforeach
                    <button type="button" class="fp-btn fp-pad-key" @click="apagar()">⌫</button>
                    <button type="button" class="fp-btn fp-pad-key" @click="digitar(0)">0</button>
                    <button type="button" class="fp-btn fp-pad-key fp-pad-ok" wire:click="identificarPorCodigo">OK</button>
                </div>
            </div>
        </div>
    @endif
@endif

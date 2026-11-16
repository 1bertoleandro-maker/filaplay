<div class="mx-auto max-w-6xl">
    <p class="text-sm font-semibold uppercase tracking-[0.2em] text-brand">Operação</p>
    <h1 class="mt-2 text-4xl font-bold">Fila e partidas ao vivo</h1>
    <p class="mt-2 text-lg text-muted">Gerencie a fila, chame os próximos e libere as quadras.</p>

    @if (session('status'))
        <p class="mt-6 rounded-2xl border border-brand/40 bg-brand/10 px-4 py-3 text-lg text-brand">{{ session('status') }}</p>
    @endif

    <form wire:submit="entrar" class="mt-8 rounded-2xl border border-line bg-card p-6">
        <h2 class="text-2xl font-semibold">Adicionar jogador à fila</h2>
        <p class="mt-1 text-muted">Escolha a quadra e busque pelo nome ou pela matrícula. A secretaria só confere a foto, sem facial.</p>
        <div class="mt-4 grid gap-4 sm:grid-cols-2">
            <select wire:model="quadra_id" class="min-h-14 rounded-xl border-line bg-canvas text-lg">
                <option value="">Quadra</option>
                @foreach ($quadras as $quadra)
                    <option value="{{ $quadra->id }}">{{ $quadra->nome }}</option>
                @endforeach
            </select>
            <select wire:model="modalidade" class="min-h-14 rounded-xl border-line bg-canvas text-lg">
                @foreach ($modalidades as $m)
                    <option value="{{ $m->value }}">{{ $m->label() }} ({{ $m->jogadores() ?? '2-8' }})</option>
                @endforeach
            </select>
        </div>
        <label class="mt-4 block">
            <span class="text-sm text-muted">Nome ou matrícula</span>
            <input wire:model.live.debounce.300ms="busca" type="search" placeholder="Ex.: Queiroz ou 7160" class="mt-2 w-full min-h-14 rounded-xl border-line bg-canvas px-4 text-lg">
        </label>
        @error('quadra_id') <p class="mt-2 text-red-400">{{ $message }}</p> @enderror
        @error('user_id') <p class="mt-2 text-red-400">{{ $message }}</p> @enderror
        @error('jogador') <p class="mt-2 text-red-400">{{ $message }}</p> @enderror
        @error('quadra') <p class="mt-2 text-red-400">{{ $message }}</p> @enderror

        @if ($selecionado)
            <div class="mt-4 flex items-center gap-4 rounded-2xl border border-brand/40 bg-canvas p-4">
                @if ($selecionado->avatarUrl)
                    <img src="{{ $selecionado->avatarUrl }}" alt="" class="h-16 w-16 rounded-full object-cover">
                @else
                    <span class="flex h-16 w-16 items-center justify-center rounded-full bg-card text-2xl font-bold">{{ mb_substr($selecionado->nome, 0, 1) }}</span>
                @endif
                <div>
                    <p class="text-xl font-bold">{{ $selecionado->nome }}</p>
                    <p class="text-muted">Matrícula {{ $selecionado->matricula }}</p>
                </div>
            </div>
        @elseif (trim($busca) !== '' && $encontrados->isEmpty())
            <p class="mt-4 text-muted">Nenhum sócio encontrado.</p>
        @endif

        @if (trim($busca) !== '' && $encontrados->isNotEmpty())
            <ul class="mt-4 space-y-2">
                @foreach ($encontrados as $socio)
                    <li>
                        <button type="button" wire:click="escolherSocio({{ $socio->id }})" class="flex w-full cursor-pointer items-center gap-3 rounded-xl border px-3 py-2 text-left {{ $selecionado && $selecionado->id === $socio->id ? 'border-brand bg-brand/10' : 'border-line hover:border-brand' }}">
                            @if ($socio->avatarUrl)
                                <img src="{{ $socio->avatarUrl }}" alt="" class="h-12 w-12 rounded-full object-cover">
                            @else
                                <span class="flex h-12 w-12 items-center justify-center rounded-full bg-canvas font-bold">{{ mb_substr($socio->nome, 0, 1) }}</span>
                            @endif
                            <span>
                                <span class="block font-semibold">{{ $socio->nome }}</span>
                                <span class="text-sm text-muted">{{ $socio->matricula }}</span>
                            </span>
                        </button>
                    </li>
                @endforeach
            </ul>
        @endif

        <button type="submit" class="mt-4 min-h-14 rounded-xl bg-brand px-6 text-lg font-semibold text-canvas">Entrar na fila</button>
    </form>

    <div class="mt-8 space-y-6">
        @foreach ($quadrasComFila as $item)
            <article class="rounded-2xl border border-line bg-card p-6">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <h2 class="text-2xl font-bold">{{ $item['quadra']->apelido ?: $item['quadra']->nome }}</h2>
                    <span class="rounded-full px-3 py-1 text-sm font-bold uppercase {{ $item['partida'] ? 'bg-accent text-canvas' : 'bg-brand/20 text-brand' }}">
                        {{ $item['partida'] ? 'Em jogo' : 'Livre' }}
                    </span>
                </div>

                @if ($item['partida'])
                    <div class="mt-4 flex flex-wrap items-center justify-between gap-4 rounded-xl bg-canvas p-4">
                        <div>
                            <p class="text-sm uppercase tracking-wide text-muted">Jogando agora · {{ $item['partida']->modalidade->label() }}</p>
                            <p class="text-lg font-semibold">
                                {{ $item['partida']->jogadores->pluck('nome')->join(', ') }}
                            </p>
                            <p class="text-muted">
                                Início {{ $item['partida']->inicio_real->format('H:i') }} ·
                                {{ $item['partida']->duracao_minutos + $item['partida']->tempo_extra }} min
                                @if ($item['partida']->tempo_extra > 0)
                                    (+{{ $item['partida']->tempo_extra }} extra)
                                @endif
                            </p>
                        </div>
                        <div class="flex gap-2">
                            <button type="button" wire:click="tempoExtra({{ $item['partida']->id }})" class="min-h-12 rounded-xl border border-line px-4 font-semibold hover:border-brand">+5 min</button>
                            <button type="button" wire:click="encerrarPartida({{ $item['partida']->id }})" wire:confirm="Liberar esta quadra agora?" class="min-h-12 rounded-xl bg-accent px-4 font-semibold text-canvas">Liberar quadra</button>
                        </div>
                    </div>
                @endif

                @if ($item['chamados']->isNotEmpty())
                    <div class="mt-4 rounded-xl border border-accent/50 bg-accent/10 p-4">
                        <p class="text-sm font-bold uppercase tracking-wide text-accent">Chamados · aguardando confirmação</p>
                        <ul class="mt-2 space-y-2">
                            @foreach ($item['chamados'] as $fila)
                                <li class="flex items-center justify-between gap-3">
                                    <span class="font-semibold">{{ $fila->user->nome }}</span>
                                    <button type="button" wire:click="confirmarManual({{ $fila->id }})" class="min-h-10 rounded-lg bg-white px-3 text-sm font-semibold text-canvas">Confirmar</button>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="mt-4">
                    <div class="flex items-center justify-between">
                        <p class="text-sm font-bold uppercase tracking-wide text-muted">Aguardando ({{ $item['aguardando']->count() }})</p>
                        @if (! $item['partida'] && $item['chamados']->isEmpty())
                            <button type="button" wire:click="chamarProximos({{ $item['quadra']->id }})" class="text-sm font-semibold text-brand">Chamar próximos</button>
                        @endif
                    </div>
                    <ul class="mt-2 space-y-1.5">
                        @forelse ($item['aguardando'] as $fila)
                            <li class="flex items-center justify-between gap-3 rounded-lg bg-canvas px-3 py-2">
                                <span class="font-semibold">{{ $fila->posicao }}º · {{ $fila->user->nome }} <span class="text-muted">({{ $fila->modalidade->label() }})</span></span>
                                <div class="flex items-center gap-3">
                                    @if ($fila->horario_estimado)
                                        <span class="text-sm text-muted tabular-nums">~{{ $fila->horario_estimado->format('H:i') }}</span>
                                    @endif
                                    <button type="button" wire:click="sair({{ $fila->id }})" class="text-sm font-semibold text-red-400">Remover</button>
                                </div>
                            </li>
                        @empty
                            <li class="px-3 py-3 text-muted">Ninguém na fila.</li>
                        @endforelse
                    </ul>
                </div>
            </article>
        @endforeach
    </div>
</div>

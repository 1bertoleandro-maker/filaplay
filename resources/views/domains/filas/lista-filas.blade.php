<div class="mx-auto max-w-6xl">
    <p class="text-sm font-semibold uppercase tracking-[0.2em] text-brand">Operação</p>
    <h1 class="mt-2 text-4xl font-bold">Fila e partidas ao vivo</h1>
    <p class="mt-2 text-lg text-muted">Gerencie a fila, chame os próximos e libere as quadras.</p>

    @if (session('status'))
        <p class="mt-6 rounded-2xl border border-brand/40 bg-brand/10 px-4 py-3 text-lg text-brand">{{ session('status') }}</p>
    @endif

    <form wire:submit="entrar" class="mt-8 rounded-2xl border border-line bg-card p-6">
        <h2 class="text-2xl font-semibold">Adicionar jogador à fila</h2>
        <div class="mt-4 grid gap-4 sm:grid-cols-4">
            <select wire:model="quadra_id" class="min-h-14 rounded-xl border-line bg-canvas text-lg">
                <option value="">Quadra</option>
                @foreach ($quadras as $quadra)
                    <option value="{{ $quadra->id }}">{{ $quadra->nome }}</option>
                @endforeach
            </select>
            <select wire:model="user_id" class="min-h-14 rounded-xl border-line bg-canvas text-lg">
                <option value="">Sócio</option>
                @foreach ($socios as $socio)
                    <option value="{{ $socio->id }}">{{ $socio->nome }} · {{ $socio->matricula }}</option>
                @endforeach
            </select>
            <select wire:model="modalidade" class="min-h-14 rounded-xl border-line bg-canvas text-lg">
                @foreach ($modalidades as $m)
                    <option value="{{ $m->value }}">{{ $m->label() }} ({{ $m->jogadores() ?? '2-8' }})</option>
                @endforeach
            </select>
            <button type="submit" class="min-h-14 rounded-xl bg-brand px-6 text-lg font-semibold text-canvas">Entrar na fila</button>
        </div>
        @error('jogador') <p class="mt-2 text-red-400">{{ $message }}</p> @enderror
        @error('quadra') <p class="mt-2 text-red-400">{{ $message }}</p> @enderror
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

<div class="mx-auto max-w-5xl">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="text-sm font-semibold uppercase tracking-[0.2em] text-brand">Super admin</p>
            <h1 class="mt-2 text-4xl font-bold">Clubes da plataforma</h1>
            <p class="mt-2 text-lg text-muted">Só você libera, cria e controla a validade de cada clube.</p>
        </div>
        @unless ($formAberto)
            <button type="button" wire:click="novoCliente" class="min-h-14 rounded-2xl bg-brand px-6 text-lg font-semibold text-canvas">
                + Criar cliente
            </button>
        @endunless
    </div>

    @if (session('status'))
        <p class="mt-6 rounded-2xl border border-brand/40 bg-brand/10 px-4 py-3 text-lg text-brand">{{ session('status') }}</p>
    @endif

    @if ($formAberto)
        <section class="mt-8 rounded-3xl border border-line bg-card p-6 sm:p-8">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h2 class="text-2xl font-bold">Criar novo cliente</h2>
                    <p class="mt-2 max-w-2xl text-muted">O clube já nasce ativo. O responsável recebe um convite por e-mail ou WhatsApp para criar a própria senha.</p>
                </div>
                <button type="button" wire:click="fechar" class="rounded-xl px-3 py-2 text-muted hover:bg-canvas hover:text-white">
                    Fechar
                </button>
            </div>

            <form wire:submit="criar" class="mt-6 grid gap-5 sm:grid-cols-2">
                <label class="block">
                    <span class="text-lg text-muted">Nome do clube</span>
                    <input type="text" wire:model="nome_clube" class="mt-2 w-full rounded-xl border-line bg-canvas text-lg" placeholder="Ex: Arena Centro">
                    @error('nome_clube') <span class="text-red-400">{{ $message }}</span> @enderror
                </label>
                <label class="block">
                    <span class="text-lg text-muted">Nome do responsável</span>
                    <input type="text" wire:model="nome_responsavel" class="mt-2 w-full rounded-xl border-line bg-canvas text-lg" placeholder="Nome completo">
                    @error('nome_responsavel') <span class="text-red-400">{{ $message }}</span> @enderror
                </label>
                <label class="block">
                    <span class="text-lg text-muted">E-mail do responsável</span>
                    <input type="email" wire:model="email" class="mt-2 w-full rounded-xl border-line bg-canvas text-lg" placeholder="email@clube.com">
                    @error('email') <span class="text-red-400">{{ $message }}</span> @enderror
                </label>
                <label class="block">
                    <span class="text-lg text-muted">Telefone / WhatsApp</span>
                    <input type="text" wire:model="telefone" class="mt-2 w-full rounded-xl border-line bg-canvas text-lg" placeholder="(11) 99999-0000">
                </label>
                <label class="block sm:col-span-2">
                    <span class="text-lg text-muted">Validade do acesso (opcional)</span>
                    <input type="date" wire:model="expira_em" class="mt-2 w-full max-w-xs rounded-xl border-line bg-canvas text-lg">
                    <span class="mt-1 block text-sm text-muted">Deixe em branco para acesso sem prazo.</span>
                </label>

                <div class="flex flex-wrap gap-3 sm:col-span-2">
                    <button type="submit" class="min-h-14 rounded-2xl bg-brand px-8 text-xl font-semibold text-canvas">
                        Criar cliente e enviar convite
                    </button>
                    <button type="button" wire:click="fechar" class="min-h-14 rounded-2xl border border-line px-6 text-lg font-semibold">
                        Cancelar
                    </button>
                </div>
            </form>
        </section>
    @endif

    <div class="mt-8 space-y-3">
        @foreach ($clubes as $clube)
            <article class="flex flex-wrap items-center justify-between gap-4 rounded-2xl border border-line bg-card px-5 py-4">
                <div>
                    <p class="text-xl font-semibold">{{ $clube->nome }}</p>
                    <p class="text-muted">{{ $clube->email }} @if($clube->telefone) · {{ $clube->telefone }} @endif</p>
                    <p class="text-sm text-muted">Cadastrado em {{ $clube->created_at->format('d/m/Y H:i') }}</p>

                    @if ($editandoExpiracaoId === $clube->id)
                        <form wire:submit="salvarExpiracao" class="mt-3 flex flex-wrap items-center gap-2">
                            <label class="text-sm text-muted">Validade do acesso</label>
                            <input type="date" wire:model="novaExpiracao" class="rounded-xl border-line bg-canvas px-3 py-2 text-base">
                            <button type="submit" class="rounded-xl bg-brand px-3 py-2 text-sm font-semibold text-canvas">Salvar</button>
                            <button type="button" wire:click="cancelarEdicaoExpiracao" class="text-sm text-muted">Cancelar</button>
                        </form>
                    @else
                        <button type="button" wire:click="editarExpiracao({{ $clube->id }})" class="mt-2 flex items-center gap-1.5 text-sm font-semibold {{ $clube->expirado() ? 'text-red-400' : 'text-brand' }}">
                            <x-heroicon-o-calendar class="h-4 w-4" />
                            @if ($clube->expira_em)
                                Validade: {{ $clube->expira_em->format('d/m/Y') }} {{ $clube->expirado() ? '(expirado)' : '' }}
                            @else
                                Sem data de expiração (editar)
                            @endif
                        </button>
                    @endif
                </div>
                <div class="flex items-center gap-3">
                    <span class="rounded-full px-4 py-1.5 text-sm font-bold uppercase
                        {{ match($clube->status) {
                            $statusEnum::Ativo => 'bg-brand/20 text-brand',
                            $statusEnum::Pendente => 'bg-accent/20 text-accent',
                            $statusEnum::Bloqueado => 'bg-red-500/20 text-red-400',
                        } }}">
                        {{ $clube->status->label() }}
                    </span>
                    @if ($clube->status !== $statusEnum::Ativo)
                        <button type="button" wire:click="aprovar({{ $clube->id }})" class="min-h-12 rounded-xl bg-brand px-4 font-semibold text-canvas">Aprovar</button>
                    @endif
                    @if ($clube->status !== $statusEnum::Bloqueado)
                        <button type="button" wire:click="bloquear({{ $clube->id }})" wire:confirm="Bloquear o acesso deste clube?" class="min-h-12 rounded-xl border border-red-400 px-4 font-semibold text-red-400">Bloquear</button>
                    @endif
                </div>
            </article>
        @endforeach
    </div>
</div>

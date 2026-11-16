<div class="mx-auto max-w-5xl">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="text-sm font-semibold uppercase tracking-[0.2em] text-brand">Pessoas</p>
            <h1 class="mt-2 text-4xl font-bold">Equipe do clube</h1>
            <p class="mt-2 text-lg text-muted">
                Quem trabalha no <span class="font-semibold text-white">{{ $clube->nome }}</span>: recepção, professores e administradores.
                Os sócios ficam em <a href="{{ route('socios.index') }}" class="font-semibold text-brand underline">Sócios</a>.
            </p>
        </div>
        <div class="text-right">
            <p class="text-sm font-semibold text-muted">
                Equipe: <span class="text-white">{{ $usada }}</span> de <span class="text-white">{{ $limite ?? 'sem limite' }}</span>
            </p>
            @if ($temVaga)
                <button type="button" wire:click="novo" class="mt-2 min-h-12 cursor-pointer rounded-2xl bg-brand px-5 text-base font-black text-canvas hover:brightness-110">
                    Novo membro da equipe
                </button>
            @else
                <p class="mt-2 max-w-xs text-sm font-semibold text-accent">
                    Limite da equipe atingido. Fale com <a href="{{ \App\Support\Contato::url() }}" target="_blank" rel="noopener" class="underline">{{ \App\Support\Contato::rotulo() }}</a> para ampliar.
                </p>
            @endif
        </div>
    </div>

    @if (session('status'))
        <p class="mt-6 rounded-2xl border border-brand/40 bg-brand/10 px-4 py-3 text-lg text-brand">{{ session('status') }}</p>
    @endif
    @if (session('erro'))
        <p class="mt-6 rounded-2xl border border-red-400/40 bg-red-500/10 px-4 py-3 text-lg text-red-400">{{ session('erro') }}</p>
    @endif

    @if ($formAberto)
        <form wire:submit="salvar" class="mt-6 rounded-2xl border border-brand/30 bg-card p-5" wire:key="form-equipe-{{ $membroId ?? 'novo' }}">
            <div class="flex items-center justify-between gap-3">
                <h2 class="text-xl font-bold">{{ $membroId ? 'Editar membro da equipe' : 'Novo membro da equipe' }}</h2>
                <button type="button" wire:click="fechar" class="cursor-pointer text-muted hover:text-white">Fechar</button>
            </div>

            @error('limite') <p class="mt-3 rounded-xl bg-red-500/10 px-4 py-3 font-semibold text-red-400">{{ $message }}</p> @enderror

            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <label class="block">
                    <span class="text-sm text-muted">Nome</span>
                    <input type="text" wire:model="nome" class="mt-1 w-full rounded-xl border-line bg-canvas text-base" autocomplete="off">
                    @error('nome') <span class="text-red-400">{{ $message }}</span> @enderror
                </label>
                <label class="block">
                    <span class="text-sm text-muted">E-mail (é o login da pessoa)</span>
                    <input type="email" wire:model="email" class="mt-1 w-full rounded-xl border-line bg-canvas text-base" autocomplete="off">
                    @error('email') <span class="text-red-400">{{ $message }}</span> @enderror
                </label>
                <label class="block">
                    <span class="text-sm text-muted">Telefone / WhatsApp (recebe o convite)</span>
                    <input type="text" wire:model="telefone" class="mt-1 w-full rounded-xl border-line bg-canvas text-base">
                </label>
                <label class="block">
                    <span class="text-sm text-muted">Função</span>
                    <select wire:model.live="role" class="mt-1 w-full rounded-xl border-line bg-canvas text-base">
                        <option value="recepcao">Recepção (secretaria)</option>
                        <option value="professor">Professor</option>
                        <option value="administrador">Administrador</option>
                    </select>
                    @error('role') <span class="text-red-400">{{ $message }}</span> @enderror
                    <span class="mt-2 block text-sm text-muted">
                        @if ($role === 'recepcao')
                            Atende no balcão: cadastra sócios, reserva quadras, registra perdas, soma minutos e usa o tablet no modo Recepção.
                        @elseif ($role === 'professor')
                            Vê quadras, fila e partidas e usa o painel da TV. Não cadastra sócios.
                        @else
                            Controla tudo do clube: horários, quadras, sócios, equipe e configurações.
                        @endif
                    </span>
                </label>
            </div>
            @unless ($membroId)
                <p class="mt-3 text-sm text-muted">A pessoa recebe um convite por e-mail (e WhatsApp, se tiver telefone) para criar a própria senha.</p>
            @endunless
            <button type="submit" class="mt-4 min-h-12 cursor-pointer rounded-2xl bg-brand px-6 text-lg font-black text-canvas">
                {{ $membroId ? 'Salvar' : 'Cadastrar e enviar convite' }}
            </button>
        </form>
    @endif

    <div class="mt-6 overflow-hidden rounded-2xl border border-line">
        <table class="min-w-full text-left text-base">
            <thead class="bg-card text-muted">
                <tr>
                    <th class="px-4 py-3 font-medium">Pessoa</th>
                    <th class="px-4 py-3 font-medium">Função</th>
                    <th class="px-4 py-3 font-medium">Acesso</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($membros as $membro)
                    <tr class="border-t border-line" wire:key="membro-{{ $membro->id }}">
                        <td class="px-4 py-3">
                            <p class="font-semibold">{{ $membro->nome }} @if ($membro->id === auth()->id()) <span class="text-sm font-normal text-muted">(você)</span> @endif</p>
                            <p class="text-sm text-muted">{{ $membro->email }}</p>
                        </td>
                        <td class="px-4 py-3">{{ $membro->role->label() }}</td>
                        <td class="px-4 py-3">
                            @if ($membro->bloqueado)
                                <span class="font-semibold text-red-400">Bloqueado</span>
                            @elseif ($membro->status === \App\Domains\Jogadores\Enums\UserStatus::Pendente)
                                <span class="font-semibold text-accent">Convite enviado</span>
                            @else
                                <span class="font-semibold text-brand">Ativo</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right whitespace-nowrap">
                            <button type="button" wire:click="editar({{ $membro->id }})" class="cursor-pointer font-semibold text-brand hover:underline">Editar</button>
                            @if ($membro->status === \App\Domains\Jogadores\Enums\UserStatus::Pendente && ! $membro->bloqueado)
                                <button type="button" wire:click="reenviarConvite({{ $membro->id }})" class="ml-3 cursor-pointer font-semibold text-accent hover:underline">Reenviar convite</button>
                            @endif
                            @if ($membro->id !== auth()->id())
                                <button type="button" wire:click="alternarAcesso({{ $membro->id }})"
                                        wire:confirm="{{ $membro->bloqueado ? 'Liberar o acesso desta pessoa?' : 'Bloquear o acesso desta pessoa?' }}"
                                        class="ml-3 cursor-pointer font-semibold {{ $membro->bloqueado ? 'text-white' : 'text-red-400' }} hover:underline">
                                    {{ $membro->bloqueado ? 'Liberar' : 'Bloquear' }}
                                </button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-4 py-10 text-center text-muted">Ninguém na equipe ainda.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

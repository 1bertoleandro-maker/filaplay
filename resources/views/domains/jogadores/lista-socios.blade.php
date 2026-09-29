<div class="mx-auto max-w-6xl" x-data="{ importando: @entangle('importarAberto') }">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="text-sm font-semibold uppercase tracking-[0.2em] text-brand">Pessoas</p>
            <h1 class="mt-2 text-4xl font-bold">Sócios</h1>
            <p class="mt-2 text-lg text-muted">Lista primeiro. Clique em <span class="font-semibold text-white">Editar</span> ou em <span class="font-semibold text-white">Novo sócio</span>.</p>
        </div>
        <div class="flex flex-wrap gap-3">
            <a href="{{ route('socios.planilha.modelo') }}" class="flex min-h-12 cursor-pointer items-center gap-2 rounded-2xl border border-line px-4 text-base font-semibold hover:border-brand">
                <x-heroicon-o-arrow-down-tray class="h-5 w-5" /> Modelo
            </a>
            <a href="{{ route('socios.planilha.exportar') }}" class="flex min-h-12 cursor-pointer items-center gap-2 rounded-2xl border border-line px-4 text-base font-semibold hover:border-brand">
                <x-heroicon-o-document-arrow-down class="h-5 w-5" /> Exportar
            </a>
            <button type="button" wire:click="abrirImportacao" class="flex min-h-12 cursor-pointer items-center gap-2 rounded-2xl border border-line px-4 text-base font-semibold hover:border-brand">
                <x-heroicon-o-arrow-up-tray class="h-5 w-5" /> Importar
            </button>
            <button type="button" wire:click="novo" class="min-h-12 cursor-pointer rounded-2xl bg-brand px-5 text-base font-black text-canvas hover:brightness-110">
                Novo sócio
            </button>
        </div>
    </div>

    @if (session('status'))
        <p class="mt-6 rounded-2xl border border-brand/40 bg-brand/10 px-4 py-3 text-lg text-brand">{{ session('status') }}</p>
    @endif

    <input type="search" wire:model.live.debounce.300ms="busca" placeholder="Buscar nome, matrícula ou e-mail"
           class="mt-6 w-full rounded-2xl border-line bg-card px-4 py-3 text-lg">

    <div x-show="importando" x-cloak style="display: none;" class="fixed inset-0 z-40 flex items-center justify-center bg-black/70 p-4">
        <div @click.outside="$wire.fecharImportacao()" class="w-full max-w-lg rounded-3xl border border-line bg-card p-6 shadow-2xl">
            <div class="flex items-center justify-between">
                <h2 class="text-2xl font-bold">Importar sócios pela planilha</h2>
                <button type="button" wire:click="fecharImportacao" class="cursor-pointer text-muted hover:text-white">✕</button>
            </div>
            <p class="mt-2 text-muted">
                Baixe o <a href="{{ route('socios.planilha.modelo') }}" class="font-semibold text-brand underline">modelo Excel</a>,
                preencha uma linha por sócio e envie o arquivo.
            </p>
            <form wire:submit="importar" class="mt-6 space-y-4">
                <input type="file" wire:model="planilha" accept=".xlsx,.xls,.csv" class="w-full text-lg">
                @error('planilha') <span class="text-red-400">{{ $message }}</span> @enderror
                <button type="submit" class="min-h-12 w-full cursor-pointer rounded-2xl bg-brand text-lg font-semibold text-canvas">Importar</button>
            </form>
            @if ($resultadoImportacao)
                <div class="mt-6 rounded-2xl border border-line bg-canvas p-4">
                    <p class="font-semibold text-brand">{{ $resultadoImportacao['criados'] }} de {{ $resultadoImportacao['linhas'] }} importada(s).</p>
                    @foreach ($resultadoImportacao['erros'] as $erro)
                        <p class="mt-1 text-sm text-red-400">{{ $erro }}</p>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    @if ($facialSocioId)
        <section class="mt-6 max-w-2xl rounded-2xl border border-brand/40 bg-card p-5">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-xs font-black uppercase tracking-[0.18em] text-brand">Facial na secretaria</p>
                    <h2 class="mt-1 text-xl font-bold">{{ $facialNome }}</h2>
                </div>
                <button type="button" wire:click="fecharFacialBalcao" class="cursor-pointer rounded-xl px-3 py-2 text-muted hover:bg-canvas hover:text-white">Fechar</button>
            </div>
            <div class="mt-4">
                <x-camera-rosto model="faceBalcao" rotulo="Ligar câmera" tamanho="grande" />
                @error('faceBalcao') <p class="mt-2 text-red-400">{{ $message }}</p> @enderror
                <div class="mt-4 flex flex-wrap gap-2">
                    <button type="button" wire:click="salvarFacialBalcao" class="inline-flex min-h-12 cursor-pointer items-center gap-2 rounded-xl bg-brand px-5 text-base font-black text-canvas">
                        Salvar facial
                    </button>
                    <button type="button" wire:click="enviarConviteFacial({{ $facialSocioId }})" class="inline-flex min-h-12 cursor-pointer items-center gap-2 rounded-xl border border-line px-5 text-base font-semibold">
                        Enviar link
                    </button>
                </div>
            </div>
        </section>
    @endif

    @if ($formAberto)
        <form wire:submit="salvar" class="mt-6 rounded-2xl border border-brand/30 bg-card p-5" wire:key="form-socio-{{ $socioId ?? 'novo' }}">
            <div class="flex items-center justify-between gap-3">
                <h2 class="text-xl font-bold">{{ $socioId ? 'Editar sócio' : 'Novo sócio' }}</h2>
                <button type="button" wire:click="fechar" class="cursor-pointer text-muted hover:text-white">Fechar</button>
            </div>
            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <label class="block">
                    <span class="text-sm text-muted">Nome</span>
                    <input type="text" wire:model="nome" class="mt-1 w-full rounded-xl border-line bg-canvas text-base">
                    @error('nome') <span class="text-red-400">{{ $message }}</span> @enderror
                </label>
                <label class="block">
                    <span class="text-sm text-muted">Matrícula (código no tablet)</span>
                    <input type="text" wire:model="matricula" class="mt-1 w-full rounded-xl border-line bg-canvas text-base">
                    @error('matricula') <span class="text-red-400">{{ $message }}</span> @enderror
                </label>
                <label class="block">
                    <span class="text-sm text-muted">Telefone</span>
                    <input type="text" wire:model="telefone" class="mt-1 w-full rounded-xl border-line bg-canvas text-base">
                </label>
                <label class="block">
                    <span class="text-sm text-muted">E-mail</span>
                    <input type="email" wire:model="email" class="mt-1 w-full rounded-xl border-line bg-canvas text-base">
                    @error('email') <span class="text-red-400">{{ $message }}</span> @enderror
                </label>
                <label class="block">
                    <span class="text-sm text-muted">Papel</span>
                    <select wire:model="role" class="mt-1 w-full rounded-xl border-line bg-canvas text-base">
                        @foreach ($papeis as $papel)
                            @if ($ehAdmin || $papel !== \App\Domains\Jogadores\Enums\UserRole::Administrador)
                                <option value="{{ $papel->value }}">{{ $papel->label() }}</option>
                            @endif
                        @endforeach
                    </select>
                    @error('role') <span class="text-red-400">{{ $message }}</span> @enderror
                </label>
                <label class="block">
                    <span class="text-sm text-muted">Nível</span>
                    <select wire:model="nivel" class="mt-1 w-full rounded-xl border-line bg-canvas text-base">
                        <option value="">Sem nível</option>
                        @foreach ($niveis as $nivel)
                            <option value="{{ $nivel->value }}">{{ $nivel->label() }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="block sm:col-span-2">
                    <span class="text-sm text-muted">Foto de perfil (opcional)</span>
                    <input type="file" wire:model="foto" accept="image/*" class="mt-1 w-full text-base">
                </label>
                <div class="sm:col-span-2 max-w-md">
                    <p class="text-sm font-bold">Reconhecimento facial <span class="font-normal text-muted">(opcional agora)</span></p>
                    <p class="mb-2 text-xs text-muted">Pode tirar a foto aqui ou depois em “Cadastrar facial” na lista.</p>
                    <x-camera-rosto model="face" rotulo="Ligar câmera" compacto />
                    @error('face') <p class="mt-1 text-red-400">{{ $message }}</p> @enderror
                </div>
            </div>
            <p class="mt-3 text-sm text-muted">Sem foto? Enviamos link por e-mail/WhatsApp para o sócio concluir.</p>
            <button type="submit" class="mt-4 min-h-12 cursor-pointer rounded-2xl bg-brand px-6 text-lg font-black text-canvas">Salvar sócio</button>
        </form>
    @endif

    <div class="mt-6 overflow-hidden rounded-2xl border border-line">
        <table class="min-w-full text-left text-base">
            <thead class="bg-card text-muted">
                <tr>
                    <th class="px-4 py-3 font-medium">Sócio</th>
                    <th class="px-4 py-3 font-medium">Matrícula</th>
                    <th class="px-4 py-3 font-medium">Papel</th>
                    <th class="px-4 py-3 font-medium">Status</th>
                    <th class="px-4 py-3 font-medium">Facial</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($socios as $socio)
                    <tr @class([
                        'border-t border-line',
                        'bg-brand/5' => $formAberto && $socioId === $socio->id,
                    ])>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                @if ($socio->fotoUrl)
                                    <img src="{{ $socio->fotoUrl }}" alt="" class="h-10 w-10 rounded-full object-cover">
                                @else
                                    <span class="flex h-10 w-10 items-center justify-center rounded-full bg-card font-bold">{{ mb_substr($socio->nome, 0, 1) }}</span>
                                @endif
                                <div>
                                    <p class="font-semibold">{{ $socio->nome }}</p>
                                    <p class="text-sm text-muted">{{ $socio->email }}</p>
                                    <button type="button" wire:click="editar({{ $socio->id }})" class="mt-1 cursor-pointer text-sm font-bold text-brand hover:underline">
                                        Editar
                                    </button>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3 font-mono tabular-nums">{{ $socio->matricula }}</td>
                        <td class="px-4 py-3">{{ $socio->role->label() }}</td>
                        <td class="px-4 py-3">
                            @if ($socio->bloqueado || $socio->status === \App\Domains\Jogadores\Enums\UserStatus::Inativo)
                                <span class="font-semibold text-red-400">Inativo</span>
                            @elseif ($socio->status === \App\Domains\Jogadores\Enums\UserStatus::Pendente)
                                <span class="font-semibold text-accent">Pendente</span>
                            @else
                                <span class="font-semibold text-brand">Ativo</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            @if ($socio->cadastro_facial_completo)
                                <span class="font-semibold text-brand">Pronto</span>
                            @else
                                <span class="text-accent">Pendente</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right whitespace-nowrap">
                            <button type="button" wire:click="abrirFacialBalcao({{ $socio->id }})" class="cursor-pointer font-semibold text-white hover:underline">Facial</button>
                            <button type="button" wire:click="enviarConviteFacial({{ $socio->id }})" class="ml-3 cursor-pointer font-semibold text-accent hover:underline">Link</button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-10 text-center text-muted">Nenhum sócio ainda. Clique em Novo sócio.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-6">{{ $socios->links() }}</div>
</div>

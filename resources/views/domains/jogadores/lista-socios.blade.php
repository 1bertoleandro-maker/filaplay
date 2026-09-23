<div class="mx-auto max-w-6xl" x-data="{ importando: @entangle('importarAberto') }">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="text-sm font-semibold uppercase tracking-[0.2em] text-brand">Pessoas</p>
            <h1 class="mt-2 text-4xl font-bold">Sócios e clientes</h1>
            <p class="mt-2 text-lg text-muted">Cadastro, foto de perfil e reconhecimento facial.</p>
        </div>
        <div class="flex flex-wrap gap-3">
            <a href="{{ route('socios.planilha.modelo') }}" class="flex min-h-14 items-center gap-2 rounded-2xl border border-line px-5 text-lg font-semibold hover:border-brand">
                <x-heroicon-o-arrow-down-tray class="h-5 w-5" /> Modelo Excel
            </a>
            <a href="{{ route('socios.planilha.exportar') }}" class="flex min-h-14 items-center gap-2 rounded-2xl border border-line px-5 text-lg font-semibold hover:border-brand">
                <x-heroicon-o-document-arrow-down class="h-5 w-5" /> Exportar
            </a>
            <button type="button" wire:click="abrirImportacao" class="flex min-h-14 items-center gap-2 rounded-2xl border border-line px-5 text-lg font-semibold hover:border-brand">
                <x-heroicon-o-arrow-up-tray class="h-5 w-5" /> Importar planilha
            </button>
            <button type="button" wire:click="novo" class="min-h-14 rounded-2xl bg-brand px-6 text-lg font-semibold text-canvas">
                Novo sócio
            </button>
        </div>
    </div>

    @if (session('status'))
        <p class="mt-6 rounded-2xl border border-brand/40 bg-brand/10 px-4 py-3 text-lg text-brand">{{ session('status') }}</p>
    @endif

    <input type="search" wire:model.live.debounce.300ms="busca" placeholder="Buscar nome, matrícula ou e-mail"
           class="mt-6 w-full rounded-2xl border-line bg-card px-4 py-4 text-lg">

    {{-- MODAL: importar planilha --}}
    <div x-show="importando" x-cloak style="display: none;" class="fixed inset-0 z-40 flex items-center justify-center bg-black/70 p-4">
        <div @click.outside="$wire.fecharImportacao()" class="w-full max-w-lg rounded-3xl border border-line bg-card p-6 shadow-2xl">
            <div class="flex items-center justify-between">
                <h2 class="text-2xl font-bold">Importar sócios pela planilha</h2>
                <button type="button" wire:click="fecharImportacao" class="text-muted hover:text-white">✕</button>
            </div>
            <p class="mt-2 text-muted">
                Baixe o <a href="{{ route('socios.planilha.modelo') }}" class="font-semibold text-brand underline">modelo Excel</a>,
                preencha uma linha por sócio e envie o arquivo (.xlsx, .xls ou .csv).
            </p>

            <form wire:submit="importar" class="mt-6 space-y-4">
                <input type="file" wire:model="planilha" accept=".xlsx,.xls,.csv" class="w-full text-lg">
                @error('planilha') <span class="text-red-400">{{ $message }}</span> @enderror

                <div wire:loading wire:target="planilha,importar" class="text-muted">Processando planilha…</div>

                <button type="submit" class="min-h-14 w-full rounded-2xl bg-brand text-xl font-semibold text-canvas">
                    Importar
                </button>
            </form>

            @if ($resultadoImportacao)
                <div class="mt-6 rounded-2xl border border-line bg-canvas p-4">
                    <p class="text-lg font-semibold text-brand">
                        {{ $resultadoImportacao['criados'] }} de {{ $resultadoImportacao['linhas'] }} linha(s) importada(s) com sucesso.
                    </p>
                    @if (count($resultadoImportacao['erros']) > 0)
                        <p class="mt-3 font-semibold text-red-400">Linhas com problema:</p>
                        <ul class="mt-2 max-h-48 space-y-1 overflow-y-auto text-sm text-red-400">
                            @foreach ($resultadoImportacao['erros'] as $erro)
                                <li>{{ $erro }}</li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            @endif
        </div>
    </div>

    @if ($formAberto)
        <form wire:submit="salvar" class="mt-8 rounded-2xl border border-line bg-card p-6">
            <div class="flex items-center justify-between">
                <h2 class="text-2xl font-semibold">{{ $socioId ? 'Editar sócio' : 'Novo sócio' }}</h2>
                <button type="button" wire:click="fechar" class="text-muted">Fechar</button>
            </div>
            <div class="mt-6 grid gap-5 sm:grid-cols-2">
                <label class="block">
                    <span class="text-lg text-muted">Nome</span>
                    <input type="text" wire:model="nome" class="mt-2 w-full rounded-xl border-line bg-canvas text-lg">
                    @error('nome') <span class="text-red-400">{{ $message }}</span> @enderror
                </label>
                <label class="block">
                    <span class="text-lg text-muted">Matrícula</span>
                    <input type="text" wire:model="matricula" class="mt-2 w-full rounded-xl border-line bg-canvas text-lg">
                    @error('matricula') <span class="text-red-400">{{ $message }}</span> @enderror
                </label>
                <label class="block">
                    <span class="text-lg text-muted">Telefone</span>
                    <input type="text" wire:model="telefone" class="mt-2 w-full rounded-xl border-line bg-canvas text-lg">
                </label>
                <label class="block">
                    <span class="text-lg text-muted">E-mail</span>
                    <input type="email" wire:model="email" class="mt-2 w-full rounded-xl border-line bg-canvas text-lg">
                    @error('email') <span class="text-red-400">{{ $message }}</span> @enderror
                </label>
                <label class="block">
                    <span class="text-lg text-muted">Papel</span>
                    <select wire:model="role" class="mt-2 w-full rounded-xl border-line bg-canvas text-lg">
                        @foreach ($papeis as $papel)
                            @if ($ehAdmin || $papel !== \App\Domains\Jogadores\Enums\UserRole::Administrador)
                                <option value="{{ $papel->value }}">{{ $papel->label() }}</option>
                            @endif
                        @endforeach
                    </select>
                    @error('role') <span class="text-red-400">{{ $message }}</span> @enderror
                </label>
                <label class="block">
                    <span class="text-lg text-muted">Nível</span>
                    <select wire:model="nivel" class="mt-2 w-full rounded-xl border-line bg-canvas text-lg">
                        <option value="">Sem nível</option>
                        @foreach ($niveis as $nivel)
                            <option value="{{ $nivel->value }}">{{ $nivel->label() }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="block">
                    <span class="text-lg text-muted">Foto de perfil</span>
                    <input type="file" wire:model="foto" accept="image/*" class="mt-2 w-full text-lg">
                </label>
                <label class="block">
                    <span class="text-lg text-muted">Foto facial (validação)</span>
                    <input type="file" wire:model="face" accept="image/*" class="mt-2 w-full text-lg">
                </label>
            </div>
            <p class="mt-4 text-muted">Sem foto agora? Enviamos um link por e-mail/WhatsApp para a pessoa definir a própria senha.</p>
            <button type="submit" class="mt-6 min-h-14 rounded-2xl bg-brand px-8 text-xl font-semibold text-canvas">Salvar sócio</button>
        </form>
    @endif

    <div class="mt-8 overflow-hidden rounded-2xl border border-line">
        <table class="min-w-full text-left text-lg">
            <thead class="bg-card text-muted">
                <tr>
                    <th class="px-4 py-3 font-medium">Sócio</th>
                    <th class="px-4 py-3 font-medium">Matrícula</th>
                    <th class="px-4 py-3 font-medium">Papel</th>
                    <th class="px-4 py-3 font-medium">Facial</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($socios as $socio)
                    <tr class="border-t border-line">
                        <td class="px-4 py-4">
                            <div class="flex items-center gap-3">
                                @if ($socio->fotoUrl)
                                    <img src="{{ $socio->fotoUrl }}" alt="" class="h-12 w-12 rounded-full object-cover">
                                @else
                                    <span class="flex h-12 w-12 items-center justify-center rounded-full bg-card font-bold">{{ mb_substr($socio->nome, 0, 1) }}</span>
                                @endif
                                <div>
                                    <p class="font-semibold">{{ $socio->nome }}</p>
                                    <p class="text-sm text-muted">{{ $socio->email }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-4">{{ $socio->matricula }}</td>
                        <td class="px-4 py-4">{{ $socio->role->label() }}</td>
                        <td class="px-4 py-4">{{ $socio->cadastro_facial_completo ? 'Pronto' : 'Pendente' }}</td>
                        <td class="px-4 py-4 text-right">
                            <button type="button" wire:click="editar({{ $socio->id }})" class="font-semibold text-brand">Editar</button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-6">{{ $socios->links() }}</div>
</div>

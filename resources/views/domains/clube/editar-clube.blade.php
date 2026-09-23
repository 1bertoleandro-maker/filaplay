<div class="mx-auto max-w-5xl">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="text-sm font-semibold uppercase tracking-[0.2em] text-brand">Empresa / Clube</p>
            <h1 class="mt-2 text-4xl font-bold">Cadastro da empresa ou clube</h1>
            <p class="mt-2 text-lg text-muted">Dados da arena, logo e horário de funcionamento.</p>
        </div>
        @if ($tenant->logoUrl)
            <img src="{{ $tenant->logoUrl }}" alt="Logo da empresa" class="h-24 w-24 rounded-2xl object-cover ring-2 ring-line">
        @endif
    </div>

    @if (session('status'))
        <p class="mt-6 rounded-2xl border border-brand/40 bg-brand/10 px-4 py-3 text-lg text-brand">{{ session('status') }}</p>
    @endif

    <section class="mt-8 flex flex-wrap items-center justify-between gap-4 rounded-2xl border {{ $tenant->modo_chuva ? 'border-accent bg-accent/10' : 'border-line bg-card' }} p-6">
        <div>
            <p class="text-2xl font-semibold {{ $tenant->modo_chuva ? 'text-accent' : '' }}">Modo chuva {{ $tenant->modo_chuva ? 'ativado' : 'desativado' }}</p>
            <p class="mt-1 text-muted">Quando ativo, só quadras cobertas ficam disponíveis e as partidas passam a durar 40 minutos.</p>
        </div>
        <button type="button" wire:click="alternarModoChuva"
                class="min-h-14 rounded-2xl px-6 text-lg font-semibold {{ $tenant->modo_chuva ? 'bg-white text-canvas' : 'bg-accent text-canvas' }}">
            {{ $tenant->modo_chuva ? 'Desativar modo chuva' : 'Ativar modo chuva' }}
        </button>
    </section>

    <form wire:submit="salvar" class="mt-8 space-y-8">
        <section class="rounded-2xl border border-line bg-card p-6">
            <h2 class="text-2xl font-semibold">Identidade</h2>
            <div class="mt-6 grid gap-5 sm:grid-cols-2">
                <label class="block sm:col-span-2">
                    <span class="text-lg text-muted">Nome da empresa / clube</span>
                    <input type="text" wire:model="nome" class="mt-2 w-full rounded-xl border-line bg-canvas text-lg">
                    @error('nome') <span class="text-red-400">{{ $message }}</span> @enderror
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
                    <span class="text-lg text-muted">Logo da empresa</span>
                    <input type="file" wire:model="logo" accept="image/*" class="mt-2 w-full text-lg">
                    @error('logo') <span class="text-red-400">{{ $message }}</span> @enderror
                    <span class="mt-1 block text-sm text-muted">Aparece no menu, na TV e no tablet do clube.</span>
                </label>
                <label class="flex min-h-14 items-center gap-3 self-end rounded-xl border border-line bg-canvas px-4">
                    <input type="checkbox" wire:model="exibir_logo" class="h-5 w-5 rounded border-line bg-card">
                    <span class="text-lg">Mostrar o logo da empresa no ambiente do clube</span>
                </label>
                @if ($logo)
                    <div>
                        <p class="text-muted">Prévia da logo</p>
                        <img src="{{ $logo->temporaryUrl() }}" alt="Prévia" class="mt-2 h-28 rounded-2xl object-contain">
                    </div>
                @elseif ($tenant->logoUrl)
                    <div>
                        <p class="text-muted">Logo atual</p>
                        <img src="{{ $tenant->logoUrl }}" alt="Logo da empresa" class="mt-2 h-28 rounded-2xl object-contain">
                    </div>
                @endif
            </div>
        </section>

        <section class="rounded-2xl border border-line bg-card p-6">
            <h2 class="text-2xl font-semibold">Endereço e localização</h2>
            <p class="mt-1 text-muted">Digite o CEP para buscar a rua e o bairro automaticamente. Depois informe o número para localizar a quadra no mapa (usado no raio GPS de presença).</p>

            <div class="mt-6 grid gap-5 sm:grid-cols-2">
                <label class="block">
                    <span class="text-lg text-muted">CEP</span>
                    <div class="mt-2 flex gap-2">
                        <input type="text" wire:model="cep" wire:keydown.enter.prevent="buscarCep" placeholder="00000-000" maxlength="9"
                               class="w-full rounded-xl border-line bg-canvas text-lg">
                        <button type="button" wire:click="buscarCep" wire:loading.attr="disabled" wire:target="buscarCep"
                                class="min-h-12 shrink-0 rounded-xl bg-brand px-4 text-lg font-semibold text-canvas">
                            <span wire:loading.remove wire:target="buscarCep">Buscar</span>
                            <span wire:loading wire:target="buscarCep">Buscando…</span>
                        </button>
                    </div>
                    @error('cep') <span class="text-red-400">{{ $message }}</span> @enderror
                </label>
                <label class="block">
                    <span class="text-lg text-muted">Número</span>
                    <div class="mt-2 flex gap-2">
                        <input type="text" wire:model="numero" wire:keydown.enter.prevent="atualizarGeolocalizacao" placeholder="Ex: 123"
                               class="w-full rounded-xl border-line bg-canvas text-lg">
                        <button type="button" wire:click="atualizarGeolocalizacao" wire:loading.attr="disabled" wire:target="atualizarGeolocalizacao"
                                class="min-h-12 shrink-0 rounded-xl bg-brand px-4 text-lg font-semibold text-canvas">
                            <span wire:loading.remove wire:target="atualizarGeolocalizacao">Localizar</span>
                            <span wire:loading wire:target="atualizarGeolocalizacao">Localizando…</span>
                        </button>
                    </div>
                </label>

                @if ($localizacaoMensagem)
                    <p class="sm:col-span-2 rounded-xl border px-4 py-3 text-lg {{ $localizacaoStatus === 'ok' ? 'border-brand/40 bg-brand/10 text-brand' : 'border-accent/40 bg-accent/10 text-accent' }}">
                        {{ $localizacaoMensagem }}
                    </p>
                @endif

                <label class="block sm:col-span-2">
                    <span class="text-lg text-muted">Rua / Logradouro</span>
                    <input type="text" wire:model="endereco" class="mt-2 w-full rounded-xl border-line bg-canvas text-lg">
                </label>
                <label class="block">
                    <span class="text-lg text-muted">Bairro</span>
                    <input type="text" wire:model="bairro" class="mt-2 w-full rounded-xl border-line bg-canvas text-lg">
                </label>
                <div class="grid grid-cols-[1fr_100px] gap-3">
                    <label class="block">
                        <span class="text-lg text-muted">Cidade</span>
                        <input type="text" wire:model="cidade" class="mt-2 w-full rounded-xl border-line bg-canvas text-lg">
                    </label>
                    <label class="block">
                        <span class="text-lg text-muted">UF</span>
                        <input type="text" wire:model="estado" maxlength="2" class="mt-2 w-full rounded-xl border-line bg-canvas text-lg uppercase">
                    </label>
                </div>

                <label class="block">
                    <span class="text-lg text-muted">Raio GPS (metros)</span>
                    <input type="number" wire:model="raio_gps_metros" min="20" max="2000" class="mt-2 w-full rounded-xl border-line bg-canvas text-lg">
                    <span class="text-sm text-muted">Distância máxima para o sócio confirmar presença pelo GPS.</span>
                </label>
                <div class="grid grid-cols-2 gap-3">
                    <label class="block">
                        <span class="text-lg text-muted">Latitude</span>
                        <input type="text" wire:model="latitude" placeholder="-23.5505" class="mt-2 w-full rounded-xl border-line bg-canvas text-lg">
                    </label>
                    <label class="block">
                        <span class="text-lg text-muted">Longitude</span>
                        <input type="text" wire:model="longitude" placeholder="-46.6333" class="mt-2 w-full rounded-xl border-line bg-canvas text-lg">
                    </label>
                </div>
                <p class="sm:col-span-2 text-sm text-muted">Preenchidas automaticamente ao buscar o CEP e o número. Você pode ajustar manualmente se necessário.</p>
            </div>
        </section>

        <section class="rounded-2xl border border-line bg-card p-6">
            <h2 class="text-2xl font-semibold">Horário de funcionamento</h2>
            <div class="mt-6 space-y-4">
                @foreach ($dias as $chave => $rotulo)
                    <div class="grid items-center gap-3 rounded-xl border border-line bg-canvas p-4 sm:grid-cols-[140px_auto_1fr_1fr]">
                        <p class="text-xl font-semibold">{{ $rotulo }}</p>
                        <label class="flex min-h-12 items-center gap-2 text-lg">
                            <input type="checkbox" wire:model.live="horarios.{{ $chave }}.fechado" class="rounded border-line bg-card">
                            Fechado
                        </label>
                        <label class="block">
                            <span class="text-muted">Abre</span>
                            <input type="time" wire:model="horarios.{{ $chave }}.abre" @disabled($horarios[$chave]['fechado'] ?? false) class="mt-1 w-full rounded-xl border-line bg-card text-lg">
                        </label>
                        <label class="block">
                            <span class="text-muted">Fecha</span>
                            <input type="time" wire:model="horarios.{{ $chave }}.fecha" @disabled($horarios[$chave]['fechado'] ?? false) class="mt-1 w-full rounded-xl border-line bg-card text-lg">
                            @error("horarios.$chave.fecha") <span class="text-red-400">{{ $message }}</span> @enderror
                        </label>
                    </div>
                @endforeach
            </div>
        </section>

        <button type="submit" class="min-h-14 rounded-2xl bg-brand px-8 text-xl font-semibold text-canvas">
            Salvar empresa / clube
        </button>
    </form>
</div>

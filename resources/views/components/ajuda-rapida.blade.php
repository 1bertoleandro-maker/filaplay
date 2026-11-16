@props(['modo' => 'tablet'])

@php
    $secretaria = $modo === 'secretaria';

    $titulo = $secretaria ? 'Como usar a recepção' : 'Como reservar a quadra';

    $passos = $secretaria
        ? [
            ['Reservar uma quadra', 'Toque em <b>Reservar</b> na quadra. O horário já vem pronto, logo depois do jogo anterior. Escolha <b>Simples</b> ou <b>Duplas</b>.'],
            ['Achar o atleta', 'Digite o <b>nome</b> ou o <b>código</b>. A foto aparece grande: confira e toque em <b>Confirmar atleta</b>. Não precisa de reconhecimento facial.'],
            ['Jogo em andamento', 'Em cada quadra em jogo há <b>+5 min</b>, <b>+10 min</b> e <b>Saíram</b>. Saíram libera a quadra e a fila anda.'],
            ['Chegou mais gente', 'Toque em <b>Cheguei · entrar neste jogo</b>. O horário não muda e o limite é de 4 jogadores.'],
            ['Perda de reserva', 'Na fila do dia, toque em <b>Perda</b> e escolha o motivo. Quem vem depois é puxado para frente.'],
            ['Choveu?', 'Toque em <b>Choveu</b>. Só as quadras cobertas ficam liberadas. Simples passa a 40 min; duplas continuam 60.'],
            ['Torneio, aula ou manutenção', 'Toque em <b>Bloquear quadra</b>, escolha o dia e o horário e escreva o motivo. Dá para agendar com antecedência.'],
            ['Facial do atleta', 'Toque em <b>Facial / Atletas</b>. Cadastre a foto no balcão ou envie o link pelo WhatsApp.'],
            ['O que está acontecendo', 'A caixa do lado direito mostra reservas, entradas, perdas e chuva em tempo real.'],
        ]
        : [
            ['Escolha a quadra', 'Toque em <b>Reservar</b> na quadra livre. O horário já vem pronto.'],
            ['Simples ou duplas', 'Simples são 2 jogadores e duplas são 4.'],
            ['Identifique-se', 'Toque em <b>Pelo rosto</b> e olhe para a câmera. Quando aparecer o seu nome, toque em <b>OK, sou eu</b>. Ou toque em <b>Pelo código</b> e digite o seu código de sócio.'],
            ['Chame o seu parceiro', 'Cada atleta se cadastra em seguida. Há um relógio na tela: se o outro não chegar a tempo, a reserva é cancelada.'],
            ['Chegou depois?', 'Toque em <b>Cheguei · entrar neste jogo</b> na quadra. O horário continua o mesmo.'],
            ['Uma quadra por vez', 'Quem está jogando só pode reservar outra quadra quando o seu horário acabar.'],
        ];
@endphp

<div x-data="{ aberto: false, chave: 'fp-ajuda-{{ $modo }}-v1' }"
     x-init="if (! localStorage.getItem(chave)) { aberto = true }"
     @keydown.escape.window="aberto = false"
     {{ $attributes }}>
    <button type="button" @click="aberto = true" class="fp-btn fp-tool" title="Ajuda">
        <x-heroicon-o-question-mark-circle class="h-5 w-5" />
        <span class="hidden md:inline">Ajuda</span>
    </button>

    <template x-teleport="body">
        <div x-show="aberto" x-cloak x-transition.opacity
             class="fixed inset-0 z-[70] flex items-center justify-center bg-black/75 p-4 backdrop-blur-sm"
             @click.self="aberto = false">
            <div class="flex max-h-full w-full max-w-2xl flex-col overflow-hidden rounded-3xl border border-white/10 bg-[#171a21] text-white shadow-2xl">
                <div class="flex items-start justify-between gap-3 border-b border-white/10 px-6 py-5">
                    <div>
                        <p class="text-xs font-black uppercase tracking-[0.22em] text-brand">Passo a passo</p>
                        <h2 class="mt-1 text-2xl font-black">{{ $titulo }}</h2>
                    </div>
                    <button type="button" @click="aberto = false; localStorage.setItem(chave, '1')"
                            class="rounded-xl bg-white/10 px-4 py-2 font-bold hover:bg-white/20">Fechar</button>
                </div>
                <ol class="min-h-0 flex-1 space-y-3 overflow-y-auto px-6 py-5">
                    @foreach ($passos as $i => [$nome, $texto])
                        <li class="flex gap-4 rounded-2xl bg-white/5 p-4">
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-brand text-lg font-black text-canvas">{{ $i + 1 }}</span>
                            <div>
                                <p class="text-lg font-black">{{ $nome }}</p>
                                <p class="mt-0.5 text-[15px] leading-snug text-gray-300">{!! $texto !!}</p>
                            </div>
                        </li>
                    @endforeach
                </ol>
                <div class="border-t border-white/10 px-6 py-4">
                    <button type="button" @click="aberto = false; localStorage.setItem(chave, '1')"
                            class="w-full rounded-2xl bg-brand py-4 text-lg font-black text-canvas">Entendi, vamos lá</button>
                </div>
            </div>
        </div>
    </template>
</div>

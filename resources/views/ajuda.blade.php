@php
    use App\Domains\Jogadores\Enums\UserRole;

    $papel = auth()->user()->role;
    $gestor = in_array($papel, [UserRole::Administrador, UserRole::Recepcao], true);

    $blocos = [
        [
            'icone' => 'heroicon-o-rocket-launch',
            'titulo' => 'Primeiros passos do clube',
            'visivel' => $gestor,
            'itens' => [
                ['1. Cadastre o clube', 'Em <b>Clube</b>: nome, logo e o <b>horário de funcionamento</b> de cada dia da semana. Sem o horário, o sistema entende que o clube está fechado.'],
                ['2. Cadastre as quadras', 'Em <b>Quadras</b>: nome, apelido, foto, tipo de piso (saibro ganha o pedido de +5 min) e se é <b>coberta</b> (importante para o modo chuva).'],
                ['3. Cadastre os atletas', 'Em <b>Sócios</b>: um por um ou por planilha. Cada atleta recebe um <b>código</b> que ele usa no tablet.'],
                ['4. Cadastre a facial', 'No balcão (foto na hora) ou enviando o <b>link pelo WhatsApp</b> em Sócios. O atleta abre o link no celular, tira a selfie e pronto.'],
                ['5. Abra o tablet e a TV', 'No <b>Início</b>: <b>Tablet do atleta</b> fica na entrada da quadra e a <b>TV</b> fica na parede. Na TV, toque uma vez em ativar o som.'],
            ],
        ],
        [
            'icone' => 'heroicon-o-calendar-days',
            'titulo' => 'Recepção: reservar e acompanhar',
            'visivel' => $gestor,
            'itens' => [
                ['Reservar para um atleta', 'Início → <b>Reservar quadra</b>. Toque em Reservar na quadra, escolha Simples ou Duplas, digite o <b>nome ou o código</b>, confira a <b>foto</b> e confirme. Não precisa de facial.'],
                ['Adicionar minutos', 'Na quadra em jogo, toque em <b>+5 min</b> ou <b>+10 min</b>. As reservas seguintes são empurradas automaticamente. Se houver um bloqueio logo depois, o sistema avisa e não deixa.'],
                ['Atletas já saíram', 'Toque em <b>Saíram</b>. A quadra fica livre e quem está na fila entra mais cedo.'],
                ['Perda de reserva', 'Na fila do dia, toque em <b>Perda</b> e escolha: não apareceu, machucou ou desistiu. A fila anda.'],
                ['Chegou mais gente', 'Toque em <b>Cheguei · entrar neste jogo</b>. O horário não muda. O atleta não pode estar em outra quadra.'],
                ['Choveu', 'Botão <b>Choveu</b> no topo. Só as quadras cobertas ficam abertas. Simples passa a <b>40 min</b> e duplas continuam <b>60 min</b>. Quando parar de chover, toque em <b>Parou de chover</b>.'],
                ['Torneio interno, aula ou manutenção', 'Botão <b>Bloquear quadra</b> (ou menu Bloqueios). Escolha a quadra, o dia, o horário e escreva o <b>motivo</b>. Dá para agendar com semanas de antecedência e repetir toda semana.'],
                ['Caixa “O que está acontecendo”', 'Na lateral direita: cada nova reserva, entrada no jogo, perda, +minutos e chuva aparecem ali na hora.'],
            ],
        ],
        [
            'icone' => 'heroicon-o-device-tablet',
            'titulo' => 'Tablet do atleta',
            'visivel' => true,
            'itens' => [
                ['Reservar sozinho', 'O atleta toca em <b>Reservar</b>, escolhe Simples ou Duplas e se identifica <b>pelo rosto</b> (olha para a câmera e toca em <b>OK, sou eu</b>) ou <b>pelo código</b>.'],
                ['Tempo entre os atletas', 'Depois do primeiro atleta aparece um relógio. Se o outro não se cadastrar a tempo, a reserva é cancelada com um aviso. O prazo muda em <b>Clube → Reserva no tablet</b> (padrão 60 s).'],
                ['Entrar num jogo que já existe', 'Quem chegou depois toca em <b>Cheguei · entrar neste jogo</b>. O horário não muda.'],
                ['Uma quadra por vez', 'Quem está jogando só reserva outra quadra quando o horário dele acabar.'],
                ['Facial obrigatória', 'Em <b>Clube → Reserva no tablet</b> dá para exigir o reconhecimento facial. A secretaria nunca precisa dele: ela só confere a foto.'],
            ],
        ],
        [
            'icone' => 'heroicon-o-tv',
            'titulo' => 'TV do clube',
            'visivel' => true,
            'itens' => [
                ['Ativar o som', 'Navegadores só liberam áudio depois de um toque. Abra a TV e toque no botão amarelo <b>Toque para ativar o som</b>. Faça isso uma vez a cada vez que abrir a página.'],
                ['Alertas em tela cheia', 'Nova reserva: tela <b>verde</b> por 12 s. Entrada em 2 minutos: tela <b>amarela</b> por 1 minuto, com o nome da quadra, horário e atletas. Sempre com sinal sonoro e voz.'],
                ['A TV atualiza sozinha', 'Não precisa apertar F5. Se a TV ficar parada, aperte F5 uma vez e toque no som de novo.'],
            ],
        ],
        [
            'icone' => 'heroicon-o-question-mark-circle',
            'titulo' => 'Perguntas frequentes',
            'visivel' => true,
            'itens' => [
                ['Aparece “o clube está fechado neste dia”', 'Cadastre o horário de funcionamento em <b>Clube</b>. Se já estiver cadastrado, veja se o dia da semana não está marcado como fechado.'],
                ['O reconhecimento facial não acha o atleta', 'Confira se ele tem foto facial cadastrada, se há boa luz e se a câmera está liberada para o site. Na primeira vez o sistema leva um tempinho para preparar os rostos; depois fica rápido.'],
                ['O atleta diz que já está em outra quadra', 'É a regra: uma quadra por vez. Se ele saiu antes, a recepção toca em <b>Saíram</b> na reserva anterior e ele fica livre.'],
                ['Apareceu um erro na tela', 'Anote o que estava fazendo e fale com o suporte. Um print ajuda muito.'],
            ],
        ],
    ];
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2 class="text-3xl font-bold text-white">Ajuda</h2>
        <p class="mt-1 text-muted">Passo a passo simples para cada tela. Toque em um título para abrir.</p>
    </x-slot>

    <div class="mx-auto max-w-4xl space-y-5">
        @foreach ($blocos as $bloco)
            @continue(! $bloco['visivel'])
            <section class="rounded-3xl border border-line bg-card p-5 sm:p-7">
                <h3 class="flex items-center gap-3 text-2xl font-black">
                    <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-brand/15 text-brand">
                        <x-dynamic-component :component="$bloco['icone']" class="h-6 w-6" />
                    </span>
                    {{ $bloco['titulo'] }}
                </h3>
                <div class="mt-4 space-y-2">
                    @foreach ($bloco['itens'] as $i => [$pergunta, $resposta])
                        <details class="group rounded-2xl border border-line bg-canvas px-4 py-3" @if ($loop->parent->first && $loop->first) open @endif>
                            <summary class="flex cursor-pointer list-none items-center justify-between gap-3 text-lg font-bold">
                                {{ $pergunta }}
                                <x-heroicon-o-chevron-down class="h-5 w-5 shrink-0 text-muted transition group-open:rotate-180" />
                            </summary>
                            <p class="mt-2 text-[16px] leading-relaxed text-muted">{!! $resposta !!}</p>
                        </details>
                    @endforeach
                </div>
            </section>
        @endforeach
    </div>
</x-app-layout>

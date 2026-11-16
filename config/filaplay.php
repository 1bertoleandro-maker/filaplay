<?php

declare(strict_types=1);

return [

    /*
    | Teste grátis para quem cria o próprio clube.
    */
    'dias_teste' => (int) env('FILAPLAY_DIAS_TESTE', 30),

    /*
    | Quantos usuários de equipe (administrador, recepção, professor) um clube novo pode criar.
    | O master ajusta por clube em "Clubes da plataforma".
    */
    'equipe_padrao' => (int) env('FILAPLAY_EQUIPE_PADRAO', 5),

    /*
    | Canal de contato para contratar / ampliar o plano. Aceita link do WhatsApp (https://wa.me/55...)
    | ou mailto:. Configure FILAPLAY_CONTATO_URL no .env.
    */
    'contato_url' => env('FILAPLAY_CONTATO_URL', 'mailto:leandro.queiroz@qisolution.com.br'),
    'contato_rotulo' => env('FILAPLAY_CONTATO_ROTULO', 'nosso canal'),

];

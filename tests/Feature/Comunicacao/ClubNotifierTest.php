<?php

declare(strict_types=1);

use App\Domains\Clube\Models\Tenant;
use App\Domains\Comunicacao\Contracts\ClubNotifier;
use App\Domains\Comunicacao\Enums\ComunicacaoCanal;
use App\Domains\Comunicacao\Enums\ComunicacaoStatus;
use App\Domains\Comunicacao\Mail\ComunicacaoMail;
use App\Domains\Comunicacao\Models\Comunicacao;
use Illuminate\Support\Facades\Mail;

test('notificador envia e-mail de verdade pelo mailer configurado', function () {
    Mail::fake();

    $tenant = Tenant::factory()->create();

    $mensagem = app(ClubNotifier::class)->send(
        tenantId: $tenant->id,
        canal: ComunicacaoCanal::Email,
        destino: 'socio@demo.filaplay.com.br',
        corpo: 'Sua vez na quadra.',
        assunto: 'Chamada',
    );

    expect($mensagem->status)->toBe(ComunicacaoStatus::Enviado)
        ->and(Comunicacao::query()->count())->toBe(1);

    Mail::assertSent(ComunicacaoMail::class);
});

test('notificador registra whatsapp sem enviar para fora (sem api conectada ainda)', function () {
    $tenant = Tenant::factory()->create();

    $mensagem = app(ClubNotifier::class)->send(
        tenantId: $tenant->id,
        canal: ComunicacaoCanal::Whatsapp,
        destino: '11999998888',
        corpo: 'Sua vez na quadra.',
    );

    expect($mensagem->status)->toBe(ComunicacaoStatus::Registrado)
        ->and(Comunicacao::query()->count())->toBe(1);
});

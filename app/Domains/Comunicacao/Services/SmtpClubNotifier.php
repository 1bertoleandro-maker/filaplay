<?php

declare(strict_types=1);

namespace App\Domains\Comunicacao\Services;

use App\Domains\Clube\Models\Tenant;
use App\Domains\Comunicacao\Contracts\ClubNotifier;
use App\Domains\Comunicacao\Enums\ComunicacaoCanal;
use App\Domains\Comunicacao\Enums\ComunicacaoStatus;
use App\Domains\Comunicacao\Mail\ComunicacaoMail;
use App\Domains\Comunicacao\Models\Comunicacao;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Envia e-mails de verdade via SMTP (mailer configurado no .env, hoje o
 * smtp.hostinger.com do gotreino). WhatsApp ainda não tem uma API conectada,
 * então continua apenas registrado (fica pronto no painel para copiar/enviar
 * manualmente) até integrarmos um provedor (Twilio, Meta Cloud API etc.).
 */
final class SmtpClubNotifier implements ClubNotifier
{
    public function send(
        int $tenantId,
        ComunicacaoCanal $canal,
        string $destino,
        string $corpo,
        ?int $userId = null,
        ?string $assunto = null,
    ): Comunicacao {
        $status = ComunicacaoStatus::Registrado;

        if ($canal === ComunicacaoCanal::Email) {
            try {
                $nomeClube = Tenant::query()->find($tenantId)?->nome;

                Mail::to($destino)->send(new ComunicacaoMail(
                    tituloAssunto: $assunto ?? 'FilaPlay',
                    corpo: $corpo,
                    nomeClube: $nomeClube,
                ));

                $status = ComunicacaoStatus::Enviado;
            } catch (Throwable $e) {
                $status = ComunicacaoStatus::Falhou;
                Log::warning("Falha ao enviar e-mail FilaPlay para {$destino}: {$e->getMessage()}");
            }
        }

        return Comunicacao::query()->create([
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'canal' => $canal,
            'destino' => $destino,
            'assunto' => $assunto,
            'corpo' => $corpo,
            'status' => $status,
            'enviado_em' => $status === ComunicacaoStatus::Enviado ? now() : null,
        ]);
    }
}

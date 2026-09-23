<?php

declare(strict_types=1);

namespace App\Domains\Jogadores\Actions;

use App\Domains\Comunicacao\Contracts\ClubNotifier;
use App\Domains\Comunicacao\Enums\ComunicacaoCanal;
use App\Domains\Jogadores\Models\User;

/**
 * Envia (por e-mail e WhatsApp) o link para o usuário confirmar o cadastro:
 * define a senha e, se for sócio/jogador, envia a foto do rosto. Usado tanto
 * para sócios cadastrados pela secretaria quanto para o responsável de um
 * clube novo criado pelo super admin.
 */
final class EnviarConviteAcesso
{
    public function __construct(private readonly ClubNotifier $notifier) {}

    public function handle(User $usuario, ?string $mensagemExtra = null): void
    {
        $link = route('socios.confirmar', $usuario->confirmacao_token);
        $mensagem = "Olá, {$usuario->nome}! ".($mensagemExtra ?? "Seu acesso ao {$usuario->tenant->nome} no FilaPlay foi criado.").
            " Acesse {$link} para definir sua senha".($usuario->role->value === 'jogador' ? ' e cadastrar sua foto.' : '.');

        $this->notifier->send(
            tenantId: $usuario->tenant_id,
            canal: ComunicacaoCanal::Email,
            destino: $usuario->email,
            corpo: $mensagem,
            userId: $usuario->id,
            assunto: 'Confirme seu acesso ao FilaPlay',
        );

        if ($usuario->telefone) {
            $this->notifier->send(
                tenantId: $usuario->tenant_id,
                canal: ComunicacaoCanal::Whatsapp,
                destino: $usuario->telefone,
                corpo: $mensagem,
                userId: $usuario->id,
            );
        }
    }
}

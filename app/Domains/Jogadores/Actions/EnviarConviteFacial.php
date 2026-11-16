<?php

declare(strict_types=1);

namespace App\Domains\Jogadores\Actions;

use App\Domains\Comunicacao\Contracts\ClubNotifier;
use App\Domains\Comunicacao\Enums\ComunicacaoCanal;
use App\Domains\Jogadores\Models\User;
use Illuminate\Support\Str;

/**
 * Envia por e-mail e WhatsApp o link para o sócio cadastrar (ou atualizar)
 * a foto do reconhecimento facial, sem precisar redefinir a senha.
 */
final class EnviarConviteFacial
{
    public function __construct(private readonly ClubNotifier $notifier) {}

    public function handle(User $usuario): User
    {
        $usuario->forceFill([
            'facial_token' => (string) Str::uuid(),
        ])->save();

        $link = route('socios.facial', $usuario->facial_token);
        $mensagem = "Olá, {$usuario->nome}! Para reservar no tablet do {$usuario->tenant->nome}, cadastre seu reconhecimento facial neste link: {$link}";

        $this->notifier->send(
            tenantId: $usuario->tenant_id,
            canal: ComunicacaoCanal::Email,
            destino: $usuario->email,
            corpo: $mensagem,
            userId: $usuario->id,
            assunto: 'Cadastre seu reconhecimento facial no FilaPlay',
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

        return $usuario->fresh();
    }

    public function urlWhatsapp(User $usuario): ?string
    {
        $numero = self::numeroWhatsapp($usuario->telefone);

        if ($numero === null || $usuario->facial_token === null) {
            return null;
        }

        $link = route('socios.facial', $usuario->facial_token);
        $mensagem = "Olá, {$usuario->nome}! Para reservar no tablet do {$usuario->tenant->nome}, cadastre seu reconhecimento facial neste link: {$link}";

        return 'https://wa.me/'.$numero.'?text='.rawurlencode($mensagem);
    }

    public static function numeroWhatsapp(?string $telefone): ?string
    {
        $digitos = preg_replace('/\D+/', '', (string) $telefone) ?? '';

        if ($digitos === '') {
            return null;
        }

        if (str_starts_with($digitos, '55') && strlen($digitos) >= 12) {
            return $digitos;
        }

        return '55'.$digitos;
    }
}

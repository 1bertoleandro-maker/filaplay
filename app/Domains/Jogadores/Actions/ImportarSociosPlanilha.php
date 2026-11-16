<?php

declare(strict_types=1);

namespace App\Domains\Jogadores\Actions;

use App\Domains\Jogadores\Enums\NivelJogador;
use App\Domains\Jogadores\Enums\UserRole;
use App\Domains\Jogadores\Models\User;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Importação em massa de sócios via planilha (.xlsx/.xls/.csv). Layout fixo
 * de colunas (ver GerarPlanilhaSocios::modelo): Nome | Matrícula | E-mail |
 * Telefone | Papel | Nível. Cada linha reaproveita a mesma validação e regra
 * de convite do cadastro manual (SalvarSocio), pra não duplicar regra de negócio.
 */
class ImportarSociosPlanilha
{
    public function __construct(private readonly SalvarSocio $salvarSocio) {}

    /**
     * @return array{criados: int, linhas: int, erros: array<int, string>}
     */
    public function handle(User $actor, string $caminhoAbsoluto): array
    {
        $planilha = IOFactory::load($caminhoAbsoluto);
        $linhas = $planilha->getActiveSheet()->toArray(null, true, true, false);

        $criados = 0;
        $processadas = 0;
        $erros = [];

        foreach ($linhas as $indice => $linha) {
            if ($indice === 0) {
                continue; // cabeçalho
            }

            [$nome, $matricula, $email, $telefone, $papel, $nivel] = array_pad($linha, 6, null);

            if (blank($nome) && blank($matricula) && blank($email)) {
                continue; // linha em branco
            }

            $processadas++;
            $numeroLinha = $indice + 1;

            try {
                $this->salvarSocio->handle($actor, [
                    'nome' => trim((string) $nome),
                    'matricula' => trim((string) $matricula),
                    'email' => trim((string) $email),
                    'telefone' => blank($telefone) ? null : trim((string) $telefone),
                    'role' => $this->normalizarPapel($papel)->value,
                    'nivel' => $this->normalizarNivel($nivel)?->value,
                ]);

                $criados++;
            } catch (ValidationException $e) {
                $erros[] = "Linha {$numeroLinha} ({$this->descrever($nome, $email)}): ".implode('; ', $e->validator->errors()->all()).$this->origemDoEmail($actor, $email);
            }
        }

        return [
            'criados' => $criados,
            'linhas' => $processadas,
            'erros' => $erros,
        ];
    }

    private function descrever(mixed $nome, mixed $email): string
    {
        $email = trim((string) $email);

        return $email !== '' ? $email : trim((string) $nome);
    }

    /**
     * Quando o e-mail já existe, diz onde (neste clube, em outro clube ou excluído)
     * para o usuário entender por que a linha foi recusada.
     */
    private function origemDoEmail(User $actor, mixed $email): string
    {
        $email = trim((string) $email);

        if ($email === '') {
            return '';
        }

        $existente = User::withoutGlobalScopes()->withTrashed()->where('email', $email)->first();

        if ($existente === null) {
            return '';
        }

        if ((int) $existente->tenant_id !== (int) $actor->tenant_id) {
            return ' (esse e-mail já é de um sócio de outro clube; cada e-mail só pode ficar em um clube, use outro e-mail)';
        }

        return $existente->trashed()
            ? ' (esse sócio foi excluído deste clube; use outro e-mail)'
            : ' (esse sócio já está na lista deste clube)';
    }

    private function normalizarPapel(mixed $valor): UserRole
    {
        $texto = Str::of((string) $valor)->lower()->ascii()->trim()->toString();

        foreach (UserRole::cases() as $papel) {
            if ($texto === $papel->value || $texto === Str::of($papel->label())->lower()->ascii()->trim()->toString()) {
                return $papel;
            }
        }

        return UserRole::Jogador;
    }

    private function normalizarNivel(mixed $valor): ?NivelJogador
    {
        $texto = Str::of((string) $valor)->lower()->ascii()->trim()->toString();

        if ($texto === '') {
            return null;
        }

        foreach (NivelJogador::cases() as $nivel) {
            if ($texto === $nivel->value || $texto === Str::of($nivel->label())->lower()->ascii()->trim()->toString()) {
                return $nivel;
            }
        }

        return null;
    }
}

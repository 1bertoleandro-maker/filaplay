<?php

declare(strict_types=1);

namespace App\Domains\Jogadores\Actions;

use App\Domains\Jogadores\Models\User;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

/**
 * Gera o modelo (.xlsx) para importação em massa de sócios e também a
 * exportação dos sócios já cadastrados no clube — mesmo layout de colunas
 * usado por ImportarSociosPlanilha.
 */
final class GerarPlanilhaSocios
{
    /** @var array<int, string> */
    private const COLUNAS = ['Nome', 'Matrícula', 'E-mail', 'Telefone', 'Papel', 'Nível'];

    public function modelo(): Spreadsheet
    {
        $planilha = new Spreadsheet;
        $aba = $planilha->getActiveSheet();
        $aba->setTitle('Sócios');

        $aba->fromArray(self::COLUNAS, null, 'A1');
        $aba->fromArray([
            'João da Silva', 'JOG001', 'joao@exemplo.com', '11999998888', 'Jogador', 'Intermediário',
        ], null, 'A2');

        foreach (range('A', 'F') as $coluna) {
            $aba->getColumnDimension($coluna)->setAutoSize(true);
        }

        return $planilha;
    }

    public function exportar(int $tenantId): Spreadsheet
    {
        $planilha = new Spreadsheet;
        $aba = $planilha->getActiveSheet();
        $aba->setTitle('Sócios');

        $aba->fromArray(self::COLUNAS, null, 'A1');

        $socios = User::query()
            ->where('tenant_id', $tenantId)
            ->orderBy('nome')
            ->get(['nome', 'matricula', 'email', 'telefone', 'role', 'nivel']);

        $linha = 2;
        foreach ($socios as $socio) {
            $aba->fromArray([
                $socio->nome,
                $socio->matricula,
                $socio->email,
                $socio->telefone,
                $socio->role->label(),
                $socio->nivel?->label(),
            ], null, "A{$linha}");
            $linha++;
        }

        foreach (range('A', 'F') as $coluna) {
            $aba->getColumnDimension($coluna)->setAutoSize(true);
        }

        return $planilha;
    }
}

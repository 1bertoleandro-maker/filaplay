<?php

declare(strict_types=1);

namespace App\Domains\Jogadores\Http;

use App\Domains\Jogadores\Actions\GerarPlanilhaSocios;
use App\Domains\Jogadores\Enums\UserRole;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class SociosPlanilhaController
{
    public function modelo(Request $request, GerarPlanilhaSocios $action): StreamedResponse
    {
        $this->autorizar($request);

        return $this->baixar($action->modelo(), 'modelo-socios-filaplay.xlsx');
    }

    public function exportar(Request $request, GerarPlanilhaSocios $action): StreamedResponse
    {
        $this->autorizar($request);

        return $this->baixar(
            $action->exportar((int) $request->user()->tenant_id),
            'socios-'.now()->format('Y-m-d').'.xlsx',
        );
    }

    private function autorizar(Request $request): void
    {
        abort_unless(in_array($request->user()?->role, [
            UserRole::Administrador,
            UserRole::Recepcao,
        ], true), 403);
    }

    private function baixar(Spreadsheet $planilha, string $nomeArquivo): StreamedResponse
    {
        $writer = new Xlsx($planilha);

        return response()->streamDownload(function () use ($writer): void {
            $writer->save('php://output');
        }, $nomeArquivo, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}

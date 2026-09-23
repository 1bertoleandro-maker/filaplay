<?php

declare(strict_types=1);

use App\Domains\Clube\Models\Tenant;
use App\Domains\Jogadores\Actions\GerarPlanilhaSocios;
use App\Domains\Jogadores\Actions\ImportarSociosPlanilha;
use App\Domains\Jogadores\Livewire\ListaSocios;
use App\Domains\Jogadores\Models\User;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Grava uma planilha real em disco (mesmo layout do modelo) e devolve o caminho.
 *
 * @param  array<int, array<int, mixed>>  $linhas
 */
function planilhaDeTeste(array $linhas): string
{
    $planilha = new Spreadsheet;
    $aba = $planilha->getActiveSheet();
    $aba->fromArray(['Nome', 'Matrícula', 'E-mail', 'Telefone', 'Papel', 'Nível'], null, 'A1');

    $numeroLinha = 2;
    foreach ($linhas as $linha) {
        $aba->fromArray($linha, null, "A{$numeroLinha}");
        $numeroLinha++;
    }

    $caminho = sys_get_temp_dir().'/filaplay-teste-'.uniqid().'.xlsx';
    (new Xlsx($planilha))->save($caminho);

    return $caminho;
}

test('importa varios socios de uma planilha e reporta erros linha a linha', function () {
    $tenant = Tenant::factory()->create();
    app(TenantContext::class)->set($tenant->id);
    $admin = User::factory()->administrador()->create(['tenant_id' => $tenant->id]);

    // Sócio que já existe, pra forçar erro de matrícula duplicada na importação.
    User::factory()->create(['tenant_id' => $tenant->id, 'matricula' => 'DUP001']);

    $caminho = planilhaDeTeste([
        ['Ana Jogadora', 'IMP001', 'ana.imp@teste.com', '11911112222', 'Jogador', 'Iniciante'],
        ['Bia Recepção', 'IMP002', 'bia.imp@teste.com', '', 'Recepção', ''],
        ['Duplicada', 'DUP001', 'duplicada@teste.com', '', 'Jogador', ''],
    ]);

    $resultado = app(ImportarSociosPlanilha::class)->handle($admin, $caminho);

    unlink($caminho);

    expect($resultado['linhas'])->toBe(3)
        ->and($resultado['criados'])->toBe(2)
        ->and($resultado['erros'])->toHaveCount(1)
        ->and($resultado['erros'][0])->toContain('Linha 4');

    expect(User::query()->where('matricula', 'IMP001')->exists())->toBeTrue()
        ->and(User::query()->where('matricula', 'IMP002')->first()?->role->value)->toBe('recepcao');
});

test('painel de socios importa planilha pelo formulario e mostra o resultado', function () {
    // A leitura real do .xlsx já é validada em "importa varios socios de uma
    // planilha...". Aqui isolamos a integração do formulário Livewire com a
    // Action (mockada), evitando depender do upload físico de um arquivo real.
    $tenant = Tenant::factory()->create();
    app(TenantContext::class)->set($tenant->id);
    $admin = User::factory()->administrador()->create(['tenant_id' => $tenant->id]);

    $this->mock(ImportarSociosPlanilha::class, function ($mock): void {
        $mock->shouldReceive('handle')
            ->once()
            ->andReturn(['criados' => 1, 'linhas' => 1, 'erros' => []]);
    });

    $arquivo = UploadedFile::fake()->create('socios.xlsx', 10, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

    Livewire::actingAs($admin)
        ->test(ListaSocios::class)
        ->call('abrirImportacao')
        ->set('planilha', $arquivo)
        ->call('importar')
        ->assertSet('resultadoImportacao.criados', 1)
        ->assertSee('sócio(s) importado(s)');
});

test('modelo e exportacao de socios em excel ficam disponiveis para administrador', function () {
    $tenant = Tenant::factory()->create();
    app(TenantContext::class)->set($tenant->id);
    $admin = User::factory()->administrador()->create(['tenant_id' => $tenant->id]);
    User::factory()->create(['tenant_id' => $tenant->id, 'nome' => 'Sócio Exportado']);

    $this->actingAs($admin)
        ->get(route('socios.planilha.modelo'))
        ->assertOk()
        ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

    $this->actingAs($admin)
        ->get(route('socios.planilha.exportar'))
        ->assertOk();
});

test('gerar planilha de socios inclui os cadastrados do tenant', function () {
    $tenant = Tenant::factory()->create();
    app(TenantContext::class)->set($tenant->id);
    User::factory()->create(['tenant_id' => $tenant->id, 'nome' => 'Zeca Sócio', 'matricula' => 'ZZZ1']);

    $planilha = app(GerarPlanilhaSocios::class)->exportar($tenant->id);
    $linhas = $planilha->getActiveSheet()->toArray(null, true, true, false);

    expect($linhas[0][0])->toBe('Nome')
        ->and(collect($linhas)->pluck(1))->toContain('ZZZ1');
});

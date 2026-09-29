<?php

declare(strict_types=1);

namespace App\Domains\Clube\Livewire;

use App\Domains\Clube\Actions\AtualizarClube;
use App\Domains\Clube\Actions\BuscarEnderecoPeloCep;
use App\Domains\Clube\Actions\GeocodificarEndereco;
use App\Domains\Clube\Services\HorarioDoClube;
use App\Domains\Configuracoes\ConfiguracaoChave;
use App\Domains\Configuracoes\Services\ConfiguracaoService;
use App\Domains\Filas\Services\FilaEngine;
use Illuminate\Http\UploadedFile;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
class EditarClube extends Component
{
    use WithFileUploads;

    public string $nome = '';

    public string $endereco = '';

    public string $cep = '';

    public string $numero = '';

    public string $bairro = '';

    public string $cidade = '';

    public string $estado = '';

    public string $telefone = '';

    public string $email = '';

    public int $raio_gps_metros = 150;

    public ?float $latitude = null;

    public ?float $longitude = null;

    public ?string $localizacaoMensagem = null;

    public ?string $localizacaoStatus = null;

    /** @var array<string, array{fechado: bool, abre: ?string, fecha: ?string}> */
    public array $horarios = [];

    public mixed $logo = null;

    public bool $exibir_logo = true;

    public bool $exigir_facial_tablet = false;

    public string $tema = 'escuro';

    public function mount(): void
    {
        $tenant = auth()->user()?->tenant;
        abort_unless($tenant !== null, 404);
        abort_unless(auth()->user()->can('update', $tenant), 403);

        $this->nome = (string) $tenant->nome;
        $this->endereco = (string) ($tenant->endereco ?? '');
        $this->cep = (string) ($tenant->cep ?? '');
        $this->numero = (string) ($tenant->numero ?? '');
        $this->bairro = (string) ($tenant->bairro ?? '');
        $this->cidade = (string) ($tenant->cidade ?? '');
        $this->estado = (string) ($tenant->estado ?? '');
        $this->telefone = (string) ($tenant->telefone ?? '');
        $this->email = (string) ($tenant->email ?? '');
        $this->raio_gps_metros = (int) $tenant->raio_gps_metros;
        $this->latitude = $tenant->latitude !== null ? (float) $tenant->latitude : null;
        $this->longitude = $tenant->longitude !== null ? (float) $tenant->longitude : null;
        $this->exibir_logo = (bool) $tenant->exibir_logo;
        $this->exigir_facial_tablet = app(ConfiguracaoService::class)->ativo(ConfiguracaoChave::TABLET_EXIGIR_FACIAL);
        $this->tema = app(ConfiguracaoService::class)->get(ConfiguracaoChave::APARENCIA_TEMA)['tema'] ?? 'escuro';

        $grade = $tenant->horario_funcionamento ?? [];

        foreach (array_keys(HorarioDoClube::DIAS) as $dia) {
            $item = $grade[$dia] ?? [];
            $fechado = (bool) ($item['fechado'] ?? false);
            $abre = $item['abre'] ?? null;
            $fecha = $item['fecha'] ?? null;

            $this->horarios[$dia] = [
                'fechado' => $fechado || ($abre === null && $fecha === null),
                'abre' => is_string($abre) ? $abre : '07:00',
                'fecha' => is_string($fecha) ? $fecha : '22:00',
            ];
        }
    }

    public function buscarCep(BuscarEnderecoPeloCep $action): void
    {
        $this->validate(['cep' => ['required', 'string']], [], ['cep' => 'CEP']);

        $endereco = $action->handle($this->cep);

        if ($endereco === null) {
            $this->localizacaoStatus = 'erro';
            $this->localizacaoMensagem = 'CEP não encontrado. Confira o número digitado.';

            return;
        }

        $this->endereco = $endereco['logradouro'] ?? $this->endereco;
        $this->bairro = $endereco['bairro'] ?? $this->bairro;
        $this->cidade = $endereco['cidade'] ?? $this->cidade;
        $this->estado = $endereco['estado'] ?? $this->estado;

        $this->localizacaoStatus = 'ok';
        $this->localizacaoMensagem = 'Endereço encontrado! Confirme o número para localizar a quadra no mapa.';

        if ($this->numero !== '') {
            $this->atualizarGeolocalizacao(app(GeocodificarEndereco::class));
        }
    }

    public function atualizarGeolocalizacao(GeocodificarEndereco $action): void
    {
        if ($this->numero === '') {
            $this->localizacaoStatus = 'erro';
            $this->localizacaoMensagem = 'Informe o número para localizar a quadra no mapa.';

            return;
        }

        $enderecoCompleto = implode(', ', array_filter([
            trim($this->endereco.' '.$this->numero),
            $this->bairro,
            $this->cidade !== '' && $this->estado !== '' ? "{$this->cidade} - {$this->estado}" : $this->cidade,
            $this->cep,
        ], fn (string $parte): bool => $parte !== ''));

        $coordenadas = $action->handle($enderecoCompleto);

        if ($coordenadas === null) {
            $this->localizacaoStatus = 'erro';
            $this->localizacaoMensagem = 'Não conseguimos localizar esse endereço automaticamente. Ajuste a latitude/longitude manualmente, se necessário.';

            return;
        }

        $this->latitude = $coordenadas['latitude'];
        $this->longitude = $coordenadas['longitude'];
        $this->localizacaoStatus = 'ok';
        $this->localizacaoMensagem = 'Localização confirmada automaticamente a partir do endereço.';
    }

    public function salvar(AtualizarClube $action, ConfiguracaoService $config): void
    {
        $logo = $this->logo instanceof UploadedFile ? $this->logo : null;

        $action->handle(auth()->user(), auth()->user()->tenant, [
            'nome' => $this->nome,
            'endereco' => $this->endereco !== '' ? $this->endereco : null,
            'cep' => $this->cep !== '' ? $this->cep : null,
            'numero' => $this->numero !== '' ? $this->numero : null,
            'bairro' => $this->bairro !== '' ? $this->bairro : null,
            'cidade' => $this->cidade !== '' ? $this->cidade : null,
            'estado' => $this->estado !== '' ? $this->estado : null,
            'telefone' => $this->telefone !== '' ? $this->telefone : null,
            'email' => $this->email !== '' ? $this->email : null,
            'raio_gps_metros' => $this->raio_gps_metros,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'exibir_logo' => $this->exibir_logo,
            'horarios' => $this->horarios,
        ], $logo);

        $config->set(ConfiguracaoChave::TABLET_EXIGIR_FACIAL, ['ativo' => $this->exigir_facial_tablet]);
        $config->set(ConfiguracaoChave::APARENCIA_TEMA, [
            'tema' => $this->tema === 'claro' ? 'claro' : 'escuro',
        ]);

        $this->logo = null;
        session()->flash('status', 'Clube atualizado.');

        $this->redirect(route('clube.editar'), navigate: true);
    }

    public function alternarModoChuva(FilaEngine $engine): void
    {
        $tenant = auth()->user()->tenant;
        $engine->alternarModoChuva($tenant, ! $tenant->modo_chuva);

        session()->flash('status', $tenant->fresh()->modo_chuva
            ? 'Modo chuva ativado: apenas quadras cobertas ficam disponíveis.'
            : 'Modo chuva desativado.');
    }

    public function render(): View
    {
        return view('domains.clube.editar-clube', [
            'dias' => HorarioDoClube::DIAS,
            'tenant' => auth()->user()->tenant->fresh(),
        ]);
    }
}

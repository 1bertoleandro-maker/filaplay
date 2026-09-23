<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domains\Clube\Models\Tenant;
use App\Domains\Clube\Policies\TenantPolicy;
use App\Domains\Comunicacao\Contracts\ClubNotifier;
use App\Domains\Comunicacao\Services\SmtpClubNotifier;
use App\Domains\Filas\Models\Fila;
use App\Domains\Filas\Policies\FilaPolicy;
use App\Domains\Jogadores\Models\User;
use App\Domains\Jogadores\Policies\UserPolicy;
use App\Domains\Partidas\Models\Partida;
use App\Domains\Partidas\Policies\PartidaPolicy;
use App\Domains\Presenca\Contracts\FaceRecognitionService;
use App\Domains\Presenca\Models\Presenca;
use App\Domains\Presenca\Policies\PresencaPolicy;
use App\Domains\Presenca\Services\MockFaceRecognitionService;
use App\Domains\Quadras\Models\Quadra;
use App\Domains\Quadras\Policies\QuadraPolicy;
use App\Domains\Reservas\Models\Reserva;
use App\Domains\Reservas\Policies\ReservaPolicy;
use App\Support\Tenancy\TenantContext;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class DomainServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(TenantContext::class);
        $this->app->singleton(FaceRecognitionService::class, MockFaceRecognitionService::class);
        $this->app->singleton(ClubNotifier::class, SmtpClubNotifier::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom([
            app_path('Domains/Clube/Database/Migrations'),
            app_path('Domains/Jogadores/Database/Migrations'),
            app_path('Domains/Quadras/Database/Migrations'),
            app_path('Domains/Reservas/Database/Migrations'),
            app_path('Domains/Filas/Database/Migrations'),
            app_path('Domains/Partidas/Database/Migrations'),
            app_path('Domains/Presenca/Database/Migrations'),
            app_path('Domains/Configuracoes/Database/Migrations'),
            app_path('Domains/Ranking/Database/Migrations'),
            app_path('Domains/Comunicacao/Database/Migrations'),
        ]);

        Gate::policy(Tenant::class, TenantPolicy::class);
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Quadra::class, QuadraPolicy::class);
        Gate::policy(Reserva::class, ReservaPolicy::class);
        Gate::policy(Fila::class, FilaPolicy::class);
        Gate::policy(Partida::class, PartidaPolicy::class);
        Gate::policy(Presenca::class, PresencaPolicy::class);
    }
}

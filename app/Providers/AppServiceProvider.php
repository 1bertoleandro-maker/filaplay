<?php

namespace App\Providers;

use Illuminate\Foundation\Http\Events\RequestHandled;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Livewire\Component as LivewireComponent;
use Livewire\Livewire;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // O app organiza os componentes Livewire por domínio
        // (App\Domains\*\Livewire\*) em vez do padrão App\Livewire\*.
        // O Livewire só usa isso pra RENDERIZAR a página (passa a classe
        // direto na rota). Mas toda vez que o navegador manda uma
        // atualização (wire:click, wire:submit, wire:model...), ele só
        // envia o NOME do componente (ex.: "app.domains.clube.livewire.
        // clubes-pendentes") e o Livewire tenta reconstruir a classe
        // prependando "App\Livewire\" (config('livewire.class_namespace')),
        // o que gera uma classe inexistente e faz ele achar que é um
        // "release token mismatch" (na real é só "não achei essa classe").
        // Esse resolver ensina ele a montar a classe certa a partir do nome.
        Livewire::resolveMissingComponent(function (string $name) {
            $class = collect(explode('.', $name))
                ->map(fn (string $segment): string => Str::studly($segment))
                ->implode('\\');

            return is_subclass_of($class, LivewireComponent::class) ? $class : null;
        });

        Event::listen(RequestHandled::class, function (RequestHandled $handled): void {
            $prefix = parse_url((string) config('app.url'), PHP_URL_PATH);
            $prefix = is_string($prefix) ? rtrim($prefix, '/') : '';

            if ($prefix === '' || ! method_exists($handled->response, 'getContent')) {
                return;
            }

            $content = $handled->response->getContent();

            if (! is_string($content) || ! str_contains($content, '"/livewire/')) {
                return;
            }

            $handled->response->setContent(str_replace('"/livewire/', '"'.$prefix.'/livewire/', $content));
        });
    }
}

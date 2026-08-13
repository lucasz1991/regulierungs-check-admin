<?php

namespace App\Providers;

use Illuminate\Console\Events\CommandStarting;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use RuntimeException;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Schema::defaultStringLength(191);
        Livewire::addPersistentMiddleware(\App\Http\Middleware\EnsureActiveUser::class);

        Event::listen(CommandStarting::class, function (CommandStarting $event): void {
            if (! in_array($event->command, ['migrate:fresh', 'migrate:refresh', 'db:wipe'], true)) {
                return;
            }

            $connection = (string) config('database.default');
            $database = (string) config("database.connections.{$connection}.database");

            if ($connection === 'sqlite' && $database === ':memory:') {
                return;
            }

            throw new RuntimeException(sprintf(
                'Destruktiver Datenbankbefehl blockiert: %s darf nur mit SQLite :memory: ausgefuehrt werden; aktiv ist %s/%s.',
                $event->command,
                $connection,
                $database,
            ));
        });
    }
}

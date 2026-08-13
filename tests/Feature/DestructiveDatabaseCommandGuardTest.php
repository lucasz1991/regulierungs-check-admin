<?php

namespace Tests\Feature;

use Illuminate\Console\Events\CommandStarting;
use Illuminate\Support\Facades\Event;
use RuntimeException;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Tests\TestCase;

class DestructiveDatabaseCommandGuardTest extends TestCase
{
    public function test_destructive_database_commands_are_blocked_outside_sqlite_memory(): void
    {
        config([
            'database.default' => 'mysql',
            'database.connections.mysql.database' => 'regulierungs-check',
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Destruktiver Datenbankbefehl blockiert');

        Event::dispatch(new CommandStarting(
            'migrate:fresh',
            new ArrayInput([]),
            new BufferedOutput(),
        ));
    }
}

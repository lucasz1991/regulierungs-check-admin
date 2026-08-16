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
            new BufferedOutput,
        ));
    }

    public function test_sqlite_memory_allows_a_destructive_test_command(): void
    {
        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
        ]);

        Event::dispatch(new CommandStarting(
            'db:wipe',
            new ArrayInput([]),
            new BufferedOutput,
        ));

        $this->addToAssertionCount(1);
    }

    public function test_testing_database_with_test_suffix_allows_a_destructive_command(): void
    {
        config([
            'database.default' => 'mysql',
            'database.connections.mysql.database' => 'regulierungs_check_test',
        ]);

        Event::dispatch(new CommandStarting(
            'migrate:refresh',
            new ArrayInput([]),
            new BufferedOutput,
        ));

        $this->addToAssertionCount(1);
    }
}

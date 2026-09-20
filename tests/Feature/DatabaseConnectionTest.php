<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

// The tripwire for the SQLite shortcut. An in-memory suite diverges from production on the
// three things this system leans on — global scopes over JSON columns, SELECT ... FOR UPDATE
// behind task numbering, and constraint timing — so a green SQLite run would prove nothing
// (03-architecture.md, 08-environment.md).
it('runs its tests against postgresql', function (): void {
    expect(DB::connection()->getDriverName())->toBe('pgsql');
});

it('migrates into a database of its own', function (): void {
    expect(DB::connection()->getDatabaseName())->toBe('taskpost_testing')
        ->and(Schema::hasTable('users'))->toBeTrue();
});

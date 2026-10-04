<?php

declare(strict_types=1);

use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| Feature tests get the full Laravel test case. RefreshDatabase is opted into per file
| rather than bound here, so that a test which touches the database says so out loud, and
| one that does not is not paying for a migration run to prove something about a route.
|
| The stub's example expectation was removed rather than kept as a placeholder
| (01-principles.md §1); the helpers below are real, and live here because more than one
| test file needs them and a global function declared twice is a fatal error.
|
*/

pest()->extend(TestCase::class)->in('Feature');

/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
*/

function taskpostRoot(): string
{
    return dirname(__DIR__);
}

function taskpostReadFile(string $path): string
{
    $contents = file_get_contents($path);

    if ($contents === false) {
        throw new RuntimeException("Unable to read {$path}");
    }

    return $contents;
}

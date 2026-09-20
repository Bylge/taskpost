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
| The stub's example expectation and helper function were removed rather than kept as
| placeholders (01-principles.md §1).
|
*/

pest()->extend(TestCase::class)->in('Feature');

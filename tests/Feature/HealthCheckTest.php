<?php

declare(strict_types=1);

use function Pest\Laravel\get;

// /up is registered by bootstrap/app.php and nothing calls it yet. It is tested because it
// is the one thing a deploy check, a monitor or a load balancer will ask for, and adding it
// later means editing routes during an incident (08-environment.md).
//
// Pest\Laravel\get() rather than $this->get(): inside a Pest closure $this is bound to the
// TestCase only at runtime, which static analysis cannot see. The plugin's helpers are the
// typed way in, and the same holds for actingAs(), post() and the rest.
it('answers the health check', function (): void {
    get('/up')->assertOk();
});

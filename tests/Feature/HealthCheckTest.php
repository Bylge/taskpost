<?php

declare(strict_types=1);

// /up is registered by bootstrap/app.php and nothing calls it yet. It is tested because it
// is the one thing a deploy check, a monitor or a load balancer will ask for, and adding it
// later means editing routes during an incident (08-environment.md).
it('answers the health check', function (): void {
    $this->get('/up')->assertOk();
});

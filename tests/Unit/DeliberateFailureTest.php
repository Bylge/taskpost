<?php

declare(strict_types=1);

// Temporary, and removed in the next commit. M1's exit criterion is that a deliberately
// failing test turns the build red *and* leaves the pull request unmergeable, and the only
// way to know that is to do it (06-build-plan.md M1.12).
it('fails on purpose', function (): void {
    expect('red')->toBe('green');
});

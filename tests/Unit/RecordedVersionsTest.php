<?php

declare(strict_types=1);

// docs/06-build-plan.md records what this stack resolved to. A record nobody checks drifts
// silently, and the plan's own dependency floor was wrong within three months of being
// written — so the record is parsed and compared rather than trusted (06-build-plan.md M1.9).

function taskpostRoot(): string
{
    return dirname(__DIR__, 2);
}

function taskpostReadFile(string $path): string
{
    $contents = file_get_contents($path);

    if ($contents === false) {
        throw new RuntimeException("Unable to read {$path}");
    }

    return $contents;
}

/**
 * Every package version in composer.lock, production and dev alike.
 *
 * @return array<string, string>
 */
function taskpostComposerLockVersions(): array
{
    $decoded = json_decode(taskpostReadFile(taskpostRoot().'/composer.lock'), true, 512, JSON_THROW_ON_ERROR);
    $versions = [];

    foreach (['packages', 'packages-dev'] as $section) {
        $entries = is_array($decoded) ? ($decoded[$section] ?? null) : null;

        if (! is_array($entries)) {
            continue;
        }

        foreach ($entries as $entry) {
            $name = is_array($entry) ? ($entry['name'] ?? null) : null;
            $version = is_array($entry) ? ($entry['version'] ?? null) : null;

            if (is_string($name) && is_string($version)) {
                $versions[$name] = $version;
            }
        }
    }

    return $versions;
}

/**
 * Every constraint composer.json declares, required and dev-required alike.
 *
 * @return array<string, string>
 */
function taskpostComposerConstraints(): array
{
    $decoded = json_decode(taskpostReadFile(taskpostRoot().'/composer.json'), true, 512, JSON_THROW_ON_ERROR);
    $constraints = [];

    foreach (['require', 'require-dev'] as $section) {
        $entries = is_array($decoded) ? ($decoded[$section] ?? null) : null;

        if (! is_array($entries)) {
            continue;
        }

        foreach ($entries as $name => $constraint) {
            if (is_string($name) && is_string($constraint)) {
                $constraints[$name] = $constraint;
            }
        }
    }

    return $constraints;
}

/**
 * Every package version in package-lock.json, keyed by package name rather than by path.
 *
 * @return array<string, string>
 */
function taskpostNpmLockVersions(): array
{
    $decoded = json_decode(taskpostReadFile(taskpostRoot().'/package-lock.json'), true, 512, JSON_THROW_ON_ERROR);
    $packages = is_array($decoded) ? ($decoded['packages'] ?? null) : null;

    if (! is_array($packages)) {
        return [];
    }

    $prefix = 'node_modules/';
    $versions = [];

    foreach ($packages as $path => $entry) {
        if (! is_string($path) || ! str_starts_with($path, $prefix)) {
            continue;
        }

        $version = is_array($entry) ? ($entry['version'] ?? null) : null;

        if (is_string($version)) {
            $versions[substr($path, strlen($prefix))] = $version;
        }
    }

    return $versions;
}

/**
 * The rows of the version table in the build plan, read from between its markers.
 *
 * @return list<array{name: string, recorded: string, source: string}>
 */
function taskpostRecordedVersions(): array
{
    $markdown = taskpostReadFile(taskpostRoot().'/docs/06-build-plan.md');
    $block = [];

    if (preg_match('/<!-- resolved-versions:start -->(.*?)<!-- resolved-versions:end -->/s', $markdown, $block) !== 1) {
        throw new RuntimeException('The resolved-versions markers are missing from docs/06-build-plan.md');
    }

    $rows = [];
    preg_match_all('/^\|\s*`([^`]+)`\s*\|\s*`([^`]+)`\s*\|\s*`([^`]+)`\s*\|\s*$/m', $block[1], $rows, PREG_SET_ORDER);

    $recorded = [];

    foreach ($rows as $row) {
        $recorded[] = [
            'name' => $row[1],
            'recorded' => $row[2],
            'source' => $row[3],
        ];
    }

    return $recorded;
}

// A parity check over zero rows is a green that cannot go red, which is worse than no check
// at all — the same argument 08-environment.md makes about the i18n job.
it('records at least one version', function (): void {
    expect(taskpostRecordedVersions())->not->toBeEmpty();
});

it('records the versions this stack actually resolved to', function (): void {
    $sources = [
        'composer.lock' => taskpostComposerLockVersions(),
        'composer.json' => taskpostComposerConstraints(),
        'package-lock.json' => taskpostNpmLockVersions(),
    ];

    $recorded = [];
    $actual = [];

    // Compared as two whole maps rather than row by row, so a failure prints the drifted
    // package beside its real version instead of stopping at the first one.
    foreach (taskpostRecordedVersions() as $row) {
        $key = "{$row['source']}: {$row['name']}";

        $recorded[$key] = $row['recorded'];
        $actual[$key] = $sources[$row['source']][$row['name']] ?? '(absent)';
    }

    expect($actual)->toBe($recorded);
});

it('runs on an interpreter that satisfies the recorded PHP constraint', function (): void {
    $constraints = taskpostComposerConstraints();

    expect($constraints)->toHaveKey('php');

    $constraint = $constraints['php'] ?? '';

    expect(str_starts_with($constraint, '^'))->toBeTrue();

    // "^8.5" means at least 8.5, below 9. Derived rather than restated, so raising the
    // constraint in composer.json is the only edit a PHP upgrade needs.
    $floor = substr($constraint, 1);
    $ceiling = ((int) explode('.', $floor)[0] + 1).'.0.0';

    expect(version_compare(PHP_VERSION, $floor, '>='))->toBeTrue()
        ->and(version_compare(PHP_VERSION, $ceiling, '<'))->toBeTrue();
});

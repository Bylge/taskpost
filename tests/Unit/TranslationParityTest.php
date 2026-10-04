<?php

declare(strict_types=1);

// `en` is authoritative and `pl` mirrors it key for key (07-conventions.md). A key present in
// one locale and missing from the other has to fail here, because Laravel's own behaviour is
// to fall back silently — which is how half a product ends up untranslated without anyone
// noticing. The literal-string lint joins this job at M4; this is the parity half.

/**
 * Flattens a locale file's nested arrays into `file.context.item` keys.
 *
 * @param  array<array-key, mixed>  $translations
 * @return list<string>
 */
function taskpostFlattenTranslationKeys(array $translations, string $prefix): array
{
    $keys = [];

    foreach ($translations as $key => $value) {
        $dotted = $prefix === '' ? (string) $key : $prefix.'.'.$key;

        if (is_array($value)) {
            foreach (taskpostFlattenTranslationKeys($value, $dotted) as $nested) {
                $keys[] = $nested;
            }

            continue;
        }

        $keys[] = $dotted;
    }

    return $keys;
}

/**
 * Every translation key a locale defines, sorted, across all of its files.
 *
 * @return list<string>
 */
function taskpostLocaleKeys(string $locale): array
{
    $directory = taskpostRoot().'/lang/'.$locale;

    if (! is_dir($directory)) {
        throw new RuntimeException("Missing locale directory: lang/{$locale}");
    }

    $files = glob($directory.'/*.php');

    if ($files === false) {
        throw new RuntimeException("Unable to list lang/{$locale}");
    }

    $keys = [];

    foreach ($files as $file) {
        $translations = require $file;

        if (! is_array($translations)) {
            throw new RuntimeException("{$file} does not return an array");
        }

        foreach (taskpostFlattenTranslationKeys($translations, basename($file, '.php')) as $key) {
            $keys[] = $key;
        }
    }

    sort($keys);

    return $keys;
}

// Both locales must actually contain something. A parity check over zero keys is a green that
// cannot go red, which is worse than no check at all (08-environment.md).
it('has a non-empty en and pl locale', function (): void {
    expect(taskpostLocaleKeys('en'))->not->toBeEmpty()
        ->and(taskpostLocaleKeys('pl'))->not->toBeEmpty();
});

it('translates every en key into pl', function (): void {
    $untranslated = array_values(array_diff(taskpostLocaleKeys('en'), taskpostLocaleKeys('pl')));

    expect($untranslated)->toBe([]);
});

// The other direction is not redundant: an orphan in `pl` is usually a typo in a key that
// also left the real one untranslated, and it is always a string nothing can reach.
it('defines no pl key that en does not have', function (): void {
    $orphans = array_values(array_diff(taskpostLocaleKeys('pl'), taskpostLocaleKeys('en')));

    expect($orphans)->toBe([]);
});

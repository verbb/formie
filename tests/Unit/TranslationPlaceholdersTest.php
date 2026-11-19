<?php

declare(strict_types=1);

use craft\i18n\MessageFormatter;

$languages = array_values(array_filter(
    array_map('basename', glob(dirname(__DIR__, 2) . '/src/translations/*', GLOB_ONLYDIR)),
    fn (string $language): bool => $language !== 'en',
));

it('keeps the original ICU arguments and keywords in translations', function (string $language): void {
    $translations = require dirname(__DIR__, 2) . "/src/translations/{$language}/formie.php";
    $formatter = new MessageFormatter();
    $failures = [];

    foreach ($translations as $message => $translation) {
        preg_match_all('/\{\s*([A-Za-z_]\w*)\s*[,}]/', $message, $matches);

        if (!$matches[1]) {
            continue;
        }

        // Format with 1 and 2 so both singular and plural branches are checked.
        foreach ([1, 2] as $value) {
            $params = array_fill_keys(array_unique($matches[1]), $value);
            $source = $formatter->format($message, $params, 'en');
            $result = $formatter->format($translation, $params, $language);

            if ($result === false || substr_count($result, '{') > substr_count((string)$source, '{')) {
                $failures[] = $message . ' => ' . $translation;

                break;
            }
        }
    }

    expect($failures)->toBe([]);
})->with($languages);

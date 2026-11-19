<?php

declare(strict_types=1);

it('formats German ICU messages using the original parameters', function (string $message, array $params, string $expected): void {
    $translations = require dirname(__DIR__, 2) . '/src/translations/de/formie.php';

    expect(MessageFormatter::formatMessage('de', $translations[$message], $params))->toBe($expected);
})->with([
    'one character' => ['"{label}" should contain at most {max, number} {max, plural, one{character} other{characters}}.', ['label' => 'Größe', 'max' => 1], '„Größe“ sollte höchstens 1 Zeichen enthalten.'],
    'multiple characters' => ['"{label}" should contain at most {max, number} {max, plural, one{character} other{characters}}.', ['label' => 'Größe', 'max' => 2], '„Größe“ sollte höchstens 2 Zeichen enthalten.'],
    'one day offset' => ['+{num, plural, =1{# day} other{# days}}', ['num' => 1], '+1 Tag'],
    'multiple day offset' => ['+{num, plural, =1{# day} other{# days}}', ['num' => 2], '+2 Tage'],
    'one hour offset' => ['+{num, plural, =1{# hour} other{# hours}}', ['num' => 1], '+1 Stunde'],
    'multiple hour offset' => ['+{num, plural, =1{# hour} other{# hours}}', ['num' => 2], '+2 Stunden'],
    'every day' => ['every {num, number} {num, plural, =1{day} other{days}}', ['num' => 1], 'jeden Tag'],
    'every two days' => ['every {num, number} {num, plural, =1{day} other{days}}', ['num' => 2], 'alle 2 Tage'],
    'every month' => ['every {num, number} {num, plural, =1{month} other{months}}', ['num' => 1], 'jeden Monat'],
    'every two months' => ['every {num, number} {num, plural, =1{month} other{months}}', ['num' => 2], 'alle 2 Monate'],
    'every week' => ['every {num, number} {num, plural, =1{week} other{weeks}}', ['num' => 1], 'jede Woche'],
    'every two weeks' => ['every {num, number} {num, plural, =1{week} other{weeks}}', ['num' => 2], 'alle 2 Wochen'],
    'every year' => ['every {num, number} {num, plural, =1{year} other{years}}', ['num' => 1], 'jedes Jahr'],
    'every two years' => ['every {num, number} {num, plural, =1{year} other{years}}', ['num' => 2], 'alle 2 Jahre'],
    'one option' => ['{label} should contain at most {max, number} {max, plural, one{option} other{options}}.', ['label' => 'Auswahl', 'max' => 1], 'Auswahl sollte höchstens 1 Option enthalten.'],
    'multiple options' => ['{label} should contain at most {max, number} {max, plural, one{option} other{options}}.', ['label' => 'Auswahl', 'max' => 2], 'Auswahl sollte höchstens 2 Optionen enthalten.'],
]);

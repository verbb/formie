<?php

use verbb\formie\fields\Date;

it('maps Craft locales to Flatpickr locale names', function(string $craftLocale, string $flatpickrLocale): void {
    $method = new ReflectionMethod(Date::class, '_getDatePickerLocale');

    expect($method->invoke(new Date(), $craftLocale))->toBe($flatpickrLocale);
})->with([
    ['nb-NO', 'no'],
    ['ca-ES', 'cat'],
    ['el-GR', 'gr'],
    ['zh-TW', 'zh-tw'],
    ['zh-Hant', 'zh-tw'],
    ['pt-BR', 'pt'],
    ['not-a-locale', 'en'],
]);

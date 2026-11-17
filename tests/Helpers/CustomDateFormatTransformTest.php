<?php

use verbb\formie\helpers\References;
use verbb\formie\helpers\Variables;

it('offers a custom pattern in the date format picker', function() {
    $transforms = Variables::getCategoryConfig()['transformerRegistry'][Variables::TYPE_DATE];
    $format = array_values(array_filter($transforms, fn(array $item): bool => $item['id'] === 'format'))[0];
    $params = array_column($format['params'], null, 'name');
    $presets = array_column($params['preset']['options'], 'label', 'value');

    expect($presets)->toHaveKey('custom')
        ->and($presets['custom'])->toBe('Custom')
        ->and($params['pattern']['showWhen'])->toBe(['param' => 'preset', 'equals' => 'custom']);
});

it('formats a date with a custom pattern', function() {
    $form = formie()->form()->singleLineTextField('text')->create();
    $submission = formie()->submission($form)->with(['text' => 'Value'])->save();
    $expected = $submission->dateCreated->setTimezone(new DateTimeZone(Craft::$app->getTimeZone()))->format('d.m.Y');

    expect(References::parseContent('{submission:date;transform=format;preset=custom;pattern=d.m.Y}', $submission))->toBe($expected);
});

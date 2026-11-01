<?php

declare(strict_types=1);

use craft\errors\GqlException;
use craft\helpers\StringHelper;
use craft\models\GqlSchema;
use verbb\formie\elements\Form;
use verbb\formie\fields\Date;
use verbb\formie\fields\SingleLineText;
use verbb\formie\Formie;

function dateGraphqlQuery(Form $form, string $selection): array
{
    $gql = Craft::$app->getGql();
    $previous = null;

    try {
        $previous = $gql->getActiveSchema();
    } catch (GqlException) {
    }

    $schema = new GqlSchema([
        'name' => 'Date field values',
        'uid' => StringHelper::UUID(),
        'scope' => ["formieForms.{$form->uid}:read", "formieSubmissions.{$form->uid}:read"],
    ]);

    $gql->flushCaches();
    $gql->setActiveSchema($schema);

    try {
        return $gql->executeQuery($schema, '{ formieSubmissions(form: "' . $form->handle . '") { ... on ' . $form->handle . '_Submission { ' . $selection . ' } } }');
    } finally {
        $gql->flushCaches();
        $gql->setActiveSchema($previous);
    }
}

function dateGraphqlRow(Form $form, string $selection): array
{
    $result = dateGraphqlQuery($form, $selection);

    expect($result['errors'] ?? [])->toBe([]);

    return json_decode(json_encode($result['data']['formieSubmissions'][0]), true);
}

it('returns submitted dates as date strings for every display type', function (array $settings, mixed $value, string $expected): void {
    $form = Form::find()->id(formie()->form()->dateField('eventDate', $settings)->create()->id)->one();
    formie()->submission($form)->with(['eventDate' => $value])->save();

    expect(dateGraphqlRow($form, 'eventDate')['eventDate'])->toBe($expected);
})->with([
    'date picker' => [['displayType' => 'datePicker'], '2010-05-20', '2010-05-20T00:00:00+00:00'],
    'calendar' => [['displayType' => 'calendar'], '2010-05-20', '2010-05-20T00:00:00+00:00'],
    'inputs' => [['displayType' => 'inputs'], ['year' => '2010', 'month' => '5', 'day' => '20'], '2010-05-20T00:00:00+00:00'],
    'dropdowns' => [['displayType' => 'dropdowns'], ['year' => '2010', 'month' => '5', 'day' => '20'], '2010-05-20T00:00:00+00:00'],
    'with time' => [['displayType' => 'datePicker', 'includeTime' => true], '2010-05-20 14:30:15', '2010-05-20T14:30:15+00:00'],
]);

it('keeps stored dates free of time zone conversion when the system zone differs', function (): void {
    $form = Form::find()->id(formie()->form()->dateField('eventDate', ['displayType' => 'datePicker'])->create()->id)->one();
    formie()->submission($form)->with(['eventDate' => '2010-05-20'])->save();
    $timeZone = Craft::$app->getTimeZone();
    Craft::$app->setTimeZone('Australia/Melbourne');

    try {
        expect(dateGraphqlRow($form, 'eventDate')['eventDate'])->toBe('2010-05-20T00:00:00+00:00');
    } finally {
        Craft::$app->setTimeZone($timeZone);
    }
});

it('applies the formatDateTime directive to submitted dates', function (string $directive, string $expected): void {
    $form = Form::find()->id(formie()->form()->dateField('eventDate', ['displayType' => 'datePicker'])->create()->id)->one();
    formie()->submission($form)->with(['eventDate' => '2010-05-20'])->save();

    expect(dateGraphqlRow($form, "formatted: eventDate {$directive}")['formatted'])->toBe($expected);
})->with([
    'php format' => ['@formatDateTime(format: "Y-m-d")', '2010-05-20'],
    'time zone' => ['@formatDateTime(format: "Y-m-d H:i", timezone: "Australia/Melbourne")', '2010-05-20 10:00'],
]);

it('returns null for an empty date', function (): void {
    $form = Form::find()->id(formie()->form()
        ->singleLineTextField('note')
        ->dateField('eventDate', ['displayType' => 'datePicker'])
        ->create()->id)->one();
    formie()->submission($form)->with(['note' => 'No date'])->save();

    expect(dateGraphqlRow($form, 'eventDate formatted: eventDate @formatDateTime(format: "Y-m-d")'))
        ->toBe(['eventDate' => null, 'formatted' => null]);
});

it('returns dates held in repeater rows', function (): void {
    $form = Form::find()->id(formie()->form()->repeaterField('visits', ['rows' => [['fields' => [
        ['type' => Date::class, 'handle' => 'visitDate', 'label' => 'Visit date', 'displayType' => 'datePicker'],
        ['type' => SingleLineText::class, 'handle' => 'note', 'label' => 'Note'],
    ]]]])->create()->id)->one();
    formie()->submission($form)->with(['visits' => [
        ['visitDate' => '2010-05-20', 'note' => 'First'],
        ['visitDate' => '', 'note' => 'Second'],
    ]])->save();

    $row = dateGraphqlRow($form, 'visits { rows { visitDate formatted: visitDate @formatDateTime(format: "d/m/Y") note } }');

    expect($row['visits']['rows'])->toBe([
        ['visitDate' => '2010-05-20T00:00:00+00:00', 'formatted' => '20/05/2010', 'note' => 'First'],
        ['visitDate' => null, 'formatted' => null, 'note' => 'Second'],
    ]);
});

it('returns date ranges with their start and end', function (): void {
    $form = Form::find()->id(formie()->form()->dateField('stay', ['displayType' => 'datePicker', 'collectMode' => Date::COLLECT_RANGE])->create()->id)->one();
    formie()->submission($form)->with(['stay' => ['start' => '2010-05-20', 'end' => '2010-05-24 09:30:00']])->save();

    expect(dateGraphqlRow($form, 'stay { start end ending: end @formatDateTime(format: "Y-m-d") }')['stay'])->toBe([
        'start' => '2010-05-20T00:00:00+00:00',
        'end' => '2010-05-24T09:30:00+00:00',
        'ending' => '2010-05-24',
    ]);
});

it('returns null for an empty date range', function (): void {
    $form = Form::find()->id(formie()->form()
        ->singleLineTextField('note')
        ->dateField('stay', ['displayType' => 'datePicker', 'collectMode' => Date::COLLECT_RANGE])
        ->create()->id)->one();
    formie()->submission($form)->with(['note' => 'No dates'])->save();

    expect(dateGraphqlRow($form, 'stay { start end }')['stay'])->toBeNull();
});

it('returns date ranges held in repeater rows', function (): void {
    $form = Form::find()->id(formie()->form()->repeaterField('visits', ['rows' => [['fields' => [
        ['type' => Date::class, 'handle' => 'stay', 'label' => 'Stay', 'displayType' => 'datePicker', 'collectMode' => Date::COLLECT_RANGE],
    ]]]])->create()->id)->one();
    formie()->submission($form)->with(['visits' => [['stay' => ['start' => '2010-05-20', 'end' => '2010-05-24']]]])->save();

    expect(dateGraphqlRow($form, 'visits { rows { stay { start end } } }')['visits']['rows'][0]['stay'])->toBe([
        'start' => '2010-05-20T00:00:00+00:00',
        'end' => '2010-05-24T00:00:00+00:00',
    ]);
});

it('only treats a saved date picker range as a date range', function (array $settings, string $expected): void {
    $form = Form::find()->id(formie()->form()->dateField('eventDate', $settings)->create()->id)->one();
    $config = current(array_filter(
        Formie::$plugin->getFields()->getAllFieldConfigsForForms([$form->id])[$form->id],
        fn(array $item): bool => $item['handle'] === 'eventDate',
    ));

    expect((string)Date::gqlContentMutationArgumentTypeFromConfig($config)['type'])->toEndWith($expected);
})->with([
    'single date picker' => [['displayType' => 'datePicker'], 'DateTime'],
    'calendar' => [['displayType' => 'calendar'], 'DateTime'],
    'range on another display type' => [['displayType' => 'inputs', 'collectMode' => Date::COLLECT_RANGE], 'DateTime'],
    'range date picker' => [['displayType' => 'datePicker', 'collectMode' => Date::COLLECT_RANGE], 'FormieDateRangeInput'],
]);

it('saves and returns date ranges through a mutation', function (): void {
    $form = Form::find()->id(formie()->form()->dateField('stay', ['displayType' => 'datePicker', 'collectMode' => Date::COLLECT_RANGE])->create()->id)->one();
    $gql = Craft::$app->getGql();
    $previous = null;

    try {
        $previous = $gql->getActiveSchema();
    } catch (GqlException) {
    }

    $schema = new GqlSchema([
        'name' => 'Date range mutation',
        'uid' => StringHelper::UUID(),
        'scope' => ["formieForms.{$form->uid}:read", "formieSubmissions.{$form->uid}:read", "formieSubmissions.{$form->uid}:create", "formieSubmissions.{$form->uid}:save"],
    ]);

    $gql->flushCaches();
    $gql->setActiveSchema($schema);

    try {
        $result = $gql->executeQuery($schema, 'mutation { save_' . $form->handle . '_Submission(stay: { start: "2010-05-20", end: "2010-05-24" }) { ... on ' . $form->handle . '_Submission { stay { start end } } } }');
    } finally {
        $gql->flushCaches();
        $gql->setActiveSchema($previous);
    }

    expect($result['errors'] ?? [])->toBe([])
        ->and(json_decode(json_encode($result['data']['save_' . $form->handle . '_Submission']), true)['stay'])->toBe([
            'start' => '2010-05-20T00:00:00+00:00',
            'end' => '2010-05-24T00:00:00+00:00',
        ]);
});

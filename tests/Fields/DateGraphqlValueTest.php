<?php

declare(strict_types=1);

use craft\errors\GqlException;
use craft\helpers\StringHelper;
use craft\models\GqlSchema;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
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

function dateGraphqlMutation(Form $form, string $query, array $variables = []): array
{
    $gql = Craft::$app->getGql();
    $previous = null;

    try {
        $previous = $gql->getActiveSchema();
    } catch (GqlException) {
    }

    $schema = new GqlSchema([
        'name' => 'Date mutation',
        'uid' => StringHelper::UUID(),
        'scope' => ["formieForms.{$form->uid}:read", "formieSubmissions.{$form->uid}:read", "formieSubmissions.{$form->uid}:create", "formieSubmissions.{$form->uid}:save"],
    ]);

    $gql->flushCaches();
    $gql->setActiveSchema($schema);

    try {
        return $gql->executeQuery($schema, $query, $variables);
    } finally {
        $gql->flushCaches();
        $gql->setActiveSchema($previous);
    }
}

function dateGraphqlInMelbourne(callable $callback): mixed
{
    $timeZone = Craft::$app->getTimeZone();
    Craft::$app->setTimeZone('Australia/Melbourne');

    try {
        return $callback();
    } finally {
        Craft::$app->setTimeZone($timeZone);
    }
}

function dateGraphqlDateOnlyRows(): array
{
    $rows = (new Date(['displayType' => 'calendar']))->getSubFields();

    foreach ($rows as &$row) {
        foreach ($row['fields'] as &$subField) {
            if ($subField['handle'] === 'time') {
                $subField['enabled'] = false;
            }
        }
    }

    unset($row, $subField);

    return $rows;
}

function dateGraphqlStoredParts(int $submissionId, string $handle): mixed
{
    return Submission::find()->id($submissionId)->status(null)->one()->getFieldValue($handle)?->toArray();
}

it('stores mutation dates as sent when the system time zone differs', function (bool $dateOnly, string $sent, bool $asVariable, array $parts, string $returned): void {
    $settings = $dateOnly ? ['displayType' => 'calendar', 'rows' => dateGraphqlDateOnlyRows()] : ['displayType' => 'calendar'];
    $form = Form::find()->id(formie()->form()->dateField('eventDate', $settings)->create()->id)->one();
    $mutation = 'save_' . $form->handle . '_Submission';
    $selection = '{ ... on ' . $form->handle . '_Submission { id eventDate } }';
    $query = $asVariable
        ? 'mutation ($eventDate: DateTime) { ' . $mutation . '(eventDate: $eventDate) ' . $selection . ' }'
        : 'mutation { ' . $mutation . '(eventDate: ' . json_encode($sent) . ') ' . $selection . ' }';

    $result = dateGraphqlInMelbourne(fn() => dateGraphqlMutation($form, $query, $asVariable ? ['eventDate' => $sent] : []));

    expect($result['errors'] ?? [])->toBe([]);

    $saved = $result['data'][$mutation];

    expect($saved['eventDate'])->toBe($returned)
        ->and(dateGraphqlStoredParts((int)$saved['id'], 'eventDate'))->toBe($parts);
})->with([
    'date only without an offset' => [true, '2026-11-17', false, ['year' => '2026', 'month' => '11', 'day' => '17'], '2026-11-17T00:00:00+00:00'],
    'date only as returned by GraphQL' => [true, '2026-11-17T00:00:00+00:00', true, ['year' => '2026', 'month' => '11', 'day' => '17'], '2026-11-17T00:00:00+00:00'],
    'date and time without an offset' => [false, '2026-11-17 20:00:00', true, ['year' => '2026', 'month' => '11', 'day' => '17', 'hour' => '20', 'minute' => '0', 'second' => '0'], '2026-11-17T20:00:00+00:00'],
    'date and time as returned by GraphQL' => [false, '2026-11-17T20:00:00+00:00', false, ['year' => '2026', 'month' => '11', 'day' => '17', 'hour' => '20', 'minute' => '0', 'second' => '0'], '2026-11-17T20:00:00+00:00'],
    'date and time with the site offset' => [false, '2026-11-17T20:00:00+11:00', true, ['year' => '2026', 'month' => '11', 'day' => '17', 'hour' => '20', 'minute' => '0', 'second' => '0'], '2026-11-17T20:00:00+00:00'],
    'date and time with another offset' => [false, '2026-11-17T20:00:00-05:00', true, ['year' => '2026', 'month' => '11', 'day' => '17', 'hour' => '20', 'minute' => '0', 'second' => '0'], '2026-11-17T20:00:00+00:00'],
]);

it('stores mutation date ranges and repeater dates as sent when the system time zone differs', function (): void {
    $form = Form::find()->id(formie()->form()
        ->dateField('stay', ['displayType' => 'datePicker', 'collectMode' => Date::COLLECT_RANGE])
        ->repeaterField('visits', ['rows' => [['fields' => [
            ['type' => Date::class, 'handle' => 'visitDate', 'label' => 'Visit date', 'displayType' => 'calendar'],
        ]]]])
        ->create()->id)->one();
    $mutation = 'save_' . $form->handle . '_Submission';
    $query = 'mutation ($start: DateTime!, $visit: DateTime) { ' . $mutation . '(stay: { start: $start, end: "2026-11-20 10:30:00" }, visits: { rows: [{ visitDate: $visit }] }) { ... on ' . $form->handle . '_Submission { id stay { start end } visits { rows { visitDate } } } } }';

    $result = dateGraphqlInMelbourne(fn() => dateGraphqlMutation($form, $query, ['start' => '2026-11-17 20:00:00', 'visit' => '2026-11-18']));

    expect($result['errors'] ?? [])->toBe([]);

    $saved = json_decode(json_encode($result['data'][$mutation]), true);

    expect($saved['stay'])->toBe(['start' => '2026-11-17T20:00:00+00:00', 'end' => '2026-11-20T10:30:00+00:00'])
        ->and($saved['visits']['rows'][0]['visitDate'])->toBe('2026-11-18T00:00:00+00:00')
        ->and(dateGraphqlStoredParts((int)$saved['id'], 'stay'))->toBe([
            'start' => ['year' => '2026', 'month' => '11', 'day' => '17', 'hour' => '20', 'minute' => '0', 'second' => '0'],
            'end' => ['year' => '2026', 'month' => '11', 'day' => '20', 'hour' => '10', 'minute' => '30', 'second' => '0'],
        ]);
});

it('stores dates as sent through the generic mutation and variable defaults', function (string $mutation): void {
    $form = Form::find()->id(formie()->form()
        ->dateField('dateOnly', ['displayType' => 'calendar', 'rows' => dateGraphqlDateOnlyRows()])
        ->dateField('eventDate', ['displayType' => 'calendar'])
        ->create()->id)->one();
    $name = $mutation === 'generic' ? 'saveSubmission' : 'save_' . $form->handle . '_Submission';
    $query = $mutation === 'generic'
        ? 'mutation { saveSubmission(formHandle: "' . $form->handle . '", fields: { dateOnly: "2026-11-17", eventDate: "2026-11-17 20:00:00" }) { id } }'
        : 'mutation ($dateOnly: DateTime = "2026-11-17", $eventDate: DateTime = "2026-11-17 20:00:00") { ' . $name . '(dateOnly: $dateOnly, eventDate: $eventDate) { id } }';

    $result = dateGraphqlInMelbourne(fn() => dateGraphqlMutation($form, $query));

    expect($result['errors'] ?? [])->toBe([]);

    $id = (int)$result['data'][$name]['id'];

    expect(dateGraphqlStoredParts($id, 'dateOnly'))->toBe(['year' => '2026', 'month' => '11', 'day' => '17'])
        ->and(dateGraphqlStoredParts($id, 'eventDate'))->toBe(['year' => '2026', 'month' => '11', 'day' => '17', 'hour' => '20', 'minute' => '0', 'second' => '0']);
})->with(['generic' => 'generic', 'variable defaults' => 'variable defaults']);

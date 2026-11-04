<?php

declare(strict_types=1);

use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\helpers\ArrayHelper;
use verbb\formie\integrations\elements\CalendarEvent;

function calendarDateForm(): Form
{
    return formie()->form(['title' => 'Calendar Dates ' . uniqid()])
        ->dateField('eventStart', ['displayType' => 'datePicker'])
        ->dateField('eventEnd', ['displayType' => 'datePicker'])
        ->dateField('eventUntil', ['displayType' => 'datePicker'])
        ->create();
}

function mapCalendarDates(Form $form, array $values, array $attributes): array
{
    $saved = formie()->submission($form)->with($values)->save();
    $submission = Submission::find()->id($saved->id)->status(null)->one();
    $integration = new CalendarEvent(['name' => 'Calendar', 'handle' => 'calendar']);

    $mapping = [];

    foreach ($attributes as $attribute => $handle) {
        $mapping[$attribute] = '{field:' . $form->getFieldByHandle($handle)->reference . '}';
    }

    return $integration->getFieldMappingValues($submission, $mapping, $integration->getElementAttributes());
}

it('maps populated event dates with their entered date and time', function (): void {
    $form = calendarDateForm();
    $values = mapCalendarDates($form, ['eventStart' => '2026-03-01 09:30:00', 'eventEnd' => '2026-03-02 17:45:00'], [
        'startDate' => 'eventStart',
        'endDate' => 'eventEnd',
    ]);

    expect($values['startDate']->format('Y-m-d H:i:s'))->toBe('2026-03-01 09:30:00')
        ->and($values['endDate']->format('Y-m-d H:i:s'))->toBe('2026-03-02 17:45:00');
});

it('keeps the entered date and time when the site uses another time zone', function (): void {
    $timeZone = Craft::$app->getTimeZone();
    Craft::$app->setTimeZone('Australia/Melbourne');

    try {
        $form = calendarDateForm();
        $values = mapCalendarDates($form, ['eventStart' => '2026-03-01 09:30:00', 'eventUntil' => '2026-04-01 18:15:00'], [
            'startDate' => 'eventStart',
            'until' => 'eventUntil',
        ]);

        expect($values['startDate']->format('Y-m-d H:i:s'))->toBe('2026-03-01 09:30:00')
            ->and($values['until']->format('Y-m-d H:i:s'))->toBe('2026-04-01 18:15:00');
    } finally {
        Craft::$app->setTimeZone($timeZone);
    }
});

it('leaves a blank mapped date out in the same way as an unmapped date', function (): void {
    $form = calendarDateForm();
    $submitted = ['eventStart' => '2026-03-01 09:30:00', 'eventEnd' => null];
    $blank = mapCalendarDates($form, $submitted, ['startDate' => 'eventStart', 'endDate' => 'eventEnd']);
    $unmapped = mapCalendarDates($form, $submitted, ['startDate' => 'eventStart']);

    expect($blank)->toHaveKey('endDate')
        ->and($blank['endDate'])->toBeNull()
        ->and(array_keys(ArrayHelper::filterNull($blank)))->toBe(['startDate'])
        ->and(array_keys(ArrayHelper::filterNull($unmapped)))->toBe(['startDate'])
        ->and(ArrayHelper::filterNull($blank)['startDate']->format('Y-m-d H:i:s'))->toBe('2026-03-01 09:30:00');
});

it('leaves out each blank event date', function (string $attribute, string $handle): void {
    $form = calendarDateForm();
    $values = mapCalendarDates($form, [$handle => null], [$attribute => $handle]);

    expect($values)->toBe([$attribute => null]);
})->with([
    'start date' => ['startDate', 'eventStart'],
    'end date' => ['endDate', 'eventEnd'],
    'repeat until' => ['until', 'eventUntil'],
]);

it('maps a populated repeat until date with its entered date and time', function (): void {
    $form = calendarDateForm();
    $values = mapCalendarDates($form, ['eventUntil' => '2026-04-01 18:15:00'], ['until' => 'eventUntil']);

    expect($values['until'])->toBeInstanceOf(DateTime::class)
        ->and($values['until']->format('Y-m-d H:i:s'))->toBe('2026-04-01 18:15:00');
});

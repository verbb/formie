<?php

declare(strict_types=1);

use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\fields\Date;
use verbb\formie\Formie;

use craft\base\Element;

function createDateDropdownEmptyOptionForm(array $fieldConfig = [], ?string $partPlaceholder = null, bool $partsRequired = false): Form
{
    $rows = (new Date(['displayType' => 'dropdowns']))->getSubFields();

    foreach ($rows as &$row) {
        foreach ($row['fields'] as &$subField) {
            if (in_array($subField['handle'], ['hour', 'minute', 'second', 'ampm'], true)) {
                $subField['enabled'] = false;
            } else {
                // Date fields are required through their sub-fields.
                $subField['required'] = $partsRequired;

                if ($partPlaceholder !== null) {
                    $subField['placeholder'] = $partPlaceholder;
                }
            }
        }
    }

    unset($row, $subField);

    $form = formie()
        ->form(['title' => 'Date Dropdown Empty Option ' . uniqid()])
        ->dateField('eventDate', array_merge(['displayType' => 'dropdowns', 'rows' => $rows], $fieldConfig))
        ->create();

    // Reload so field settings, such as the Min Date, are normalized as they are for a saved form.
    return Form::find()->withoutCpIndexScope()->id($form->id)->siteId($form->siteId)->status(null)->one();
}

function getDateDropdownEmptyOptions(array $options): array
{
    return array_values(array_filter($options, static fn(array $option): bool => !isset($option['optgroup']) && (string)($option['value'] ?? '') === ''));
}

it('starts every date and time dropdown with one empty option', function (string $handle): void {
    $field = new Date(['handle' => 'eventDate', 'displayType' => 'dropdowns']);
    $field->setRows((new Date(['displayType' => 'dropdowns']))->getSubFields());

    $options = $field->getFieldByHandle($handle)->getFieldOptions();

    expect($options[0])->toBe(['label' => '', 'value' => ''])
        ->and(getDateDropdownEmptyOptions($options))->toHaveCount(1)
        ->and(count($options))->toBeGreaterThan(1);
})->with(['year', 'month', 'day', 'hour', 'minute', 'second', 'ampm']);

it('keeps a date dropdown placeholder as the only empty option', function (): void {
    $form = createDateDropdownEmptyOptionForm([], 'Choose');
    $options = $form->getFieldByHandle('eventDate')->getFieldByHandle('year')->getFieldOptions();

    expect($options[0])->toBe(['label' => 'Choose', 'value' => ''])
        ->and(getDateDropdownEmptyOptions($options))->toHaveCount(1);
});

it('renders untouched date dropdowns with the empty option selected', function (): void {
    $form = createDateDropdownEmptyOptionForm(['minDateOption' => 'date', 'minDate' => '2026-12-01']);

    $html = (string)Formie::$plugin->getRendering()->renderField($form, 'eventDate');

    expect(substr_count($html, '<option value="" selected></option>'))->toBe(3);
});

it('only applies date dropdown limits and required to chosen dates', function (bool $required, array $parts, bool $valid): void {
    $form = createDateDropdownEmptyOptionForm([
        'minDateOption' => 'date',
        'minDate' => '2026-12-01',
    ], null, $required);

    $submission = new Submission();
    $submission->setForm($form);
    $submission->title = 'Date dropdown empty option';
    $submission->setScenario(Element::SCENARIO_LIVE);
    $submission->setFieldValueFromRequest('eventDate', $parts);

    expect(Craft::$app->getElements()->saveElement($submission))->toBe($valid);

    if ($valid) {
        expect(Submission::find()->id($submission->id)->status(null)->one()->getFieldValue('eventDate'))->toBeNull();
    } else {
        expect($submission)->toHaveFieldError('eventDate.year');
    }
})->with([
    'optional untouched' => [false, ['year' => '', 'month' => '', 'day' => ''], true],
    'required untouched' => [true, ['year' => '', 'month' => '', 'day' => ''], false],
]);

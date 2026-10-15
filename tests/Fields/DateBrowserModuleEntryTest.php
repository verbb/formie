<?php

declare(strict_types=1);

use verbb\formie\Formie;
use verbb\formie\models\BrowserModule;

it('passes includeFlatpickrCss through the date-picker client module config', function (): void {
    $previousSetting = Formie::$plugin->getSettings()->includeFlatpickrCss;

    try {
        Formie::$plugin->getSettings()->includeFlatpickrCss = false;

        $form = formie()
            ->form(['title' => 'Flatpickr CSS Setting'])
            ->dateField('eventDate', [
                'displayType' => 'datePicker',
            ])
            ->create();

        $modules = Formie::$plugin->getBrowserModuleManifestBuilder()->buildForSurface($form, BrowserModule::SURFACE_SERVER_RENDERED)->toArray()['entries'];
        $datePickerModule = current(array_filter($modules, static fn(array $module): bool => ($module['moduleId'] ?? null) === 'formie:date-picker')) ?: null;

        expect($datePickerModule)->not->toBeNull()
            ->and($datePickerModule['config']['includeFlatpickrCss'] ?? null)->toBeFalse();
    } finally {
        Formie::$plugin->getSettings()->includeFlatpickrCss = $previousSetting;
    }
});

it('renders and round trips cp date range picker boundaries', function (bool $withTime): void {
    $form = formie()->form()->dateField('stay', [
        'displayType' => 'datePicker',
        'collectMode' => \verbb\formie\fields\Date::COLLECT_RANGE,
        'dateFormat' => 'd/m/Y',
        'timeFormat' => 'H:i',
    ])->create();
    $field = $form->getFieldByHandle('stay');
    foreach (['startDate', 'endDate'] as $handle) {
        $field->getFieldByHandle($handle)->enabled = true;
    }
    foreach (['startTime', 'endTime'] as $handle) {
        $field->getFieldByHandle($handle)->enabled = $withTime;
    }
    $start = $withTime ? '2026-10-21 09:30:00' : '2026-10-21';
    $end = $withTime ? '2026-10-24 17:45:00' : '2026-10-24';
    $value = $field->normalizeValue(['start' => $start, 'end' => $end], null);

    \Tests\Support\WebRequestTestHelper::withWebRequestContext(function () use ($form, $field, $value, $start, $end): void {
        Craft::$app->getRequest()->setIsCpRequest(true);
        $submission = new \verbb\formie\elements\Submission();
        $submission->setForm($form);
        $document = new DOMDocument();
        @$document->loadHTML((string)$field->getSubmissionHtml($value, $submission));
        $xpath = new DOMXPath($document);
        $picker = $xpath->query('//input[@data-formie-date-datepicker-input]');
        $startInput = $xpath->query('//input[@data-formie-date-range-start-input]')->item(0);
        $endInput = $xpath->query('//input[@data-formie-date-range-end-input]')->item(0);
        expect($picker->length)->toBe(1)
            ->and($picker->item(0)->hasAttribute('name'))->toBeFalse()
            ->and($startInput->getAttribute('name'))->toBe('stay[start]')
            ->and($startInput->getAttribute('value'))->toBe($start)
            ->and($endInput->getAttribute('name'))->toBe('stay[end]')
            ->and($endInput->getAttribute('value'))->toBe($end);

        $entries = Formie::$plugin->getBrowserModuleManifestBuilder()->buildForSurface($form, BrowserModule::SURFACE_CP_EDIT)->toArray()['entries'];
        $entry = current(array_filter($entries, fn($entry) => $entry['moduleId'] === 'formie:date-picker'));
        expect($entry['config']['collectMode'])->toBe('range');

        $posted = $field->normalizeValue(['start' => $startInput->getAttribute('value'), 'end' => $endInput->getAttribute('value')], $submission);
        expect($field->serializeValue($posted, $submission))->toBe($field->serializeValue($value, $submission));
    });
})->with([false, true]);

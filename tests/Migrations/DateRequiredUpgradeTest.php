<?php

declare(strict_types=1);

use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\Formie;
use verbb\formie\migrations\m231125_000000_craft5;
use verbb\formie\migrations\m240313_000000_subfields;
use verbb\formie\migrations\m240318_000000_migrate_stencils;

it('retains legacy required Date validation when converting each display layout', function (string $migrationClass, string $display, bool $required): void {
    $migration = new $migrationClass();
    $method = new ReflectionMethod($migration, '_getDate' . ucfirst($display) . 'Config');
    $rows = $method->invoke($migration, ['required' => $required, 'includeDate' => true, 'includeTime' => true]);
    $form = formie()->form()->dateField('appointment', [
        'displayType' => $display,
        'layouts' => [$display => $rows],
        'rows' => $rows,
    ])->create();
    Formie::$plugin->getFields()->resetFieldRegistryCache();
    $form = Form::find()->id($form->id)->one();
    $submission = new Submission(['title' => 'Legacy date conversion']);
    $submission->setForm($form);
    $submission->setScenario('live');
    $submission->setFieldValues(['appointment' => []]);
    expect($submission->validate())->toBe(!$required);

    if ($required) {
        expect($submission->getErrors())->not->toBeEmpty();
    }

    $submission->clearErrors();
    $submission->setFieldValues(['appointment' => ['year' => '2026', 'month' => '11', 'day' => '26', 'hour' => '10', 'minute' => '30']]);
    expect($submission->validate())->toBeTrue()->and($submission->getErrors())->toBeEmpty();
    $enabled = array_map(fn($child) => $child->handle, array_filter($form->getFieldByHandle('appointment')->getFields(), fn($child) => $child->enabled));
    expect($enabled)->not->toContain('second')->not->toContain('ampm');
})->with([m231125_000000_craft5::class, m240313_000000_subfields::class, m240318_000000_migrate_stencils::class])
    ->with(['calendar', 'dropdowns', 'inputs'])
    ->with([false, true]);


it('requires only the enabled legacy date and time parts during the direct Craft 5 conversion', function (string $display, bool $required, array $settings, array $value, array $expectedEnabled): void {
    $migration = new m231125_000000_craft5();
    $method = new ReflectionMethod($migration, '_getDate' . ucfirst($display) . 'Config');
    $rows = $method->invoke($migration, ['required' => $required] + $settings);
    $form = formie()->form()->dateField('appointment', ['displayType' => $display, 'layouts' => [$display => $rows], 'rows' => $rows])->create();
    Formie::$plugin->getFields()->resetFieldRegistryCache();
    $form = Form::find()->id($form->id)->one();
    $enabled = array_map(fn($child) => $child->handle, array_filter($form->getFieldByHandle('appointment')->getFields(), fn($child) => $child->enabled));
    expect(array_values($enabled))->toBe($expectedEnabled);
    $submission = new Submission(['title' => 'Legacy enabled date parts']);
    $submission->setForm($form);
    $submission->setScenario('live');
    $submission->setFieldValues(['appointment' => []]);
    expect($submission->validate())->toBe(!$required);
    $submission->clearErrors();
    $submission->setFieldValues(['appointment' => $value]);
    expect($submission->validate())->toBeTrue()->and($submission->getErrors())->toBeEmpty();
})->with(['dropdowns', 'inputs'])->with([false, true])->with([
    'date only' => [
        ['includeDate' => true, 'includeTime' => false, 'timeFormat' => 'H:i'],
        ['year' => '2026', 'month' => '11', 'day' => '26'],
        ['year', 'month', 'day'],
    ],
    'midnight time only' => [
        ['includeDate' => false, 'includeTime' => true, 'timeFormat' => 'H:i'],
        ['hour' => '0', 'minute' => '0'],
        ['hour', 'minute'],
    ],
    'seconds and meridiem' => [
        ['includeDate' => false, 'includeTime' => true, 'timeFormat' => 'h:i:s A'],
        ['hour' => '10', 'minute' => '30', 'second' => '45', 'ampm' => 'PM'],
        ['hour', 'minute', 'second', 'ampm'],
    ],
]);

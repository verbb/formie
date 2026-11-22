<?php

declare(strict_types=1);

use craft\base\Element;
use verbb\formie\elements\Submission;

it('applies option limits to empty selections on optional multi-option fields', function(string $type, mixed $value, ?int $min, ?int $max, bool $required, ?string $error): void {
    $options = [
        ['label' => 'One', 'value' => 'one'],
        ['label' => 'Two', 'value' => 'two'],
        ['label' => 'Three', 'value' => 'three'],
    ];
    $settings = ['limitOptions' => true, 'min' => $min, 'max' => $max, 'required' => $required, 'options' => $options];
    $builder = formie()->form();
    $form = ($type === 'dropdown' ? $builder->dropdownField('topics', [...$settings, 'multi' => true]) : $builder->checkboxesField('topics', $settings))->create();

    $submission = new Submission(['title' => 'Option limits']);
    $submission->setForm($form);
    $submission->setFieldValue('topics', $value);
    // Front-end submissions validate in the live scenario, where required fields apply.
    $submission->setScenario(Element::SCENARIO_LIVE);

    expect($submission->validate())->toBe($error === null);

    $errors = implode(' ', $submission->getErrors('topics'));
    expect($errors)->not->toContain('must be an array');

    if ($error !== null) {
        expect($errors)->toContain($error);
    }
})->with(['checkboxes', 'dropdown'])->with([
    'empty string under a maximum' => ['', null, 2, false, null],
    'empty list under a maximum' => [[], null, 2, false, null],
    'null under a maximum' => [null, null, 2, false, null],
    'empty with a minimum' => [[], 1, 2, false, 'at least 1'],
    'empty when required' => [[], null, 2, true, 'cannot be blank'],
    'within the limits' => [['one'], 1, 2, false, null],
    'over the maximum' => [['one', 'two', 'three'], null, 2, false, 'at most 2'],
]);

<?php

declare(strict_types=1);

use Tests\Support\Factories\ConditionFormFactory;
use verbb\formie\fields\Group;

it('creates an options-driven runtime condition form for browser harnesses', function (): void {
    $form = formie()->conditionForms()->optionsValueVisibility();

    expect($form->hasFieldConditions())->toBeTrue()
        ->and($form->getFieldByHandle('enquiryType'))->not->toBeNull()
        ->and($form->getFieldByHandle('otherReason'))->not->toBeNull()
        ->and(ConditionFormFactory::browserPath($form))->toBe('/formie/tests/conditions/' . $form->handle);
});

it('creates a nested group condition form that can exercise row-collapse behavior', function (): void {
    $form = formie()->conditionForms()->nestedGroupRowCollapse();
    $group = $form->getFieldByHandle('contactGroup');
    $hasConditionalNestedField = false;

    foreach ($group?->getFields() ?? [] as $field) {
        if ($field->handle === 'smsNumber' && $field->enableConditions) {
            $hasConditionalNestedField = true;
            break;
        }
    }

    expect($group)->toBeInstanceOf(Group::class)
        ->and($form->hasFieldConditions())->toBeTrue()
        ->and($hasConditionalNestedField)->toBeTrue();
});

it('creates a page-condition form for front-end navigation coverage', function (): void {
    $form = formie()->conditionForms()->pageVisibility();

    expect($form->hasMultiplePages())->toBeTrue()
        ->and($form->hasPageConditions())->toBeTrue()
        ->and(ConditionFormFactory::browserPath($form))->toBe('/formie/tests/conditions/' . $form->handle);
});

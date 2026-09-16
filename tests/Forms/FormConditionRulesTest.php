<?php

declare(strict_types=1);

use verbb\formie\elements\Form;
use verbb\formie\elements\conditions\{FormHandleConditionRule, FormStatusConditionRule};

it('applies registered form handle and status conditions to actual queries', function (string $type): void {
    $form = formie()->form()->singleLineTextField('message')->create();
    $rule = $type === 'handle'
        ? new FormHandleConditionRule(['values' => [$form->handle]])
        : new FormStatusConditionRule(['values' => [(string)$form->getFormStatusId()]]);
    $condition = Form::createCondition();
    $condition->addConditionRule($rule);
    $query = Form::find()->id($form->id);
    $rule->modifyQuery($query);
    expect($query->one()?->id)->toBe($form->id);
    expect($rule->matchElement($form))->toBeTrue();
    expect($rule->getLabel())->not->toBeEmpty();
})->with(['handle', 'status']);

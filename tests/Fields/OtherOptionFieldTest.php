<?php

declare(strict_types=1);

use verbb\formie\elements\Submission;
use verbb\formie\fields\Radio;
use verbb\formie\fields\traits\OtherOptionFieldTrait;
use verbb\formie\theme\context\RenderContext;

it('stores a custom radio value when the other option is selected', function (): void {
    $options = [
        ['label' => 'One', 'value' => 'one'],
        ['label' => 'Two', 'value' => 'two'],
    ];

    $form = formie()
        ->form(['title' => 'Other Option Fields'])
        ->radioField('priority', [
            'options' => $options,
            'enableOtherOption' => true,
            'otherOptionLabel' => 'Something else',
        ])
        ->create();

    $submission = formie()
        ->submission($form)
        ->with([
            'priority' => [
                'value' => OtherOptionFieldTrait::OTHER_OPTION_VALUE,
                'other' => 'Custom priority',
            ],
        ])
        ->save();

    expect($submission->getFieldValueAsString('priority'))->toContain('Custom priority');

    $priorityResults = Submission::find()
        ->formId($form->id)
        ->field('priority', 'Custom priority')
        ->all();

    expect($priorityResults)->toHaveCount(1)
        ->and((int)$priorityResults[0]->id)->toBe((int)$submission->id);
});

it('rejects static option values that use the reserved other sentinel', function (): void {
    $field = new Radio([
        'options' => [
            ['label' => 'Other', 'value' => OtherOptionFieldTrait::OTHER_OPTION_VALUE],
        ],
        'enableOtherOption' => true,
    ]);

    $field->validateOptions();

    expect($field->hasErrors('options'))->toBeTrue();
});

it('requires custom text when the other option is selected', function (): void {
    $form = formie()
        ->form(['title' => 'Other Option Validation'])
        ->radioField('priority', [
            'options' => [
                ['label' => 'One', 'value' => 'one'],
                ['label' => 'Two', 'value' => 'two'],
            ],
            'enableOtherOption' => true,
        ])
        ->create();

    Craft::$app->getRequest()->setBodyParams([
        'fields' => [
            'priority' => OtherOptionFieldTrait::OTHER_OPTION_VALUE,
        ],
    ]);

    $submission = formie()
        ->submission($form)
        ->with(['priority' => OtherOptionFieldTrait::OTHER_OPTION_VALUE])
        ->allowValidationFailure()
        ->save();

    expect($submission)->toHaveFieldError('priority');
});

it('applies radio other option theme config tags', function (): void {
    $form = formie()
        ->form(['title' => 'Other Option Theme Config'])
        ->radioField('priority', [
            'options' => [
                ['label' => 'One', 'value' => 'one'],
            ],
            'enableOtherOption' => true,
        ])
        ->create();

    $field = $form->getFieldByHandle('priority');
    $page = $form->getPages()[0];

    $form->setThemeConfig([
        'radioButtons' => [
            'fieldOtherOptionText' => [
                'attributes' => [
                    'class' => 'theme-other-text',
                    'placeholder' => 'Custom other text',
                ],
            ],
        ],
    ]);

    $tag = $field->renderSlotTag('fieldOtherOptionText', RenderContext::from([
        'form' => $form,
        'field' => $field,
        'page' => $page,
        'currentPage' => $page,
        'value' => '',
    ]));

    expect($tag?->attributes['class'] ?? [])->toContain('formie-other-option-text')
        ->and($tag?->attributes['class'] ?? [])->toContain('theme-other-text')
        ->and($tag?->attributes['placeholder'] ?? null)->toBe('Custom other text');
});

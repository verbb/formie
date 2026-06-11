<?php

declare(strict_types=1);

use verbb\formie\elements\Form;
use verbb\formie\fields\SingleLineText;
use verbb\formie\helpers\FieldAttributesHelper;
use verbb\formie\models\FieldLayout;

function fieldAttributesHelperFormHandle(): string
{
    return 'fieldAttributes' . uniqid();
}

function fieldAttributesHelperForm(array $fieldConfig): Form
{
    $form = new Form([
        'title' => 'Attribute Overrides',
        'handle' => fieldAttributesHelperFormHandle(),
    ]);

    $form->setFormLayout(new FieldLayout([
        'pages' => [
            [
                'label' => 'Page 1',
                'settings' => [],
                'rows' => [
                    [
                        'fields' => [
                            array_merge([
                                'type' => SingleLineText::class,
                                'handle' => 'fullName',
                                'label' => 'Full Name',
                            ], $fieldConfig),
                        ],
                    ],
                ],
            ],
        ],
    ]));

    return $form;
}

it('normalizes craft-style attribute maps to editable-table storage', function (): void {
    $normalized = FieldAttributesHelper::normalize([
        'readonly' => true,
        'data' => [
            'foo' => 'bar',
        ],
    ], FieldAttributesHelper::SETTING_INPUT);

    expect($normalized)->toBe([
        ['label' => 'readonly', 'value' => true],
        ['label' => 'data-foo', 'value' => 'bar'],
    ]);
});

it('keeps editable-table attribute format intact', function (): void {
    $normalized = FieldAttributesHelper::normalize([
        ['label' => 'data-test-input', 'value' => 'input-value'],
    ], FieldAttributesHelper::SETTING_INPUT);

    expect($normalized)->toBe([
        ['label' => 'data-test-input', 'value' => 'input-value'],
    ]);
});

it('throws when attribute settings use an invalid format', function (): void {
    FieldAttributesHelper::normalize('readonly', FieldAttributesHelper::SETTING_INPUT);
})->throws(InvalidArgumentException::class);

it('merges craft-style attributes with existing field attributes', function (): void {
    $merged = FieldAttributesHelper::merge(
        [
            ['label' => 'data-existing', 'value' => 'keep-me'],
            ['label' => 'class', 'value' => 'existing-class'],
        ],
        [
            'readonly' => true,
            'class' => 'extra-class',
            'data' => [
                'new' => 'value',
            ],
        ],
        FieldAttributesHelper::SETTING_INPUT,
    );

    expect($merged)->toEqualCanonicalizing([
        ['label' => 'data-existing', 'value' => 'keep-me'],
        ['label' => 'readonly', 'value' => true],
        ['label' => 'class', 'value' => ['existing-class', 'extra-class']],
        ['label' => 'data-new', 'value' => 'value'],
    ]);
});

it('applies merge and replace field settings for setFieldSettings', function (): void {
    $form = fieldAttributesHelperForm([
        'inputAttributes' => [
            ['label' => 'data-existing', 'value' => 'saved'],
        ],
        'containerAttributes' => [
            ['label' => 'data-container', 'value' => 'saved-container'],
        ],
    ]);

    $form->setFieldSettings('fullName', [
        'mergeInputAttributes' => [
            'readonly' => true,
        ],
        'mergeContainerAttributes' => [
            'data' => [
                'template' => 'override',
            ],
        ],
    ]);

    $field = $form->getFieldByHandle('fullName');

    expect($field?->getInputAttributes())->toMatchArray([
        'data-existing' => 'saved',
        'readonly' => true,
    ])->and($field?->getContainerAttributes())->toMatchArray([
        'data-container' => 'saved-container',
        'data-template' => 'override',
    ]);
});

it('replaces attributes when craft-style inputAttributes are passed to setFieldSettings', function (): void {
    $form = fieldAttributesHelperForm([
        'inputAttributes' => [
            ['label' => 'data-existing', 'value' => 'saved'],
        ],
    ]);

    $form->setFieldSettings('fullName', [
        'inputAttributes' => [
            'readonly' => true,
        ],
    ]);

    $field = $form->getFieldByHandle('fullName');

    expect($field?->getInputAttributes())->toBe([
        'readonly' => true,
    ]);
});

it('normalizes craft-style attributes when constructing fields directly', function (): void {
    $field = new SingleLineText([
        'handle' => 'fullName',
        'label' => 'Full Name',
        'inputAttributes' => [
            'readonly' => true,
        ],
    ]);

    expect($field->getInputAttributes())->toBe([
        'readonly' => true,
    ]);
});

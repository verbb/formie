<?php

declare(strict_types=1);

use verbb\formie\fields\MultiLineText;
use verbb\formie\fields\SingleLineText;
use verbb\formie\helpers\HtmlAutocomplete;
use verbb\formie\theme\context\RenderContext;

it('validates html autocomplete tokens', function (): void {
    expect(HtmlAutocomplete::isValid(null))->toBeTrue()
        ->and(HtmlAutocomplete::isValid(''))->toBeTrue()
        ->and(HtmlAutocomplete::isValid('email'))->toBeTrue()
        ->and(HtmlAutocomplete::isValid('billing email'))->toBeTrue()
        ->and(HtmlAutocomplete::isValid('not valid!'))->toBeFalse();
});

it('outputs autocomplete attribute from single-line text field settings', function (): void {
    $form = formie()
        ->form(['title' => 'Autocomplete Single Line'])
        ->singleLineTextField('fullName', [
            'autocomplete' => 'name',
        ])
        ->create();

    /** @var SingleLineText $field */
    $field = $form->getFieldByHandle('fullName');

    $tag = $field->renderSlotTag('fieldInput', RenderContext::from([
        'form' => $form,
        'value' => '',
    ]));

    expect($tag?->coreAttributes['autocomplete'] ?? null)->toBe('name');
});

it('outputs autocomplete attribute from multi-line text field settings', function (): void {
    $form = formie()
        ->form(['title' => 'Autocomplete Multi Line'])
        ->multiLineTextField('notes', [
            'autocomplete' => 'off',
            'useRichText' => false,
        ])
        ->create();

    /** @var MultiLineText $field */
    $field = $form->getFieldByHandle('notes');

    $tag = $field->renderSlotTag('fieldInput', RenderContext::from([
        'form' => $form,
        'value' => '',
    ]));

    expect($tag?->coreAttributes['autocomplete'] ?? null)->toBe('off');
});

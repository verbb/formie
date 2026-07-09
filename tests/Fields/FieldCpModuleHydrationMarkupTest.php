<?php

declare(strict_types=1);

use verbb\formie\fields\SingleLineText;

it('renders cp module hydration markers for shared module-backed fields', function (): void {
    $rows = [[
        'fields' => [[
            'type' => SingleLineText::class,
            'handle' => 'innerText',
            'label' => 'Inner Text',
        ]],
    ]];

    $form = formie()
        ->form(['title' => 'CP Module Hydration Markup'])
        ->singleLineTextField('fullName', [
            'limit' => true,
            'min' => 2,
            'max' => 20,
            'maxType' => 'characters',
        ])
        ->multiLineTextField('bio', [
            'useRichText' => true,
        ])
        ->phoneField('contactPhone', [
            'countryEnabled' => true,
        ])
        ->calculationsField('orderTotal', [
            'formula' => '1 + 2',
        ])
        ->signatureField('signature')
        ->repeaterField('lineItems', ['rows' => $rows])
        ->create();

    $submission = formie()
        ->submission($form)
        ->with([
            'fullName' => 'Taylor',
            'bio' => '<p>Hello <strong>there</strong></p>',
            'contactPhone' => [
                'number' => '+1 415 555 0111',
                'country' => 'US',
            ],
            'orderTotal' => '3',
            'signature' => 'data:image/png;base64,Zm9v',
            'lineItems' => [
                ['innerText' => 'Row One'],
            ],
        ])
        ->save();

    $textField = $form->getFieldByHandle('fullName');
    $richTextField = $form->getFieldByHandle('bio');
    $phoneField = $form->getFieldByHandle('contactPhone');
    $calculationsField = $form->getFieldByHandle('orderTotal');
    $signatureField = $form->getFieldByHandle('signature');
    $repeaterField = $form->getFieldByHandle('lineItems');

    $textHtml = (string)$textField?->getSubmissionHtml($submission->getFieldValue('fullName'), $submission);
    $richTextHtml = (string)$richTextField?->getSubmissionHtml($submission->getFieldValue('bio'), $submission);
    $phoneHtml = (string)$phoneField?->getSubmissionHtml($submission->getFieldValue('contactPhone'), $submission);
    $calculationsHtml = (string)$calculationsField?->getSubmissionHtml($submission->getFieldValue('orderTotal'), $submission);
    $signatureHtml = (string)$signatureField?->getSubmissionHtml($submission->getFieldValue('signature'), $submission);
    $repeaterHtml = (string)$repeaterField?->getSubmissionHtml($submission->getFieldValue('lineItems'), $submission);

    expect($textHtml)
        ->toContain('data-formie-field-handle="fullName"')
        ->and($textHtml)->toContain('data-formie-field-type="single-line-text"')
        ->and($textHtml)->toContain('data-formie-single-line-text-input')
        ->and($textHtml)->toContain('data-formie-max-chars="20"')
        ->and($textHtml)->not->toContain('maxlength=')
        ->and($textHtml)->toContain('data-formie-limit-text');

    expect($richTextHtml)
        ->toContain('data-formie-field-handle="bio"')
        ->and($richTextHtml)->toContain('data-formie-field-type="multi-line-text"')
        ->and($richTextHtml)->toContain('data-formie-rich-text')
        ->and($richTextHtml)->toContain('data-formie-multi-line-text-input')
        ->and($richTextHtml)->not->toContain('data-rich-text');

    expect($phoneHtml)
        ->toContain('data-formie-field-handle="contactPhone"')
        ->and($phoneHtml)->toContain('data-formie-phone-input')
        ->and($phoneHtml)->toContain('data-formie-phone-country-input');

    expect($calculationsHtml)
        ->toContain('data-formie-field-handle="orderTotal"')
        ->and($calculationsHtml)->toContain('data-formie-calculation-input');

    expect($signatureHtml)
        ->toContain('data-formie-field-handle="signature"')
        ->and($signatureHtml)->toContain('data-formie-signature-input')
        ->and($signatureHtml)->toContain('data-formie-signature-canvas')
        ->and($signatureHtml)->toContain('data-formie-signature-clear')
        ->and($signatureHtml)->toContain('get-signature-image');

    expect($repeaterHtml)
        ->toContain('data-formie-field-handle="lineItems"')
        ->and($repeaterHtml)->toContain('data-formie-field-type="repeater"')
        ->and($repeaterHtml)->toContain('data-formie-repeater-field-layout')
        ->and($repeaterHtml)->toContain('data-formie-repeater-container')
        ->and($repeaterHtml)->toContain('data-formie-repeater-add')
        ->and($repeaterHtml)->toContain('data-formie-repeater-item')
        ->and($repeaterHtml)->toContain('data-formie-repeater-remove')
        ->and($repeaterHtml)->toContain('data-formie-repeater-template')
        ->and($repeaterHtml)->toContain('data-formie-template-id');
});

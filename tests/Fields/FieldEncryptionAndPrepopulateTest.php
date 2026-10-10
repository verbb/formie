<?php

declare(strict_types=1);

use craft\db\Query;
use Tests\Support\WebRequestTestHelper;
use verbb\formie\Formie;
use verbb\formie\helpers\Table;

it('stores encrypted field values as non-plaintext payloads when encryption is enabled', function (): void {
    $plainText = 'HighlySensitiveValue-123';

    $form = formie()
        ->form(['title' => 'Encryption Contract'])
        ->singleLineTextField('secretValue', ['enableContentEncryption' => true])
        ->create();

    $submission = formie()
        ->submission($form)
        ->with(['secretValue' => $plainText])
        ->save();

    $content = (new Query())
        ->select(['content'])
        ->from([Table::FORMIE_SUBMISSIONS])
        ->where(['id' => $submission->id])
        ->scalar();

    expect(is_string($content))->toBeTrue()
        ->and(str_contains((string)$content, $plainText))->toBeFalse();

    $loaded = \verbb\formie\elements\Submission::find()->id($submission->id)->status(null)->one();
    expect($loaded->getFieldValue('secretValue'))->toBe($plainText);
});

it('stores encryption-marker-shaped plaintext without interpreting it as ciphertext', function (): void {
    $validCiphertext = \verbb\formie\helpers\StringHelper::encenc('private');
    $malformedCiphertext = 'base64:' . base64_encode('crypt:broken');

    $form = formie()
        ->form(['title' => 'Plaintext Marker Contract'])
        ->singleLineTextField('validMarker')
        ->singleLineTextField('malformedMarker')
        ->create();

    $submission = formie()->submission($form)->with([
        'validMarker' => $validCiphertext,
        'malformedMarker' => $malformedCiphertext,
    ])->save();

    $content = (new Query())
        ->select(['content'])
        ->from([Table::FORMIE_SUBMISSIONS])
        ->where(['id' => $submission->id])
        ->scalar();

    expect($content)->toContain('formie:plain:v1:8d986bd501b14ae6a8de160e74b602db');

    $loaded = \verbb\formie\elements\Submission::find()->id($submission->id)->status(null)->one();
    expect($loaded->getFieldValue('validMarker'))->toBe($validCiphertext)
        ->and($loaded->getFieldValue('malformedMarker'))->toBe($malformedCiphertext);
});

it('keeps pre-populate query-string feature callable in current runtime context', function (): void {
    $form = formie()
        ->form(['title' => 'PrePopulate Contract'])
        ->singleLineTextField('fullName', [
            'defaultValue' => 'Default Name',
            'prePopulate' => 'fullName',
        ])
        ->create();

    $field = $form->getFieldByHandle('fullName');

    WebRequestTestHelper::withWebRequestContext(function () use ($field, $form): void {
        expect($field?->getDefaultValue())->toBe('Default Name')
            ->and($field?->getInitialValue($form))->toBe('Peter Sherman');
    }, [
        'queryParams' => [
            'fullName' => 'Peter Sherman',
        ],
    ]);
});

it('keeps template populate values separate from field defaults', function (): void {
    $form = formie()
        ->form(['title' => 'Populate Contract'])
        ->singleLineTextField('fullName', [
            'defaultValue' => 'Default Name',
        ])
        ->create();

    Formie::$plugin->getRendering()->populateFormValues($form, [
        'fullName' => 'Template Prefill',
    ]);

    $field = $form->getFieldByHandle('fullName');

    expect($field?->getDefaultValue())->toBe('Default Name')
        ->and($field?->getInitialValue($form))->toBe('Template Prefill')
        ->and($field?->getElementValue($form))->toBe('Template Prefill');
});

it('does not execute Twig in hidden field initial values', function (): void {
    $form = formie()
        ->form(['title' => 'Hidden Initial Value Contract'])
        ->hiddenField('trackingToken', [
            'defaultOption' => 'custom',
            'defaultValue' => '{{ 7 * 7 }}',
        ])
        ->create();

    $field = $form->getFieldByHandle('trackingToken');
    $inputOptions = $field?->getInputTemplateVariables($form, $field?->getElementValue($form));

    expect($inputOptions)->toBeArray()
        ->and($inputOptions['value'] ?? null)->toBe('{{ 7 * 7 }}');
});

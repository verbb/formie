<?php

declare(strict_types=1);

use Craft;
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

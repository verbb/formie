<?php

use craft\elements\User;
use verbb\formie\fields\definitions\FieldValueType;
use verbb\formie\fields\values\NameFieldValue;
use verbb\formie\helpers\References;
use verbb\formie\helpers\Variables;
use verbb\formie\references\ReferenceContext;
use verbb\formie\references\ReferenceDefinition;
use verbb\formie\references\ReferenceDiagnostic;

it('exposes kebab case in the text transform picker', function() {
    $config = Variables::getCategoryConfig();
    $transforms = $config['transformerRegistry'][Variables::TYPE_TEXT];
    $transform = array_values(array_filter($transforms, fn(array $item): bool => $item['id'] === 'kebab'));

    expect($transform)->toHaveCount(1)
        ->and($transform[0]['label'])->toBe('Kebab Case')
        ->and($transform[0]['params'])->toBe([]);

    $userSources = array_column($config['staticGroups'][Variables::GROUP_CURRENT_USER], null, 'value');
    expect($userSources['{user:fullName}']['transforms'])->toBeNull()
        ->and($userSources['{user:fullName}']['allowTransforms'])->toBeTrue();
});

it('preserves explicit transform allowlists for custom picker sources', function() {
    $definition = new ReferenceDefinition('acme/name', 'Name', 'custom', FieldValueType::string(), transforms: ['kebab']);
    expect($definition->toPickerSource('{custom:acme/name}')['transforms'])->toBe(['kebab']);

    $definition = new ReferenceDefinition('acme/name', 'Name', 'custom', FieldValueType::string());
    expect($definition->toPickerSource('{custom:acme/name}')['transforms'])->toBe([]);
});

it('resolves kebab case user references', function(string $name, string $expected) {
    $context = new ReferenceContext(user: new User(['fullName' => $name]), permissions: ['server']);
    $token = '{user:fullName;transform=kebab}';

    expect(References::resolveValue($token, $context)->requireValue())->toBe($expected)
        ->and(References::interpolateText($token, $context))->toBe($expected);
})->with([
    'full name' => ['Ada Lovelace', 'ada-lovelace'],
    'whitespace' => ["  Ada \t Lovelace  ", 'ada-lovelace'],
    'camel case' => ['adaLovelace', 'ada-lovelace'],
    'punctuation' => ["Mary_Jane O'Connor-Smith", 'mary-jane-o-connor-smith'],
    'unicode' => ['Élodie Müller', 'élodie-müller'],
    'empty' => ['', ''],
]);

it('preserves a fallback for a missing kebab case user reference', function() {
    expect(References::resolveValue('{user:fullName;transform=kebab|anonymous}', new ReferenceContext())->requireValue())
        ->toBe('anonymous');
});

it('projects composite name fields before applying kebab case', function() {
    $form = formie()->form()->nameField('person', ['useMultipleFields' => true])->create();
    $submission = formie()->submission($form)->save();
    $submission->setFieldValue('person', new NameFieldValue(['firstName' => 'Ada', 'lastName' => 'Lovelace']));
    $token = References::field($form->getFieldByHandle('person')->reference, metadata: ['transform' => 'kebab']);
    $context = ReferenceContext::forSubmission($submission);

    expect(References::resolveValue($token, $context)->requireValue())->toBe('ada-lovelace')
        ->and(References::interpolateText($token, $context))->toBe('ada-lovelace');
});

it('rejects arrays and unsupported parameters for kebab case', function() {
    $context = new ReferenceContext(metadata: ['custom' => ['names' => ['Ada', 'Lovelace']]]);

    expect(References::resolveValue('{metadata:custom.names;transform=kebab}', $context)->diagnostic)->toBe(ReferenceDiagnostic::InvalidType)
        ->and(References::resolveValue('{user:fullName;transform=kebab;glue=_}', $context)->diagnostic)->toBe(ReferenceDiagnostic::InvalidExpression);
});

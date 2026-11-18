<?php

declare(strict_types=1);

use verbb\formie\Formie;
use verbb\formie\client\models\LoadContext;
use verbb\formie\fields\Address;
use verbb\formie\fields\Name;
use verbb\formie\positions\Hidden as HiddenPosition;
use Tests\Support\WebRequestTestHelper;

function clientPresentationFields(array $fields): array
{
    $output = [];

    foreach ($fields as $field) {
        $output[$field['handle']] = $field;

        foreach ($field['input']['parts'] ?? [] as $part) {
            $output[$field['handle'] . '.' . $part['handle']] = $part;
        }
    }

    return $output;
}

it('sends the label position, autocomplete hints and button text that client-rendered forms need', function(): void {
    $form = formie()
        ->form(['title' => 'Client presentation'])
        ->singleLineTextField('nickname', ['labelPosition' => HiddenPosition::class])
        ->singleLineTextField('company', ['autocomplete' => 'organization'])
        ->singleLineTextField('coupon', ['autocomplete' => 'organization', 'inputAttributes' => [['label' => 'autocomplete', 'value' => 'off']]])
        ->emailField('emailAddress')
        ->nameField('fullName', [
            'useMultipleFields' => true,
            'rows' => (new Name(['useMultipleFields' => true]))->getSubFields(),
        ])
        ->addressField('homeAddress', ['rows' => (new Address())->getSubFields()])
        ->create();

    WebRequestTestHelper::withWebRequestContext(function() use ($form): void {
        $definition = Formie::$plugin->getClientFormBootstrapBuilder()->build($form, new LoadContext([
            'handle' => $form->handle,
        ]))->toArrayRecursive()['definition'];
        $fields = clientPresentationFields(array_merge(...array_column($definition['pages'][0]['rows'], 'fields')));

        expect($fields['nickname']['input']['labelPosition'])->toBe('hidden')
            ->and($fields['company']['input']['labelPosition'])->toBe('above')
            ->and($fields['company']['input']['autocomplete'])->toBe('organization')
            ->and($fields['nickname']['input'])->not->toHaveKey('autocomplete')
            ->and($fields['emailAddress']['input']['autocomplete'])->toBe('email')
            ->and($fields['fullName.firstName']['input']['autocomplete'])->toBe('given-name')
            ->and($fields['fullName.lastName']['input']['autocomplete'])->toBe('family-name')
            ->and($fields['coupon']['input']['autocomplete'])->toBe('off')
            ->and($fields['homeAddress.country']['input']['autocomplete'])->toBe('country')
            ->and($fields['homeAddress.zip']['input']['autocomplete'])->toBe('postal-code')
            ->and($fields['homeAddress']['input'])->not->toHaveKey('autocomplete')
            ->and($definition['settings']['strings'])->toBe([
                'addRow' => 'Add another row',
                'remove' => 'Remove',
                'removeRow' => 'Remove row',
                'clear' => 'Clear',
                'clearSignature' => 'Clear signature',
            ]);
    });
});

it('translates the button text for the form’s language', function(): void {
    $form = formie()->form(['title' => 'Client presentation German'])->singleLineTextField('nickname')->create();

    WebRequestTestHelper::withWebRequestContext(function() use ($form): void {
        $language = Craft::$app->language;
        Craft::$app->language = 'de';

        try {
            $strings = Formie::$plugin->getClientFormBootstrapBuilder()->build($form, new LoadContext([
                'handle' => $form->handle,
            ]))->toArrayRecursive()['definition']['settings']['strings'];
        } finally {
            Craft::$app->language = $language;
        }

        expect($strings['remove'])->toBe('Entfernen')
            ->and($strings['addRow'])->toBe('Eine weitere Zeile hinzufügen')
            ->and($strings['clear'])->toBe('Löschen')
            ->and($strings['clearSignature'])->toBe('Unterschrift löschen');
    });
});

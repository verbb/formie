<?php

declare(strict_types=1);

use verbb\formie\Formie;
use verbb\formie\client\models\LoadContext;
use verbb\formie\fields\Address;
use verbb\formie\fields\Date;
use verbb\formie\fields\Name;
use verbb\formie\fields\SingleLineText;
use Tests\Support\WebRequestTestHelper;

it('builds a canonical client bootstrap for simple and advanced config fields', function(): void {
    $form = formie()
        ->form(['title' => 'Frontend Envelope'])
        ->singleLineTextField('fullName', ['required' => true, 'placeholder' => 'Your name'])
        ->emailField('emailAddress', ['required' => true])
        ->dropdownField('topic', [
            'options' => [
                ['label' => 'General', 'value' => 'general', 'default' => true],
                ['label' => 'Support', 'value' => 'support'],
            ],
        ])
        ->nameField('contactName', [
            'useMultipleFields' => true,
            'rows' => (new Name(['useMultipleFields' => true]))->getSubFields(),
        ])
        ->addressField('shippingAddress', [
            'rows' => (new Address())->getSubFields(),
        ])
        ->dateField('appointmentDate', [
            'displayType' => 'inputs',
            'rows' => (new Date(['displayType' => 'inputs']))->getSubFields(),
        ])
        ->repeaterField('lineItems', ['rows' => [[
            'fields' => [[
                'type' => SingleLineText::class,
                'handle' => 'itemName',
                'label' => 'Item Name',
            ]],
        ]]])
        ->fileUploadField('attachments', ['restrictFiles' => false])
        ->signatureField('signature')
        ->agreeField('terms')
        ->create();

    WebRequestTestHelper::withWebRequestContext(function() use ($form) {

        $bootstrap = Formie::$plugin->getClientFormBootstrapBuilder()->build($form, new LoadContext([
            'handle' => $form->handle,
        ]))->toArrayRecursive();

        expect($bootstrap)->toHaveKeys(['schemaVersion', 'definition', 'session'])
            ->and($bootstrap)->not->toHaveKeys(['version', 'mode', 'transport'])
            ->and($bootstrap['definition']['handle'])->toBe($form->handle)
            ->and($bootstrap['definition']['pages'])->toHaveCount(1)
            ->and($bootstrap['definition']['pages'][0]['rows'])->not->toBeEmpty()
            ->and($bootstrap['definition']['pages'][0])->not->toHaveKey('instructions')
            ->and($bootstrap['definition']['settings']['validation'])->not->toHaveKey('liveStrategy')
            ->and($bootstrap['session']['currentPageId'])->toBe((string)$form->getPages()[0]->id);

        $fields = [];
        $fieldTypesByHandle = [];

        foreach ($bootstrap['definition']['pages'] as $page) {
            foreach ($page['rows'] as $row) {
                foreach ($row['fields'] as $field) {
                    $fields[] = $field;
                }
            }
        }

        foreach ($fields as $field) {
            $fieldTypesByHandle[$field['handle']] = $field['type'];
        }

        expect($fieldTypesByHandle)->toMatchArray([
            'fullName' => 'single-line-text',
            'emailAddress' => 'email',
            'topic' => 'dropdown',
            'contactName' => 'name',
            'shippingAddress' => 'address',
            'appointmentDate' => 'date',
            'lineItems' => 'repeater',
            'attachments' => 'file-upload',
            'signature' => 'signature',
            'terms' => 'agree',
        ]);

        $contactName = $fields[array_search('contactName', array_column($fields, 'handle'), true)];
        $shippingAddress = $fields[array_search('shippingAddress', array_column($fields, 'handle'), true)];
        $appointmentDate = $fields[array_search('appointmentDate', array_column($fields, 'handle'), true)];
        $lineItems = $fields[array_search('lineItems', array_column($fields, 'handle'), true)];
        $attachments = $fields[array_search('attachments', array_column($fields, 'handle'), true)];
        $signature = $fields[array_search('signature', array_column($fields, 'handle'), true)];

        expect($fields[0]['meta'] ?? [])->not->toHaveKey('settings')
            ->and($contactName['input']['parts'] ?? [])->not->toBeEmpty()
            ->and($contactName['client'] ?? [])->toMatchArray([
                'children' => [
                    'model' => 'fixed-parent',
                    'mode' => 'parts',
                ],
            ])
            ->and($contactName['client']['valueClass']['class'] ?? null)->toBe('verbb\\formie\\fields\\values\\NameFieldValue')
            ->and($shippingAddress['input']['parts'] ?? [])->not->toBeEmpty()
            ->and($shippingAddress['client']['valueClass']['class'] ?? null)->toBe('verbb\\formie\\fields\\values\\AddressFieldValue')
            ->and($appointmentDate['input']['parts'] ?? [])->not->toBeEmpty()
            ->and($appointmentDate['client']['valueClass']['class'] ?? null)->toBe('verbb\\formie\\fields\\values\\DateFieldValue')
            ->and($lineItems['input']['rowSchema']['rows'][0]['fields'][0]['handle'] ?? null)->toBe('itemName')
            ->and($lineItems['client'] ?? [])->toMatchArray([
                'children' => [
                    'model' => 'repeatable-parent',
                    'mode' => 'rows',
                ],
            ])
            ->and($lineItems['input']['fieldKind'] ?? null)->toBe('repeater')
            ->and($attachments['input']['fieldKind'] ?? null)->toBe('file')
            ->and($attachments['client'] ?? [])->toMatchArray([
                'children' => [
                    'model' => 'scalar',
                ],
            ])
            ->and($signature['moduleRefs'] ?? [])->toContain('signature');
    });
});

it('exports pre-populated initial values instead of raw field defaults in the client payload', function(): void {
    $form = formie()
        ->form([
            'title' => 'Frontend Initial Value Contract ' . uniqid(),
        ])
        ->singleLineTextField('fullName', [
            'defaultValue' => 'Default Name',
            'prePopulate' => 'fullName',
        ])
        ->create();

    WebRequestTestHelper::withWebRequestContext(function($request) use ($form) {
        $request->setQueryParams([
            'fullName' => 'Peter Sherman',
        ]);

        $bootstrap = Formie::$plugin->getClientFormBootstrapBuilder()->build($form, new LoadContext([
            'handle' => $form->handle,
        ]))->toArrayRecursive();

        $fields = [];

        foreach ($bootstrap['definition']['pages'] as $page) {
            foreach ($page['rows'] as $row) {
                foreach ($row['fields'] as $field) {
                    $fields[$field['handle']] = $field;
                }
            }
        }

        expect($fields['fullName']['input']['defaultValue'] ?? null)->toBe('Peter Sherman');
    });
});

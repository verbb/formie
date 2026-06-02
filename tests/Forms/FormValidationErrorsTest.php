<?php

declare(strict_types=1);

use verbb\formie\elements\Form;
use verbb\formie\fields\Dropdown;
use verbb\formie\fields\SingleLineText;
use verbb\formie\models\FieldLayout;
use verbb\formie\models\Notification;

it('surfaces validation errors for invalid handles', function (): void {
    $form = new Form([
        'title' => 'Invalid Handle',
        'handle' => 'invalid-handle-space',
    ]);

    $saved = Craft::$app->elements->saveElement($form);

    expect($saved)->toBeFalse()
        ->and($form->getErrors())->not->toBeEmpty();
});

it('surfaces validation errors for handles longer than allowed maximum', function (): void {
    $tooLongHandle = str_repeat('a', 300);

    $form = new Form([
        'title' => 'Handle Length Limit',
        'handle' => $tooLongHandle,
    ]);

    $saved = Craft::$app->elements->saveElement($form);
    $handleErrors = $form->getErrors('handle');

    expect($saved)->toBeFalse()
        ->and($handleErrors)->not->toBeEmpty()
        ->and((string)($handleErrors[0] ?? ''))->toContain('at most');
});

it('surfaces validation errors for missing required form attributes', function (): void {
    $form = new Form([
        'title' => '',
        'handle' => 'missingTitle' . uniqid(),
    ]);

    $saved = Craft::$app->elements->saveElement($form);

    expect($saved)->toBeFalse()
        ->and($form->getErrors())->not->toBeEmpty();
});

it('scopes nested options validation errors to the correct field path', function (): void {
    $form = new Form([
        'title' => 'Options Error Scope',
        'handle' => 'optionsErrorScope' . uniqid(),
    ]);

    $form->setFormLayout(new FieldLayout([
        'pages' => [[
            'label' => 'Page 1',
            'settings' => [],
            'rows' => [[
                'fields' => [
                    [
                        'type' => SingleLineText::class,
                        'handle' => 'fullName',
                        'label' => 'Full Name',
                    ],
                    [
                        'type' => Dropdown::class,
                        'handle' => 'dropdown',
                        'label' => 'Dropdown',
                        'options' => [
                            ['label' => 'Option 3', 'value' => 'Option 3'],
                            ['label' => 'Option 3', 'value' => 'Option 3'],
                        ],
                    ],
                ],
            ]],
        ]],
    ]));

    $saved = Craft::$app->elements->saveElement($form);
    $errors = $form->getErrors();

    expect($saved)->toBeFalse()
        ->and($errors)->toHaveKey('pages.0.rows.0.fields.1.options')
        ->and($errors['pages.0.rows.0.fields.1.options'])->toContain('All option labels must be unique.')
        ->and($errors['pages.0.rows.0.fields.1.options'])->toContain('All option values must be unique.')
        ->and($errors)->not->toHaveKey('pages.0.rows.0.fields.0.options');
});

it('scopes notification validation errors to notification indexes', function (): void {
    $form = new Form([
        'title' => 'Notification Error Scope',
        'handle' => 'notificationErrorScope' . uniqid(),
    ]);

    $form->setNotifications([
        new Notification([
            // Intentionally invalid notification payload.
            'name' => '',
            'subject' => '',
            'handle' => '',
            'recipients' => Notification::RECIPIENTS_EMAIL,
            'to' => '',
        ]),
    ]);

    $saved = Craft::$app->elements->saveElement($form);
    $errors = $form->getErrors();

    expect($saved)->toBeFalse()
        ->and($errors)->toHaveKey('notifications.0.name')
        ->and($errors)->toHaveKey('notifications.0.subject')
        ->and($errors)->toHaveKey('notifications.0.handle')
        ->and($errors)->toHaveKey('notifications.0.to');
});

<?php

declare(strict_types=1);

use verbb\formie\elements\Form;
use verbb\formie\fields\Email;
use verbb\formie\helpers\References;
use verbb\formie\models\Stencil;
use verbb\formie\models\StencilData;
use verbb\formie\services\Stencils as StencilsService;

function stencilFieldReferencesHandle(string $prefix): string
{
    static $counter = 0;

    return $prefix . (++$counter) . uniqid();
}

it('remaps legacy handle-based field tokens when materializing stencils', function (): void {
    $stencil = new Stencil([
        'name' => 'Legacy Handle Token Stencil',
        'handle' => stencilFieldReferencesHandle('legacyStencil'),
        'scope' => StencilsService::SCOPE_SITE,
    ]);
    $stencil->data = new StencilData([
        'notifications' => [
            [
                'name' => 'Admin Notification',
                'handle' => 'adminNotification',
                'enabled' => true,
                'subject' => 'New submission',
                'to' => '{systemEmail}',
                'replyTo' => '{field:emailAddress}',
            ],
            [
                'name' => 'User Notification',
                'handle' => 'userNotification',
                'enabled' => true,
                'subject' => 'Thanks',
                'to' => '{field:emailAddress}',
            ],
        ],
        'pages' => [
            [
                'label' => 'Page 1',
                'settings' => [],
                'rows' => [
                    [
                        'fields' => [
                            [
                                'type' => Email::class,
                                'settings' => [
                                    'label' => 'Email Address',
                                    'handle' => 'emailAddress',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ]);

    $newForm = new Form([
        'title' => 'Legacy Handle Token Form',
        'handle' => stencilFieldReferencesHandle('legacyForm'),
    ]);
    $stencil->applyStencilToForm($newForm, true);

    $emailField = $newForm->getFieldByHandle('emailAddress');

    expect($emailField)->not->toBeNull()
        ->and($emailField->reference)->not->toBeEmpty();

    $emailReferenceToken = References::field((string)$emailField->reference);
    $notifications = $newForm->getNotifications();

    expect($notifications)->toHaveCount(2)
        ->and($notifications[0]->replyTo)->toBe($emailReferenceToken)
        ->and($notifications[1]->to)->toBe($emailReferenceToken);
});

it('remaps canonical stencil field references when materializing stencils', function (): void {
    $stencilReference = 'b2c3d4e5-f6a7-8901-b234-567890abcdef';

    $stencil = new Stencil([
        'name' => 'Canonical Reference Stencil',
        'handle' => stencilFieldReferencesHandle('canonicalStencil'),
        'scope' => StencilsService::SCOPE_SITE,
    ]);
    $stencil->data = new StencilData([
        'notifications' => [
            [
                'name' => 'User Notification',
                'handle' => 'userNotification',
                'enabled' => true,
                'subject' => 'Thanks',
                'to' => '{field:' . $stencilReference . '}',
            ],
        ],
        'pages' => [
            [
                'label' => 'Page 1',
                'settings' => [],
                'rows' => [
                    [
                        'fields' => [
                            [
                                'type' => Email::class,
                                'reference' => $stencilReference,
                                'settings' => [
                                    'label' => 'Email Address',
                                    'handle' => 'emailAddress',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ]);

    $newForm = new Form([
        'title' => 'Canonical Reference Form',
        'handle' => stencilFieldReferencesHandle('canonicalForm'),
    ]);
    $stencil->applyStencilToForm($newForm, true);

    $emailField = $newForm->getFieldByHandle('emailAddress');

    expect($emailField)->not->toBeNull()
        ->and($emailField->reference)->not->toBe($stencilReference);

    $notification = $newForm->getNotifications()[0] ?? null;

    expect($notification?->to)->toBe(References::field((string)$emailField->reference));
});

it('materializes the default contact form stencil with working email notification references', function (): void {
    $stencilData = json_decode(
        file_get_contents(Craft::getAlias('@verbb/formie/migrations/stencils/contact-form.json')),
        true,
    );

    $stencil = new Stencil([
        'name' => 'Contact Form',
        'handle' => stencilFieldReferencesHandle('contactStencil'),
        'scope' => StencilsService::SCOPE_SITE,
    ]);
    $stencil->data = new StencilData($stencilData);

    $newForm = new Form([
        'title' => 'Contact Form',
        'handle' => stencilFieldReferencesHandle('contactForm'),
    ]);
    $stencil->applyStencilToForm($newForm, true);

    $emailField = $newForm->getFieldByHandle('emailAddress');
    $emailReferenceToken = References::field((string)$emailField->reference);
    $notifications = $newForm->getNotifications();

    expect($emailField)->not->toBeNull()
        ->and($notifications)->toHaveCount(2)
        ->and($notifications[0]->replyTo)->toBe($emailReferenceToken)
        ->and($notifications[1]->to)->toBe($emailReferenceToken);
});

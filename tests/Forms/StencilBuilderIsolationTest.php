<?php

declare(strict_types=1);

use verbb\formie\elements\Form;
use verbb\formie\Formie;
use verbb\formie\helpers\SchemaHelper;
use verbb\formie\models\FieldLayout;
use verbb\formie\models\Notification;

use craft\elements\Entry;
use craft\helpers\DateTimeHelper;

it('keeps a stencil name isolated from an entry with the same id', function (): void {
    $collidingEntry = Entry::find()->status(null)->one();

    expect($collidingEntry)->not->toBeNull();

    $now = DateTimeHelper::currentUTCDateTime();
    $stencilForm = new Form([
        'title' => 'Stencil Name',
        'handle' => 'stencil' . bin2hex(random_bytes(6)),
        'builderEntityType' => Form::BUILDER_ENTITY_TYPE_STENCIL,
    ]);
    $stencilForm->id = $collidingEntry->id;
    $stencilForm->uid = 'stencil-' . bin2hex(random_bytes(6));
    $stencilForm->dateCreated = $now;
    $stencilForm->dateUpdated = $now;
    $stencilForm->setFormLayout(new FieldLayout([
        'pages' => [
            [
                'label' => 'Stencil Page',
                'rows' => [],
            ],
        ],
    ]));

    $variables = Formie::$plugin->getForms()->getFormBuilderVariables($stencilForm);

    expect($collidingEntry->title)->not->toBe('Stencil Name')
        ->and($variables['data']['title'])->toBe('Stencil Name')
        ->and($variables['canonicalData']['title'])->toBe('Stencil Name')
        ->and($variables['multiSite'])->toBe(['enabled' => false]);
});

it('keeps stencil builder data isolated from a form with the same id', function (): void {
    $collidingForm = formie()
        ->form(['title' => 'Unrelated Form'])
        ->singleLineTextField('unrelatedField')
        ->create();

    $stencilForm = clone $collidingForm;
    $stencilForm->title = 'Stencil Name';
    $stencilForm->handle = 'stencil' . bin2hex(random_bytes(6));
    $stencilForm->builderEntityType = Form::BUILDER_ENTITY_TYPE_STENCIL;
    $stencilForm->setNotifications([
        new Notification([
            'name' => 'Stencil Notification',
            'handle' => 'stencilNotification',
        ]),
    ]);
    $stencilForm->setFormLayout(new FieldLayout([
        'pages' => [
            [
                'label' => 'Stencil Page',
                'rows' => [],
            ],
        ],
    ]));

    $variables = Formie::$plugin->getForms()->getFormBuilderVariables($stencilForm);

    expect($variables['data']['title'])->toBe('Stencil Name')
        ->and($variables['data']['handle'])->toBe($stencilForm->handle)
        ->and($variables['data']['isStencil'])->toBeTrue()
        ->and($variables['data']['pages'][0]['label'])->toBe('Stencil Page')
        ->and($variables['canonicalData']['title'])->toBe('Stencil Name')
        ->and($variables['canonicalData']['handle'])->toBe($stencilForm->handle)
        ->and($variables['canonicalData']['isStencil'])->toBeTrue()
        ->and($variables['canonicalData']['notifications'][0]['name'])->toBe('Stencil Notification')
        ->and($variables['hasSubmissions'])->toBeFalse()
        ->and($variables['multiSite'])->toBe(['enabled' => false]);
});

it('does not mark a stencil name as translatable', function (): void {
    $stencilForm = new Form([
        'title' => 'Stencil Name',
        'handle' => 'stencil' . bin2hex(random_bytes(6)),
        'builderEntityType' => Form::BUILDER_ENTITY_TYPE_STENCIL,
    ]);

    $compiledSchema = SchemaHelper::compileSchema($stencilForm->defineFormBuilderSettingsSchema());
    $titleField = null;

    foreach ($compiledSchema['fieldEntries'] as $entry) {
        if (($entry['path'] ?? null) === 'title') {
            $titleField = $entry['field'] ?? null;
            break;
        }
    }

    expect($titleField)->toBeArray()
        ->and($titleField['translatable'] ?? false)->toBeFalse();
});

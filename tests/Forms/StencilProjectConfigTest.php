<?php

declare(strict_types=1);

use verbb\formie\Formie;
use verbb\formie\models\Stencil;
use verbb\formie\models\StencilData;
use verbb\formie\services\Stencils;

use craft\helpers\ProjectConfig as ProjectConfigHelper;

it('applies stencil config packed by upgrade migrations', function (): void {
    $form = formie()->form()->singleLineTextField('message')->create();
    $stencil = new Stencil([
        'name' => 'Packed stencil',
        'handle' => 'packedStencil' . uniqid(),
        'scope' => Stencils::SCOPE_PROJECT,
    ]);
    $stencil->data = new StencilData();
    $stencil->data->populateFormData($form);
    expect(Formie::$plugin->getStencils()->saveStencil($stencil))->toBeTrue();

    $path = Stencils::CONFIG_STENCILS_KEY . '.' . $stencil->uid;
    $config = Craft::$app->getProjectConfig()->get($path, true);
    $config['name'] = 'Packed stencil renamed';
    Craft::$app->getProjectConfig()->set($path, ProjectConfigHelper::packAssociativeArray($config));

    $saved = Formie::$plugin->getStencils()->getStencilByUid($stencil->uid);
    expect($saved?->name)->toBe('Packed stencil renamed')
        ->and($saved?->handle)->toBe($stencil->handle)
        ->and($saved?->data->getSerializedData()['pages'] ?? null)->toHaveCount(count($config['data']['pages']));
});

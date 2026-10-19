<?php

declare(strict_types=1);

use craft\elements\User;
use Tests\Support\WebRequestTestHelper;
use verbb\formie\Formie;
use verbb\formie\elements\Form;
use verbb\formie\models\FormSettings;
use verbb\formie\models\Stencil;
use verbb\formie\models\StencilData;
use verbb\formie\services\Stencils;

it('discards legacy honeypot bindings without changing other integrations', function (string $source, bool $enabled): void {
    $bindings = [
        'honeypot' => ['enabled' => $enabled, 'showAllPages' => true],
        'recaptcha' => ['enabled' => false],
        'unavailableProvider' => ['enabled' => true, 'customSetting' => 'Keep'],
    ];

    if ($source === 'stored') {
        $settings = new FormSettings(['integrations' => $bindings]);
    } else {
        $settings = new FormSettings();
        $settings->setAttributes(['integrations' => $bindings], false);
    }

    unset($bindings['honeypot']);
    expect($settings->integrations)->toBe($bindings);
})->with(['stored', 'posted'])->with([true, false]);

it('creates forms from legacy honeypot settings and stencils', function (string $source): void {
    $settings = Formie::$plugin->getSettings();
    $settings->defaultFormStencil = '';
    $settings->enableHoneypot = true;
    $settings->honeypotFieldName = 'currentBotTrap';
    $legacySettings = ['integrations' => ['honeypot' => ['enabled' => true]]];
    $suffix = bin2hex(random_bytes(6));
    $stencil = null;

    if ($source !== 'blank') {
        $stencil = new Stencil([
            'name' => 'Legacy Honeypot',
            'handle' => 'legacyHoneypot' . $suffix,
            'scope' => Stencils::SCOPE_SITE,
            'data' => new StencilData(['settings' => $legacySettings]),
        ]);
        expect(Formie::$plugin->getStencils()->saveStencil($stencil))->toBeTrue();

        if ($source === 'default stencil') {
            $settings->defaultFormStencil = $stencil->handle;
        }
    }

    WebRequestTestHelper::withWebRequestContext(function ($request) use ($source, $stencil, $legacySettings, $suffix): void {
        $actor = User::find()->admin()->one();
        expect($actor)->not->toBeNull();
        Craft::$app->getUser()->setIdentity($actor);
        $request->setIsCpRequest(true);
        $post = ['title' => 'Legacy Honeypot', 'handle' => 'legacyHoneypotForm' . $suffix];

        if ($source === 'blank') {
            $post['settings'] = $legacySettings;
        } elseif ($source === 'selected stencil') {
            $post['applyStencilId'] = $stencil->id;
        }

        $request->setBodyParams($post);
        $form = Formie::$plugin->getForms()->buildFormFromPost();
        expect(Craft::$app->getElements()->saveElement($form))->toBeTrue();
        $stored = Form::find()->id($form->id)->one();
        expect($stored)->not->toBeNull()
            ->and($stored->settings->integrations)->not->toHaveKey('honeypot')
            ->and(Formie::$plugin->getSettings()->enableHoneypot)->toBeTrue()
            ->and(Formie::$plugin->getSettings()->honeypotFieldName)->toBe('currentBotTrap');
    }, ['method' => 'POST']);
})->with(['blank', 'selected stencil', 'default stencil']);

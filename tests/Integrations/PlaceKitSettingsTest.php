<?php

use verbb\formie\integrations\addressproviders\PlaceKit;
use verbb\formie\models\BrowserModule;
use verbb\formie\models\BrowserModuleContext;

it('reports missing PlaceKit settings without throwing', function(array $config) {
    $provider = new PlaceKit($config);

    expect($provider->hasValidSettings())->toBeFalse();
})->with([
    'unset key' => [[]],
    'null key' => [['apiKey' => null]],
    'empty key' => [['apiKey' => '']],
]);

it('omits the PlaceKit browser module when no API key is configured', function(array $config) {
    $provider = new PlaceKit($config);

    expect($provider->getBrowserModule(new BrowserModuleContext()))->toBeNull();
})->with([
    'unset key' => [[]],
    'null key' => [['apiKey' => null]],
    'empty key' => [['apiKey' => '']],
]);

it('preserves configured PlaceKit browser settings', function() {
    $provider = new PlaceKit([
        'apiKey' => 'local-test-key',
        'options' => [['language', '"en"']],
    ]);

    expect($provider->hasValidSettings())->toBeTrue();

    $module = $provider->getBrowserModule(new BrowserModuleContext());

    expect($module)->toBeInstanceOf(BrowserModule::class)
        ->and($module->moduleId)->toBe('formie:place-kit')
        ->and($module->config)->toBe([
            'apiKey' => 'local-test-key',
            'options' => ['language' => 'en'],
        ])
        ->and($module->surfaces)->toBe([
            BrowserModule::SURFACE_SERVER_RENDERED,
            BrowserModule::SURFACE_CLIENT_RENDERED,
            BrowserModule::SURFACE_CP_EDIT,
        ]);
});

it('omits the PlaceKit browser module after clearing a configured key', function() {
    $provider = new PlaceKit(['apiKey' => 'local-test-key']);

    foreach (['', null] as $key) {
        $provider->apiKey = $key;

        expect($provider->hasValidSettings())->toBeFalse()
            ->and($provider->getBrowserModule(new BrowserModuleContext()))->toBeNull();
    }
});

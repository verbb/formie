<?php

declare(strict_types=1);

use verbb\formie\Formie;
use verbb\formie\fields\Address;
use verbb\formie\helpers\Table;
use verbb\formie\integrations\addressproviders\Google;

// The client definition says whether to show "Use my location", as server-rendered forms do on the auto-complete label.
it('sends the location control label only when the provider and sub-field offer it', function(bool $currentLocation): void {
    $provider = new Google(['name' => 'Client location', 'handle' => 'clientLocation' . bin2hex(random_bytes(4)), 'enabled' => true, 'apiKey' => 'key']);
    expect(Formie::$plugin->getIntegrations()->saveIntegration($provider, false))->toBeTrue();

    $rows = (new Address())->getSubFields();

    foreach ($rows as &$row) {
        foreach ($row['fields'] as &$subField) {
            if (($subField['handle'] ?? null) === 'autoComplete') {
                $subField['enabled'] = true;
                $subField['integrationHandle'] = $provider->handle;
                $subField['currentLocation'] = $currentLocation;
            }
        }
    }

    unset($row, $subField);

    $form = formie()->form()->addressField('home', ['label' => 'Home', 'rows' => $rows])->create();

    try {
        $input = $form->getFieldByHandle('home')->getFieldByHandle('autoComplete')->getClientRenderedInput();

        expect($input['currentLocationLabel'])->toBe($currentLocation ? 'Use my location' : null);
    } finally {
        Craft::$app->getElements()->deleteElement($form, true);
        // A provider left behind adds an auto-complete row to every later Address field, so remove it and its cache.
        Craft::$app->getDb()->createCommand()->delete(Table::FORMIE_INTEGRATIONS, ['id' => $provider->id])->execute();
        (new ReflectionMethod(Formie::$plugin->getIntegrations(), '_resetIntegrationCaches'))->invoke(Formie::$plugin->getIntegrations());
    }
})->with([true, false]);

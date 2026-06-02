<?php

declare(strict_types=1);

it('boots Craft and keeps Formie ready in integration runtime', function (): void {
    $plugins = Craft::$app->plugins;
    $formie = $plugins->getPlugin('formie');

    expect(Craft::$app)->not->toBeNull()
        ->and($formie)->not->toBeNull()
        ->and($plugins->isPluginEnabled('formie'))->toBeTrue();
});

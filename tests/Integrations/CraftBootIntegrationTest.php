<?php

declare(strict_types=1);

it('boots Craft and keeps Formie ready in integration runtime', function (): void {
    $plugins = Craft::$app->plugins;
    $formie = $plugins->getPlugin('formie');

    expect(Craft::$app)->toBeInstanceOf(\craft\console\Application::class)
        ->and($formie)->toBeInstanceOf(\verbb\formie\Formie::class)
        ->and($plugins->isPluginEnabled('formie'))->toBeTrue();
});

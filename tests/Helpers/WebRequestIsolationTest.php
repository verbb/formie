<?php

declare(strict_types=1);

use Tests\Support\WebRequestTestHelper;
use craft\services\UserPermissions;
use verbb\formie\Formie;
use yii\base\Event;

it('does not register another copy of plugin permissions for simulated web requests', function (): void {
    $events = new ReflectionProperty(Event::class, '_events');
    $handlers = fn() => $events->getValue()[UserPermissions::EVENT_REGISTER_PERMISSIONS][UserPermissions::class] ?? [];
    $before = $handlers();
    $plugin = Formie::$plugin;
    $plugins = Craft::$app->getPlugins()->getAllPlugins();
    expect($before)->not->toBeEmpty();
    for ($i = 0; $i < 2; $i++) {
        WebRequestTestHelper::withWebRequestContext(function () use ($plugin, $plugins): void {
            expect(Craft::$app->getPlugins()->getPlugin('formie'))->toBe($plugin);
            foreach ($plugins as $loaded) {
                expect($loaded::getInstance())->toBe($loaded);
            }
        });
        expect($handlers())->toBe($before);
    }
});

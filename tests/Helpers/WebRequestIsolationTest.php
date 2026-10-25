<?php

declare(strict_types=1);

use Tests\Support\WebRequestTestHelper;
use craft\services\Gc;
use craft\services\UserPermissions;
use craft\web\Application;
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

it('registers plugin translations before garbage collection during web application initialization', function (): void {
    // Older forms can need translated defaults when GC reloads their settings.
    $form = formie()->form()->create();
    Craft::$app->getDb()->createCommand()->update('{{%formie_forms}}', [
        'settings' => '{}',
    ], ['id' => $form->id])->execute();
    $originalApp = Craft::$app;
    $originalI18n = $originalApp->getI18n();
    $folderUser = new \craft\elements\User(['id' => random_int(100000000, 999999999)]);
    $folder = $originalApp->getAssets()->getUserTemporaryUploadFolder($folderUser);
    expect((new \craft\db\Query())->from('{{%volumefolders}}')->where(['id' => $folder->id])->exists())->toBeTrue();
    $gcRuns = 0;
    $forceGc = static function (Event $event): void {
        $event->sender->getGc()->probability = 1_000_001;
    };
    $recordGc = static function () use (&$gcRuns): void {
        $gcRuns++;
    };

    Event::on(Application::class, Application::EVENT_INIT, $forceGc);
    Event::on(Gc::class, Gc::EVENT_RUN, $recordGc);

    try {
        WebRequestTestHelper::withWebRequestContext(function () use (&$gcRuns, $originalI18n): void {
            expect($gcRuns)->toBe(1)
                ->and(Craft::$app->getI18n())->not->toBe($originalI18n)
                ->and(Craft::t('formie', 'Submit'))->toBe('Submit');
        });
    } finally {
        Event::off(Application::class, Application::EVENT_INIT, $forceGc);
        Event::off(Gc::class, Gc::EVENT_RUN, $recordGc);
    }

    expect((new \craft\db\Query())->from('{{%volumefolders}}')->where(['id' => $folder->id])->exists())->toBeFalse();
    $replacement = $originalApp->getAssets()->getUserTemporaryUploadFolder($folderUser);
    expect((new \craft\db\Query())->from('{{%volumefolders}}')->where(['id' => $replacement->id])->exists())->toBeTrue()
        ->and($replacement->id)->not->toBe($folder->id);

    expect(Craft::$app)->toBe($originalApp)
        ->and(Craft::$app->getI18n())->toBe($originalI18n);
});

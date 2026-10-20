<?php

declare(strict_types=1);

use craft\services\ProjectConfig;
use Tests\Support\WebRequestTestHelper;

it('releases shared project config locks in their owning request context', function (bool $throws, bool $flushes): void {
    $config = Craft::$app->getProjectConfig();
    $mutex = Craft::$app->getMutex();
    $key = 'formieRequestLockTest' . bin2hex(random_bytes(5));

    try {
        $config->set($key, 'console');
        $request = function () use ($config, $key, $throws, $flushes): void {
            WebRequestTestHelper::withWebRequestContext(function () use ($config, $key, $throws, $flushes): void {
                Craft::$app->set('projectConfig', $config);
                $config->set($key, 'web');
                // Controller actions can finish config writes before the request ends.
                if ($flushes) {
                    $config->flush();
                }
                if ($throws) {
                    throw new RuntimeException('Synthetic request failure');
                }
            });
        };

        if ($throws) {
            expect($request)->toThrow(RuntimeException::class, 'Synthetic request failure');
        } else {
            $request();
        }

        // A following console write must acquire the lock again, even after an exception.
        $config->set($key, 'next console');
        $config->flush();
        expect($config->get($key))->toBe('next console')
            ->and($mutex->isAcquired(ProjectConfig::MUTEX_NAME))->toBeFalse();
    } finally {
        // Keep a failing regression from poisoning the rest of the suite.
        $mutex->release(ProjectConfig::MUTEX_NAME);
        $config->remove($key);
        $config->flush();
    }
})->with(['completed request' => false, 'failed request' => true])
    ->with(['controller flush' => true, 'request-end flush' => false]);

it('restores existing global listeners after a simulated request throws', function (string $eventName): void {
    $calls = [];
    $original = static function () use (&$calls): void {
        $calls[] = 'original';
    };
    $request = static function () use (&$calls): void {
        $calls[] = 'request';
    };
    \yii\base\Event::on(self::class, $eventName, $original);

    try {
        expect(function () use ($eventName, $request): void {
            WebRequestTestHelper::withWebRequestContext(function () use ($eventName, $request): void {
                \yii\base\Event::on(self::class, $eventName, $request);
                \yii\base\Event::trigger(self::class, 'requestCleanup');
                throw new RuntimeException('Synthetic request failure');
            });
        })->toThrow(RuntimeException::class, 'Synthetic request failure');

        \yii\base\Event::trigger(self::class, 'requestCleanup');
        expect($calls)->toBe(['original', 'request', 'original']);
    } finally {
        \yii\base\Event::off(self::class, $eventName);
    }
})->with(['exact event' => 'requestCleanup', 'wildcard event' => 'request*']);

it('does not retain completed applications through global request listeners', function (): void {
    $requests = [];
    for ($index = 0; $index < 3; $index++) {
        WebRequestTestHelper::withWebRequestContext(function () use (&$requests): void {
            $requests[] = WeakReference::create(Craft::$app);
        });
    }

    gc_collect_cycles();
    // The logger can retain the latest application; earlier requests must be freed.
    expect($requests[0]->get())->toBeNull()
        ->and($requests[1]->get())->toBeNull();
});

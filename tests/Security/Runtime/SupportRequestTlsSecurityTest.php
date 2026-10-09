<?php

declare(strict_types=1);

use verbb\formie\controllers\SupportController;

it('keeps TLS certificate verification enabled for support requests in development mode', function (): void {
    expect(Craft::$app->getConfig()->getGeneral()->devMode)->toBeTrue();

    $controller = new SupportController('formie-support-security', Craft::$app);
    $method = new ReflectionMethod(SupportController::class, '_createSupportClient');
    $client = $method->invoke($controller);

    expect($client->getConfig('verify'))->not->toBeFalse()
        ->and($client->getConfig('timeout'))->toBe(120)
        ->and($client->getConfig('connect_timeout'))->toBe(120);
})->group('security');

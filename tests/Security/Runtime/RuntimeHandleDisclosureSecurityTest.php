<?php

declare(strict_types=1);

use verbb\formie\gql\resolvers\ClientFormResolver;
use yii\web\BadRequestHttpException;

it('returns a generic error for unknown client submit handles', function (): void {
    $missingHandle = 'security-missing-submit-' . uniqid();

    try {
        \Tests\Support\WebRequestTestHelper::withWebRequestContext(fn() => ClientFormResolver::submitForm(null, [
            'input' => [
                'handle' => $missingHandle,
                'session' => [],
                'values' => [],
            ],
        ]));

        $this->fail('Expected a bad request exception for unknown handle.');
    } catch (BadRequestHttpException $exception) {
        expect($exception->getMessage())
            ->toBe('Form not found')
            ->not->toContain($missingHandle);
    }
})->group('security');

it('returns a generic error for unknown client refresh handles', function (): void {
    $missingHandle = 'security-missing-refresh-' . uniqid();

    try {
        \Tests\Support\WebRequestTestHelper::withWebRequestContext(fn() => ClientFormResolver::refreshSession(null, [
            'input' => [
                'handle' => $missingHandle,
                'session' => [],
            ],
        ]));

        $this->fail('Expected a bad request exception for unknown handle.');
    } catch (BadRequestHttpException $exception) {
        expect($exception->getMessage())
            ->toBe('Form not found')
            ->not->toContain($missingHandle);
    }
})->group('security');

it('returns a generic error for unknown client page-transition handles', function (): void {
    $missingHandle = 'security-missing-page-' . uniqid();

    try {
        \Tests\Support\WebRequestTestHelper::withWebRequestContext(fn() => ClientFormResolver::setPage(null, [
            'input' => [
                'handle' => $missingHandle,
                'session' => [],
                'values' => [],
            ],
        ]));

        $this->fail('Expected a bad request exception for unknown handle.');
    } catch (BadRequestHttpException $exception) {
        expect($exception->getMessage())
            ->toBe('Form not found')
            ->not->toContain($missingHandle);
    }
})->group('security');

<?php

declare(strict_types=1);

use craft\web\Request;
use verbb\formie\helpers\CrossOriginRequestHelper;

it('allows only configured cross-origin callers when a GraphQL allowlist is present', function (): void {
    $generalConfig = \verbb\formie\Formie::$plugin->getSettings();
    $originalAllowedOrigins = $generalConfig->allowedOrigins;
    $request = new Request();
    $request->setHostInfo('https://craft.example.com');
    $request->getHeaders()->set('Origin', 'https://allowed.example.com');

    try {
        $generalConfig->allowedOrigins = [
            'https://allowed.example.com',
            'https://fallback.example.com',
        ];

        expect(CrossOriginRequestHelper::resolveAllowedOrigin($request))
            ->toBe('https://allowed.example.com');
    } finally {
        $generalConfig->allowedOrigins = $originalAllowedOrigins;
    }
})->group('security');

it('rejects unknown cross-origin callers when a GraphQL allowlist is present', function (): void {
    $generalConfig = \verbb\formie\Formie::$plugin->getSettings();
    $originalAllowedOrigins = $generalConfig->allowedOrigins;
    $request = new Request();
    $request->setHostInfo('https://craft.example.com');
    $request->getHeaders()->set('Origin', 'https://blocked.example.com');

    try {
        $generalConfig->allowedOrigins = [
            'https://allowed.example.com',
        ];

        expect(CrossOriginRequestHelper::resolveAllowedOrigin($request))->toBeNull();
    } finally {
        $generalConfig->allowedOrigins = $originalAllowedOrigins;
    }
})->group('security');

it('does not reflect arbitrary origins when graphql origins are unset without a local-dev exception', function (): void {
    $generalConfig = \verbb\formie\Formie::$plugin->getSettings();
    $originalAllowedOrigins = $generalConfig->allowedOrigins;
    $request = new Request();
    $request->setHostInfo('https://craft.example.com');
    $request->getHeaders()->set('Origin', 'https://odd.example.com');

    try {
        $generalConfig->allowedOrigins = [];

        expect(CrossOriginRequestHelper::resolveAllowedOrigin($request))->toBeNull();
    } finally {
        $generalConfig->allowedOrigins = $originalAllowedOrigins;
    }
})->group('security');

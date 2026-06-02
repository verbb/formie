<?php

declare(strict_types=1);

use Craft;
use craft\web\Request;
use verbb\formie\helpers\CrossOriginRequestHelper;

it('allows only configured cross-origin callers when a GraphQL allowlist is present', function (): void {
    $generalConfig = Craft::$app->getConfig()->getGeneral();
    $originalAllowedOrigins = $generalConfig->allowedGraphqlOrigins;
    $request = new Request();
    $request->setHostInfo('https://craft.example.com');
    $request->getHeaders()->set('Origin', 'https://blocked.example.com, https://allowed.example.com');

    try {
        $generalConfig->allowedGraphqlOrigins = [
            'https://allowed.example.com',
            'https://fallback.example.com',
        ];

        expect(CrossOriginRequestHelper::resolveAllowedOrigin($request))
            ->toBe('https://allowed.example.com');
    } finally {
        $generalConfig->allowedGraphqlOrigins = $originalAllowedOrigins;
    }
})->group('security');

it('rejects unknown cross-origin callers when a GraphQL allowlist is present', function (): void {
    $generalConfig = Craft::$app->getConfig()->getGeneral();
    $originalAllowedOrigins = $generalConfig->allowedGraphqlOrigins;
    $request = new Request();
    $request->setHostInfo('https://craft.example.com');
    $request->getHeaders()->set('Origin', 'https://blocked.example.com');

    try {
        $generalConfig->allowedGraphqlOrigins = [
            'https://allowed.example.com',
        ];

        expect(CrossOriginRequestHelper::resolveAllowedOrigin($request))->toBeNull();
    } finally {
        $generalConfig->allowedGraphqlOrigins = $originalAllowedOrigins;
    }
})->group('security');

it('does not reflect arbitrary origins when graphql origins are unset without a local-dev exception', function (): void {
    $generalConfig = Craft::$app->getConfig()->getGeneral();
    $originalAllowedOrigins = $generalConfig->allowedGraphqlOrigins;
    $request = new Request();
    $request->setHostInfo('https://craft.example.com');
    $request->getHeaders()->set('Origin', 'https://odd.example.com');

    try {
        $generalConfig->allowedGraphqlOrigins = null;

        expect(CrossOriginRequestHelper::resolveAllowedOrigin($request))->toBeNull();
    } finally {
        $generalConfig->allowedGraphqlOrigins = $originalAllowedOrigins;
    }
})->group('security');

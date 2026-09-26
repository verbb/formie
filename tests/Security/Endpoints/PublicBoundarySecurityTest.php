<?php

declare(strict_types=1);

use craft\web\Request;
use verbb\formie\helpers\CrossOriginRequestHelper;

it('recognizes only formie action routes as formie action paths', function (): void {
    $request = new class extends Request {
        public string $path = '';

        public function getPathInfo(bool $returnRealPathInfo = false): string
        {
            return $this->path;
        }
    };

    $request->path = 'formie/client/submissions/submit';
    expect(CrossOriginRequestHelper::isFormieActionPath($request))->toBeTrue();

    $request->path = 'api/runtime/rest/submissions/submit';
    expect(CrossOriginRequestHelper::isFormieActionPath($request))->toBeFalse();
})->group('security');

it('allows localhost origins only for local-dev hosts when graphql origins are disabled', function (): void {
    $generalConfig = \verbb\formie\Formie::$plugin->getSettings();
    $originalAllowedOrigins = $generalConfig->allowedOrigins;
    $request = new Request();
    $request->setHostInfo('http://craft.local.test');
    $request->getHeaders()->set('Origin', 'http://localhost:3000');

    try {
        $generalConfig->allowedOrigins = [];

        expect(CrossOriginRequestHelper::resolveAllowedOrigin($request))
            ->toBeNull();
    } finally {
        $generalConfig->allowedOrigins = $originalAllowedOrigins;
    }
})->group('security');

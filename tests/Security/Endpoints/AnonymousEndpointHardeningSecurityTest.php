<?php

declare(strict_types=1);

use Tests\Support\UploadTestHelper;
use Tests\Support\WebRequestTestHelper;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\TooManyRequestsHttpException;
use verbb\formie\Formie;
use verbb\formie\controllers\AddressController;
use verbb\formie\controllers\FileUploadController;
use verbb\formie\controllers\FieldsController;
use verbb\formie\controllers\PaymentWebhooksController;
use verbb\formie\controllers\PaymentSubscriptionsController;
use verbb\formie\controllers\TestController;
use verbb\formie\helpers\FieldAccess;
use verbb\formie\helpers\PaymentAccess;
use verbb\formie\integrations\payments\GoCardless;
use verbb\formie\integrations\payments\Mollie;
use verbb\formie\integrations\payments\Opayo;
use verbb\formie\integrations\payments\Stripe;
use verbb\formie\models\Payment as PaymentModel;
use verbb\formie\models\Subscription;

function withEnvOverrides(array $values, callable $callback): mixed
{
    $original = [];

    foreach ($values as $name => $value) {
        $original[$name] = getenv($name);

        if ($value === null) {
            putenv((string)$name);
            unset($_ENV[$name], $_SERVER[$name]);
            continue;
        }

        putenv("{$name}={$value}");
        $_ENV[$name] = $value;
        $_SERVER[$name] = $value;
    }

    try {
        return $callback();
    } finally {
        foreach ($original as $name => $value) {
            if ($value === false) {
                putenv((string)$name);
                unset($_ENV[$name], $_SERVER[$name]);
                continue;
            }

            putenv("{$name}={$value}");
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }
    }
}

function createPaymentIntegrationFixture(): Mollie
{
    $integration = new Mollie([
        'name' => 'Security Payment Integration ' . uniqid(),
        'handle' => 'securityPayment' . uniqid(),
        'enabled' => false,
    ]);

    Formie::$plugin->getIntegrations()->saveIntegration($integration, false);

    return $integration;
}

function createPaymentFixture(array $overrides = []): PaymentModel
{
    $integration = createPaymentIntegrationFixture();
    $form = formie()
        ->form(['title' => 'Security Payment Fixture'])
        ->singleLineTextField('fullName')
        ->paymentField('paymentField', [
            'paymentIntegration' => $integration->handle,
            'paymentIntegrationType' => get_class($integration),
        ])
        ->create();
    $submission = formie()
        ->submission($form)
        ->with(['fullName' => 'Payment Fixture'])
        ->save();
    $paymentField = $form->getFieldByHandle('paymentField');

    $payment = new PaymentModel(array_merge([
        'integrationId' => $integration->id,
        'submissionId' => $submission->id,
        'fieldId' => $paymentField->id,
        'amount' => 10.00,
        'currency' => 'AUD',
        'status' => PaymentModel::STATUS_PENDING,
        'reference' => 'security-payment-' . uniqid(),
        'message' => null,
        'redirectUrl' => 'https://example.test/return',
    ], $overrides));

    Formie::$plugin->getPayments()->savePayment($payment, false);

    return $payment;
}

function createOpayoCallbackFixture(): Opayo
{
    return new class([
        'id' => random_int(10000, 99999),
        'name' => 'Security Opayo Integration',
        'handle' => 'securityOpayo' . uniqid(),
        'vendorName' => 'test-vendor',
        'integrationKey' => 'test-key',
        'integrationPassword' => 'test-password',
    ]) extends Opayo {
        public function request(string $method, string $uri, array $options = []): mixed
        {
            return [
                'merchantSessionKey' => 'test-merchant-session-key',
            ];
        }
    };
}

it('requires a field access token for anonymous summary rendering', function (): void {
    $form = formie()
        ->form(['title' => 'Anonymous Summary Hardening'])
        ->singleLineTextField('fullName')
        ->summaryField('summary')
        ->create();

    $submission = formie()
        ->submission($form)
        ->with(['fullName' => 'Jane Example'])
        ->save();

    $summaryField = $form->getFieldByHandle('summary');

    WebRequestTestHelper::withWebRequestContext(function ($request) use ($submission, $summaryField): void {
        $request->setBodyParams([
            'submissionUid' => (string)$submission->uid,
            'fieldId' => (string)$summaryField->id,
        ]);

        $controller = new FieldsController('formie-fields-security', Craft::$app);
        $html = $controller->actionGetSummaryHtml();

        expect($html)->toBe('');
    }, [
        'method' => 'POST',
    ]);
})->group('security');

it('renders summary html only when presented with a valid field access token', function (): void {
    $form = formie()
        ->form(['title' => 'Anonymous Summary Capability'])
        ->singleLineTextField('fullName')
        ->summaryField('summary')
        ->create();

    $payload = '<script>alert("xss")</script><p>Jane Example</p>';
    $submission = formie()
        ->submission($form)
        ->with(['fullName' => $payload])
        ->save();

    $summaryField = $form->getFieldByHandle('summary');
    $accessToken = FieldAccess::issueAccessToken($submission, (int)$summaryField->id);

    WebRequestTestHelper::withWebRequestContext(function ($request) use ($accessToken): void {
        $request->setBodyParams([
            'accessToken' => $accessToken,
        ]);

        $controller = new FieldsController('formie-fields-security', Craft::$app);
        $html = $controller->actionGetSummaryHtml();

        expect($html)
            ->toBeString()
            ->and($html)->not->toContain('<script>alert("xss")</script>');
    }, [
        'method' => 'POST',
    ]);
})->group('security');

it('generates opaque signature image urls instead of exposing raw submission identifiers', function (): void {
    $form = formie()
        ->form(['title' => 'Anonymous Signature Capability'])
        ->signatureField('signature')
        ->create();

    $signaturePayload = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVQIHWP4////fwAJ+wP9KobjigAAAABJRU5ErkJggg==';
    $submission = formie()
        ->submission($form)
        ->with(['signature' => $signaturePayload])
        ->save();

    $signatureField = $form->getFieldByHandle('signature');
    $imageUrl = $signatureField->getImageUrl($submission, $signaturePayload);

    expect($imageUrl)
        ->toContain('accessToken=')
        ->and($imageUrl)->not->toContain('submissionUid=')
        ->and($imageUrl)->not->toContain('fieldId=');
})->group('security');

it('does not allow client supplied google geocode keys without valid form context', function (): void {
    WebRequestTestHelper::withWebRequestContext(function ($request): void {
        $request->setBodyParams([
            'latlng' => '-37.8136,144.9631',
            'key' => 'attacker-supplied-key',
        ]);

        $controller = new AddressController('formie-address-security', Craft::$app);
        $response = $controller->actionGooglePlacesGeocode();

        expect($response->data['error'] ?? null)->toBe('Unable to geocode location.')
            ->and($response->data['error'] ?? null)->not->toContain('attacker-supplied-key');
    }, [
        'method' => 'POST',
        'headers' => [
            'Accept' => 'application/json',
        ],
    ]);
})->group('security');

it('rejects raw payment uids for anonymous payment polling', function (): void {
    $payment = createPaymentFixture();

    WebRequestTestHelper::withWebRequestContext(function ($request) use ($payment): void {
        $request->setQueryParams([
            'paymentUid' => (string)$payment->uid,
        ]);

        $controller = new PaymentWebhooksController('formie-payment-security', Craft::$app);

        expect(fn() => $controller->actionPollStatus())
            ->toThrow(BadRequestHttpException::class, 'Request missing required param');
    });
})->group('security');

it('treats payment status polling as an opaque token capability', function (): void {
    $payment = createPaymentFixture();
    $statusToken = PaymentAccess::issueStatusToken($payment);

    WebRequestTestHelper::withWebRequestContext(function ($request) use ($statusToken): void {
        $request->setQueryParams([
            'statusToken' => $statusToken,
        ]);

        $controller = new PaymentWebhooksController('formie-payment-security', Craft::$app);
        $response = $controller->actionPollStatus();

        expect($response->data['status'] ?? null)->toBe('pending');
    });
})->group('security');

it('rejects expired payment status tokens', function (): void {
    $payment = createPaymentFixture();
    $statusToken = PaymentAccess::issueStatusToken($payment, time() - 90000);

    WebRequestTestHelper::withWebRequestContext(function ($request) use ($statusToken): void {
        $request->setQueryParams([
            'statusToken' => $statusToken,
        ]);

        $controller = new PaymentWebhooksController('formie-payment-security', Craft::$app);

        expect(fn() => $controller->actionPollStatus())->toThrow(NotFoundHttpException::class);
    });
})->group('security');

it('rate limits anonymous payment status polling by token and client', function (): void {
    $payment = createPaymentFixture();
    $statusToken = PaymentAccess::issueStatusToken($payment);

    WebRequestTestHelper::withWebRequestContext(function ($request) use ($statusToken): void {
        $request->setQueryParams([
            'statusToken' => $statusToken,
        ]);

        $controller = new PaymentWebhooksController('formie-payment-security', Craft::$app);

        for ($i = 0; $i < 120; $i++) {
            expect($controller->actionPollStatus()->data['status'] ?? null)->toBe('pending');
        }

        expect(fn() => $controller->actionPollStatus())->toThrow(TooManyRequestsHttpException::class);
    }, [
        'remoteAddr' => '198.51.100.20',
    ]);
})->group('security');

it('issues opaque payment status tokens instead of public payment identifiers', function (): void {
    $payment = createPaymentFixture();
    $statusToken = PaymentAccess::issueStatusToken($payment);

    expect($statusToken)
        ->not->toBeNull()
        ->and($statusToken)->not->toContain((string)$payment->uid)
        ->and(strlen((string)$statusToken))->toBeGreaterThan(20);
})->group('security');

it('requires an opayo session token before issuing merchant session keys', function (): void {
    $integration = createOpayoCallbackFixture();

    WebRequestTestHelper::withWebRequestContext(function ($request) use ($integration): void {
        $request->setBodyParams([
            'merchantSessionKey' => 'true',
        ]);

        expect(fn() => $integration->processCallback())->toThrow(BadRequestHttpException::class);
    }, [
        'method' => 'POST',
    ]);
})->group('security');

it('rejects expired opayo merchant session tokens', function (): void {
    $integration = createOpayoCallbackFixture();
    $sessionToken = PaymentAccess::issueProviderSessionToken('opayo', (int)$integration->id, (string)$integration->handle, time() - 3600);

    WebRequestTestHelper::withWebRequestContext(function ($request) use ($integration, $sessionToken): void {
        $request->setBodyParams([
            'merchantSessionKey' => 'true',
            'sessionToken' => $sessionToken,
        ]);

        expect(fn() => $integration->processCallback())->toThrow(BadRequestHttpException::class);
    }, [
        'method' => 'POST',
    ]);
})->group('security');

it('rate limits opayo merchant session keys by token and client', function (): void {
    $integration = createOpayoCallbackFixture();
    $sessionToken = PaymentAccess::issueProviderSessionToken('opayo', (int)$integration->id, (string)$integration->handle);

    WebRequestTestHelper::withWebRequestContext(function ($request) use ($integration, $sessionToken): void {
        $request->setBodyParams([
            'merchantSessionKey' => 'true',
            'sessionToken' => $sessionToken,
        ]);

        for ($i = 0; $i < 20; $i++) {
            expect($integration->processCallback()->data['merchantSessionKey'] ?? null)->toBe('test-merchant-session-key');
        }

        expect(fn() => $integration->processCallback())->toThrow(TooManyRequestsHttpException::class);
    }, [
        'method' => 'POST',
        'remoteAddr' => '198.51.100.40',
    ]);
})->group('security');

it('rate limits anonymous google geocode proxy requests by form field and client', function (): void {
    $formHandle = 'missing-form-' . uniqid();

    WebRequestTestHelper::withWebRequestContext(function ($request) use ($formHandle): void {
        $request->setBodyParams([
            'latlng' => '-37.8136,144.9631',
            'handle' => $formHandle,
            'fieldHandle' => 'address',
        ]);
        $request->getHeaders()->set('Accept', 'application/json');

        $controller = new AddressController('formie-address-security', Craft::$app);

        for ($i = 0; $i < 60; $i++) {
            expect($controller->actionGooglePlacesGeocode()->data['error'] ?? null)->toBe('Unable to geocode location.');
        }

        expect(fn() => $controller->actionGooglePlacesGeocode())->toThrow(TooManyRequestsHttpException::class);
    }, [
        'method' => 'POST',
        'remoteAddr' => '198.51.100.30',
    ]);
})->group('security');

it('requires upload context for anonymous upload deletion', function (): void {
    $volume = UploadTestHelper::ensureUploadVolume();
    $form = formie()
        ->form(['title' => 'Anonymous Upload Delete Security'])
        ->singleLineTextField('fullName')
        ->fileUploadField('documents')
        ->create();
    $field = $form->getFieldByHandle('documents');
    $asset = UploadTestHelper::seedAsset('upload-delete-security.txt', 'tracked', $volume);

    Formie::$plugin->getFileUploads()->trackSubmissionAsset($asset, (int)$form->id, null, $field->uid);

    WebRequestTestHelper::withWebRequestContext(function ($request) use ($asset): void {
        $request->setBodyParams([
            'assetId' => (int)$asset->id,
        ]);

        $controller = new FileUploadController('formie-file-upload-security', Craft::$app);

        expect(fn() => $controller->actionDelete())
            ->toThrow(BadRequestHttpException::class, 'Invalid upload context.');
    }, [
        'method' => 'POST',
        'headers' => [
            'Accept' => 'application/json',
        ],
    ]);
})->group('security');

it('requires upload context for anonymous upload hydration', function (): void {
    $volume = UploadTestHelper::ensureUploadVolume();
    $form = formie()
        ->form(['title' => 'Anonymous Upload Hydrate Security'])
        ->singleLineTextField('fullName')
        ->fileUploadField('documents')
        ->create();
    $field = $form->getFieldByHandle('documents');
    $asset = UploadTestHelper::seedAsset('upload-hydrate-security.txt', 'tracked', $volume);

    Formie::$plugin->getFileUploads()->trackSubmissionAsset($asset, (int)$form->id, null, $field->uid);

    WebRequestTestHelper::withWebRequestContext(function ($request) use ($asset): void {
        $request->setBodyParams([
            'assetIds' => [(int)$asset->id],
        ]);

        $controller = new FileUploadController('formie-file-upload-security', Craft::$app);

        expect(fn() => $controller->actionHydrate())
            ->toThrow(BadRequestHttpException::class, 'Invalid upload context.');
    }, [
        'method' => 'POST',
        'headers' => [
            'Accept' => 'application/json',
        ],
    ]);
})->group('security');

it('rejects anonymous upload binding to an incomplete submission outside the current draft state', function (): void {
    $form = formie()
        ->form(['title' => 'Anonymous Upload Submission Binding'])
        ->fileUploadField('documents')
        ->create();
    $submission = formie()
        ->submission($form)
        ->with([])
        ->save();
    $submission->isIncomplete = true;
    expect(Craft::$app->getElements()->saveElement($submission))->toBeTrue();

    WebRequestTestHelper::withWebRequestContext(function ($request) use ($form, $submission): void {
        $request->setBodyParams([
            'handle' => (string)$form->handle,
            'fieldHandle' => 'documents',
            'submissionId' => (int)$submission->id,
        ]);

        $controller = new FileUploadController('formie-file-upload-security', Craft::$app);

        expect(fn() => $controller->actionUpload())
            ->toThrow(BadRequestHttpException::class, 'Invalid upload submission.');
    }, [
        'method' => 'POST',
        'remoteAddr' => '198.51.100.55',
        'headers' => [
            'Accept' => 'application/json',
        ],
    ]);
})->group('security');

it('rate limits anonymous file upload attempts by form field and client', function (): void {
    $formHandle = 'uploadRate' . uniqid();
    $fieldHandle = 'documents' . uniqid();
    $form = formie()
        ->form([
            'title' => 'Anonymous Upload Rate Limit',
            'handle' => $formHandle,
        ])
        ->fileUploadField($fieldHandle)
        ->create();

    WebRequestTestHelper::withWebRequestContext(function ($request) use ($form, $fieldHandle): void {
        $request->setBodyParams([
            'handle' => (string)$form->handle,
            'fieldHandle' => $fieldHandle,
        ]);

        $controller = new FileUploadController('formie-file-upload-security', Craft::$app);

        for ($i = 0; $i < 30; $i++) {
            expect(fn() => $controller->actionUpload())->toThrow(BadRequestHttpException::class);
        }

        expect(fn() => $controller->actionUpload())->toThrow(TooManyRequestsHttpException::class);
    }, [
        'method' => 'POST',
        'remoteAddr' => '198.51.100.56',
        'headers' => [
            'Accept' => 'application/json',
        ],
    ]);
})->group('security');

it('requires a valid gocardless webhook signature before processing webhook payloads', function (): void {
    $integration = new class([
        'name' => 'Security GoCardless',
        'handle' => 'securityGoCardless' . uniqid(),
        'accessToken' => '$GO_CARDLESS_ACCESS_TOKEN',
        'webhookSecretKey' => '$GO_CARDLESS_WEBHOOK_SECRET',
    ]) extends GoCardless {
        public bool $requested = false;

        public function request(string $method, string $uri, array $options = []): mixed
        {
            $this->requested = true;

            return ['payments' => []];
        }
    };

    withEnvOverrides([
        'GO_CARDLESS_ACCESS_TOKEN' => 'test-token',
        'GO_CARDLESS_WEBHOOK_SECRET' => 'correct-secret',
    ], function () use ($integration): void {
        $payload = json_encode([
            'events' => [[
                'resource_type' => 'payments',
                'links' => [
                    'payment' => 'PM123',
                ],
            ]],
        ], JSON_THROW_ON_ERROR);

        WebRequestTestHelper::withWebRequestContext(function ($request) use ($integration, $payload): void {
            $request->setRawBody($payload);
            $request->getHeaders()->set('Webhook-Signature', hash_hmac('sha256', $payload, 'wrong-secret'));

            $response = $integration->processWebhook();

            expect($response->data)->toBe('success')
                ->and($integration->requested)->toBeFalse();
        }, [
            'method' => 'POST',
        ]);
    });
})->group('security');

it('accepts correctly signed gocardless webhook payloads before processing them', function (): void {
    $payment = createPaymentFixture();
    $integration = new class([
        'name' => 'Security GoCardless',
        'handle' => 'securityGoCardless' . uniqid(),
        'accessToken' => '$GO_CARDLESS_ACCESS_TOKEN',
        'webhookSecretKey' => '$GO_CARDLESS_WEBHOOK_SECRET',
    ]) extends GoCardless {
        public bool $requested = false;

        public function request(string $method, string $uri, array $options = []): mixed
        {
            $this->requested = true;

            return ['payments' => [
                'id' => 'PM123',
                'status' => 'submitted',
                'metadata' => [
                    'formiePaymentId' => (string)$this->context['paymentId'],
                ],
            ]];
        }
    };
    $integration->context['paymentId'] = (int)$payment->id;

    withEnvOverrides([
        'GO_CARDLESS_ACCESS_TOKEN' => 'test-token',
        'GO_CARDLESS_WEBHOOK_SECRET' => 'correct-secret',
    ], function () use ($integration): void {
        $payload = json_encode([
            'events' => [[
                'resource_type' => 'payments',
                'links' => [
                    'payment' => 'PM123',
                ],
            ]],
        ], JSON_THROW_ON_ERROR);

        WebRequestTestHelper::withWebRequestContext(function ($request) use ($integration, $payload): void {
            $request->setRawBody($payload);
            $request->getHeaders()->set('Webhook-Signature', hash_hmac('sha256', $payload, 'correct-secret'));

            $response = $integration->processWebhook();

            expect($integration->requested)->toBeTrue();
        }, [
            'method' => 'POST',
        ]);
    });
})->group('security');

it('ignores unknown mollie webhook references before requesting provider status', function (): void {
    $integration = new class([
        'name' => 'Security Mollie',
        'handle' => 'securityMollie' . uniqid(),
        'apiKey' => '$MOLLIE_API_KEY',
    ]) extends Mollie {
        public bool $requested = false;

        public function request(string $method, string $uri, array $options = []): mixed
        {
            $this->requested = true;

            return [];
        }
    };

    withEnvOverrides([
        'MOLLIE_API_KEY' => 'test-key',
    ], function () use ($integration): void {
        WebRequestTestHelper::withWebRequestContext(function ($request) use ($integration): void {
            $request->setBodyParams([
                'id' => 'tr_unknown_' . uniqid(),
            ]);

            $response = $integration->processWebhook();

            expect($response->data)->toBe('success')
                ->and($integration->requested)->toBeFalse();
        }, [
            'method' => 'POST',
        ]);
    });
})->group('security');

it('rate limits anonymous payment webhook requests by handle reference and client', function (): void {
    $integration = createPaymentIntegrationFixture();

    WebRequestTestHelper::withWebRequestContext(function ($request) use ($integration): void {
        $request->setQueryParams([
            'handle' => (string)$integration->handle,
            'id' => 'tr_rate_limited_' . uniqid(),
        ]);

        $controller = new PaymentWebhooksController('formie-payment-security', Craft::$app);

        for ($i = 0; $i < 60; $i++) {
            expect($controller->actionProcessWebhook()->data)->toBe('success');
        }

        expect(fn() => $controller->actionProcessWebhook())->toThrow(TooManyRequestsHttpException::class);
    }, [
        'method' => 'POST',
        'remoteAddr' => '198.51.100.57',
    ]);
})->group('security');

it('rate limits anonymous payment callback requests by handle reference and client', function (): void {
    $integration = createPaymentIntegrationFixture();

    WebRequestTestHelper::withWebRequestContext(function ($request) use ($integration): void {
        $request->setQueryParams([
            'handle' => (string)$integration->handle,
        ]);

        $controller = new PaymentWebhooksController('formie-payment-security', Craft::$app);

        for ($i = 0; $i < 60; $i++) {
            $controller->actionProcessCallback();
        }

        expect(fn() => $controller->actionProcessCallback())->toThrow(TooManyRequestsHttpException::class);
    }, [
        'method' => 'POST',
        'remoteAddr' => '198.51.100.58',
    ]);
})->group('security');

it('fails closed when stripe webhook signing is not configured or supplied', function (): void {
    $integration = new Stripe([
        'name' => 'Security Stripe',
        'handle' => 'securityStripe' . uniqid(),
    ]);

    WebRequestTestHelper::withWebRequestContext(function ($request) use ($integration): void {
        $request->setRawBody('{"type":"payment_intent.succeeded"}');

        $response = $integration->processWebhook();

        expect($response->getStatusCode())->toBe(400)
            ->and($response->data)->toBe('error');
    }, [
        'method' => 'POST',
    ]);
})->group('security');

it('renders a confirmation page before executing subscription cancel links via get', function (): void {
    $integration = createPaymentIntegrationFixture();
    $subscription = new Subscription([
        'integrationId' => (int)$integration->id,
        'reference' => 'subscription-reference-' . uniqid(),
        'trialDays' => 0,
    ]);
    expect(Formie::$plugin->getSubscriptions()->saveSubscription($subscription, false))->toBeTrue();
    $hash = Craft::$app->getSecurity()->hashData((string)$subscription->reference);

    WebRequestTestHelper::withWebRequestContext(function ($request) use ($subscription, $hash): void {
        $request->setQueryParams([
            'id' => (int)$subscription->id,
            'hash' => $hash,
        ]);

        $controller = new PaymentSubscriptionsController('formie-subscription-security', Craft::$app);
        $response = $controller->actionCancel();

        expect($response->data)
            ->toContain('Are you sure you want to cancel this subscription?')
            ->and($response->data)->toContain('method="post"');
    }, [
        'method' => 'GET',
    ]);
})->group('security');

it('requires a configured secret before exposing test endpoints', function (): void {
    withEnvOverrides([
        'FORMIE_ENABLE_TEST_ENDPOINTS' => '1',
        'FORMIE_TEST_ENDPOINT_KEY' => null,
    ], function (): void {
        WebRequestTestHelper::withWebRequestContext(function ($request): void {
            $request->setBodyParams([
                'fieldHandle' => 'fullName',
            ]);

            $controller = new TestController('formie-test-security', Craft::$app);

            expect(fn() => $controller->actionQuerySubmissions())
                ->toThrow(ForbiddenHttpException::class, 'Test endpoint key is not configured.');
        }, [
            'method' => 'POST',
        ]);
    });
})->group('security');

it('does not expose test endpoints outside the test runtime even when enabled', function (): void {
    withEnvOverrides([
        'ENVIRONMENT' => 'production',
        'FORMIE_ENABLE_TEST_ENDPOINTS' => '1',
        'FORMIE_TEST_ENDPOINT_KEY' => 'endpoint-secret',
    ], function (): void {
        WebRequestTestHelper::withWebRequestContext(function ($request): void {
            $request->setBodyParams([
                'fieldHandle' => 'fullName',
            ]);

            $controller = new TestController('formie-test-security', Craft::$app);

            expect(fn() => $controller->actionQuerySubmissions())
                ->toThrow(ForbiddenHttpException::class, 'Test endpoint is only available in the test environment.');
        }, [
            'method' => 'POST',
            'headers' => [
                'X-Formie-Test-Key' => 'endpoint-secret',
            ],
        ]);
    });
})->group('security');

it('allows test endpoint queries only when the configured secret is supplied', function (): void {
    $form = formie()
        ->form(['title' => 'Secured Test Endpoint'])
        ->singleLineTextField('fullName')
        ->create();

    $submission = formie()
        ->submission($form)
        ->with(['fullName' => 'Secured Query'])
        ->save();

    withEnvOverrides([
        'FORMIE_ENABLE_TEST_ENDPOINTS' => '1',
        'FORMIE_TEST_ENDPOINT_KEY' => 'endpoint-secret',
    ], function () use ($form, $submission): void {
        WebRequestTestHelper::withWebRequestContext(function ($request) use ($form, $submission): void {
            $request->setBodyParams([
                'handle' => (string)$form->handle,
                'fieldHandle' => 'fullName',
                'value' => 'Secured Query',
            ]);

            $controller = new TestController('formie-test-security', Craft::$app);
            $response = $controller->actionQuerySubmissions();

            expect($response->data['success'] ?? false)->toBeTrue()
                ->and($response->data['ids'] ?? [])->toContain($submission->id)
                ->and((int)($response->data['total'] ?? 0))->toBe(1);
        }, [
            'method' => 'POST',
            'headers' => [
                'X-Formie-Test-Key' => 'endpoint-secret',
            ],
        ]);
    });
})->group('security');

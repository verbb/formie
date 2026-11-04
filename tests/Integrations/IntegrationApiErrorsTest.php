<?php

declare(strict_types=1);

use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use verbb\formie\Formie;
use verbb\formie\base\Integration;
use verbb\formie\elements\Submission;
use verbb\formie\errors\IntegrationException;
use verbb\formie\helpers\IntegrationApiErrors;
use verbb\formie\integrations\emailmarketing\CampaignMonitor;
use verbb\formie\integrations\emailmarketing\Mailchimp;
use verbb\formie\models\Settings;

function integrationApiErrorRequestException(int $statusCode, string $body): RequestException
{
    return new RequestException(
        'API request failed',
        new Request('POST', 'https://example.test'),
        new Response($statusCode, [], $body),
    );
}

it('classifies repeated Campaign Monitor subscriptions as subscriber rejections', function (): void {
    $integration = new CampaignMonitor(['name' => 'Campaign Monitor', 'handle' => 'campaignMonitor']);
    $exception = integrationApiErrorRequestException(429, '{"Code":429,"Message":"Subscriber was added too many times too quickly, try again later."}');

    expect($integration->classifyIntegrationApiError($exception))
        ->toBe(IntegrationApiErrors::SEVERITY_REJECTED);
});

it('keeps Mailchimp mapping validation errors as failures', function (): void {
    $integration = new Mailchimp(['name' => 'Mailchimp', 'handle' => 'mailchimp']);
    $exception = integrationApiErrorRequestException(400, '{"title":"Invalid Resource","detail":"Your merge fields were invalid."}');

    expect($integration->classifyIntegrationApiError($exception))
        ->toBeNull();
});

it('includes form context in integration API error messages', function (): void {
    expect(Integration::formatSubmissionLogContext(null))->toBe('');

    $submission = new Submission();
    $submission->id = 456;

    expect(Integration::formatSubmissionLogContext($submission))
        ->toContain('456');
});

it('does not fail the queue for downgraded Mailchimp API errors', function (): void {
    $settings = Formie::$plugin->getSettings();
    $previousHandling = $settings->integrationApiErrorHandling;

    $settings->integrationApiErrorHandling = [
        ['severity' => IntegrationApiErrors::SEVERITY_RATE_LIMITED, 'action' => IntegrationApiErrors::ACTION_LOG_WARNING],
    ];

    $integration = new Mailchimp(['name' => 'Mailchimp', 'handle' => 'mailchimp']);
    $submission = new Submission();
    $submission->id = 123;
    $exception = integrationApiErrorRequestException(429, '{"title":"Too Many Requests"}');

    try {
        expect($integration->handleSubmissionApiError($exception, $submission))->toBeTrue();
    } finally {
        $settings->integrationApiErrorHandling = $previousHandling;
    }
});

it('fails the queue when integration API error handling is set to failQueue', function (): void {
    $settings = Formie::$plugin->getSettings();
    $previousHandling = $settings->integrationApiErrorHandling;

    $settings->integrationApiErrorHandling = [
        ['severity' => IntegrationApiErrors::SEVERITY_RATE_LIMITED, 'action' => IntegrationApiErrors::ACTION_FAIL_QUEUE],
    ];

    $integration = new CampaignMonitor(['name' => 'Campaign Monitor', 'handle' => 'campaignMonitor']);
    $submission = new Submission();
    $submission->id = 123;
    $exception = integrationApiErrorRequestException(429, '{"Code":429,"Message":"Too many requests"}');

    try {
        $integration->handleSubmissionApiError($exception, $submission);
    } finally {
        $settings->integrationApiErrorHandling = $previousHandling;
    }
})->throws(IntegrationException::class);

it('defaults integrations without severity handling to unsupported', function (): void {
    $integration = new class(['name' => 'Custom', 'handle' => 'custom']) extends Integration {
        public static function displayName(): string
        {
            return 'Custom';
        }

        public function fetchConfig(): \verbb\formie\models\IntegrationConfig
        {
            return new \verbb\formie\models\IntegrationConfig();
        }

        public function sendPayload(Submission $submission): bool
        {
            return true;
        }
    };

    $submission = new Submission();
    $submission->id = 1;

    expect($integration->supportsIntegrationApiErrorSeverity())->toBeFalse();

    $integration->handleSubmissionApiError(new Exception('test'), $submission);
})->throws(IntegrationException::class);

it('resolves integration API error actions from plugin settings', function (): void {
    $settings = new Settings([
        'integrationApiErrorHandling' => [
            ['severity' => IntegrationApiErrors::SEVERITY_REJECTED, 'action' => IntegrationApiErrors::ACTION_LOG_INFO],
        ],
    ]);

    expect($settings->getIntegrationApiErrorAction(IntegrationApiErrors::SEVERITY_REJECTED))
        ->toBe(IntegrationApiErrors::ACTION_LOG_INFO)
        ->and($settings->getIntegrationApiErrorAction(IntegrationApiErrors::SEVERITY_FAILURE))
        ->toBe(IntegrationApiErrors::ACTION_FAIL_QUEUE);
});

it('returns a skipped result for a policy-approved subscriber rejection', function (): void {
    $integration = new class(['name' => 'Mailchimp', 'handle' => 'mailchimp']) extends Mailchimp {
        protected function createDeliveryHttpHandler(): callable {
            return \GuzzleHttp\HandlerStack::create(new \GuzzleHttp\Handler\MockHandler([
                new Response(400, [], '{"title":"Invalid Resource","detail":"test@example.test looks fake or invalid, please enter a real email address."}'),
            ]));
        }
    };
    $integration->setClient(new \GuzzleHttp\Client(['base_uri' => 'https://8.8.8.8']));
    try {
        $integration->request('POST', '/');
        test()->fail('Expected the mocked rejection.');
    } catch (RequestException $exception) {
        $result = $integration->resultForPayload($integration->handleSubmissionApiError($exception, new Submission()));
    }
    expect($result->status)->toBe(\verbb\formie\enums\IntegrationStatus::Skipped)
        ->and($result->shouldFailQueue())->toBeFalse();
    $restored = \verbb\formie\models\IntegrationResult::fromStorage($result->withOutputs(['id' => 1])->toStorage());
    expect($restored->status)->toBe($result->status)->and($restored->shouldFailQueue())->toBeFalse();
});

it('lets an API error policy acknowledge the queue job for any failure', function (): void {
    expect(\verbb\formie\models\IntegrationResult::unknown()->withQueueFailure(false)->shouldFailQueue())->toBeFalse();
});

it('recognises only known subscriber responses and actual HTTP rate limits', function(string $provider, int $status, string $body, ?string $category): void {
    $integration = new $provider(['name' => 'Provider', 'handle' => 'provider']);
    expect($integration->classifyIntegrationApiError(integrationApiErrorRequestException($status, $body)))->toBe($category);
})->with([
    [CampaignMonitor::class, 400, '{"Code":429,"Message":"Subscriber was added too many times too quickly, try again later."}', 'rejected'],
    [CampaignMonitor::class, 429, '{"Code":429,"Message":"Rate limit exceeded"}', 'rate_limited'],
    [CampaignMonitor::class, 400, '{"Code":400,"Message":"Invalid custom field"}', null],
    [CampaignMonitor::class, 401, '{"Code":100,"Message":"Invalid API key"}', null],
    [CampaignMonitor::class, 503, '{"Code":429,"Message":"Subscriber was added too many times too quickly, try again later."}', null],
    [CampaignMonitor::class, 400, '{"Code":429,"Message":"Unrecognised throttle"}', null],
    [CampaignMonitor::class, 400, '{bad json', null],
    [Mailchimp::class, 400, '{"title":"Invalid Resource","detail":"test@example.test looks fake or invalid, please enter a real email address."}', 'rejected'],
    [Mailchimp::class, 400, '{"title":"Invalid Resource","detail":"test@example.test has signed up to a lot of lists very recently; we\'re not allowing more signups for now"}', 'rejected'],
    [Mailchimp::class, 400, '{"title":"Invalid Resource","detail":"Please provide a valid merge field."}', null],
    [Mailchimp::class, 400, '{"title":"Invalid Resource","detail":"Unknown validation error"}', null],
    [Mailchimp::class, 400, '{"title":"Member Exists","detail":"Already subscribed"}', null],
    [Mailchimp::class, 400, '"Invalid Resource"', null],
    [Mailchimp::class, 400, '{"title":"Invalid Resource","detail":[]}', null],
    [Mailchimp::class, 400, '{bad json', null],
    [Mailchimp::class, 429, '{"title":"Too Many Requests"}', 'rate_limited'],
    [Mailchimp::class, 401, '{"title":"API Key Invalid"}', null],
    [Mailchimp::class, 503, '{"title":"Invalid Resource","detail":"test@example.test looks fake or invalid, please enter a real email address."}', null],
]);

it('fails rate limits by default and preserves explicitly saved actions', function(): void {
    $defaults = new Settings();
    expect($defaults->getIntegrationApiErrorAction('rejected'))->toBe('logWarning')
        ->and($defaults->getIntegrationApiErrorAction('rate_limited'))->toBe('failQueue')
        ->and($defaults->getIntegrationApiErrorAction('failure'))->toBe('failQueue');
    $saved = [
        ['severity' => 'rate_limited', 'action' => 'logWarning'],
        ['severity' => 'rejected', 'action' => 'logInfo'],
        ['severity' => 'failure', 'action' => 'ignore'],
    ];
    $settings = new Settings(['integrationApiErrorHandling' => $saved]);
    $settings->setAttributes(['integrationApiErrorHandling' => $saved]);
    expect($settings->getIntegrationApiErrorHandlingRows())->toBe($saved);
});

it('normalises acknowledged legacy failures without marking them delivered', function(string $status): void {
    $result = \verbb\formie\models\IntegrationResult::fromStorage([
        'status' => $status, 'code' => 'provider_rejected', 'failQueue' => false,
        'message' => 'Provider declined', 'outputs' => ['id' => 1],
    ]);
    $batch = new \verbb\formie\models\IntegrationBatchResult();
    $batch->record('provider', $result);
    expect($result->status)->toBe(\verbb\formie\enums\IntegrationStatus::Skipped)
        ->and($result->isSuccessful())->toBeFalse()
        ->and($result->message)->toBe('Provider declined')
        ->and($result->code)->toBe('provider_rejected')
        ->and($result->outputs)->toBe(['id' => 1])
        ->and($batch->accepts())->toBeTrue()
        ->and($batch->failedHandles())->toBe([]);
})->with(['failed', 'rejected', 'unknown']);

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

it('classifies Campaign Monitor 429 responses as rate limited', function (): void {
    $integration = new CampaignMonitor(['name' => 'Campaign Monitor', 'handle' => 'campaignMonitor']);
    $exception = integrationApiErrorRequestException(429, '{"Code":429,"Message":"Subscriber was added too many times too quickly, try again later."}');

    expect($integration->classifyIntegrationApiError($exception))
        ->toBe(IntegrationApiErrors::SEVERITY_RATE_LIMITED);
});

it('classifies Mailchimp 400 validation responses as rejected', function (): void {
    $integration = new Mailchimp(['name' => 'Mailchimp', 'handle' => 'mailchimp']);
    $exception = integrationApiErrorRequestException(400, '{"title":"Invalid Resource","detail":"Your merge fields were invalid."}');

    expect($integration->classifyIntegrationApiError($exception))
        ->toBe(IntegrationApiErrors::SEVERITY_REJECTED);
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

        public function fetchFormSettings(): \verbb\formie\models\IntegrationFormSettings
        {
            return new \verbb\formie\models\IntegrationFormSettings();
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

<?php

use craft\db\Query;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Tests\Support\UploadTestHelper;
use verbb\formie\Formie;
use verbb\formie\enums\SubmissionOperation;
use verbb\formie\events\ModifyFormIntegrationsEvent;
use verbb\formie\helpers\References;
use verbb\formie\integrations\crm\HubSpot;
use verbb\formie\jobs\TriggerIntegration;
use verbb\formie\models\IntegrationCollection;
use verbb\formie\models\IntegrationConfig;
use verbb\formie\models\IntegrationField;
use verbb\formie\services\Integrations;
use yii\base\Event;

class HubSpotUploadDeliveryFixture extends HubSpot
{
    public static array $requests = [];
    public static array $bodies = [];
    public static HandlerStack $handler;

    protected function createDeliveryHttpHandler(): callable
    {
        return self::$handler;
    }

    public function getUploadClient(): Client
    {
        return new Client(['base_uri' => 'https://8.8.8.8/', 'handler' => self::$handler]);
    }

    public function getFormsClient(): Client
    {
        return new Client(['base_uri' => 'https://8.8.8.8/', 'handler' => self::$handler]);
    }
}

function withHubSpotUploadDelivery(array $responses, callable $callback, int $fileCount = 1): void
{
    HubSpotUploadDeliveryFixture::$requests = [];
    HubSpotUploadDeliveryFixture::$bodies = [];
    HubSpotUploadDeliveryFixture::$handler = HandlerStack::create(new MockHandler($responses));
    HubSpotUploadDeliveryFixture::$handler->push(Middleware::history(HubSpotUploadDeliveryFixture::$requests));
    // Capture multipart bytes while the upload handle is still open.
    HubSpotUploadDeliveryFixture::$handler->push(Middleware::tap(function($request): void {
        $body = $request->getBody();
        $position = $body->tell();
        HubSpotUploadDeliveryFixture::$bodies[] = (string)$body;
        $body->seek($position);
    }));

    $volume = UploadTestHelper::ensureUploadVolume();
    $assets = [];

    for ($i = 1; $i <= $fileCount; $i++) {
        $assets[] = UploadTestHelper::seedAsset("hubspot-{$i}.txt", "HubSpot attachment {$i}", $volume);
    }

    $form = formie()->form()->fileUploadField('files', ['restrictFiles' => false])->create();
    $submission = formie()->submission($form)->with(['files' => array_column($assets, 'id')])->save();
    $integration = new HubSpotUploadDeliveryFixture([
        'name' => 'HubSpot', 'handle' => 'hubspotUpload', 'enabled' => true,
        'accessToken' => 'synthetic-token', 'mapToForm' => true, 'formId' => '123__abc',
        'formFieldMapping' => ['attachment' => References::field($form->getFieldByHandle('files')->reference)],
    ]);
    $config = new IntegrationConfig([
        'forms' => [new IntegrationCollection([
            'id' => '123__abc', 'name' => 'Upload form',
            'fields' => [new IntegrationField(['handle' => 'attachment', 'name' => 'Attachment', 'sourceType' => 'file'])],
        ])],
    ], $integration->getIntegrationConfigKey(), time());
    $integration->cache = ['config' => $config->toStorage()];
    $listener = function(ModifyFormIntegrationsEvent $event) use ($form, $integration): void {
        if ($event->form->id === $form->id) {
            $event->integrations[] = $integration;
        }
    };
    Event::on(Integrations::class, Integrations::EVENT_MODIFY_FORM_INTEGRATIONS, $listener);

    try {
        Formie::$plugin->getIntegrationRunner()->queueSteps($submission, [$integration->handle], SubmissionOperation::SUBMIT, ['triggerEvent' => 'submit']);
        $attempts = Formie::$plugin->getDeliveryAttempts();
        $uid = (new Query())->select('uid')->from($attempts::TABLE)->where(['submissionId' => $submission->id, 'step' => 'dispatch'])->scalar();
        $callback(new TriggerIntegration(['deliveryAttemptUid' => $uid]), $uid);
    } finally {
        Event::off(Integrations::class, Integrations::EVENT_MODIFY_FORM_INTEGRATIONS, $listener);
    }
}

function hubSpotUploadPaths(): array
{
    return array_map(fn($entry) => $entry['request']->getUri()->getPath(), HubSpotUploadDeliveryFixture::$requests);
}

function hubSpotUploadedFileResponse(int $id = 1): Response
{
    return new Response(201, [], json_encode(['id' => $id, 'url' => "https://files.example.test/attachment-{$id}.txt"]));
}

it('fails a HubSpot delivery when an upload fails and retries with the attachment', function(int|string $failure): void {
    $response = $failure === 'lost'
        ? new ConnectException('Response lost', new Request('POST', 'https://8.8.8.8/files/v3/files'))
        : new Response($failure, [], '{"message":"Upload failed"}');

    withHubSpotUploadDelivery([$response, hubSpotUploadedFileResponse(), new Response(200, [], '{}')], function(TriggerIntegration $job, string $uid): void {
        expect(fn() => $job->execute(Craft::$app->getQueue()))->toThrow(RuntimeException::class, 'Integration delivery failed.');
        expect(Formie::$plugin->getDeliveryAttempts()->get($uid)['status'])->toBe('failed')
            ->and(hubSpotUploadPaths())->toBe(['/files/v3/files']);

        $job->execute(Craft::$app->getQueue());

        expect(Formie::$plugin->getDeliveryAttempts()->get($uid)['status'])->toBe('succeeded')
            ->and(hubSpotUploadPaths())->toBe(['/files/v3/files', '/files/v3/files', '/submissions/v3/integration/submit/123/abc']);
        $payload = json_decode((string)HubSpotUploadDeliveryFixture::$requests[2]['request']->getBody(), true);
        expect($payload['fields'])->toBe([['objectTypeId' => '0-1', 'name' => 'attachment', 'value' => 'https://files.example.test/attachment-1.txt']]);
        expect(HubSpotUploadDeliveryFixture::$bodies[1])->toContain('HubSpot attachment 1');
    });
})->with([400, 429, 503, 'lost']);

it('reuses a completed HubSpot upload when the form delivery is retried', function(): void {
    withHubSpotUploadDelivery([hubSpotUploadedFileResponse(), new Response(503), new Response(200, [], '{}')], function(TriggerIntegration $job, string $uid): void {
        expect(fn() => $job->execute(Craft::$app->getQueue()))->toThrow(RuntimeException::class);

        $job->execute(Craft::$app->getQueue());

        expect(Formie::$plugin->getDeliveryAttempts()->get($uid)['status'])->toBe('succeeded')
            ->and(hubSpotUploadPaths())->toBe(['/files/v3/files', '/submissions/v3/integration/submit/123/abc', '/submissions/v3/integration/submit/123/abc']);
        $first = json_decode((string)HubSpotUploadDeliveryFixture::$requests[1]['request']->getBody(), true);
        $retry = json_decode((string)HubSpotUploadDeliveryFixture::$requests[2]['request']->getBody(), true);
        expect($retry['fields'])->toBe($first['fields'])
            ->and($retry['fields'][0]['value'])->toBe('https://files.example.test/attachment-1.txt');
    });
});

it('retains earlier HubSpot uploads when a later attachment fails', function(): void {
    withHubSpotUploadDelivery([hubSpotUploadedFileResponse(), new Response(503), hubSpotUploadedFileResponse(2), new Response(200, [], '{}')], function(TriggerIntegration $job, string $uid): void {
        expect(fn() => $job->execute(Craft::$app->getQueue()))->toThrow(RuntimeException::class);
        expect(hubSpotUploadPaths())->toBe(['/files/v3/files', '/files/v3/files']);

        $job->execute(Craft::$app->getQueue());

        expect(Formie::$plugin->getDeliveryAttempts()->get($uid)['status'])->toBe('succeeded')
            ->and(hubSpotUploadPaths())->toBe(['/files/v3/files', '/files/v3/files', '/files/v3/files', '/submissions/v3/integration/submit/123/abc']);
        $payload = json_decode((string)HubSpotUploadDeliveryFixture::$requests[3]['request']->getBody(), true);
        expect(array_column($payload['fields'], 'value'))->toBe(['https://files.example.test/attachment-1.txt', 'https://files.example.test/attachment-2.txt']);
        expect(HubSpotUploadDeliveryFixture::$bodies[2])->toContain('HubSpot attachment 2');
    }, 2);
});

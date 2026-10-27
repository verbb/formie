<?php

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Tests\Support\UploadTestHelper;
use verbb\formie\elements\Submission;
use verbb\formie\enums\IntegrationStatus;
use verbb\formie\helpers\References;
use verbb\formie\integrations\helpdesk\Freshdesk;
use verbb\formie\models\IntegrationConfig;
use verbb\formie\models\IntegrationField;
use verbb\formie\models\IntegrationResult;

class FreshdeskPayloadFixture extends Freshdesk
{
    public array $transactions = [];

    public function request(string $method, string $uri, array $options = []): mixed
    {
        $handler = HandlerStack::create(new MockHandler([
            new Response(201, ['Content-Type' => 'application/json'], '{"id":123}'),
        ]));
        $handler->push(Middleware::history($this->transactions));
        $client = new Client(['base_uri' => 'https://freshdesk.example.test/api/v2/', 'handler' => $handler]);

        return json_decode((string)$client->request($method, $uri, $options)->getBody(), true);
    }

    public function executeForTest(Submission $submission): IntegrationResult
    {
        return $this->executePayload($submission);
    }
}

function freshdeskPayloadFixture(array $mapping): FreshdeskPayloadFixture
{
    $integration = new FreshdeskPayloadFixture([
        'name' => 'Freshdesk', 'handle' => 'freshdesk', 'mapToTicket' => true, 'ticketFieldMapping' => $mapping,
    ]);
    $fields = [];
    foreach (['subject', 'description', 'email', 'status', 'priority', 'source', 'tags', 'cc_emails', 'attachments', 'custom:cf_text', 'custom:cf_number'] as $handle) {
        $fields[] = new IntegrationField([
            'handle' => $handle,
            'type' => in_array($handle, ['attachments', 'tags', 'cc_emails'], true) ? IntegrationField::TYPE_ARRAY : IntegrationField::TYPE_STRING,
        ]);
    }
    $integration->cache = ['config' => (new IntegrationConfig(['ticket' => $fields], $integration->getIntegrationConfigKey(), time()))->toStorage()];
    return $integration;
}

it('sends Freshdesk JSON objects when no uploaded attachment is available', function(string $attachmentMode): void {
    UploadTestHelper::ensureUploadVolume();
    $form = formie()->form()->singleLineTextField('subject')->fileUploadField('files', ['restrictFiles' => false])->create();
    $submission = formie()->submission($form)->with(['subject' => 'Help'])->save();
    $mapping = [
        'subject' => ['kind' => 'reference', 'value' => References::field($form->getFieldByHandle('subject')->reference)],
        'description' => ['kind' => 'text', 'value' => 'Request: ' . References::field($form->getFieldByHandle('subject')->reference)],
        'email' => ['kind' => 'literal', 'value' => 'customer@example.test'],
        'status' => '2', 'priority' => '1', 'source' => '2',
        'custom:cf_text' => ['kind' => 'literal', 'value' => 'Custom value'],
    ];
    if ($attachmentMode !== 'omitted') {
        $mapping['attachments'] = match ($attachmentMode) {
            'empty upload' => ['kind' => 'reference', 'value' => References::field($form->getFieldByHandle('files')->reference)],
            'submission' => ['kind' => 'reference', 'value' => '{submission:id}'],
            'literal' => ['kind' => 'literal', 'value' => '/tmp/not-an-upload'],
            default => ['kind' => 'literal', 'value' => ''],
        };
    }
    $integration = freshdeskPayloadFixture($mapping);
    expect($integration->executeForTest($submission)->status)->toBe(IntegrationStatus::Succeeded)
        ->and($integration->transactions)->toHaveCount(1);
    $request = $integration->transactions[0]['request'];
    expect($request->getHeaderLine('Content-Type'))->toBe('application/json')
        ->and(json_decode((string)$request->getBody(), true))->toBe([
            'subject' => 'Help', 'description' => 'Request: Help', 'email' => 'customer@example.test',
            'status' => 2, 'priority' => 1, 'source' => 2, 'custom_fields' => ['cf_text' => 'Custom value'],
        ]);
})->with(['omitted', 'empty', 'empty upload', 'submission', 'literal']);

it('sends Freshdesk uploaded bytes and structured mappings as multipart data', function(bool $legacy): void {
    $volume = UploadTestHelper::ensureUploadVolume();
    $first = UploadTestHelper::seedAsset('freshdesk-first.txt', 'first attachment contents', $volume);
    $second = UploadTestHelper::seedAsset('freshdesk-second.txt', 'second attachment contents', $volume);
    $form = formie()->form()->singleLineTextField('subject')->fileUploadField('files', ['restrictFiles' => false])->create();
    $submission = formie()->submission($form)->with(['subject' => 'Help', 'files' => [$first->id, $second->id]])->save();
    $attachmentReference = References::field($form->getFieldByHandle('files')->reference);
    $integration = freshdeskPayloadFixture([
        'subject' => ['kind' => 'reference', 'value' => References::field($form->getFieldByHandle('subject')->reference)],
        'attachments' => $legacy ? $attachmentReference : ['kind' => 'reference', 'value' => $attachmentReference],
        'tags' => ['kind' => 'literal', 'value' => ['one', 'two']],
        'custom:cf_number' => ['kind' => 'literal', 'value' => '0'],
    ]);
    expect($integration->executeForTest($submission)->status)->toBe(IntegrationStatus::Succeeded)
        ->and($integration->transactions)->toHaveCount(1);
    $request = $integration->transactions[0]['request'];
    $body = (string)$request->getBody();
    expect($request->getHeaderLine('Content-Type'))->toStartWith('multipart/form-data;')
        ->and($body)->toContain('name="subject"', 'Help', 'name="attachments[]"', 'filename="' . $first->filename . '"', 'filename="' . $second->filename . '"', 'first attachment contents', 'second attachment contents', 'name="tags[]"', 'one', 'two', 'name="custom_fields[cf_number]"', "\r\n\r\n0\r\n");

    // A reused provider must not retain files from the preceding submission.
    $empty = formie()->submission($form)->with(['subject' => 'No files'])->save();
    expect($integration->executeForTest($empty)->status)->toBe(IntegrationStatus::Succeeded)
        ->and($integration->transactions)->toHaveCount(2)
        ->and($integration->transactions[1]['request']->getHeaderLine('Content-Type'))->toBe('application/json')
        ->and(json_decode((string)$integration->transactions[1]['request']->getBody(), true))->not->toHaveKey('attachments');
})->with([false, true]);

it('builds standalone Freshdesk multipart mappings with uploads and mapping hooks', function(): void {
    $volume = UploadTestHelper::ensureUploadVolume();
    $asset = UploadTestHelper::seedAsset('freshdesk-direct.txt', 'standalone attachment', $volume);
    $form = formie()->form()->singleLineTextField('subject')->fileUploadField('files', ['restrictFiles' => false])->create();
    $submission = formie()->submission($form)->with(['subject' => 'Original', 'files' => [$asset->id]])->save();
    $mapping = [
        'subject' => ['kind' => 'text', 'value' => 'Request: ' . References::field($form->getFieldByHandle('subject')->reference)],
        'attachments' => ['kind' => 'reference', 'value' => References::field($form->getFieldByHandle('files')->reference)],
    ];
    $integration = freshdeskPayloadFixture($mapping);
    $integration->on(Freshdesk::EVENT_MODIFY_FIELD_MAPPING_VALUE, function(\verbb\formie\events\ModifyFieldIntegrationValueEvent $event): void {
        $event->value .= '!';
    });
    $integration->on(Freshdesk::EVENT_MODIFY_FIELD_MAPPING_VALUES, function(\verbb\formie\events\ModifyFieldIntegrationValuesEvent $event): void {
        $event->fieldValues[] = ['name' => 'extra', 'contents' => 'hook'];
    });
    $parts = $integration->getFieldMappingMultipartValues($submission, $mapping, 'ticket');
    expect($parts[0])->toBe(['name' => 'subject', 'contents' => 'Request: Original!'])
        ->and($parts[1]['name'])->toBe('attachments[]')
        ->and(stream_get_contents($parts[1]['contents']))->toBe('standalone attachment')
        ->and($parts[2])->toBe(['name' => 'extra', 'contents' => 'hook']);
});

it('sends ordinary legacy Freshdesk ticket mappings without multipart wrappers or empty custom fields', function(): void {
    $form = formie()->form()->singleLineTextField('subject')->create();
    $submission = formie()->submission($form)->with(['subject' => 'Legacy ticket'])->save();
    $integration = freshdeskPayloadFixture(['subject' => References::field($form->getFieldByHandle('subject')->reference), 'status' => '2']);
    expect($integration->executeForTest($submission)->status)->toBe(IntegrationStatus::Succeeded)
        ->and(json_decode((string)$integration->transactions[0]['request']->getBody(), true))->toBe(['subject' => 'Legacy ticket', 'status' => 2]);
});

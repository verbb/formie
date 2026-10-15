<?php

use verbb\formie\elements\Submission;
use verbb\formie\enums\IntegrationStatus;
use verbb\formie\helpers\References;
use verbb\formie\integrations\helpdesk\Zendesk;
use verbb\formie\models\IntegrationConfig;
use verbb\formie\models\IntegrationField;
use verbb\formie\models\IntegrationResult;

class ZendeskRequesterDeliveryFixture extends Zendesk
{
    public array $deliveries = [];

    public function deliverPayload(Submission $submission, string $endpoint, mixed $payload, string $method = 'POST', string $contentType = 'json'): mixed
    {
        $this->deliveries[] = compact('endpoint', 'payload', 'method', 'contentType');

        return ['ticket' => ['id' => 123]];
    }

    public function executeForTest(Submission $submission): IntegrationResult
    {
        return $this->executePayload($submission);
    }
}

it('uses the Zendesk anonymous fallback only when the requester name is blank', function(?string $name, bool $mapName, string $expected): void {
    $form = formie()->form()->singleLineTextField('fullName')->emailField('email')->create();
    $saved = formie()->submission($form)->with(['fullName' => $name, 'email' => 'requester@example.test'])->save();
    $submission = Submission::find()->id($saved->id)->status(null)->one();
    $mapping = [
        'subject' => 'Support request',
        'description' => 'Please help with this request.',
        'requester_email' => References::field($form->getFieldByHandle('email')->reference),
    ];

    if ($mapName) {
        $mapping['requester_name'] = References::field($form->getFieldByHandle('fullName')->reference);
    }

    $integration = new ZendeskRequesterDeliveryFixture([
        'name' => 'Zendesk',
        'handle' => 'zendesk',
        'mapToTicket' => true,
        'ticketFieldMapping' => $mapping,
    ]);
    $integration->cache = ['config' => (new IntegrationConfig([
        'ticket' => array_map(fn(string $handle) => new IntegrationField(['handle' => $handle]), [
            'subject', 'description', 'requester_name', 'requester_email',
        ]),
    ], $integration->getIntegrationConfigKey(), time()))->toStorage()];

    $result = $integration->executeForTest($submission);

    expect($result->status)->toBe(IntegrationStatus::Succeeded)
        ->and($integration->deliveries)->toHaveCount(1)
        ->and($integration->deliveries[0])->toBe([
            'endpoint' => 'tickets.json',
            'payload' => ['ticket' => [
                'subject' => 'Support request',
                'comment' => ['body' => 'Please help with this request.'],
                'priority' => null,
                'tags' => [],
                'requester' => ['name' => $expected, 'email' => 'requester@example.test'],
            ]],
            'method' => 'POST',
            'contentType' => 'json',
        ]);
})->with([
    'empty mapped field' => ['', true, 'Anonymous'],
    'whitespace mapped field' => [" \t\n", true, 'Anonymous'],
    'null mapped field' => [null, true, 'Anonymous'],
    'unmapped name' => ['Do not infer a name', false, 'Anonymous'],
    'provided name' => ['Avery Example', true, 'Avery Example'],
    'zero is not blank' => ['0', true, '0'],
]);

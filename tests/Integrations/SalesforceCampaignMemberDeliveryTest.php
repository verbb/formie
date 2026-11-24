<?php

declare(strict_types=1);

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use League\OAuth2\Client\Token\AccessToken;
use verbb\auth\models\Token;
use verbb\formie\Formie;
use verbb\formie\enums\IntegrationStatus;
use verbb\formie\integrations\crm\Salesforce;

class SalesforceCampaignMemberDeliveryFixture extends Salesforce
{
    public mixed $transport;

    public function getToken(): ?Token
    {
        $token = new Token();
        $token->setToken(new AccessToken(['access_token' => 'synthetic-salesforce-token', 'expires' => time() + 3600]));
        $token->values = ['instance_url' => 'https://8.8.8.8'];

        return $token;
    }

    public function validate($attributeNames = null, $clearErrors = true): bool
    {
        return true;
    }

    public function getFieldMappingValues($submission, $fieldMapping, $fieldSettings = null)
    {
        return match ($fieldSettings) {
            'account' => ['Name' => 'Example Pty Ltd'],
            'contact' => ['LastName' => 'Example', 'Email' => 'contact@example.test'],
            'lead' => ['LastName' => 'Example', 'Company' => 'Example Pty Ltd', 'Email' => 'contact@example.test'],
            'campaignMember' => ['CampaignId' => '701EXAMPLE', 'Status' => 'Responded'],
            default => [],
        };
    }

    protected function createDeliveryHttpHandler(): callable
    {
        return $this->transport;
    }
}

function salesforceCampaignMemberJson(int $status, array $body): Response
{
    return new Response($status, ['Content-Type' => 'application/json'], json_encode($body, JSON_THROW_ON_ERROR));
}

it('delivers a Salesforce Campaign Member whether or not a Lead was created', function(string $lead, array $leadResponses, array $leadPaths, ?string $leadId): void {
    $history = [];
    $transport = HandlerStack::create(new MockHandler([
        salesforceCampaignMemberJson(201, ['id' => '001ACCOUNT', 'success' => true, 'errors' => []]),
        salesforceCampaignMemberJson(200, ['totalSize' => 0, 'done' => true, 'records' => []]),
        salesforceCampaignMemberJson(201, ['id' => '003CONTACT', 'success' => true, 'errors' => []]),
        salesforceCampaignMemberJson(200, ['totalSize' => 1, 'done' => true, 'records' => [['Id' => '003CONTACT', 'OwnerId' => '005OWNER']]]),
        ...$leadResponses,
        salesforceCampaignMemberJson(201, ['id' => '00vMEMBER', 'success' => true, 'errors' => []]),
    ]));
    $transport->push(Middleware::history($history));
    $integration = new SalesforceCampaignMemberDeliveryFixture([
        'name' => 'Salesforce', 'handle' => 'salesforceCampaignMember', 'enabled' => true,
        'mapToAccount' => true, 'mapToContact' => true, 'mapToLead' => $lead !== 'off', 'mapToCampaignMember' => true,
        'duplicateLeadTask' => true, 'duplicateLeadTaskSubject' => 'Duplicate lead',
    ]);
    $integration->transport = $transport;
    $form = formie()->form()->singleLineTextField('name')->create();
    $submission = formie()->submission($form)->save();

    // Craft throws PHP errors in devMode, so a read of an unassigned variable fails the delivery there.
    set_error_handler(static function(int $level, string $message, string $file, int $line): bool {
        if (!(error_reporting() & $level) || ($level & (E_DEPRECATED | E_USER_DEPRECATED))) {
            return false;
        }

        throw new ErrorException($message, 0, $level, $file, $line);
    });

    try {
        $result = Formie::$plugin->getIntegrationRunner()->runIntegration($integration, $submission, 'salesforce-campaign-member-' . $lead, 'queued');
    } finally {
        restore_error_handler();
    }

    $paths = array_map(fn($entry) => $entry['request']->getMethod() . ' ' . $entry['request']->getUri()->getPath(), $history);
    $memberPayload = array_merge(
        ['CampaignId' => '701EXAMPLE', 'Status' => 'Responded', 'ContactId' => '003CONTACT', 'AccountId' => '001ACCOUNT'],
        $leadId ? ['LeadId' => $leadId] : [],
    );

    expect($result->status)->toBe(IntegrationStatus::Succeeded)
        ->and($paths)->toBe([
            'POST /services/data/v49.0/sobjects/Account',
            'GET /services/data/v49.0/query',
            'POST /services/data/v49.0/sobjects/Contact',
            'GET /services/data/v49.0/query',
            ...$leadPaths,
            'POST /services/data/v49.0/sobjects/CampaignMember',
        ])
        ->and(json_decode((string)end($history)['request']->getBody(), true))->toBe($memberPayload);
})->with([
    'lead mapping off' => ['off', [], [], null],
    'duplicate lead handled with a task' => [
        'duplicate',
        [
            salesforceCampaignMemberJson(400, [[
                'message' => 'Use one of these records?', 'errorCode' => 'DUPLICATES_DETECTED',
                'duplicateResult' => ['matchResults' => [['matchRecords' => [['record' => ['Id' => '00QDUPLICATE']]]]]],
            ]]),
            salesforceCampaignMemberJson(201, ['id' => '00TTASK', 'success' => true, 'errors' => []]),
        ],
        ['POST /services/data/v49.0/sobjects/Lead', 'POST /services/data/v49.0/sobjects/Task'],
        null,
    ],
    'lead created' => [
        'created',
        [salesforceCampaignMemberJson(201, ['id' => '00QLEAD', 'success' => true, 'errors' => []])],
        ['POST /services/data/v49.0/sobjects/Lead'],
        '00QLEAD',
    ],
]);

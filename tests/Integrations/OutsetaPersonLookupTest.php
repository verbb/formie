<?php

declare(strict_types=1);

use verbb\formie\elements\Submission;
use verbb\formie\enums\IntegrationStatus;
use verbb\formie\helpers\References;
use verbb\formie\integrations\crm\Outseta;
use verbb\formie\models\IntegrationConfig;
use verbb\formie\models\IntegrationField;
use verbb\formie\models\IntegrationResult;

class OutsetaPersonLookupFixture extends Outseta
{
    public const PEOPLE = [
        ['Uid' => 'first-person', 'Email' => 'first@example.test'],
        ['Uid' => 'jane-person', 'Email' => 'jane@example.test'],
    ];

    public array $requests = [];

    public function request(string $method, string $uri, array $options = []): mixed
    {
        $this->requests[] = $method . ' ' . $uri;

        if ($method === 'GET') {
            // Like Outseta, an unfiltered list returns every person, the oldest first.
            $email = $options['query']['Email'] ?? null;

            return ['items' => array_values(array_filter(
                self::PEOPLE,
                static fn(array $person) => $email === null || strcasecmp($person['Email'], (string)$email) === 0,
            ))];
        }

        return ['Uid' => 'saved-person'];
    }

    public function executeForTest(Submission $submission): IntegrationResult
    {
        return $this->executePayload($submission);
    }

    public function cacheConfig(): void
    {
        $config = ['people' => [new IntegrationField(['handle' => 'Email', 'name' => 'Email', 'required' => true])]];
        $this->cache = ['config' => (new IntegrationConfig($config, $this->getIntegrationConfigKey(), time()))->toStorage()];
    }
}

function outsetaLookupRequests(string $email): array
{
    $form = formie()->form()->emailField('email')->create();
    $saved = formie()->submission($form)->with(['email' => $email])->save();
    $submission = Submission::find()->id($saved->id)->status(null)->one();

    $integration = new OutsetaPersonLookupFixture([
        'name' => 'Outseta',
        'handle' => 'outseta',
        'mapToPeople' => true,
        'peopleFieldMapping' => ['Email' => References::field($form->getFieldByHandle('email')->reference)],
    ]);
    $integration->cacheConfig();

    expect($integration->executeForTest($submission)->status)->toBe(IntegrationStatus::Succeeded);

    return $integration->requests;
}

it('creates a new Outseta person instead of updating whoever is first in the account', function (): void {
    expect(outsetaLookupRequests('new@example.test'))->toBe(['GET people', 'POST people']);
});

it('updates the Outseta person with the submitted email', function (): void {
    expect(outsetaLookupRequests('Jane@example.test'))->toBe(['GET people', 'PUT people/jane-person']);
});

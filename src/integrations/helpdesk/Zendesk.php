<?php
namespace verbb\formie\integrations\helpdesk;

use verbb\formie\base\Integration;
use verbb\formie\base\HelpDesk;
use verbb\formie\elements\Submission;
use verbb\formie\models\IntegrationField;
use verbb\formie\models\IntegrationFormSettings;

use Craft;
use craft\helpers\App;
use craft\helpers\ArrayHelper;
use craft\helpers\Json;
use craft\helpers\StringHelper;

use GuzzleHttp\Client;

use Throwable;

class Zendesk extends HelpDesk
{
    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('formie', 'Zendesk');
    }


    // Properties
    // =========================================================================

    public ?string $domain = null;
    public ?string $username = null;
    public ?string $apiKey = null;
    public bool $mapToTicket = false;
    public ?array $ticketFieldMapping = null;


    // Public Methods
    // =========================================================================

    public function getDescription(): string
    {
        return Craft::t('formie', 'Send your form content to Zendesk.');
    }

    public function fetchFormSettings(): IntegrationFormSettings
    {
        $settings = [];

        try {
            $ticketFields = [
                new IntegrationField([
                    'handle' => 'subject',
                    'name' => Craft::t('formie', 'Subject'),
                    'required' => true,
                ]),
                new IntegrationField([
                    'handle' => 'description',
                    'name' => Craft::t('formie', 'Description'),
                    'required' => true,
                ]),
                new IntegrationField([
                    'handle' => 'priority',
                    'name' => Craft::t('formie', 'Priority'),
                    'options' => [
                        'label' => Craft::t('formie', 'Priority'),
                        'options' => [
                            ['label' => Craft::t('formie', 'Low'), 'value' => 'low'],
                            ['label' => Craft::t('formie', 'Normal'), 'value' => 'normal'],
                            ['label' => Craft::t('formie', 'High'), 'value' => 'high'],
                            ['label' => Craft::t('formie', 'Urgent'), 'value' => 'urgent'],
                        ],
                    ],
                ]),
                new IntegrationField([
                    'handle' => 'tags',
                    'name' => Craft::t('formie', 'Tags'),
                ]),
                new IntegrationField([
                    'handle' => 'requester_name',
                    'name' => Craft::t('formie', 'Requester Name'),
                ]),
                new IntegrationField([
                    'handle' => 'requester_email',
                    'name' => Craft::t('formie', 'Requester Email'),
                ]),
            ];

            $settings = [
                'ticket' => $ticketFields,
            ];
        } catch (Throwable $e) {
            Integration::apiError($this, $e);
        }

        return new IntegrationFormSettings($settings);
    }

    public function sendPayload(Submission $submission): bool
    {
        try {
            if ($this->mapToTicket) {
                $ticketValues = $this->getFieldMappingValues($submission, $this->ticketFieldMapping, 'ticket');

                $subject = ArrayHelper::remove($ticketValues, 'subject') ?? 'New Form Submission';
                $description = ArrayHelper::remove($ticketValues, 'description') ?? '';
                $priority = ArrayHelper::remove($ticketValues, 'priority') ?? null;
                $tags = ArrayHelper::remove($ticketValues, 'tags') ?? [];
                $requesterName = ArrayHelper::remove($ticketValues, 'requester_name') ?? 'Anonymous';
                $requesterEmail = ArrayHelper::remove($ticketValues, 'requester_email') ?? 'anonymous@example.com';

                $tags = is_array($tags) ? $tags : array_filter(array_map('trim', explode(',', $tags)));

                $ticketPayload = [
                    'ticket' => [
                        'subject' => $subject,
                        'comment' => [
                            'body' => $description,
                        ],
                        'priority' => $priority,
                        'tags' => $tags,
                        'requester' => [
                            'name' => $requesterName,
                            'email' => $requesterEmail,
                        ],
                    ],
                ];

                $response = $this->deliverPayload($submission, 'tickets.json', $ticketPayload);

                if ($response === false) {
                    return true;
                }

                $ticketId = $response['ticket']['id'] ?? '';

                if (!$ticketId) {
                    Integration::error($this, Craft::t('formie', 'Missing return “ticketId” {response}. Sent payload {payload}', [
                        'response' => Json::encode($response),
                        'payload' => Json::encode($ticketPayload),
                    ]), true);

                    return false;
                }
            }
        } catch (Throwable $e) {
            Integration::apiError($this, $e);

            return false;
        }

        return true;
    }

    public function fetchConnection(): bool
    {
        try {
            $response = $this->request('GET', 'users/me.json');
        } catch (Throwable $e) {
            Integration::apiError($this, $e);

            return false;
        }

        return true;
    }

    
    // Protected Methods
    // =========================================================================

    protected function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['domain', 'username', 'apiKey'], 'required'];

        $ticket = $this->getFormSettingValue('ticket');

        // Validate the following when saving form settings
        $rules[] = [
            ['ticketFieldMapping'], 'validateFieldMapping', 'params' => $ticket, 'when' => function($model) {
                return $model->enabled && $model->mapToTicket;
            }, 'on' => [Integration::SCENARIO_FORM],
        ];

        return $rules;
    }

    protected function defineClient(): Client
    {
        $url = rtrim(App::parseEnv($this->domain), '/');
        $username = App::parseEnv($this->username);
        $apiKey = App::parseEnv($this->apiKey);

        return Craft::createGuzzleClient([
            'base_uri' => "$url/api/v2/",
            'auth' => ["{$username}/token", $apiKey],
        ]);
    }
}
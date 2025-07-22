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

class Gorgias extends HelpDesk
{
    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('formie', 'Gorgias');
    }


    // Properties
    // =========================================================================

    public ?string $apiUrl = null;
    public ?string $username = null;
    public ?string $apiKey = null;
    public bool $mapToTicket = false;
    public ?array $ticketFieldMapping = null;


    // Public Methods
    // =========================================================================

    public function getDescription(): string
    {
        return Craft::t('formie', 'Send your form content to Gorgias.');
    }

    public function fetchFormSettings(): IntegrationFormSettings
    {
        $settings = [];

        try {
            $response = $this->request('GET', 'custom-fields', [
                'query' => [
                    'object_type' => 'Ticket',
                ],
            ]);

            $fields = $response['data'] ?? [];

            $ticketFields = array_merge([
                new IntegrationField([
                    'handle' => 'message',
                    'name' => Craft::t('formie', 'Message'),
                    'required' => true,
                ]),
                new IntegrationField([
                    'handle' => 'channel',
                    'name' => Craft::t('formie', 'Channel'),
                ]),
                new IntegrationField([
                    'handle' => 'tags',
                    'name' => Craft::t('formie', 'Tags'),
                ]),
                new IntegrationField([
                    'handle' => 'name',
                    'name' => Craft::t('formie', 'Name'),
                ]),
                new IntegrationField([
                    'handle' => 'email',
                    'name' => Craft::t('formie', 'Email'),
                ]),
                new IntegrationField([
                    'handle' => 'subject',
                    'name' => Craft::t('formie', 'Subject'),
                ]),
            ], $this->_getCustomFields($fields));

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

                $ticketPayload = $this->_prepCustomFields($ticketValues);

                $subject = ArrayHelper::remove($ticketPayload, 'subject') ?? 'New Form Submission';
                $name = ArrayHelper::remove($ticketPayload, 'name') ?? 'Anonymous';
                $email = ArrayHelper::remove($ticketPayload, 'email') ?? 'no-reply@example.com';
                $message = ArrayHelper::remove($ticketPayload, 'message');
                $channel = ArrayHelper::remove($ticketPayload, 'channel') ?? 'contact_form';
                $tags = ArrayHelper::remove($ticketPayload, 'tags') ?? [];

                $ticketPayload['from_agent'] = false;
                $ticketPayload['channel'] = $channel;
                $ticketPayload['tags'] = is_array($tags) ? $tags : array_filter(array_map('trim', explode(',', $tags)));

                $ticketPayload['messages'] = [[
                    'body_text' => $message,
                    'via' => 'form',
                    'channel' => $channel,
                    'from_agent' => false,
                    'source' => [
                        'type' => 'form',
                        'from' => [
                            'address' => $email,
                            'name' => $name,
                        ],
                    ],
                    'sender' => [
                        'email' => $email,
                        'name' => $name,
                    ],
                ]];

                $response = $this->deliverPayload($submission, 'tickets', $ticketPayload);

                if ($response === false) {
                    return true;
                }

                $ticketId = $response['id'] ?? '';

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
            $response = $this->request('GET', 'account');
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

        $rules[] = [['apiUrl', 'username', 'apiKey'], 'required'];

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
        $url = rtrim(App::parseEnv($this->apiUrl), '/');
        $username = App::parseEnv($this->username);
        $password = App::parseEnv($this->apiKey);

        return Craft::createGuzzleClient([
            'base_uri' => "$url/",
            'auth' => [$username, $password],
        ]);
    }


    // Private Methods
    // =========================================================================

    private function _convertFieldType(string $fieldType): string
    {
        $fieldTypes = [
            // 'boolean' => IntegrationField::TYPE_BOOLEAN,
            // 'legal' => IntegrationField::TYPE_BOOLEAN,
            // 'multi_choice' => IntegrationField::TYPE_ARRAY,
        ];

        return $fieldTypes[$fieldType] ?? IntegrationField::TYPE_STRING;
    }

    private function _getCustomFields(array $fields): array
    {
        $customFields = [];

        foreach ($fields as $field) {
            $options = [];

            $fieldOptions = $field['definition']['input_settings']['choices'] ?? [];

            foreach ($fieldOptions as $key => $fieldOption) {
                $options[] = [
                    'label' => $fieldOption,
                    'value' => $fieldOption,
                ];
            }

            if ($options) {
                $options = [
                    'label' => $field['label'],
                    'options' => $options,
                ];
            }

            $customFields[] = new IntegrationField([
                'handle' => 'custom:' . $field['id'],
                'name' => $field['label'],
                'type' => $this->_convertFieldType($field['definition']['data_type']),
                'sourceType' => $field['definition']['data_type'],
                'required' => $field['required'],
                'options' => $options,
            ]);
        }

        return $customFields;
    }

    private function _prepCustomFields(array $fields): array
    {
        $payload = $fields;

        foreach ($payload as $key => $value) {
            if (StringHelper::startsWith($key, 'custom:')) {
                ArrayHelper::remove($payload, $key);

                $payload['custom_fields'][] = [
                    'id' => str_replace('custom:', '', $key),
                    'value' => $value,
                ];
            }
        }

        return $payload;
    }
}
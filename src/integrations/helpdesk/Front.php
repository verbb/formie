<?php
namespace verbb\formie\integrations\helpdesk;

use verbb\formie\base\Integration;
use verbb\formie\base\HelpDesk;
use verbb\formie\elements\Submission;
use verbb\formie\helpers\RichTextHelper;
use verbb\formie\models\IntegrationField;
use verbb\formie\models\IntegrationFormSettings;

use Craft;
use craft\helpers\App;
use craft\helpers\ArrayHelper;
use craft\helpers\Json;
use craft\helpers\StringHelper;

use GuzzleHttp\Client;

use League\HTMLToMarkdown\HtmlConverter;

use Throwable;

use verbb\auth\base\OAuthProviderInterface;
use verbb\auth\models\Token;
use verbb\auth\providers\Front as FrontProvider;

class Front extends HelpDesk implements OAuthProviderInterface
{
    // Static Methods
    // =========================================================================

    public static function supportsOAuthConnection(): bool
    {
        return true;
    }

    public static function getOAuthProviderClass(): string
    {
        return FrontProvider::class;
    }

    public static function displayName(): string
    {
        return Craft::t('formie', 'Front');
    }


    // Properties
    // =========================================================================

    public ?string $message = null;
    public bool $mapToMessage = false;
    public ?array $messageFieldMapping = null;


    // Public Methods
    // =========================================================================

    public function getDescription(): string
    {
        return Craft::t('formie', 'Send your form content to Front.');
    }

    public function fetchFormSettings(): IntegrationFormSettings
    {
        $settings = [];

        try {
            $response = $this->request('GET', 'channels');
            $channels = $response['_results'] ?? [];

            $channelOptions = [];

            foreach ($channels as $channel) {
                $channelOptions[] = [
                    'label' => $channel['name'],
                    'value' => $channel['id'],
                ];
            }

            $messageFields = [
                new IntegrationField([
                    'handle' => 'channelId',
                    'name' => Craft::t('formie', 'Channel'),
                    'required' => true,
                    'options' => [
                        'label' => Craft::t('formie', 'Channel'),
                        'options' => $channelOptions,
                    ],
                ]),
                new IntegrationField([
                    'handle' => 'subject',
                    'name' => Craft::t('formie', 'Subject'),
                    'required' => true,
                ]),
                new IntegrationField([
                    'handle' => 'email',
                    'name' => Craft::t('formie', 'Sender Email'),
                    'required' => true,
                ]),
                new IntegrationField([
                    'handle' => 'name',
                    'name' => Craft::t('formie', 'Sender Name'),
                ]),
            ];

            $settings = [
                'message' => $messageFields,
            ];
        } catch (Throwable $e) {
            Integration::apiError($this, $e);
        }

        return new IntegrationFormSettings($settings);
    }

    public function sendPayload(Submission $submission): bool
    {
        try {
            if ($this->mapToMessage) {
                $messageValues = $this->getFieldMappingValues($submission, $this->messageFieldMapping, 'message');

                $payload = $messageValues;

                $channelId = ArrayHelper::remove($payload, 'channelId');
                $email = ArrayHelper::remove($payload, 'email');
                $name = ArrayHelper::remove($payload, 'name');

                $payload['sender'] = [
                    'handle' => $email,
                    'name' => $name,
                ];

                $payload['body'] = $this->_renderMessage($submission);

                $response = $this->deliverPayload($submission, "channels/{$channelId}/incoming_messages", $payload);

                if ($response === false) {
                    return true;
                }

                $messageId = $response['message_uid'] ?? '';

                if (!$ticketId) {
                    Integration::error($this, Craft::t('formie', 'Missing return “messageId” {response}. Sent payload {payload}', [
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

    
    // Protected Methods
    // =========================================================================

    protected function defineRules(): array
    {
        $rules = parent::defineRules();

        // Validate the following when saving form settings
        $rules[] = [['message'], 'required', 'on' => [Integration::SCENARIO_FORM]];

        return $rules;
    }


    // Private Methods
    // =========================================================================

    private function _convertFieldType(string $fieldType): string
    {
        $fieldTypes = [
            'boolean' => IntegrationField::TYPE_BOOLEAN,
            'datetime' => IntegrationField::TYPE_DATETIME,
            'number' => IntegrationField::TYPE_NUMBER,
        ];

        return $fieldTypes[$fieldType] ?? IntegrationField::TYPE_STRING;
    }

    private function _getCustomFields(array $fields): array
    {
        $customFields = [];

        foreach ($fields as $field) {
            $customFields[] = new IntegrationField([
                'handle' => 'custom:' . $field['id'],
                'name' => $field['name'],
                'type' => $this->_convertFieldType($field['type']),
                'sourceType' => $field['type'],
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

    private function _renderMessage($submission): array|string
    {
        $html = RichTextHelper::getHtmlContent($this->message, $submission, false);

        $converter = new HtmlConverter(['strip_tags' => true]);
        $markdown = $converter->convert($html);

        return $markdown;
    }
}
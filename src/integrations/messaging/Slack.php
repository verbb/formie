<?php
namespace verbb\formie\integrations\messaging;

use verbb\formie\Formie;
use verbb\formie\base\FormInterface;
use verbb\formie\base\Integration;
use verbb\formie\base\Messaging;
use verbb\formie\elements\Submission;
use verbb\formie\helpers\RichTextHelper;
use verbb\formie\helpers\SchemaHelper;
use verbb\formie\models\IntegrationFormSettings;

use Craft;
use craft\helpers\App;
use craft\helpers\Json;

use League\HTMLToMarkdown\HtmlConverter;

use Throwable;

use verbb\auth\base\OAuthProviderInterface;
use verbb\auth\models\Token;
use verbb\auth\providers\Slack as SlackProvider;

class Slack extends Messaging implements OAuthProviderInterface
{
    // Constants
    // =========================================================================

    public const TYPE_PUBLIC = 'public';
    public const TYPE_DM = 'directMessage';
    public const TYPE_WEBHOOK = 'webhook';


    // Static Methods
    // =========================================================================

    public static function supportsOAuthConnection(): bool
    {
        return true;
    }

    public static function getOAuthProviderClass(): string
    {
        return SlackProvider::class;
    }

    public static function displayName(): string
    {
        return 'Slack';
    }
    

    // Properties
    // =========================================================================

    public ?string $channelType = null;
    public ?string $userId = null;
    public ?string $channelId = null;
    public ?string $message = null;
    public ?string $webhook = null;


    // Public Methods
    // =========================================================================

    public function getAuthorizationUrlOptions(): array
    {
        $options = parent::getAuthorizationUrlOptions();
        $options['granular_bot_scope'] = false;

        $options['scope'] = [
            'channels:read',
            'channels:write',
            'chat:write:bot',
            'groups:read',
            'groups:write',
            'users:read',
        ];
        
        return $options;
    }

    public function getDescription(): string
    {
        return Craft::t('formie', 'Send your form content to Slack.');
    }
    
    public function fetchFormSettings(): IntegrationFormSettings
    {
        $settings = [];

        try {
            $channels = $this->_getPaginated('conversations.list', 'channels', [
                'exclude_archived' => true,
                'types' => 'public_channel',
            ], 200);

            $members = $this->_getPaginated('users.list', 'members', [], 200);

            // Sort the results alphabetically
            $sort = function(array $a, array $b): int {
                return strtolower($a['name']) <=> strtolower($b['name']);
            };
            usort($channels, $sort);
            usort($members, $sort);

            $settings = [
                'channels' => $channels,
                'members' => $members,
            ];
        } catch (Throwable $e) {
            Integration::apiError($this, $e);
        }

        return new IntegrationFormSettings($settings);
    }

    public function sendPayload(Submission $submission): bool
    {
        try {
            if ($this->channelType === self::TYPE_WEBHOOK) {
                $payload = [
                    'text' => $this->_renderMessage($submission),
                ];

                $response = $this->deliverPayload($submission, $this->webhook, $payload);
            } else {

                $channel = null;

                if ($this->channelType === self::TYPE_PUBLIC) {
                    $channel = $this->channelId;
                } else if ($this->channelType === self::TYPE_DM) {
                    $channel = $this->userId;
                }

                if (!$channel) {
                    Integration::error($this, Craft::t('formie', '“channel” not configured.'), true);

                    return false;
                }

                $payload = [
                    'channel' => $channel,
                    'parse' => 'full',
                    'text' => $this->_renderMessage($submission),
                ];

                $response = $this->deliverPayload($submission, 'chat.postMessage', $payload);

                if ($response === false) {
                    return true;
                }

                $isOkay = $response['ok'] ?? '';

                if (!$isOkay) {
                    Integration::error($this, Craft::t('formie', 'Response returned “not ok” {response}', [
                        'response' => Json::encode($response),
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
        $rules[] = [['channelType', 'message'], 'required', 'on' => [Integration::SCENARIO_FORM]];

        $rules[] = [
            ['userId'], 'required', 'when' => function($model) {
                return $model->enabled && $model->channelType === self::TYPE_DM;
            }, 'on' => [Integration::SCENARIO_FORM],
        ];

        $rules[] = [
            ['channelId'], 'required', 'when' => function($model) {
                return $model->enabled && $model->channelType === self::TYPE_PUBLIC;
            }, 'on' => [Integration::SCENARIO_FORM],
        ];

        $rules[] = [
            ['webhook'], 'required', 'when' => function($model) {
                return $model->enabled && $model->channelType === self::TYPE_WEBHOOK;
            }, 'on' => [Integration::SCENARIO_FORM],
        ];

        return $rules;
    }

    protected function defineFormSettingsSchema(FormInterface $form): array
    {
        $schema = parent::defineFormSettingsSchema($form);
        $schema[] = SchemaHelper::selectField([
            'label' => Craft::t('formie', 'Channel Type'),
            'instructions' => Craft::t('formie', 'Select what type of channel {name} will send the message to.', ['name' => $this->displayName()]),
            'name' => 'channelType',
            'required' => true,
            'options' => [
                ['label' => Craft::t('formie', 'Select an option'), 'value' => ''],
                ['label' => Craft::t('formie', 'Public Channel'), 'value' => self::TYPE_PUBLIC],
                ['label' => Craft::t('formie', 'Direct Message'), 'value' => self::TYPE_DM],
                ['label' => Craft::t('formie', 'Webhook'), 'value' => self::TYPE_WEBHOOK],
            ],
        ]);
        $schema[] = SchemaHelper::comboboxField([
            'label' => Craft::t('formie', 'Channel'),
            'instructions' => Craft::t('formie', 'Select which {name} channel to post a message to.', ['name' => $this->displayName()]),
            'name' => 'channelId',
            'if' => 'channelType == "public"',
            'placeholder' => Craft::t('formie', 'Select an option'),
            'options' => [], // Populated from fetchFormSettings (channels)
        ]);
        $schema[] = SchemaHelper::comboboxField([
            'label' => Craft::t('app', 'User'),
            'instructions' => Craft::t('formie', 'Select which {name} user to post a message to.', ['name' => $this->displayName()]),
            'name' => 'userId',
            'if' => 'channelType == "directMessage"',
            'placeholder' => Craft::t('formie', 'Select an option'),
            'options' => [], // Populated from fetchFormSettings (members)
        ]);
        $schema[] = SchemaHelper::textField([
            'label' => Craft::t('formie', 'Webhook URL'),
            'instructions' => Craft::t('formie', 'Enter the {name} webhook URL that will be triggered.', ['name' => $this->displayName()]),
            'name' => 'webhook',
            'if' => 'channelType == "webhook"',
            'required' => true,
        ]);
        $schema[] = SchemaHelper::richTextField([
            'label' => Craft::t('formie', 'Message'),
            'instructions' => Craft::t('formie', 'This text will be sent to {name}.', ['name' => $this->displayName()]),
            'name' => 'message',
            'required' => true,
        ]);

        return $schema;
    }
    

    // Private Methods
    // =========================================================================

    private function _renderMessage($submission): array|string
    {
        $html = RichTextHelper::getHtmlContent($this->message, $submission, false);

        $converter = new HtmlConverter(['strip_tags' => true]);
        $markdown = $converter->convert($html);

        // Some extra work to get it to play with Slack's mrkdwn
        return str_replace(['*', '__'], ['_', '*'], $markdown);
    }

    private function _getPaginated($endpoint, $collection, $params, $limit = 100, $cursor = null, $items = []): array
    {
        $response = $this->request('GET', $endpoint, [
            'query' => array_merge($params, [
                'limit' => $limit,
                'cursor' => $cursor,
            ]),
        ]);

        $newItems = $response[$collection] ?? [];
        $cursor = $response['response_metadata']['next_cursor'] ?? null;

        $items = array_merge($items, $newItems);

        if ($cursor) {
            $items = $this->_getPaginated($endpoint, $collection, $params, $limit, $cursor, $items);
        }

        return $items;
    }
}
<?php
namespace verbb\formie\integrations\messaging;

use verbb\formie\Formie;
use verbb\formie\base\Integration;
use verbb\formie\base\Messaging;
use verbb\formie\elements\Submission;
use verbb\formie\helpers\RichTextHelper;
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
        return Craft::t('formie', 'Slack');
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

                $response = $this->deliverPayloadRequest($submission, $this->webhook, $payload);
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
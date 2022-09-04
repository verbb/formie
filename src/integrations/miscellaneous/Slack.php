<?php
namespace verbb\formie\integrations\miscellaneous;

use verbb\formie\Formie;
use verbb\formie\base\Integration;
use verbb\formie\base\Miscellaneous;
use verbb\formie\elements\Submission;
use verbb\formie\helpers\RichTextHelper;
use verbb\formie\models\IntegrationFormSettings;

use Craft;
use craft\helpers\App;
use craft\helpers\Json;

use League\HTMLToMarkdown\HtmlConverter;

use Throwable;

use GuzzleHttp\Client;

class Slack extends Miscellaneous
{
    // Constants
    // =========================================================================

    public const TYPE_PUBLIC = 'public';
    public const TYPE_DM = 'directMessage';
    public const TYPE_WEBHOOK = 'webhook';


    // Static Methods
    // =========================================================================

    public static function supportsOauthConnection(): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public static function displayName(): string
    {
        return Craft::t('formie', 'Slack');
    }
    

    // Properties
    // =========================================================================

    public ?string $clientId = null;
    public ?string $clientSecret = null;
    public ?string $channelType = null;
    public ?string $userId = null;
    public ?string $channelId = null;
    public ?string $message = null;
    public ?string $webhook = null;


    // Public Methods
    // =========================================================================

    public function getAuthorizeUrl(): string
    {
        return 'https://slack.com/oauth/v2/authorize';
    }

    public function getAccessTokenUrl(): string
    {
        return 'https://slack.com/api/oauth.access';
    }

    public function getClientId(): string
    {
        return App::parseEnv($this->clientId);
    }

    public function getClientSecret(): string
    {
        return App::parseEnv($this->clientSecret);
    }

    public function getOauthScope(): array
    {
        return [
            'channels:read',
            'channels:write',
            'chat:write:bot',
            'groups:read',
            'groups:write',
            'users:read',
        ];
    }

    public function getOauthAuthorizationOptions(): array
    {
        return [
            'granular_bot_scope' => false,
        ];
    }

    public function getDescription(): string
    {
        return Craft::t('formie', 'Send your form content to Slack.');
    }

    /**
     * @inheritDoc
     */
    public function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['clientId', 'clientSecret'], 'required'];

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
                    'json' => [
                        'text' => $this->_renderMessage($submission),
                    ],
                ];

                $response = $this->getClient()->request('POST', $this->webhook, $payload);
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

    public function getClient(): Client
    {
        if ($this->_client) {
            return $this->_client;
        }

        $token = $this->getToken();

        if (!$token) {
            Integration::apiError($this, 'Token not found for integration.', true);
        }

        $this->_client = Craft::createGuzzleClient([
            'base_uri' => 'https://slack.com/api/',
            'headers' => [
                'Authorization' => 'Bearer ' . ($token->accessToken ?? 'empty'),
                'Content-Type' => 'application/json',
            ],
        ]);

        // Always provide an authenticated client - so check first.
        // We can't always rely on the EOL of the token.
        try {
            $response = $this->request('GET', 'users.list');
        } catch (Throwable $e) {
            if ($e->getCode() === 401) {
                // Force-refresh the token
                Formie::$plugin->getTokens()->refreshToken($token, true);

                // Then try again, with the new access token
                $this->_client = Craft::createGuzzleClient([
                    'base_uri' => 'https://slack.com/api/',
                    'headers' => [
                        'Authorization' => 'Bearer ' . ($token->accessToken ?? 'empty'),
                        'Content-Type' => 'application/json',
                    ],
                ]);
            }
        }

        return $this->_client;
    }


    // Private Methods
    // =========================================================================

    private function _renderMessage($submission): array|string
    {
        $html = RichTextHelper::getHtmlContent($this->message, $submission);

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
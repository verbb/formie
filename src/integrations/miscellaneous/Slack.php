<?php
namespace verbb\formie\integrations\miscellaneous;

use verbb\formie\Formie;
use verbb\formie\base\Integration;
use verbb\formie\base\Miscellaneous;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\helpers\VariableNode;
use verbb\formie\helpers\Variables;
use verbb\formie\models\IntegrationCollection;
use verbb\formie\models\IntegrationField;
use verbb\formie\models\IntegrationFormSettings;
use verbb\formie\prosemirror\toprosemirror\Renderer as ProseMirrorRenderer;
use verbb\formie\prosemirror\tohtml\Renderer as HtmlRenderer;

use Craft;
use craft\helpers\ArrayHelper;
use craft\helpers\Json;
use craft\web\View;

use League\HTMLToMarkdown\HtmlConverter;

class Slack extends Miscellaneous
{
    // Constants
    // =========================================================================

    const TYPE_PUBLIC = 'public';
    const TYPE_DM = 'directMessage';
    const TYPE_WEBHOOK = 'webhook';


    // Properties
    // =========================================================================

    public $clientId;
    public $clientSecret;
    public $channelType;
    public $userId;
    public $channelId;
    public $message;
    public $webhook;


    // OAuth Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public static function supportsOauthConnection(): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function getAuthorizeUrl(): string
    {
        return 'https://slack.com/oauth/v2/authorize';
    }

    /**
     * @inheritDoc
     */
    public function getAccessTokenUrl(): string
    {
        return 'https://slack.com/api/oauth.access';
    }

    /**
     * @inheritDoc
     */
    public function getClientId(): string
    {
        return Craft::parseEnv($this->clientId);
    }

    /**
     * @inheritDoc
     */
    public function getClientSecret(): string
    {
        return Craft::parseEnv($this->clientSecret);
    }

    /**
     * @inheritDoc
     */
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

    /**
     * @inheritDoc
     */
    public function getOauthAuthorizationOptions(): array
    {
        return [
            'granular_bot_scope' => false,
        ];
    }


    // Public Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public static function displayName(): string
    {
        return Craft::t('formie', 'Slack');
    }

    /**
     * @inheritDoc
     */
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

        $rules[] = [['userId'], 'required', 'when' => function($model) {
            return $model->enabled && $model->channelType === self::TYPE_DM;
        }, 'on' => [Integration::SCENARIO_FORM]];

        $rules[] = [['channelId'], 'required', 'when' => function($model) {
            return $model->enabled && $model->channelType === self::TYPE_PUBLIC;
        }, 'on' => [Integration::SCENARIO_FORM]];

        $rules[] = [['webhook'], 'required', 'when' => function($model) {
            return $model->enabled && $model->channelType === self::TYPE_WEBHOOK;
        }, 'on' => [Integration::SCENARIO_FORM]];

        return $rules;
    }

    /**
     * @inheritDoc
     */
    public function fetchFormSettings()
    {
        $settings = [];

        try {
            $response = $this->request('GET', 'conversations.list', [
                'query' => [
                    'exclude_archived' => true,
                    'exclude_members' => true,
                    'limit' => 50,
                ],
            ]);

            $channels = $response['channels'] ?? [];

            $response = $this->request('GET', 'users.list', [
                'query' => [
                    'limit' => 50,
                ],
            ]);

            $members = $response['members'] ?? [];

            $settings = [
                'channels' => $channels,
                'members' => $members,
            ];
        } catch (\Throwable $e) {
            Integration::error($this, Craft::t('formie', 'API error: “{message}” {file}:{line}', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]), true);
        }

        return new IntegrationFormSettings($settings);
    }

    /**
     * @inheritDoc
     */
    public function sendPayload(Submission $submission): bool
    {
        try {
            if ($this->channelType === self::TYPE_WEBHOOK) {
                $payload = [
                    'json' => [
                        'texts' => $this->_renderMessage($submission),
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
                    return false;
                }

                $isOkay = $response['ok'] ?? '';

                if (!$isOkay) {
                    Integration::error($this, Craft::t('formie', 'Reponse returned “not ok” {response}', [
                        'response' => Json::encode($response),
                    ]), true);

                    return false;
                }
            }
        } catch (\Throwable $e) {
            Integration::error($this, Craft::t('formie', 'API error: “{message}” {file}:{line}', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]), true);

            return false;
        }

        return true;
    }


    // Private Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    protected function getClient()
    {
        if ($this->_client) {
            return $this->_client;
        }

        $token = $this->getToken();

        $this->_client = Craft::createGuzzleClient([
            'base_uri' => 'https://slack.com/api/',
            'headers' => [
                'Authorization' => 'Bearer ' . $token->accessToken ?? '',
                'Content-Type' => 'application/json',
            ],
        ]);

        // Always provide an authenticated client - so check first.
        // We can't always rely on the EOL of the token.
        try {
            $response = $this->request('GET', 'users.list');
        } catch (\Throwable $e) {
            if ($e->getCode() === 401) {
                // Force-refresh the token
                Formie::$plugin->getTokens()->refreshToken($token, true);

                // Then try again, with the new access token
                $this->_client = Craft::createGuzzleClient([
                    'base_uri' => 'https://slack.com/api/',
                    'headers' => [
                        'Authorization' => 'Bearer ' . $token->accessToken ?? '',
                        'Content-Type' => 'application/json',
                    ],
                ]);
            }
        }

        return $this->_client;
    }


    // Private Methods
    // =========================================================================

    private function _renderMessage($submission)
    {
        $content = Json::decode($this->message);

        $renderer = new HtmlRenderer();
        $renderer->addNode(VariableNode::class);

        $html = $renderer->render([
            'type' => 'doc',
            'content' => $content,
        ]);

        $html = Variables::getParsedValue($html, $submission);

        $converter = new HtmlConverter(['strip_tags' => true]);
        $markdown = $converter->convert($html);

        // Some extra work to get it to play with Slack's mrkdwn
        $markdown = str_replace(['*', '__'], ['_', '*'], $markdown);

        return $markdown;
    }
}
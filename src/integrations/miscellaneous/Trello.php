<?php
namespace verbb\formie\integrations\miscellaneous;

use verbb\formie\base\Integration;
use verbb\formie\base\Miscellaneous;
use verbb\formie\elements\Submission;
use verbb\formie\helpers\RichTextHelper;
use verbb\formie\models\IntegrationFormSettings;

use Craft;
use craft\helpers\App;
use craft\helpers\Json;

use League\HTMLToMarkdown\HtmlConverter;
use League\OAuth1\Client\Server\Server as Oauth1Provider;
use League\OAuth1\Client\Server\Trello as TrelloProvider;
use League\OAuth2\Client\Provider\AbstractProvider;

use GuzzleHttp\HandlerStack;
use GuzzleHttp\Subscriber\Oauth\Oauth1;
use GuzzleHttp\Client;

use Throwable;

use LitEmoji\LitEmoji;

class Trello extends Miscellaneous
{
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
        return Craft::t('formie', 'Trello');
    }


    // Properties
    // =========================================================================

    public ?string $clientId = null;
    public ?string $clientSecret = null;
    public ?string $boardId = null;
    public ?string $listId = null;
    public ?string $cardName = null;
    public ?string $cardDescription = null;


    // Public Methods
    // =========================================================================

    public function oauthVersion(): int
    {
        return 1;
    }

    public function getOauthProviderConfig(): array
    {
        return [
            'identifier' => App::parseEnv($this->clientId),
            'secret' => App::parseEnv($this->clientSecret),
            'name' => Craft::t('formie', 'Formie'),
            'callback_uri' => $this->getRedirectUri(),
            'scope' => $this->getOauthScope(),
            'expiration' => 'never',
        ];
    }

    public function getOauthProvider(): AbstractProvider|Oauth1Provider
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return new TrelloProvider($this->getOauthProviderConfig());
    }


    // Public Methods
    // =========================================================================

    public function getOauthScope(): array
    {
        return [
            'read',
            'write',
            'account',
        ];
    }

    public function getDescription(): string
    {
        return Craft::t('formie', 'Send your form content to Trello.');
    }

    /**
     * @inheritDoc
     */
    public function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['clientId', 'clientSecret'], 'required'];

        // Validate the following when saving form settings
        $rules[] = [['boardId', 'listId', 'cardName', 'cardDescription'], 'required', 'on' => [Integration::SCENARIO_FORM]];

        return $rules;
    }

    public function fetchFormSettings(): IntegrationFormSettings
    {
        $settings = [];

        try {
            $allBoards = $this->request('GET', 'members/me/boards', [
                'query' => [
                    'filter' => 'open,members,organization,public',
                    'fields' => 'id,name',
                ],
            ]);
            $boards = [];

            foreach ($allBoards as $key => $board) {
                $lists = [];

                $allLists = $this->request('GET', "boards/${board['id']}/lists");

                foreach ($allLists as $list) {
                    $lists[] = [
                        'id' => $list['id'],
                        'name' => LitEmoji::unicodeToShortcode($list['name']),
                    ];
                }

                $boards[$board['id']] = [
                    'id' => $board['id'],
                    'name' => LitEmoji::unicodeToShortcode($board['name']),
                    'lists' => $lists,
                ];
            }

            $settings = [
                'boards' => $boards,
            ];
        } catch (Throwable $e) {
            Integration::apiError($this, $e);
        }

        return new IntegrationFormSettings($settings);
    }

    public function getFormSettings($useCache = true): bool|IntegrationFormSettings
    {
        $settings = parent::getFormSettings($useCache);
        $boards = $settings->getSettingsByKey('boards');

        // Parse any Emoji's in board/list titles from the saved cache
        foreach ($boards as $boardKey => $board) {
            $boards[$boardKey]['name'] = LitEmoji::shortcodeToUnicode($board['name']);

            $lists = $board['lists'] ?? [];

            foreach ($lists as $listKey => $list) {
                $boards[$boardKey]['lists'][$listKey]['name'] = LitEmoji::shortcodeToUnicode($list['name']);
            }
        }

        $settings->setSettingsByKey('boards', $boards);

        return $settings;
    }

    public function sendPayload(Submission $submission): bool
    {
        try {
            $payload = [
                'name' => $this->cardName,
                'desc' => $this->_renderMessage($submission),
                'pos' => 'bottom',
                'idList' => $this->listId,
            ];

            $response = $this->deliverPayload($submission, 'cards', $payload);

            if ($response === false) {
                return true;
            }

            $cardId = $response['id'] ?? '';

            if (!$cardId) {
                Integration::error($this, Craft::t('formie', 'Missing return “cardId” {response}', [
                    'response' => Json::encode($response),
                ]), true);

                return false;
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
            Integration::error($this, 'Token not found for integration.', true);
        }

        $info = $this->getOauthProviderConfig();
        $stack = HandlerStack::create();

        $stack->push(new Oauth1([
            'consumer_key' => $info['identifier'],
            'consumer_secret' => $info['secret'],
            'token' => $token->accessToken,
            'token_secret' => $token->secret,
        ]));

        $this->_client = Craft::createGuzzleClient([
            'base_uri' => 'https://api.trello.com/1/',
            'handler' => $stack,
            'auth' => 'oauth',
        ]);

        return $this->_client;
    }


    // Private Methods
    // =========================================================================

    private function _renderMessage($submission): array|string
    {
        $html = RichTextHelper::getHtmlContent($this->cardDescription, $submission);

        $converter = new HtmlConverter(['strip_tags' => true]);
        $markdown = $converter->convert($html);

        // Some extra work to get it to play with Slack's mrkdwn
        return str_replace(['*', '__'], ['_', '*'], $markdown);
    }
}
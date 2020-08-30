<?php
namespace verbb\formie\integrations\miscellaneous;

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
use craft\helpers\UrlHelper;
use craft\web\View;

use League\HTMLToMarkdown\HtmlConverter;
use League\OAuth1\Client\Server\Trello as TrelloProvider;

use GuzzleHttp\HandlerStack;
use GuzzleHttp\Subscriber\Oauth\Oauth1;

class Trello extends Miscellaneous
{
    // Properties
    // =========================================================================

    public $clientId;
    public $clientSecret;
    public $boardId;
    public $listId;
    public $cardName;
    public $cardDescription;


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
    public function oauthVersion(): int
    {
        return 1;
    }

    /**
     * @inheritDoc
     */
    public function getOauthProviderConfig()
    {
        return [
            'identifier' => $this->clientId,
            'secret' => $this->clientSecret,
            'name' => Craft::t('formie', 'Formie'),
            'callback_uri' => $this->getRedirectUri(),
            'scope' => $this->getOauthScope(),
            'expiration' => 'never',
        ];
    }

    /**
     * @inheritDoc
     */
    public function getOauthProvider()
    {
        return new TrelloProvider($this->getOauthProviderConfig());
    }

    /**
     * @inheritDoc
     */
    public function getOauthScope(): array
    {
        return [
            'read',
            'write',
            'account',
        ];
    }


    // Public Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public static function displayName(): string
    {
        return Craft::t('formie', 'Trello');
    }

    /**
     * @inheritDoc
     */
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

    /**
     * @inheritDoc
     */
    public function fetchFormSettings()
    {
        $settings = [];

        try {
            $allBoards = $this->request('GET', 'members/me/boards');
            $boards = [];

            foreach ($allBoards as $key => $board) {
                $lists = [];

                $allLists = $this->request('GET', "boards/${board['id']}/lists");

                foreach ($allLists as $key => $list) {
                    $lists[] = [
                        'id' => $list['id'],
                        'name' => $list['name'],
                    ];
                }

                $boards[$board['id']] = [
                    'id' => $board['id'],
                    'name' => $board['name'],
                    'lists' => $lists,
                ];
            }

            $settings = [
                'boards' => $boards,
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
            $payload = [
                'name' => $this->cardName,
                'desc' => $this->_renderMessage($submission),
                'pos' => 'bottom',
                'idList' => $this->listId,
            ];

            $response = $this->deliverPayload($submission, 'cards', $payload);

            if ($response === false) {
                return false;
            }

            $cardId = $response['id'] ?? '';

            if (!$cardId) {
                Integration::error($this, Craft::t('formie', 'Missing return “cardId” {response}', [
                    'response' => Json::encode($response),
                ]), true);

                return false;
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
            'auth' => 'oauth'
        ]);

        return $this->_client;
    }


    // Private Methods
    // =========================================================================

    private function _renderMessage($submission)
    {
        $content = Json::decode($this->cardDescription);

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
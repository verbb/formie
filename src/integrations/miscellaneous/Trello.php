<?php
namespace verbb\formie\integrations\miscellaneous;

use verbb\formie\base\Integration;
use verbb\formie\base\Miscellaneous;
use verbb\formie\elements\Submission;
use verbb\formie\helpers\RichTextHelper;
use verbb\formie\helpers\StringHelper;
use verbb\formie\models\IntegrationFormSettings;

use Craft;
use craft\helpers\App;
use craft\helpers\Json;

use Throwable;

use verbb\auth\base\OAuthProviderInterface;
use verbb\auth\models\Token;
use verbb\auth\providers\Trello as TrelloProvider;

class Trello extends Miscellaneous implements OAuthProviderInterface
{
    // Static Methods
    // =========================================================================

    public static function supportsOAuthConnection(): bool
    {
        return true;
    }

    public static function getOAuthProviderClass(): string
    {
        return TrelloProvider::class;
    }

    public static function displayName(): string
    {
        return Craft::t('formie', 'Trello');
    }


    // Properties
    // =========================================================================

    public ?string $boardId = null;
    public ?string $listId = null;
    public ?string $cardName = null;
    public ?string $cardDescription = null;


    // Public Methods
    // =========================================================================

    public function getAuthorizationUrlOptions(): array
    {
        $options = parent::getAuthorizationUrlOptions();

        $options['scope'] = [
            'read',
            'write',
            'account',
        ];
        
        return $options;
    }

    public function getDescription(): string
    {
        return Craft::t('formie', 'Send your form content to Trello.');
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

                $allLists = $this->request('GET', "boards/{$board['id']}/lists");

                foreach ($allLists as $list) {
                    $lists[] = [
                        'id' => $list['id'],
                        'name' => (string)$list['name'],
                    ];
                }

                $boards[$board['id']] = [
                    'id' => $board['id'],
                    'name' => (string)$board['name'],
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

    
    // Protected Methods
    // =========================================================================

    protected function defineRules(): array
    {
        $rules = parent::defineRules();

        // Validate the following when saving form settings
        $rules[] = [['boardId', 'listId', 'cardName', 'cardDescription'], 'required', 'on' => [Integration::SCENARIO_FORM]];

        return $rules;
    }


    // Private Methods
    // =========================================================================

    private function _renderMessage($submission): array|string
    {
        $html = RichTextHelper::getHtmlContent($this->cardDescription, $submission, false);

        $converter = new HtmlConverter(['strip_tags' => true]);
        $markdown = $converter->convert($html);

        // Some extra work to get it to play with Slack's mrkdwn
        return str_replace(['*', '__'], ['_', '*'], $markdown);
    }
}
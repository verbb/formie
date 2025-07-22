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
use verbb\auth\providers\HelpScout as HelpScoutProvider;

class HelpScout extends HelpDesk implements OAuthProviderInterface
{
    // Static Methods
    // =========================================================================

    public static function supportsOAuthConnection(): bool
    {
        return true;
    }

    public static function getOAuthProviderClass(): string
    {
        return HelpScoutProvider::class;
    }

    public static function displayName(): string
    {
        return Craft::t('formie', 'Help Scout');
    }


    // Properties
    // =========================================================================

    public ?string $message = null;
    public bool $mapToConversation = false;
    public ?array $conversationFieldMapping = null;


    // Public Methods
    // =========================================================================

    public function getDescription(): string
    {
        return Craft::t('formie', 'Send your form content to Help Scout.');
    }

    public function fetchFormSettings(): IntegrationFormSettings
    {
        $settings = [];

        try {
            // Fetch mailboxes
            $response = $this->request('GET', 'mailboxes');
            $mailboxes = $response['_embedded']['mailboxes'] ?? [];

            $mailboxOptions = [];

            foreach ($mailboxes as $mailbox) {
                $mailboxOptions[] = [
                    'label' => $mailbox['name'],
                    'value' => (string)$mailbox['id'],
                ];
            }

            // Fetch tags (optional, not required to build conversation)
            $response = $this->request('GET', 'tags');
            $tags = $response['_embedded']['tags'] ?? [];

            $tagOptions = [];

            foreach ($tags as $tag) {
                $tagOptions[] = [
                    'label' => $tag['name'],
                    'value' => (string)$tag['id'],
                ];
            }

            $conversationFields = [
                new IntegrationField([
                    'handle' => 'mailboxId',
                    'name' => Craft::t('formie', 'Mailbox'),
                    'required' => true,
                    'options' => [
                        'label' => Craft::t('formie', 'Mailbox'),
                        'options' => $mailboxOptions,
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
                    'handle' => 'firstName',
                    'name' => Craft::t('formie', 'Sender First Name'),
                ]),
                new IntegrationField([
                    'handle' => 'lastName',
                    'name' => Craft::t('formie', 'Sender Last Name'),
                ]),
                new IntegrationField([
                    'handle' => 'tags',
                    'name' => Craft::t('formie', 'Tags'),
                    'options' => [
                        'label' => Craft::t('formie', 'Tags'),
                        'options' => $tagOptions,
                    ],
                ]),
            ];

            $settings = [
                'conversation' => $conversationFields,
            ];
        } catch (Throwable $e) {
            Integration::apiError($this, $e);
        }

        return new IntegrationFormSettings($settings);
    }

    public function sendPayload(Submission $submission): bool
    {
        try {
            if ($this->mapToConversation) {
                $conversationValues = $this->getFieldMappingValues($submission, $this->conversationFieldMapping, 'conversation');

                $mailboxId = ArrayHelper::remove($conversationValues, 'mailboxId');
                $subject = ArrayHelper::remove($conversationValues, 'subject');
                $email = ArrayHelper::remove($conversationValues, 'email');
                $firstName = ArrayHelper::remove($conversationValues, 'firstName');
                $lastName = ArrayHelper::remove($conversationValues, 'lastName');
                $tags = ArrayHelper::remove($conversationValues, 'tags') ?? [];

                $tags = is_array($tags) ? $tags : array_filter(array_map('trim', explode(',', $tags)));

                $body = $this->_renderMessage($submission);

                $conversationPayload = [
                    'type' => 'email',
                    'subject' => $subject,
                    'mailboxId' => (int)$mailboxId,
                    'customer' => [
                        'email' => $email,
                        'firstName' => $firstName ?: '',
                        'lastName' => $lastName ?: '',
                    ],
                    'threads' => [
                        [
                            'type' => 'customer',
                            'customer' => [
                                'email' => $email,
                                'firstName' => $firstName ?: '',
                                'lastName' => $lastName ?: '',
                            ],
                            'text' => $body,
                        ],
                    ],
                    'tags' => $tags,
                    'status' => 'active',
                ];

                $response = $this->deliverPayload($submission, 'conversations', $conversationPayload);

                if ($response === false) {
                    return true;
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

    private function _renderMessage($submission): array|string
    {
        $html = RichTextHelper::getHtmlContent($this->message, $submission, false);

        $converter = new HtmlConverter(['strip_tags' => true]);
        $markdown = $converter->convert($html);

        return $markdown;
    }
}
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
use verbb\auth\providers\LiveChat as LiveChatProvider;

class LiveChat extends HelpDesk implements OAuthProviderInterface
{
    // Static Methods
    // =========================================================================

    public static function supportsOAuthConnection(): bool
    {
        return true;
    }

    public static function getOAuthProviderClass(): string
    {
        return LiveChatProvider::class;
    }

    public static function displayName(): string
    {
        return Craft::t('formie', 'Live Chat');
    }


    // Properties
    // =========================================================================

    public ?string $licenseId = null;
    public ?string $message = null;
    public bool $mapToTicket = false;
    public ?array $ticketFieldMapping = null;


    // Public Methods
    // =========================================================================

    public function getDescription(): string
    {
        return Craft::t('formie', 'Send your form content to Live Chat.');
    }

    public function fetchFormSettings(): IntegrationFormSettings
    {
        $settings = [];

        try {
            $ticketFields = [
                new IntegrationField([
                    'handle' => 'email',
                    'name' => Craft::t('formie', 'Email'),
                    'required' => true,
                ]),
                new IntegrationField([
                    'handle' => 'name',
                    'name' => Craft::t('formie', 'Name'),
                ]),
                new IntegrationField([
                    'handle' => 'subject',
                    'name' => Craft::t('formie', 'Subject'),
                    'required' => true,
                ]),
                new IntegrationField([
                    'handle' => 'tags',
                    'name' => Craft::t('formie', 'Tags'),
                ]),
            ];

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
            $ticketValues = $this->getFieldMappingValues($submission, $this->ticketFieldMapping, 'ticket');

            $payload = [];

            $licenseId = App::parseEnv($this->licenseId);
            $email = ArrayHelper::remove($ticketValues, 'email');
            $name = ArrayHelper::remove($ticketValues, 'name');
            $subject = ArrayHelper::remove($ticketValues, 'subject');
            $tags = ArrayHelper::remove($ticketValues, 'tags') ?? [];
            $tags = is_array($tags) ? $tags : array_filter(array_map('trim', explode(',', $tags)));

            $payload = [
                'visitor_id' => 'visitor_' . StringHelper::randomString(10),
                'ticket_message' => $this->_renderMessage($submission),
                'offline_message' => $this->_renderMessage($submission),
                'requester' => [
                    'name' => $name,
                    'mail' => $email,
                ],
                'subject' => $subject,
                'licence_id' => $licenseId,
            ];

            $response = $this->deliverPayload($submission, 'v2/tickets/new', $payload);

            if ($response === false) {
                return true;
            }

            $ticketId = $response['id'] ?? '';

            if (!$ticketId) {
                Integration::error($this, Craft::t('formie', 'Missing return “ticketId” {response}. Sent payload {payload}', [
                    'response' => Json::encode($response),
                    'payload' => Json::encode($payload),
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

        $rules[] = [['licenseId'], 'required'];

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
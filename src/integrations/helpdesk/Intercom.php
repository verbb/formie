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
use verbb\auth\providers\Intercom as IntercomProvider;

class Intercom extends HelpDesk implements OAuthProviderInterface
{
    // Static Methods
    // =========================================================================

    public static function supportsOAuthConnection(): bool
    {
        return true;
    }

    public static function getOAuthProviderClass(): string
    {
        return IntercomProvider::class;
    }

    public static function displayName(): string
    {
        return Craft::t('formie', 'Intercom');
    }


    // Properties
    // =========================================================================

    public ?string $message = null;
    public bool $mapToContact = false;
    public ?array $contactFieldMapping = null;


    // Public Methods
    // =========================================================================

    public function getDescription(): string
    {
        return Craft::t('formie', 'Send your form content to Intercom.');
    }

    public function fetchFormSettings(): IntegrationFormSettings
    {
        $settings = [];

        try {
            $response = $this->request('GET', 'admins');
            $admins = $response['admins'] ?? [];

            $ownerOptions = [];

            foreach ($admins as $admin) {
                $ownerOptions[] = [
                    'label' => $admin['name'] . ' (' . $admin['email'] . ')',
                    'value' => (string)$admin['id'],
                ];
            }

            $response = $this->request('GET', 'companies');
            $companies = $response['companies'] ?? [];

            $companyOptions = [];

            foreach ($companies as $company) {
                $companyOptions[] = [
                    'label' => $company['name'] . ' (' . $company['company_id'] . ')',
                    'value' => (string)$company['company_id'],
                ];
            }

            $contactFields = [
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
                    'handle' => 'phone',
                    'name' => Craft::t('formie', 'Phone'),
                ]),
                new IntegrationField([
                    'handle' => 'avatar',
                    'name' => Craft::t('formie', 'Avatar URL'),
                ]),
                new IntegrationField([
                    'handle' => 'owner_id',
                    'name' => Craft::t('formie', 'Owner'),
                    'options' => [
                        'label' => Craft::t('formie', 'Owner'),
                        'options' => $ownerOptions,
                    ],
                ]),
                new IntegrationField([
                    'handle' => 'role',
                    'name' => Craft::t('formie', 'Role'),
                    'required' => true,
                    'options' => [
                        'label' => Craft::t('formie', 'Role'),
                        'options' => [
                            ['label' => 'User', 'value' => 'user'],
                            ['label' => 'Lead', 'value' => 'lead'],
                            ['label' => 'Visitor', 'value' => 'visitor'],
                        ],
                    ],
                ]),
                new IntegrationField([
                    'handle' => 'company_id',
                    'name' => Craft::t('formie', 'Company'),
                    'options' => [
                        'label' => Craft::t('formie', 'Company'),
                        'options' => $companyOptions,
                    ],
                ]),
                new IntegrationField([
                    'handle' => 'tags',
                    'name' => Craft::t('formie', 'Tags (comma-separated)'),
                ]),
            ];

            $settings = [
                'contact' => $contactFields,
            ];
        } catch (Throwable $e) {
            Integration::apiError($this, $e);
        }

        return new IntegrationFormSettings($settings);
    }

    public function sendPayload(Submission $submission): bool
    {
        try {
            if ($this->mapToContact) {
                $contactValues = $this->getFieldMappingValues($submission, $this->contactFieldMapping, 'contact');
                $contactPayload = $contactValues;
                $contactPayload['update_enabled'] = true;

                if ($tags = ArrayHelper::remove($contactPayload, 'tags')) {
                    $contactPayload['tags'] = array_filter(array_map('trim', explode(',', $tags)));
                }

                if ($companyId = ArrayHelper::remove($contactPayload, 'company_id')) {
                    $contactPayload['companies'] = [
                        [
                            'company_id' => $companyId,
                        ],
                    ];
                }

                $contactResponse = $this->deliverPayload($submission, 'contacts', $contactPayload);

                if ($contactResponse === false) {
                    return true;
                }

                $contactId = $contactResponse['id'] ?? '';

                if (!$contactId) {
                    Integration::error($this, Craft::t('formie', 'Missing return “contactId” {response}. Sent payload {payload}', [
                        'response' => Json::encode($contactResponse),
                        'payload' => Json::encode($contactPayload),
                    ]), true);

                    return false;
                }

                $messagePayload = [
                    'message_type' => 'email',
                    'subject' => 'New Form Submission',
                    'body' => $this->_renderMessage($submission),
                    'from' => [
                        'type' => 'user',
                        'id' => $contactId,
                    ],
                ];

                $messageResponse = $this->deliverPayload($submission, 'messages', $messagePayload);

                if ($messageResponse === false) {
                    return true;
                }

                $messageId = $messageResponse['id'] ?? '';

                if (!$messageId) {
                    Integration::error($this, Craft::t('formie', 'Missing return “messageId” {response}. Sent payload {payload}', [
                        'response' => Json::encode($messageResponse),
                        'payload' => Json::encode($messagePayload),
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

    private function _renderMessage($submission): array|string
    {
        $html = RichTextHelper::getHtmlContent($this->message, $submission, false);

        $converter = new HtmlConverter(['strip_tags' => true]);
        $markdown = $converter->convert($html);

        return $markdown;
    }
}
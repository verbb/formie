<?php
namespace verbb\formie\integrations\emailmarketing;

use verbb\formie\Formie;
use verbb\formie\base\Integration;
use verbb\formie\base\EmailMarketing;
use verbb\formie\elements\Submission;
use verbb\formie\errors\IntegrationException;
use verbb\formie\helpers\ArrayHelper;
use verbb\formie\models\IntegrationCollection;
use verbb\formie\models\IntegrationField;
use verbb\formie\models\IntegrationFormSettings;

use Craft;
use craft\helpers\App;
use craft\helpers\Json;

use Throwable;

use verbb\auth\base\OAuthProviderInterface;
use verbb\auth\models\Token;
use verbb\auth\providers\CleverReach as CleverReachProvider;

class CleverReach extends EmailMarketing implements OAuthProviderInterface
{
    // Static Methods
    // =========================================================================

    public static function supportsOAuthConnection(): bool
    {
        return true;
    }

    public static function getOAuthProviderClass(): string
    {
        return CleverReachProvider::class;
    }

    public static function displayName(): string
    {
        return Craft::t('formie', 'CleverReach');
    }


    // Public Methods
    // =========================================================================

    public function getDescription(): string
    {
        return Craft::t('formie', 'Sign up users to your {name} lists to grow your audience for campaigns.', ['name' => static::displayName()]);
    }

    public function fetchFormSettings(): IntegrationFormSettings
    {
        $settings = [];

        try {
            $groups = $this->request('GET', 'groups');
            $fields = $this->request('GET', 'attributes');

            foreach ($groups as $group) {
                $groupId = $group['id'];

                $listFields = array_merge([
                    new IntegrationField([
                        'handle' => 'email',
                        'name' => Craft::t('formie', 'Email'),
                        'required' => true,
                    ]),
                ], $this->_getCustomFields($fields));

                $settings['lists'][] = new IntegrationCollection([
                    'id' => (string)$groupId,
                    'name' => $group['name'],
                    'fields' => $listFields,
                ]);
            }
        } catch (Throwable $e) {
            Integration::apiError($this, $e);
        }

        return new IntegrationFormSettings($settings);
    }

    public function sendPayload(Submission $submission): bool
    {
        try {
            $fieldValues = $this->getFieldMappingValues($submission, $this->fieldMapping);
            $email = ArrayHelper::remove($fieldValues, 'email');

            $payload = [
                [
                    'email' => $email,
                    'registered' => time(),
                    'activated' => time(),
                    'attributes' => $fieldValues,
                    'global_attributes' => $fieldValues,
                    'source' => 'Formie',
                ],
            ];

            $response = $this->deliverPayload($submission, "groups/{$this->listId}/receivers/upsert", $payload);

            if ($response === false) {
                return true;
            }
        } catch (Throwable $e) {
            Integration::apiError($this, $e);

            return false;
        }

        return true;
    }


    // Private Methods
    // =========================================================================

    private function _convertFieldType(string $fieldType): string
    {
        $fieldTypes = [
            'date' => IntegrationField::TYPE_DATETIME,
            'number' => IntegrationField::TYPE_NUMBER,
        ];

        return $fieldTypes[$fieldType] ?? IntegrationField::TYPE_STRING;
    }

    private function _getCustomFields(array $fields = []): array
    {
        $customFields = [];

        foreach ($fields as $field) {
            $customFields[] = new IntegrationField([
                'handle' => (string)$field['name'],
                'name' => $field['description'],
                'type' => $this->_convertFieldType($field['type']),
                'sourceType' => $field['type'],
            ]);
        }

        return $customFields;
    }
}
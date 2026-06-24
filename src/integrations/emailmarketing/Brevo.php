<?php
namespace verbb\formie\integrations\emailmarketing;

use verbb\formie\base\Integration;
use verbb\formie\base\EmailMarketing;
use verbb\formie\base\FormInterface;
use verbb\formie\elements\Submission;
use verbb\formie\helpers\ArrayHelper;
use verbb\formie\helpers\SchemaHelper;
use verbb\formie\models\IntegrationCollection;
use verbb\formie\models\IntegrationField;
use verbb\formie\models\IntegrationFormSettings;

use Craft;
use craft\helpers\App;

use GuzzleHttp\Client;

use Throwable;

class Brevo extends EmailMarketing
{
    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return 'Brevo';
    }

    // Properties
    // =========================================================================

    public ?string $apiKey = null;
    public bool $useDoubleOptIn = false;
    public ?string $templateId = null;
    public ?string $redirectionUrl = null;

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
            $lists = $this->_getPaginated('contacts/lists', 'lists');

            $response = $this->request('GET', 'contacts/attributes');
            $fields = $response['attributes'] ?? [];
            $listField = $this->_getListIntegrationField($lists);

            foreach ($lists as $list) {
                $listFields = array_merge([
                    new IntegrationField([
                        'handle' => 'email',
                        'name' => Craft::t('formie', 'Email'),
                        'required' => true,
                    ]),
                    $listField,
                ], $this->_getCustomFields($fields));

                $settings['lists'][] = new IntegrationCollection([
                    'id' => (string)$list['id'],
                    'name' => $list['name'],
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

            // Pull out email, as it needs to be top level
            $email = ArrayHelper::remove($fieldValues, 'email');
            $listIds = $this->_resolveListIds(ArrayHelper::remove($fieldValues, 'listId'));

            if ($listIds === []) {
                Integration::error($this, Craft::t('formie', 'Unable to add contact to Brevo. No list was configured or mapped.'), true);

                return false;
            }

            if ($this->useDoubleOptIn) {
                $endpoint = 'contacts/doubleOptinConfirmation';

                $payload = [
                    'email' => $email,
                    'includeListIds' => $listIds,
                    'templateId' => (int)$this->templateId,
                    'redirectionUrl' => $this->redirectionUrl,
                ];
            } else {
                $endpoint = 'contacts';

                $payload = [
                    'email' => $email,
                    'listIds' => $listIds,
                    'updateEnabled' => true,
                ];
            }
            
            if ($fieldValues) {
                $payload['attributes'] = $fieldValues;
            }

            $response = $this->deliverPayload($submission, $endpoint, $payload);

            if ($response === false) {
                return true;
            }
        } catch (Throwable $e) {
            Integration::apiError($this, $e);

            return false;
        }

        return true;
    }

    public function fetchConnection(): bool
    {
        try {
            $response = $this->request('GET', 'account');
            $accountId = $response['email'] ?? '';

            if (!$accountId) {
                Integration::error($this, 'Unable to find “{email}” in response.', true);
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

        $rules[] = [['apiKey'], 'required'];

        return $rules;
    }

    protected function defineClient(): Client
    {
        return Craft::createGuzzleClient([
            'base_uri' => 'https://api.brevo.com/v3/',
            'headers' => ['api-key' => App::parseEnv($this->apiKey)],
        ]);
    }



    protected function defineFormSettingsSchema(FormInterface $form): array
    {
        $schema = parent::defineFormSettingsSchema($form);

        $schema[] = SchemaHelper::lightswitchField([
            'label' => Craft::t('formie', 'Use Double Opt in'),
            'instructions' => Craft::t('formie', 'Whether to use double opt-in, which will send the user a confirmation email before they‘re added to the list.'),
            'name' => 'useDoubleOptIn',
        ]);

        $schema[] = SchemaHelper::textField([
            'label' => Craft::t('formie', 'Template ID'),
            'instructions' => Craft::t('formie', 'ID of the Double opt-in (DOI) template.'),
            'name' => 'templateId',
            'if' => 'useDoubleOptIn',
            'required' => true,
        ]);

        $schema[] = SchemaHelper::textField([
            'label' => Craft::t('formie', 'Redirection URL'),
            'instructions' => Craft::t('formie', 'URL of the web page that user will be redirected to after clicking on the double opt in URL.'),
            'name' => 'redirectionUrl',
            'if' => 'useDoubleOptIn',
            'required' => true,
        ]);

        return $schema;
    }
    

    // Private Methods
    // =========================================================================

    private function _getPaginated($endpoint, $collection, $limit = 50, $offset = 0, $items = [])
    {
        $response = $this->request('GET', $endpoint, [
            'query' => [
                'limit' => $limit,
                'offset' => $offset,
            ],
        ]);

        $newItems = $response[$collection] ?? [];
        $total = $response['count'] ?? 0;

        $items = array_merge($items, $newItems);

        if (count($items) < $total) {
            $items = $this->_getPaginated($endpoint, $collection, $limit, $offset + $limit, $items);
        }

        return $items;
    }

    private function _getListIntegrationField(array $lists): IntegrationField
    {
        return new IntegrationField([
            'handle' => 'listId',
            'name' => Craft::t('formie', 'List'),
            'options' => [
                'label' => Craft::t('formie', 'Lists'),
                'options' => array_map(static fn(array $list): array => [
                    'label' => $list['name'],
                    'value' => (string)$list['id'],
                ], $lists),
            ],
        ]);
    }

    /**
     * @return int[]
     */
    private function _resolveListIds(mixed $mappedListId): array
    {
        $rawIds = [];

        if (is_array($mappedListId)) {
            $rawIds = $mappedListId;
        } elseif ($mappedListId !== null && $mappedListId !== '') {
            $rawIds = explode(',', (string)$mappedListId);
        } elseif ($this->listId) {
            $rawIds = [(string)$this->listId];
        }

        $listIds = [];

        foreach ($rawIds as $rawId) {
            $listId = (int)trim((string)$rawId);

            if ($listId > 0) {
                $listIds[] = $listId;
            }
        }

        return array_values(array_unique($listIds));
    }

    private function _convertFieldType(string $fieldType): string
    {
        $fieldTypes = [
            'float' => IntegrationField::TYPE_FLOAT,
            'date' => IntegrationField::TYPE_DATETIME,
            'boolean' => IntegrationField::TYPE_BOOLEAN,
        ];

        return $fieldTypes[$fieldType] ?? IntegrationField::TYPE_STRING;
    }

    private function _getCustomFields(array $fields, array $excludeNames = []): array
    {
        $customFields = [];

        foreach ($fields as $key => $field) {
            // Exclude any names
            if (in_array($field['name'], $excludeNames)) {
                continue;
            }

            // Ignore a calculated value field
            if (isset($field['calculatedValue'])) {
                continue;
            }

            $type = $field['type'] ?? '';

            // Add in any options for some fields
            $options = [];
            $fieldOptions = $field['enumeration'] ?? [];

            foreach ($fieldOptions as $fieldOption) {
                $options[] = [
                    'label' => $fieldOption['label'],
                    'value' => $fieldOption['value'],
                ];
            }

            if ($options) {
                $options = [
                    'label' => $field['name'],
                    'options' => $options,
                ];
            }

            $customFields[] = new IntegrationField([
                'handle' => $field['name'],
                'name' => $field['name'],
                'type' => $this->_convertFieldType($type),
                'sourceType' => $type,
                'options' => $options,
            ]);
        }

        return $customFields;
    }
}
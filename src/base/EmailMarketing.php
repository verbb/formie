<?php
namespace verbb\formie\base;

use verbb\formie\base\FormInterface;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\events\SendIntegrationPayloadEvent;
use verbb\formie\helpers\ArrayHelper;
use verbb\formie\helpers\SchemaHelper;
use verbb\formie\helpers\StringHelper;
use verbb\formie\models\Stencil;

use Craft;
use craft\helpers\Html;
use craft\helpers\UrlHelper;

use yii\helpers\Markdown;

abstract class EmailMarketing extends Integration
{
    // Static Methods
    // =========================================================================

    public static function typeName(): string
    {
        return Craft::t('formie', 'Email Marketing');
    }


    // Properties
    // =========================================================================

    public ?array $fieldMapping = null;
    public ?string $listId = null;


    // Public Methods
    // =========================================================================

    public function getType(): string
    {
        return self::TYPE_EMAIL_MARKETING;
    }

    public function getCategory(): string
    {
        return self::CATEGORY_EMAIL_MARKETING;
    }

    public function getCpEditUrl(): ?string
    {
        return UrlHelper::cpUrl('formie/integrations/email-marketing/edit/' . $this->id);
    }

    public function getIconUrl(): string
    {
        $handle = $this->getClassHandle();

        return Craft::$app->getAssetManager()->getPublishedUrl('@verbb/formie/web/assets/cp/dist/', true, "icons/email-marketing/{$handle}.svg");
    }

    public function getSettingsHtml(): ?string
    {
        $handle = $this->getClassHandle();
        $variables = $this->getSettingsHtmlVariables();

        return Craft::$app->getView()->renderTemplate("formie/integrations/email-marketing/{$handle}/_plugin-settings", $variables);
    }

    public function supportsFormSettingsRefresh(): bool
    {
        return true;
    }

    public function getFieldMappingValues(Submission $submission, ?array $fieldMapping, mixed $fieldSettings = [])
    {
        // A quick shortcut as all email marketing integrations are the same field mapping-wise
        $fields = $this->_getListSettings()->fields ?? [];

        return parent::getFieldMappingValues($submission, $fieldMapping, $fields);
    }
    

    // Protected Methods
    // =========================================================================

    protected function defineRules(): array
    {
        $rules = parent::defineRules();

        // Validate the following when saving form settings
        $rules[] = [['listId'], 'required', 'on' => [Integration::SCENARIO_FORM]];

        $fields = $this->_getListSettings()->fields ?? [];

        $rules[] = [
            ['fieldMapping'], 'validateFieldMapping', 'params' => $fields, 'when' => function($model) {
                return $model->enabled;
            }, 'on' => [Integration::SCENARIO_FORM], 'skipOnEmpty' => false,
        ];

        return $rules;
    }

    protected function defineFormSettingsSchema(FormInterface $form): array
    {
        $schema = parent::defineFormSettingsSchema($form);
        $schema[] = $this->getOptInFieldSchema();

        $options = [
            [
                'label' => Craft::t('formie', 'Select an option'),
                'value' => '',
            ],
        ];

        $lists = $this->getFormSettingValue('lists');
        
        if (is_array($lists)) {
            foreach ($lists as $list) {
                if (is_array($list)) {
                    $id = $list['id'] ?? null;
                    $name = $list['name'] ?? null;
                } else {
                    $id = $list->id ?? null;
                    $name = $list->name ?? null;
                }

                if ($id === null || $name === null) {
                    continue;
                }

                $options[] = [
                    'label' => (string)$name,
                    'value' => (string)$id,
                ];
            }
        }

        $schema[] = SchemaHelper::integrationRefreshSelectField([
            'label' => Craft::t('formie', 'List'),
            'instructions' => Craft::t('formie', 'Select your {name} list to create contacts on.', ['name' => $this->displayName()]),
            'name' => 'listId',
            'required' => true,
            'options' => $options,
        ]);

        $listFields = [];
        $selectedListId = (string)($this->listId ?? '');
        $fieldCollections = [];

        if (is_array($lists)) {
            foreach ($lists as $list) {
                $listId = is_array($list) ? ($list['id'] ?? null) : ($list->id ?? null);
                if ($listId === null || $listId === '') {
                    continue;
                }

                $fields = is_array($list) ? ($list['fields'] ?? []) : ($list->fields ?? []);
                if (!is_array($fields)) {
                    $fields = [];
                }

                $fieldCollections[] = [
                    'id' => (string)$listId,
                    'fields' => $this->convertIntegrationFieldsToSchema($fields),
                ];
            }
        }

        if ($selectedListId !== '' && is_array($lists)) {
            foreach ($lists as $list) {
                $listId = is_array($list) ? ($list['id'] ?? null) : ($list->id ?? null);
                if ((string)$listId !== $selectedListId) {
                    continue;
                }

                $listFields = is_array($list) ? ($list['fields'] ?? []) : ($list->fields ?? []);
                break;
            }
        }

        $schema[] = SchemaHelper::integrationFieldMappingField([
            'name' => 'fieldMapping',
            'label' => Craft::t('formie', 'Field Mapping'),
            'instructions' => Craft::t('formie', 'Choose how your form fields should map to your {name} fields.', ['name' => $this->displayName()]),
            'integrationLabel' => Craft::t('formie', '{name} Field', ['name' => $this->displayName()]),
            'selectedCollectionField' => 'listId',
            'integrationFieldCollections' => $fieldCollections,
            'integrationFields' => $this->convertIntegrationFieldsToSchema($listFields),
        ]);

        return $schema;
    }


    // Private Methods
    // =========================================================================

    private function _getListSettings()
    {
        $lists = $this->getFormSettingValue('lists');

        if ($list = ArrayHelper::firstWhere($lists, 'id', $this->listId)) {
            return $list;
        }

        return [];
    }
}

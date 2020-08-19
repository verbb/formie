<?php
namespace verbb\formie\base;

use verbb\formie\Formie;
use verbb\formie\base\ThirdPartyIntegration;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\events\SendIntegrationPayloadEvent;
use verbb\formie\helpers\UrlHelper;
use verbb\formie\models\EmailMarketingList;

use Craft;
use craft\base\Model;
use craft\helpers\StringHelper;
use craft\helpers\UrlHelper as CraftUrlHelper;
use craft\web\Response;

use League\OAuth2\Client\Provider\GenericProvider;

abstract class EmailMarketing extends ThirdPartyIntegration
{
    // Properties
    // =========================================================================

    public $listId;

    
    // Public Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public function getIconUrl(): string
    {
        $handle = StringHelper::toKebabCase($this->handle);

        return Craft::$app->getAssetManager()->getPublishedUrl("@verbb/formie/web/assets/emailmarketing/dist/img/{$handle}.svg", true);
    }

    /**
     * @inheritDoc
     */
    public function getSettingsHtml(): string
    {
        $handle = StringHelper::toKebabCase($this->handle);

        return Craft::$app->getView()->renderTemplate("formie/integrations/email-marketing/{$handle}/_plugin-settings", [
            'integration' => $this,
        ]);
    }

    /**
     * @inheritDoc
     */
    public function getFormSettingsHtml(Form $form): string
    {
        $handle = StringHelper::toKebabCase($this->handle);

        return Craft::$app->getView()->renderTemplate("formie/integrations/email-marketing/{$handle}/_form-settings", [
            'integration' => $this,
            'form' => $form,
            'listOptions' => $this->getListOptions(),
        ]);
    }

    /**
     * @inheritDoc
     */
    public function getAllLists($useCache = true)
    {
        $cacheKey = 'formie-email-' . $this->handle . '-lists';
        $cache = Craft::$app->getCache();

        if ($useCache && $lists = $cache->get($cacheKey)) {
            return $lists;
        }

        $lists = $this->fetchLists();

        $cache->set($cacheKey, $lists);

        return $lists;
    }

    /**
     * @inheritDoc
     */
    public function getListFields($listId = null)
    {
        $fields = [];

        if (!$listId) {
            $listId = $this->listId;
        }

        $list = $this->getListById($listId);

        foreach ($list->fields as $listField) {
            $fields[] = [
                'name' => $listField->name,
                'handle' => $listField->tag,
                'required' => $listField->required,
            ];
        }

        return $fields;
    }

    /**
     * @inheritDoc
     */
    public function validateFieldMapping($attribute)
    {
        if ($this->enabled) {
            // Ensure we check against any required fields
            $list = $this->getListById($this->listId);

            foreach ($list->fields as $field) {
                $value = $this->fieldMapping[$field->tag] ?? '';

                if ($field->required && $value === '') {
                    $this->addError($attribute, Craft::t('formie', '{name} must be mapped.', ['name' => $field->name]));
                    return;
                }
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['listId'], 'required'];
        $rules[] = [['fieldMapping'], 'validateFieldMapping'];

        return $rules;
    }

    /**
     * @inheritDoc
     */
    public function getElementFieldsFromRequest($request)
    {
        $listId = $request->getParam('listId');

        if (!$listId) {
            return ['error' => Craft::t('formie', 'No “{listId}” provided.')];
        }

        return $this->getListFields($listId);
    }

    /**
     * @inheritDoc
     */
    public function getListOptions($useCache = true): array
    {
        $options = [
            ['label' => Craft::t('formie', 'Select an option'), 'value' => ''],
        ];

        $lists = $this->getAllLists($useCache);

        foreach ($lists as $list) {
             $options[] = ['label' => $list->name, 'value' => $list->id];
        }

        return $options;
    }


    // Protected Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    protected function getListById($listId)
    {
        $lists = $this->getAllLists();

        foreach ($lists as $list) {
            if ($list->id === $listId) {
                return $list;
            }
        }

        return new EmailMarketingList();
    }

    /**
     * @inheritDoc
     */
    protected function getFieldMappingValues(Submission $submission)
    {
        $fieldValues = [];

        foreach ($this->fieldMapping as $tag => $formFieldHandle) {
            if ($formFieldHandle) {
                $formFieldHandle = str_replace(['{', '}'], ['', ''], $formFieldHandle);

                // Convert to string. We'll introduce more complex field handling in the future, but this will
                // be controlled at the integration-level. Some providers might only handle an address as a string
                // others might accept an array of content. The integration should handle this...
                $fieldValues[$tag] = (string)$submission->getFieldValue($formFieldHandle);
            }
        }

        return $fieldValues;
    }
}

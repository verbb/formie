<?php
namespace verbb\formie\integrations\elements;

use verbb\formie\Formie;
use verbb\formie\base\Integration;
use verbb\formie\base\Element;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\models\IntegrationCollection;
use verbb\formie\models\IntegrationField;
use verbb\formie\models\IntegrationFormSettings;
use verbb\formie\models\IntegrationResponse;

use Craft;
use craft\base\Element as CraftElement;
use craft\elements\Entry as EntryElement;
use craft\elements\User;
use craft\helpers\ArrayHelper;
use craft\helpers\DateTimeHelper;
use craft\helpers\Json;
use craft\helpers\Template;
use craft\web\View;

class Entry extends Element
{
    // Properties
    // =========================================================================

    public $entryTypeId;
    public $defaultAuthorId;
    public $createDraft;


    // Public Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public static function displayName(): string
    {
        return Craft::t('formie', 'Entry');
    }

    /**
     * @inheritDoc
     */
    public function getDescription(): string
    {
        return Craft::t('formie', 'Map content provided by form submissions to create Entry elements.');
    }

    /**
     * @inheritDoc
     */
    public function defineRules(): array
    {
        $rules = parent::defineRules();

        // Validate the following when saving form settings
        $rules[] = [['entryTypeId', 'defaultAuthorId'], 'required', 'on' => [Integration::SCENARIO_FORM]];

        // Find the field for the entry type - a little trickier due to nested in sections
        $fields = $this->_getEntryTypeSettings()->fields ?? [];

        $rules[] = [['fieldMapping'], 'validateFieldMapping', 'params' => $fields, 'when' => function($model) {
            return $model->enabled;
        }, 'on' => [Integration::SCENARIO_FORM]];

        return $rules;
    }

    /**
     * @inheritDoc
     */
    public function fetchFormSettings()
    {
        $customFields = [];

        $sections = Craft::$app->getSections()->getAllSections();

        foreach ($sections as $section) {
            if ($section->type === 'single') {
                continue;
            }

            foreach ($section->getEntryTypes() as $entryType) {
                $fields = [];

                foreach ($entryType->getFieldLayout()->getFields() as $field) {
                    $fields[] = new IntegrationField([
                        'handle' => $field->handle,
                        'name' => $field->name,
                        'type' => $this->getFieldTypeForField(get_class($field)),
                        'required' => (bool)$field->required,
                    ]);
                }

                $customFields[$section->name][] = new IntegrationCollection([
                    'id' => $entryType->id,
                    'name' => $entryType->name,
                    'fields' => $fields,
                ]);
            }
        }

        return new IntegrationFormSettings([
            'elements' => $customFields,
            'attributes' => $this->getElementAttributes(),
        ]);
    }

    /**
     * @inheritDoc
     */
    public function getElementAttributes()
    {
        return [
            new IntegrationField([
                'name' => Craft::t('app', 'Title'),
                'handle' => 'title',
            ]),
            new IntegrationField([
                'name' => Craft::t('app', 'Slug'),
                'handle' => 'slug',
            ]),
            new IntegrationField([
                'name' => Craft::t('app', 'Author'),
                'handle' => 'author',
                'type' => IntegrationField::TYPE_ARRAY,
            ]),
            new IntegrationField([
                'name' => Craft::t('app', 'Post Date'),
                'handle' => 'postDate',
                'type' => IntegrationField::TYPE_DATETIME,
            ]),
            new IntegrationField([
                'name' => Craft::t('app', 'Expiry Date'),
                'handle' => 'expiryDate',
                'type' => IntegrationField::TYPE_DATETIME,
            ]),
            new IntegrationField([
                'name' => Craft::t('app', 'Enabled'),
                'handle' => 'enabled',
                'type' => IntegrationField::TYPE_BOOLEAN,
            ]),
            new IntegrationField([
                'name' => Craft::t('app', 'Date Created'),
                'handle' => 'dateCreated',
                'type' => IntegrationField::TYPE_DATETIME,
            ]),
            new IntegrationField([
                'name' => Craft::t('app', 'Date Updated'),
                'handle' => 'dateUpdated',
                'type' => IntegrationField::TYPE_DATETIME,
            ]),
        ];
    }

    /**
     * @inheritDoc
     */
    public function getUpdateAttributes()
    {
        $attributes = [
            new IntegrationField([
                'name' => Craft::t('app', 'ID'),
                'handle' => 'id',
            ]),
            new IntegrationField([
                'name' => Craft::t('app', 'Title'),
                'handle' => 'title',
            ]),
            new IntegrationField([
                'name' => Craft::t('app', 'Slug'),
                'handle' => 'slug',
            ]),
            new IntegrationField([
                'name' => Craft::t('app', 'Site'),
                'handle' => 'site',
            ]),
        ];

        $sections = Craft::$app->getSections()->getAllSections();

        foreach ($sections as $section) {
            if ($section->type === 'single') {
                continue;
            }

            foreach ($section->getEntryTypes() as $entryType) {
                foreach ($entryType->getFieldLayout()->getFields() as $field) {
                    if (!$this->fieldCanBeUniqueId($field)) {
                        continue;
                    }

                    $attributes[] = new IntegrationField([
                        'handle' => $field->handle,
                        'name' => $field->name,
                        'type' => $this->getFieldTypeForField(get_class($field)),
                    ]);
                }
            }
        }

        return $attributes;
    }

    /**
     * @inheritDoc
     */
    public function sendPayload(Submission $submission)
    {
        if (!$this->entryTypeId) {
            Integration::error(Craft::t('formie', 'Unable to save element integration. No `entryTypeId`.'), true);
            
            return false;
        }

        try {
            $entryType = Craft::$app->getSections()->getEntryTypeById($this->entryTypeId);

            $entry = $this->getElementForPayload(EntryElement::class, $submission);
            $entry->typeId = $entryType->id;
            $entry->sectionId = $entryType->sectionId;

            $attributeValues = $this->getFieldMappingValues($submission, $this->attributeMapping, $this->getElementAttributes());
            $attributeValues = array_filter($attributeValues);

            foreach ($attributeValues as $entryFieldHandle => $fieldValue) {
                if ($entryFieldHandle === 'author') {
                    if (isset($fieldValue[0])) {
                        $entry->authorId = $fieldValue[0] ?? null;
                    }
                } else {
                    $entry->{$entryFieldHandle} = $fieldValue;
                }
            }

            $fields = $this->_getEntryTypeSettings()->fields ?? [];
            $fieldValues = $this->getFieldMappingValues($submission, $this->fieldMapping, $fields);
            $fieldValues = array_filter($fieldValues);

            $entry->setFieldValues($fieldValues);

            // Check if we need to create a new draft
            if ($this->createDraft) {
                $authorId = $entry->authorId ?? Craft::$app->getUser()->getId();

                // Is this a brand-new entry?
                if (!$entry->id) {
                    $entry->setScenario(CraftElement::SCENARIO_ESSENTIALS);
                    
                    if (!Craft::$app->getDrafts()->saveElementAsDraft($entry, $authorId)) {
                        Integration::error(Craft::t('formie', 'Unable to save “{type}” draft element integration. Error: {error}.', [
                            'type' => $this->handle,
                            'error' => Json::encode($entry->getErrors()),
                        ]), true);
                        
                        return false;
                    }
                } else {
                    // Otherwise, create a new draft on the entry
                    Craft::$app->getDrafts()->createDraft($entry, $authorId);
                }

                return true;
            }

            if (!$entry->validate()) {
                Integration::error($this, Craft::t('formie', 'Unable to validate “{type}” element integration. Error: {error}.', [
                    'type' => $this->handle,
                    'error' => Json::encode($entry->getErrors()),
                ]), true);

                return false;
            }

            if (!Craft::$app->getElements()->saveElement($entry)) {
                Integration::error(Craft::t('formie', 'Unable to save “{type}” element integration. Error: {error}.', [
                    'type' => $this->handle,
                    'error' => Json::encode($entry->getErrors()),
                ]), true);
                
                return false;
            }
        } catch (\Throwable $e) {
            $error = Craft::t('formie', 'Element integration failed for submission “{submission}”. Error: {error} {file}:{line}', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'submission' => $submission->id,
            ]);

            Formie::error($error);

            return new IntegrationResponse(false, $error);
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public function getAuthor($form)
    {
        $defaultAuthorId = $form->settings->integrations[$this->handle]['defaultAuthorId'] ?? '';

        if (!$defaultAuthorId) {
            $defaultAuthorId = $this->defaultAuthorId;
        }

        if ($defaultAuthorId) {
            return User::find()->id($defaultAuthorId)->all();
        }

        return [Craft::$app->getUser()->getIdentity()];
    }


    // Private Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    private function _getEntryTypeSettings()
    {
        $entryTypes = $this->getFormSettingValue('elements');

        foreach ($entryTypes as $key => $entryType) {
            if ($collection = ArrayHelper::firstWhere($entryType, 'id', $this->entryTypeId)) {
                return $collection;
            }
        }

        return [];
    }
}
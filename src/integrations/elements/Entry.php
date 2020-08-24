<?php
namespace verbb\formie\integrations\elements;

use verbb\formie\Formie;
use verbb\formie\base\Integration;
use verbb\formie\base\Element;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\models\IntegrationField;

use Craft;
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
    public $attributeMapping;


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

        return $rules;
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

    /**
     * @inheritDoc
     */
    public function getFormSettings($useCache = true)
    {
        $settings = [];

        $sections = Craft::$app->getSections()->getAllSections();

        foreach ($sections as $section) {
            if ($section->type === 'single') {
                continue;
            }

            foreach ($section->getEntryTypes() as $entryType) {
                $fields = [];

                foreach ($entryType->getFields() as $field) {
                    $fields[] = new IntegrationField([
                        'handle' => $field->id,
                        'name' => $field->name,
                        'type' => get_class($field),
                        'required' => $field->required,
                    ]);
                }

                $settings['elements'][$section->name][] = [
                    'id' => $entryType->id,
                    'name' => $entryType->name,
                    'fields' => $fields,
                ];
            }
        }

        return $settings;
    }

    /**
     * @inheritDoc
     */
    public function getElementAttributes()
    {
        return [
            [
                'name' => Craft::t('app', 'Title'),
                'handle' => 'title',
            ],
            [
                'name' => Craft::t('app', 'Slug'),
                'handle' => 'slug',
            ],
            [
                'name' => Craft::t('app', 'Author'),
                'handle' => 'author',
            ],
            [
                'name' => Craft::t('app', 'Post Date'),
                'handle' => 'postDate',
            ],
            [
                'name' => Craft::t('app', 'Expiry Date'),
                'handle' => 'expiryDate',
            ],
            [
                'name' => Craft::t('app', 'Enabled'),
                'handle' => 'enabled',
            ],
            [
                'name' => Craft::t('app', 'Date Created'),
                'handle' => 'dateCreated',
            ],
            [
                'name' => Craft::t('app', 'Date Updated'),
                'handle' => 'dateUpdated',
            ],
        ];
    }

    /**
     * @inheritDoc
     */
    public function sendPayload(Submission $submission)
    {
        if (!$this->entryTypeId) {
            Formie::error('Unable to save element integration. No `entryTypeId`.');

            return false;
        }

        try {
            $entryType = Craft::$app->getSections()->getEntryTypeById($this->entryTypeId);

            $entry = new EntryElement();
            $entry->typeId = $entryType->id;
            $entry->sectionId = $entryType->sectionId;

            foreach ($this->attributeMapping as $entryFieldHandle => $formFieldHandle) {
                if ($formFieldHandle) {
                    $formFieldHandle = str_replace(['{', '}'], ['', ''], $formFieldHandle);
                    $fieldValue = $submission->{$formFieldHandle};

                    if ($entryFieldHandle === 'author') {
                        $entry->authorId = $fieldValue->one()->id ?? '';
                    } else {
                        $entry->{$entryFieldHandle} = $fieldValue;
                    }
                }
            }

            foreach ($this->fieldMapping as $entryFieldHandle => $formFieldHandle) {
                if ($formFieldHandle) {
                    $formFieldHandle = str_replace(['{', '}'], ['', ''], $formFieldHandle);
                    $fieldValue = $submission->{$formFieldHandle};

                    $entry->setFieldValue($entryFieldHandle, $fieldValue);
                }
            }

            if (!$entry->validate()) {
                Formie::error('Unable to validate “{type}” element integration. Error: {error}.', [
                    'type' => $this->handle,
                    'error' => Json::encode($entry->getErrors()),
                ]);

                return false;
            }

            if (!Craft::$app->getElements()->saveElement($entry)) {
                Formie::error('Unable to save “{type}” element integration. Error: {error}.', [
                    'type' => $this->handle,
                    'error' => Json::encode($entry->getErrors()),
                ]);
                
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

            return false;
        }

        return true;
    }
}
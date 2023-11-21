<?php
namespace verbb\formie\models;

use verbb\formie\Formie;
use verbb\formie\base\FormFieldInterface;
use verbb\formie\elements\Submission;
use verbb\formie\fields\formfields\MissingField;
use verbb\formie\helpers\ConditionsHelper;

use Craft;
use craft\base\Field;
use craft\base\FieldInterface;
use craft\base\Model;
use craft\errors\MissingComponentException;
use craft\fieldlayoutelements\CustomField;
use craft\helpers\ArrayHelper;
use craft\helpers\Component;
use craft\helpers\Json;
use craft\models\FieldLayout as CraftFieldLayout;

use yii\base\InvalidConfigException;

class FormRow extends Model
{
    // Properties
    // =========================================================================

    private array $_fields = [];


    // Public Methods
    // =========================================================================

    public function __construct($config = [])
    {
        unset($config['id']);

        parent::__construct($config);
    }

    public function attributes(): array
    {
        $attributes = parent::attributes();
        $attributes[] = 'fields';
        
        return $attributes;
    }

    public function getFields(): array
    {
        return $this->_fields;
    }

    public function setFields(array $fields): void
    {
        $fieldsService = Craft::$app->getFields();

        foreach ($fields as $field) {
            if (!($field instanceof FormFieldInterface)) {
                // If loading in the config from the database, we just have a reference to the UID for the field.
                $fieldUid = $field['fieldUid'] ?? null;
                $required = $field['required'] ?? false;

                if ($fieldUid && $fieldInstance = $fieldsService->getFieldByUid($fieldUid)) {
                    // The required state is stored in our config
                    $fieldInstance->required = $required;

                    $this->_fields[] = $fieldInstance;
                } else {
                    // Otherwise, these scenarios handle saving fields from the form builder
                    $fieldId = (int)($field['id'] ?? null);

                    // Rename the `label` to `name`
                    $field['settings']['name'] = ArrayHelper::remove($field['settings'], 'label');

                    // If passing in a saved field, grab it first, then populate the settings
                    if ($fieldId && $fieldInstance = $fieldsService->getFieldById($fieldId)) {
                        $fieldInstance->setAttributes($field['settings'], false);

                        $this->_fields[] = $fieldInstance;
                    } else {
                        // Create a (new) field class from the form builder
                        $this->_fields[] = $this->createField($field);
                    }
                }
            }
        }
    }

    public function createField(array $config): FormFieldInterface
    {
        // Grab just the `settings` for the field from form builder, which houses our writeable attributes
        $fieldConfig = $config['settings'] ?? $config;
        $fieldConfig['type'] = $config['type'] ?? MissingField::class;

        try {
            $field = Component::createComponent($fieldConfig, FormFieldInterface::class);
        } catch (MissingComponentException $e) {
            $fieldConfig['errorMessage'] = $e->getMessage();
            $fieldConfig['expectedType'] = $fieldConfig['type'];
            unset($fieldConfig['type']);

            $field = new MissingField($fieldConfig);
        }

        return $field;
    }

    public function getFormBuilderConfig(): array
    {
        return [
            'errors' => $this->getErrors(),
            'fields' => array_map(function($field) {
                return $field->getFormBuilderConfig();
            }, $this->getFields()),
        ];
    }

    public function validateFields(): void
    {
        foreach ($this->getFields() as $field) {
            if (!$field->validate()) {
                $this->addError('fields', $field->getErrors());
            }
        }
    }


    // Protected Methods
    // =========================================================================

    protected function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['fields'], 'validateFields'];

        return $rules;
    }
}

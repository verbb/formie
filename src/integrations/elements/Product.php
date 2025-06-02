<?php
namespace verbb\formie\integrations\elements;

use verbb\formie\Formie;
use verbb\formie\base\Integration;
use verbb\formie\base\Element;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\events\ModifyFieldIntegrationValueEvent;
use verbb\formie\helpers\ArrayHelper;
use verbb\formie\models\IntegrationCollection;
use verbb\formie\models\IntegrationField;
use verbb\formie\models\IntegrationFormSettings;
use verbb\formie\models\IntegrationResponse;

use Craft;
use craft\base\Element as CraftElement;
use craft\elements\User;
use craft\helpers\DateTimeHelper;
use craft\helpers\Json;
use craft\helpers\Template;
use craft\web\View;

use yii\base\Event;

use craft\commerce\Plugin as Commerce;
use craft\commerce\elements\Product as ProductElement;
use craft\commerce\elements\Variant;

class Product extends Element
{
    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('formie', 'Product');
    }

    public static function getRequiredPlugins(): array
    {
        return ['commerce'];
    }


    // Properties
    // =========================================================================

    public ?int $productTypeId = null;
    public mixed $defaultAuthorId = null;


    // Public Methods
    // =========================================================================

    public function getDescription(): string
    {
        return Craft::t('formie', 'Map content provided by form submissions to create {name} elements.', ['name' => static::displayName()]);
    }

    public function fetchFormSettings()
    {
        $customFields = [];

        $productTypes = Commerce::getInstance()->getProductTypes()->getAllProductTypes();

        foreach ($productTypes as $productType) {
            $fields = $this->getFieldLayoutFields($productType->getFieldLayout());

            $customFields[] = new IntegrationCollection([
                'id' => $productType->id,
                'name' => $productType->name,
                'fields' => $fields,
            ]);
        }

        return new IntegrationFormSettings([
            'elements' => $customFields,
            'attributes' => $this->getElementAttributes(),
        ]);
    }

    public function getElementAttributes()
    {
        return [
            new IntegrationField([
                'name' => Craft::t('app', 'Title'),
                'handle' => 'title',
                'required' => true,
            ]),
            new IntegrationField([
                'name' => Craft::t('app', 'Site ID'),
                'handle' => 'siteId',
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
                'type' => IntegrationField::TYPE_DATECLASS,
            ]),
            new IntegrationField([
                'name' => Craft::t('app', 'Expiry Date'),
                'handle' => 'expiryDate',
                'type' => IntegrationField::TYPE_DATECLASS,
            ]),
            new IntegrationField([
                'name' => Craft::t('app', 'Enabled'),
                'handle' => 'enabled',
                'type' => IntegrationField::TYPE_BOOLEAN,
            ]),
            new IntegrationField([
                'name' => Craft::t('app', 'Date Created'),
                'handle' => 'dateCreated',
                'type' => IntegrationField::TYPE_DATECLASS,
            ]),
            new IntegrationField([
                'name' => Craft::t('app', 'Date Updated'),
                'handle' => 'dateUpdated',
                'type' => IntegrationField::TYPE_DATECLASS,
            ]),

            // Support just the default variant for now
            new IntegrationField([
                'name' => Craft::t('commerce', 'SKU'),
                'handle' => 'variants.new1.sku',
                'required' => true,
            ]),
            new IntegrationField([
                'name' => Craft::t('commerce', 'Price'),
                'handle' => 'variants.new1.price',
                'required' => true,
            ]),
            new IntegrationField([
                'name' => Craft::t('commerce', 'Stock'),
                'handle' => 'variants.new1.stock',
            ]),
            new IntegrationField([
                'name' => Craft::t('commerce', 'Unlimited Stock'),
                'handle' => 'variants.new1.hasUnlimitedStock',
            ]),
            new IntegrationField([
                'name' => Craft::t('commerce', 'Minimum allowed quantity'),
                'handle' => 'variants.new1.minQty',
            ]),
            new IntegrationField([
                'name' => Craft::t('commerce', 'Maximum allowed quantity'),
                'handle' => 'variants.new1.maxQty',
            ]),
            new IntegrationField([
                'name' => Craft::t('commerce', 'Length'),
                'handle' => 'variants.new1.length',
            ]),
            new IntegrationField([
                'name' => Craft::t('commerce', 'Width'),
                'handle' => 'variants.new1.width',
            ]),
            new IntegrationField([
                'name' => Craft::t('commerce', 'Height'),
                'handle' => 'variants.new1.height',
            ]),
            new IntegrationField([
                'name' => Craft::t('commerce', 'Weight'),
                'handle' => 'variants.new1.weight',
            ]),
        ];
    }

    public function getUpdateAttributes()
    {
        $attributes = [];

        $productTypes = Commerce::getInstance()->getProductTypes()->getAllProductTypes();

        foreach ($productTypes as $productType) {
            $attributes[$productType->id] = [
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

            if ($fieldLayout = $productType->getFieldLayout()) {
                foreach ($fieldLayout->getCustomFields() as $field) {
                    if (!$this->fieldCanBeUniqueId($field)) {
                        continue;
                    }

                    $attributes[$productType->id][] = new IntegrationField([
                        'handle' => $field->handle,
                        'name' => $field->name,
                        'type' => $this->getFieldTypeForField(get_class($field)),
                        'sourceType' => get_class($field),
                    ]);
                }
            }
        }

        return $attributes;
    }

    public function sendPayload(Submission $submission)
    {
        if (!$this->productTypeId) {
            Integration::error($this, Craft::t('formie', 'Unable to save element integration. No `productTypeId`.'), true);

            return false;
        }

        try {
            $productType = Commerce::getInstance()->getProductTypes()->getProductTypeById($this->productTypeId);

            $product = $this->getElementForPayload(ProductElement::class, $this->productTypeId, $submission, [
                'typeId' => $productType->id,
            ]);

            $product->siteId = $submission->siteId;
            $product->typeId = $productType->id;

            $attributeValues = $this->getFieldMappingValues($submission, $this->attributeMapping, $this->getElementAttributes());

            // Filter null values
            if (!$this->overwriteValues) {
                $attributeValues = ArrayHelper::filterNullValues($attributeValues);
            }

            // Populate variant values, even if null, as Commerce expects at least a value
            $attributeValues['variants.new1.minQty'] = $attributeValues['variants.new1.minQty'] ?? null;
            $attributeValues['variants.new1.maxQty'] = $attributeValues['variants.new1.maxQty'] ?? null;

            foreach (ArrayHelper::expand($attributeValues) as $productFieldHandle => $fieldValue) {
                if ($productFieldHandle === 'author') {
                    if (isset($fieldValue[0])) {
                        $product->authorId = $fieldValue[0] ?? null;
                    }
                } else {
                    $product->{$productFieldHandle} = $fieldValue;
                }
            }

            $fields = $this->_getProductTypeSettings()->fields ?? [];
            $fieldValues = $this->getFieldMappingValues($submission, $this->fieldMapping, $fields);

            // Filter null values
            if (!$this->overwriteValues) {
                $fieldValues = ArrayHelper::filterNullValues($fieldValues);
            }

            $product->setFieldValues($fieldValues);

            // Although empty, because we pass via reference, we need variables
            $endpoint = '';
            $method = '';

            // Allow events to cancel sending - return as success            
            if (!$this->beforeSendPayload($submission, $endpoint, $product, $method)) {
                return true;
            }

            if (!$product->validate()) {
                Integration::error($this, Craft::t('formie', 'Unable to validate “{type}” element integration. Error: {error}.', [
                    'type' => $this->handle,
                    'error' => Json::encode($product->getErrors()),
                ]), true);

                return false;
            }

            if (!Craft::$app->getElements()->saveElement($product)) {
                Integration::error($this, Craft::t('formie', 'Unable to save “{type}” element integration. Error: {error}.', [
                    'type' => $this->handle,
                    'error' => Json::encode($product->getErrors()),
                ]), true);

                return false;
            }

            // Allow events to say the response is invalid
            if (!$this->afterSendPayload($submission, '', $product, '', [])) {
                return true;
            }
        } catch (\Throwable $e) {
            $error = Craft::t('formie', 'Element integration failed for submission “{submission}”. Error: {error} {file}:{line}', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'submission' => $submission->id,
            ]);

            Formie::error($error);

            return new IntegrationResponse(false, [$error]);
        }

        return true;
    }

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

    
    // Protected Methods
    // =========================================================================

    protected function defineRules(): array
    {
        $rules = parent::defineRules();

        // Validate the following when saving form settings
        $rules[] = [['productTypeId', 'defaultAuthorId'], 'required', 'on' => [Integration::SCENARIO_FORM]];

        $fields = $this->_getProductTypeSettings()->fields ?? [];

        $rules[] = [['fieldMapping'], 'validateFieldMapping', 'params' => $fields, 'when' => function($model) {
            return $model->enabled;
        }, 'on' => [Integration::SCENARIO_FORM]];

        return $rules;
    }


    // Private Methods
    // =========================================================================

    private function _getProductTypeSettings()
    {
        $productTypes = $this->getFormSettingValue('elements');

        return ArrayHelper::firstWhere($productTypes, 'id', $this->productTypeId);
    }
}
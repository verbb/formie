<?php
namespace verbb\formie\base;

use verbb\formie\models\IntegrationField;

use Craft;
use craft\base\Element;
use craft\base\ElementInterface;
use craft\elements\db\ElementQuery;
use craft\elements\db\ElementQueryInterface;
use craft\fields\Assets;
use craft\fields\Categories;
use craft\fields\Checkboxes;
use craft\fields\Entries;
use craft\fields\Matrix;
use craft\fields\MultiSelect;
use craft\fields\Table;
use craft\fields\Tags;
use craft\fields\Users;
use craft\fields\data\MultiOptionsFieldData;
use craft\fields\data\OptionData;
use craft\fields\data\SingleOptionFieldData;
use craft\helpers\Cp;
use craft\helpers\ArrayHelper;
use craft\helpers\Json;
use craft\helpers\Template as TemplateHelper;

use Throwable;

use Twig\Markup;

trait RelationFieldTrait
{
    // Properties
    // =========================================================================

    public ?string $limitOptions = null;
    public string $displayType = 'dropdown';
    public string $labelSource = 'title';
    public string $orderBy = 'title ASC';
    public bool $multiple = false;

    protected ?ElementQuery $elementsQuery = null;


    // Public Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public function getIsFieldset(): bool
    {
        if ($this->displayType === 'checkboxes') {
            return true;
        }

        if ($this->displayType === 'radio') {
            return true;
        }

        return false;
    }

    public function getIsMultiDropdown(): bool
    {
        return ($this->displayType === 'dropdown' && $this->multiple);
    }

    /**
     * @inheritDoc
     */
    public function renderLabel(): bool
    {
        return !$this->getIsFieldset();
    }

    public function getPreviewElements(): array
    {
        $options = array_map(function($input) {
            return ['label' => $this->_getElementLabel($input), 'value' => $input->id];
        }, $this->getElementsQuery()->limit(5)->all());

        return [
            'total' => $this->getElementsQuery()->count(),
            'options' => $options,
        ];
    }

    public function modifyFieldSettings($settings): array
    {
        $defaultValue = $this->defaultValue ?? [];

        // For a default value, supply extra content that can't be called directly in Vue, like it can in Twig.
        if ($ids = ArrayHelper::getColumn($defaultValue, 'id')) {
            $elements = static::elementType()::find()->id($ids)->all();

            // Maintain an options array, so we can keep track of the label in Vue, not just the saved value
            $settings['defaultValueOptions'] = array_map(function($input) {
                return ['label' => $this->_getElementLabel($input), 'value' => $input->id];
            }, $elements);

            // Render the HTML needed for the element select field (for default value). jQuery needs DOM manipulation
            // so while gross, we have to supply the raw HTML, as opposed to models in the Vue-way.
            $settings['defaultValueHtml'] = Craft::$app->getView()->renderTemplate('formie/_includes/element-select-input-elements', ['elements' => $elements]);
        }

        // For certain display types, pre-fetch elements for use in the preview in the CP for the field. Saves an initial Ajax request
        if ($this->displayType === 'checkboxes' || $this->displayType === 'radio' || $this->getIsMultiDropdown()) {
            $settings['elements'] = $this->getPreviewElements();
        }

        return $settings;
    }

    public function getCpElementHtml(array $context): ?Markup
    {
        if (!isset($context['element'])) {
            return null;
        }

        if (isset($context['size']) && in_array($context['size'], [Cp::ELEMENT_SIZE_SMALL, Cp::ELEMENT_SIZE_LARGE], true)) {
            $size = $context['size'];
        } else {
            $size = (isset($context['viewMode']) && $context['viewMode'] === 'thumbs') ? Cp::ELEMENT_SIZE_LARGE : Cp::ELEMENT_SIZE_SMALL;
        }

        return TemplateHelper::raw(Cp::elementHtml(
            $context['element'],
            $context['context'] ?? 'index',
            $size,
            $context['name'] ?? null,
            true,
            true,
            true,
            true,
            $context['single'] ?? false
        ));
    }

    /**
     * @inheritdoc
     */
    public function getInputHtml(mixed $value, ?ElementInterface $element = null): string
    {
        /** @var Element|null $element */
        if ($element !== null && $element->hasEagerLoadedElements($this->handle)) {
            $value = $element->getEagerLoadedElements($this->handle);
        } else {
            /** @var ElementQueryInterface $value */
            $value = $this->_all($value, $element);
        }

        /** @var ElementQuery|array $value */
        $variables = $this->inputTemplateVariables($value, $element);

        $variables['field'] = $this;

        return Craft::$app->getView()->renderTemplate($this->inputTemplate, $variables);
    }

    public function getDefaultValueQuery()
    {
        $defaultValue = $this->defaultValue ?? '';

        if ($defaultValue instanceof ElementQuery) {
            $defaultValue = $defaultValue->all();
        }

        // If passing in a single ID, normalise it
        if (!is_array($defaultValue)) {
            $defaultValue = $defaultValue ? [['id' => $defaultValue]] : [];
        }

        // Just in case there are empty items
        $defaultValue = array_filter($defaultValue);

        if ($defaultValue) {
            // Handle when setting via a multidimensional array with `id`
            $ids = array_filter(ArrayHelper::getColumn($defaultValue, 'id'));

            // If nothing found, we might be setting an array of IDs
            if (!$ids) {
                $ids = $defaultValue;
            }

            if ($ids) {
                return static::elementType()::find()->id($ids);
            }
        }

        return null;
    }

    public function populateValue($value): void
    {
        $query = static::elementType()::find()->id($value);

        $this->defaultValue = $query;
    }

    public function getFieldOptions(): array
    {
        $options = [];

        if ($this->displayType === 'dropdown') {
            $options[] = ['label' => $this->placeholder, 'value' => ''];
        }

        foreach ($this->getElementsQuery()->all() as $element) {
            // Important to cast as a string, otherwise Twig will struggle to compare
            $options[] = ['label' => $this->_getElementLabel($element), 'value' => (string)$element->id];
        }

        return $options;
    }

    public function getDisplayTypeValue($value): MultiOptionsFieldData|SingleOptionFieldData|null
    {
        if ($this->displayType === 'checkboxes' || $this->getIsMultiDropdown()) {
            $options = [];

            if ($value) {
                foreach ($value->all() as $element) {
                    $options[] = new OptionData($this->_getElementLabel($element), $element->id, true);
                }
            }

            return new MultiOptionsFieldData($options);
        }

        if ($this->displayType === 'radio') {
            if ($value && $element = $value->one()) {
                return new SingleOptionFieldData($this->_getElementLabel($element), $element->id, true);
            }

            return null;
        }

        if ($this->displayType === 'dropdown') {
            if ($value && $element = $value->one()) {
                return new SingleOptionFieldData($this->_getElementLabel($element), $element->id, true);
            }

            return null;
        }

        return $value;
    }

    public function setElementsQuery($query): void
    {
        $this->elementsQuery = $query;
    }

    public function defineLabelSourceOptions(): array
    {
        return [];
    }

    public function getLabelSourceOptions(): array
    {
        return array_merge([
            ['value' => 'id', 'label' => Craft::t('app', 'ID')],
        ], $this->defineLabelSourceOptions(), [
            ['value' => 'dateCreated', 'label' => Craft::t('app', 'Date Created')],
            ['value' => 'dateUpdated', 'label' => Craft::t('app', 'Date Updated')],
        ]);
    }

    public function getOrderByOptions(): array
    {
        $options = [];

        foreach ($this->getLabelSourceOptions() as $opt) {
            $options[] = ['value' => $opt['value'] . ' ASC', 'label' => $opt['label'] . ' Ascending'];
            $options[] = ['value' => $opt['value'] . ' DESC', 'label' => $opt['label'] . ' Descending'];
        }

        return $options;
    }


    // Protected Methods
    // =========================================================================

    protected function defineValueAsString($value, ElementInterface $element = null): string
    {
        $value = $this->_all($value, $element)->all();

        return implode(', ', array_map(function($item) {
            return $this->_getElementLabel($item);
        }, $value));
    }

    protected function defineValueAsJson($value, ElementInterface $element = null): mixed
    {
        $value = $this->_all($value, $element)->all();

        return array_map(function($item) {
            return $this->_elementToArray($item);
        }, $value);
    }

    protected function defineValueForIntegration($value, $integrationField, $integration, ElementInterface $element = null, $fieldKey = ''): mixed
    {
        // Set the status to null to include disabled elements
        $value->status(null);

        // Send through a CSV of element titles, when mapping to a string
        if ($integrationField->getType() === IntegrationField::TYPE_STRING) {
            return $this->defineValueAsString($value, $element);
        }

        // When an array, assume a collection of IDs
        if ($integrationField->getType() === IntegrationField::TYPE_ARRAY) {
            return $value->ids();
        }

        // When a number, assume a single ID
        if ($integrationField->getType() === IntegrationField::TYPE_NUMBER) {
            return $value->ids()[0] ?? null;
        }

        // When a number, assume a single ID
        if ($integrationField->getType() === IntegrationField::TYPE_FLOAT) {
            return $value->ids()[0] ?? null;
        }

        return null;
    }

    protected function getStringCustomFieldOptions($fields): array
    {
        $options = [];

        // Better to opt-out fields, so we can always allow third-party ones which are impossible to check
        $excludedFields = [
            Assets::class,
            Categories::class,
            Checkboxes::class,
            Entries::class,
            Matrix::class,
            MultiSelect::class,
            Table::class,
            Tags::class,
            Users::class,
        ];

        foreach ($fields as $field) {
            if (in_array(get_class($field), $excludedFields)) {
                continue;
            }

            $options[] = ['label' => $field->name, 'value' => $field->handle];
        }

        return $options;
    }


    // Private Methods
    // =========================================================================

    /**
     * Returns a clone of the element query value, prepped to include disabled and cross-site elements.
     *
     * @param ElementQueryInterface $query
     * @param ElementInterface|null $element
     * @return ElementQueryInterface
     */
    private function _all(ElementQueryInterface $query, ElementInterface $element = null): ElementQueryInterface
    {
        $clone = clone $query;
        $clone
            ->status(null)
            ->siteId('*')
            ->unique();

        if ($element !== null) {
            $clone->preferSites([$this->targetSiteId($element)]);
        }
        return $clone;
    }

    private function _getElementLabel($element): string
    {
        try {
            return (string)$element->{$this->labelSource};
        } catch (Throwable $e) {

        }

        return $element->title;
    }

    private function _elementToArray($element)
    {
        // Watch out for nested element queries
        foreach ($element as $key => $value) {
            if ($value instanceof ElementQuery) {
                $elements = [];

                foreach ($value->all() as $nestedElement) {
                    $elements = $this->_elementToArray($nestedElement);
                }

                $element[$key] = $elements;
            }
        }

        return Json::decode(Json::encode($element));
    }

}

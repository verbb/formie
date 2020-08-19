<?php
namespace verbb\formie\fields\formfields;

use verbb\formie\base\FormField;
use verbb\formie\base\NestedFieldInterface;
use verbb\formie\base\NestedFieldTrait;
use verbb\formie\elements\Form;
use verbb\formie\elements\db\NestedFieldRowQuery;
use verbb\formie\elements\NestedFieldRow;
use verbb\formie\helpers\SchemaHelper;
use verbb\formie\web\assets\repeater\RepeaterAsset;

use Craft;
use craft\base\EagerLoadingFieldInterface;
use craft\base\Element;
use craft\base\ElementInterface;
use craft\helpers\Html;
use craft\helpers\Json;
use craft\validators\ArrayValidator;
use craft\web\View;

use Throwable;

class Repeater extends FormField implements NestedFieldInterface, EagerLoadingFieldInterface
{
    // Traits
    // =========================================================================

    use NestedFieldTrait;


    // Public Properties
    // =========================================================================

    /**
     * @var int
     */
    public $minRows;

    /**
     * @var int
     */
    public $maxRows;

    /**
     * @var string
     */
    public $addLabel;


    // Static Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public static function displayName(): string
    {
        return Craft::t('formie', 'Repeater');
    }

    /**
     * @inheritDoc
     */
    public static function getSvgIconPath(): string
    {
        return 'formie/_formfields/repeater/icon.svg';
    }

    /**
     * @inheritdoc
     */
    public static function hasContentColumn(): bool
    {
        return false;
    }


    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    protected function defineRules(): array
    {
        $rules = parent::defineRules();
        $rules[] = [['minRows', 'maxRows'], 'integer', 'min' => 0];
        return $rules;
    }

    /**
     * @inheritdoc
     */
    public function getElementValidationRules(): array
    {
        return [
            'validateRows',
            [
                ArrayValidator::class,
                'min' => $this->minRows ?: null,
                'max' => $this->maxRows ?: null,
                'tooFew' => Craft::t('formie', '{attribute} should contain at least {min, number} {min, plural, one{row} other{rows}}.'),
                'tooMany' => Craft::t('formie', '{attribute} should contain at most {max, number} {max, plural, one{row} other{rows}}.'),
                'skipOnEmpty' => false,
                'on' => Element::SCENARIO_LIVE,
            ],
        ];
    }

    /**
     * @inheritDoc
     */
    public function getIsFieldset(): bool
    {
        return true;
    }

    /**
     * @return array
     */
    public function getFieldDefaults(): array
    {
        return [
            'addLabel' => Craft::t('formie', 'Add another row'),
        ];
    }

    /**
     * @inheritDoc
     */
    public function getInputHtml($value, ElementInterface $element = null): string
    {
        /** @var Element $element */
        if ($element !== null && $element->hasEagerLoadedElements($this->handle)) {
            $value = $element->getEagerLoadedElements($this->handle);
        }

        if ($value instanceof NestedFieldRowQuery) {
            $value = $value->getCachedResult() ?? $value->limit(null)->anyStatus()->all();
        }

        $view = Craft::$app->getView();
        $id = $view->formatInputId($this->handle);

        // Get the row data
        $rowInfo = $this->_getRowInfoForInput($element);

        $createDefaultRows = $this->minRows != 0;

        $view->registerAssetBundle(RepeaterAsset::class);

        $js = 'var repeaterInput = new Craft.Formie.Repeater.Input(' .
            '"' . $view->namespaceInputId($id) . '", ' .
            Json::encode($rowInfo, JSON_UNESCAPED_UNICODE) . ', ' .
            '"' . $view->namespaceInputName($this->handle) . '", ' .
            Json::encode($this, JSON_UNESCAPED_UNICODE) .
        ');';

        // Safe to create the default blocks?
        if ($createDefaultRows) {
            $minRows = $this->minRows ?? 0;

            for ($i = count($value); $i < $minRows; $i++) {
                $js .= "\nrepeaterInput.addRow();";
            }
        }

        $view->registerJs($js, View::POS_END);

        return $view->renderTemplate('formie/_formfields/repeater/input', [
            'id' => $id,
            'name' => $this->handle,
            'rows' => $value,
            'nestedField' => $this,
        ]);
    }

    /**
     * @inheritDoc
     */
    public function getPreviewInputHtml(): string
    {
        return Craft::$app->getView()->renderTemplate('formie/_formfields/repeater/preview', [
            'field' => $this
        ]);
    }

    /**
     * @inheritdoc
     */
    public function getFrontEndJs(Form $form)
    {
        $src = Craft::$app->getAssetManager()->getPublishedUrl('@verbb/formie/web/assets/frontend/dist/js/fields/repeater.js', true);
        $onload = 'new FormieRepeater(' . Json::encode(['formId' => $form->id]) . ');';

        return [
            'src' => $src,
            'onload' => $onload,
        ];
    }

    /**
     * @inheritDoc
     */
    public function defineGeneralSchema(): array
    {
        return [
            SchemaHelper::labelField(),
            SchemaHelper::textField([
                'label' => Craft::t('formie', 'Add Label'),
                'help' => Craft::t('formie', 'The label for the button that adds another instance.'),
                'name' => 'addLabel',
                'validation' => 'required',
                'required' => true,
            ]),
        ];
    }

    /**
     * @inheritDoc
     */
    public function defineSettingsSchema(): array
    {
        return [
            SchemaHelper::textField([
                'label' => Craft::t('formie', 'Minimum instances'),
                'help' => Craft::t('formie', 'The minimum required number of instances of this repeater‘s fields that must be completed.'),
                'type' => 'number',
                'name' => 'minRows',
                'validation' => 'optional|number|min:0',
            ]),
            SchemaHelper::textField([
                'label' => Craft::t('formie', 'Maximum instances'),
                'help' => Craft::t('formie', 'The maximum required number of instances of this repeater‘s fields that must be completed.'),
                'type' => 'number',
                'name' => 'maxRows',
                'validation' => 'optional|number|min:0',
            ]),
        ];
    }

    /**
     * @inheritDoc
     */
    public function defineAppearanceSchema(): array
    {
        return [
            SchemaHelper::labelPosition($this),
            SchemaHelper::instructions(),
            SchemaHelper::instructionsPosition($this),
        ];
    }

    /**
     * @inheritDoc
     */
    public function defineAdvancedSchema(): array
    {
        return [
            SchemaHelper::handleField(),
            SchemaHelper::cssClasses(),
            SchemaHelper::containerAttributesField(),
        ];
    }


    // Private Properties
    // =========================================================================

    /**
     * Returns info field types for the repeater field input.
     *
     * @param ElementInterface|null $element
     * @return array|null
     * @throws Throwable
     */
    private function _getRowInfoForInput(ElementInterface $element = null)
    {
        $settings = $this->getSettings();

        $view = Craft::$app->getView();

        // Set a temporary namespace for these
        $originalNamespace = $view->getNamespace();
        $namespace = $view->namespaceInputName($this->handle . '[rows][__ROW__][fields]', $originalNamespace);
        $view->setNamespace($namespace);

        // Create a fake NestedFieldRow so the field types have a way to get at the owner element, if there is one
        $row = new NestedFieldRow();
        $row->fieldId = $this->id;

        if ($element) {
            $row->setOwner($element);
            $row->siteId = $element->siteId;
        }

        if ($fieldLayout = $this->getFieldLayout()) {
            $fieldLayoutFields = $fieldLayout->getFields();

            foreach ($fieldLayoutFields as $field) {
                $field->setIsFresh(true);
            }

            $view->startJsBuffer();

            $bodyHtml = $view->namespaceInputs($view->renderTemplate('formie/_formfields/repeater/fields', [
                'namespace' => null,
                'fields' => $fieldLayoutFields,
                'element' => $row,
                'settings' => $settings,
            ]));

            // Reset $_isFresh's
            foreach ($fieldLayoutFields as $field) {
                $field->setIsFresh(null);
            }

            $footHtml = $view->clearJsBuffer();

            $view->setNamespace($originalNamespace);

            return [
                'bodyHtml' => $bodyHtml,
                'footHtml' => $footHtml,
            ];
        }

        $view->setNamespace($originalNamespace);

        return null;
    }
}

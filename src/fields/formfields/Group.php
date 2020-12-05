<?php
namespace verbb\formie\fields\formfields;

use verbb\formie\base\FormField;
use verbb\formie\base\NestedFieldInterface;
use verbb\formie\base\NestedFieldTrait;
use verbb\formie\elements\Form;
use verbb\formie\elements\db\NestedFieldRowQuery;
use verbb\formie\helpers\SchemaHelper;

use Craft;
use craft\base\EagerLoadingFieldInterface;
use craft\base\Element;
use craft\base\ElementInterface;

class Group extends FormField implements NestedFieldInterface, EagerLoadingFieldInterface
{
    // Traits
    // =========================================================================

    use NestedFieldTrait {
        getFrontEndJsVariables as traitGetFrontEndJsVariables;
    }


    // Static Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public static function displayName(): string
    {
        return Craft::t('formie', 'Group');
    }

    /**
     * @inheritDoc
     */
    public static function getSvgIconPath(): string
    {
        return 'formie/_formfields/group/icon.svg';
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
     * @inheritDoc
     */
    public function getIsFieldset(): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function getValueAsString(ElementInterface $element)
    {
        return $this->getValue($element)[0] ?? [];
    }

    /**
     * @inheritDoc
     */
    public function serializeValueForIntegration($value, ElementInterface $element = null)
    {
        if ($value instanceof NestedFieldRowQuery) {
            if ($row = $value->one()) {
                return $row->getSerializedFieldValues();
            }
        }

        return $value;
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

        return $view->renderTemplate('formie/_formfields/group/input', [
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
        return Craft::$app->getView()->renderTemplate('formie/_formfields/group/preview', [
            'field' => $this
        ]);
    }

    /**
     * @inheritdoc
     */
    public function getFrontEndJsVariables(Form $form)
    {
        $modules = [];

        // $settings = [
        //     'fieldId' => $this->getHtmlWrapperId($form),
        //     'formSettings' => [
        //         'hasMultiplePages' => $form->hasMultiplePages(),
        //         'submitMethod' => $form->settings->submitMethod,
        //     ],
        // ];
        
        // $src = Craft::$app->getAssetManager()->getPublishedUrl('@verbb/formie/web/assets/frontend/dist/js/fields/repeater.js', true);
        // $onload = 'new FormieRepeater(' . Json::encode($settings) . ');';

        // $modules[] = [
        //     'src' => $src,
        //     'onload' => $onload,
        // ];

        // Check for any nested fields
        $modules = array_merge($modules, $this->traitGetFrontEndJsVariables($form));

        return $modules;
    }

    /**
     * @inheritDoc
     */
    public function defineGeneralSchema(): array
    {
        return [
            SchemaHelper::labelField(),
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
}

<?php
namespace verbb\formie\fields\formfields;

use verbb\formie\base\FormField;
use verbb\formie\Formie;
use verbb\formie\helpers\SchemaHelper;

use Craft;
use craft\base\ElementInterface;
use craft\base\PreviewableFieldInterface;
use craft\helpers\Template;

use yii\db\Schema;
use LitEmoji\LitEmoji;
use Twig\Markup;

class Heading extends FormField
{
    // Static Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public static function displayName(): string
    {
        return Craft::t('formie', 'Heading');
    }

    /**
     * @inheritDoc
     */
    public static function getSvgIconPath(): string
    {
        return 'formie/_formfields/heading/icon.svg';
    }

    /**
     * @inheritDoc
     */
    public static function hasContentColumn(): bool
    {
        return false;
    }


    // Properties
    // =========================================================================

    public $headingSize;


    // Public Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    protected function defineRules(): array
    {
        $rules = parent::defineRules();
        $rules[] = [['headingSize'], 'in', 'range' => [
            'h2',
            'h3',
            'h4',
            'h5',
            'h6',
        ]];

        return $rules;
    }

    /**
     * @inheritDoc
     */
    public function getIsCosmetic(): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function hasLabel(): bool
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function getFieldDefaults(): array
    {
        return [
            'headingSize' => 'h2',
        ];
    }

    /**
     * @inheritDoc
     */
    public function getInputHtml($value, ElementInterface $element = null): string
    {
        return Craft::$app->getView()->renderTemplate('formie/_formfields/heading/input', [
            'name' => $this->handle,
            'value' => $value,
            'field' => $this,
        ]);
    }

    /**
     * @inheritDoc
     */
    public function getPreviewInputHtml(): string
    {
        return Craft::$app->getView()->renderTemplate('formie/_formfields/heading/preview', [
            'field' => $this
        ]);
    }

    /**
     * @inheritDoc
     */
    public function defineGeneralSchema(): array
    {
        return [
            SchemaHelper::labelField([
                'label' => Craft::t('formie', 'Heading Text'),
                'help' => Craft::t('formie', 'The text to be displayed in the heading.'),
            ]),
            SchemaHelper::selectField([
                'label' => Craft::t('formie', 'Heading Size'),
                'help' => Craft::t('formie', 'Choose the size for the heading.'),
                'name' => 'headingSize',
                'options' => [
                    [ 'label' => Craft::t('formie', 'H2'), 'value' => 'h2' ],
                    [ 'label' => Craft::t('formie', 'H3'), 'value' => 'h3' ],
                    [ 'label' => Craft::t('formie', 'H4'), 'value' => 'h4' ],
                    [ 'label' => Craft::t('formie', 'H5'), 'value' => 'h5' ],
                    [ 'label' => Craft::t('formie', 'H6'), 'value' => 'h6' ],
                ],
            ]),
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

    /**
     * @inheritDoc
     */
    public function defineConditionsSchema(): array
    {
        return [
            SchemaHelper::enableConditionsField(),
            SchemaHelper::conditionsField(),
        ];
    }
}

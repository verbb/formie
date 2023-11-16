<?php
namespace verbb\formie\fields\formfields;

use verbb\formie\base\FormField;
use verbb\formie\helpers\SchemaHelper;
use verbb\formie\models\HtmlTag;

use Craft;
use craft\base\ElementInterface;

class Heading extends FormField
{
    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('formie', 'Heading');
    }

    public static function getSvgIconPath(): string
    {
        return 'formie/_formfields/heading/icon.svg';
    }


    // Properties
    // =========================================================================

    public ?string $headingSize = 'h2';


    // Public Methods
    // =========================================================================

    public function getIsCosmetic(): bool
    {
        return true;
    }

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

    protected function inputHtml(mixed $value, ?ElementInterface $element, bool $inline): string
    {
        return Craft::$app->getView()->renderTemplate('formie/_formfields/heading/input', [
            'name' => $this->handle,
            'value' => $value,
            'field' => $this,
        ]);
    }

    public function getPreviewInputHtml(): string
    {
        return Craft::$app->getView()->renderTemplate('formie/_formfields/heading/preview', [
            'field' => $this,
        ]);
    }

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
                    ['label' => Craft::t('formie', 'H2'), 'value' => 'h2'],
                    ['label' => Craft::t('formie', 'H3'), 'value' => 'h3'],
                    ['label' => Craft::t('formie', 'H4'), 'value' => 'h4'],
                    ['label' => Craft::t('formie', 'H5'), 'value' => 'h5'],
                    ['label' => Craft::t('formie', 'H6'), 'value' => 'h6'],
                ],
            ]),
        ];
    }

    public function defineAdvancedSchema(): array
    {
        return [
            SchemaHelper::handleField(),
            SchemaHelper::cssClasses(),
            SchemaHelper::containerAttributesField(),
        ];
    }

    public function defineConditionsSchema(): array
    {
        return [
            SchemaHelper::enableConditionsField(),
            SchemaHelper::conditionsField(),
        ];
    }

    public function defineHtmlTag(string $key, array $context = []): ?HtmlTag
    {
        if ($key === 'fieldHeading') {
            return new HtmlTag($this->headingSize, [
                'class' => "fui-heading fui-heading-{$this->headingSize}",
            ], $this->getInputAttributes());
        }

        return parent::defineHtmlTag($key, $context);
    }


    // Protected Methods
    // =========================================================================

    protected function defineRules(): array
    {
        $rules = parent::defineRules();
        $rules[] = [
            ['headingSize'], 'in', 'range' => [
                'h2',
                'h3',
                'h4',
                'h5',
                'h6',
            ],
        ];

        return $rules;
    }
}

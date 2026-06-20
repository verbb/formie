<?php
namespace verbb\formie\fields;

use verbb\formie\base\CosmeticField;
use verbb\formie\helpers\SchemaHelper;
use verbb\formie\models\SlotTag;

use verbb\formie\theme\context\RenderContext;

use Craft;
use craft\base\ElementInterface;

use GraphQL\Type\Definition\Type;

class Heading extends CosmeticField
{
    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('formie', 'Heading');
    }

    public static function translatableProperties(): array
    {
        return ['label'];
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

    public function defineFormBuilderPreviewSchema(): array
    {
        return [
            SchemaHelper::previewHeading(),
        ];
    }

    public function getSettingGqlTypes(): array
    {
        return array_merge(parent::getSettingGqlTypes(), [
            'headingSize' => [
                'name' => 'headingSize',
                'type' => Type::string(),
            ],
        ]);
    }

    public function defineFormBuilderGeneralSchema(): array
    {
        return [
            SchemaHelper::labelField([
                'label' => Craft::t('formie', 'Heading Text'),
                'instructions' => Craft::t('formie', 'The text to be displayed in the heading.'),
            ]),
            SchemaHelper::selectField([
                'label' => Craft::t('formie', 'Heading Size'),
                'instructions' => Craft::t('formie', 'Choose the size for the heading.'),
                'name' => 'headingSize',
                'options' => [
                    ['label' => Craft::t('formie', 'H2'), 'value' => 'h2'],
                    ['label' => Craft::t('formie', 'H3'), 'value' => 'h3'],
                    ['label' => Craft::t('formie', 'H4'), 'value' => 'h4'],
                    ['label' => Craft::t('formie', 'H5'), 'value' => 'h5'],
                    ['label' => Craft::t('formie', 'H6'), 'value' => 'h6'],
                ],
            ]),
            SchemaHelper::includeInEmailFieldSummariesField(),
        ];
    }

    public function defineFormBuilderAdvancedSchema(): array
    {
        return [
            SchemaHelper::handleField(),
            SchemaHelper::cssClasses(),
            SchemaHelper::containerAttributesField(),
        ];
    }

    public function defineFormBuilderConditionsSchema(): array
    {
        return [
            SchemaHelper::enableConditionsField(),
            SchemaHelper::conditionsField(),
        ];
    }

    public function isValueEmpty(mixed $value, ?ElementInterface $element): bool
    {
        return trim((string)$this->label) === '';
    }

    // Protected Methods
    // =========================================================================

    protected function defineFieldSlotTag(string $key, RenderContext $context): ?SlotTag
    {
        if ($key === 'fieldHeading') {
            return SlotTag::make($this->headingSize)
                ->core([
                    'data-formie-heading' => true,
                    'data-formie-heading-size' => $this->headingSize,
                ])
                ->theme([
                    'class' => [
                        'formie-heading',
                        "formie-heading-{$this->headingSize}",
                    ],
                ])
                ->instanceAttributes($this->getInputAttributes());
        }

        return parent::defineFieldSlotTag($key, $context);
    }

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

    protected function defineSubmissionHtml(mixed $value, ?ElementInterface $element, bool $inline): string
    {
        return Craft::$app->getView()->renderTemplate('formie/_formfields/heading/input', [
            'name' => $this->handle,
            'value' => $value,
            'field' => $this,
        ]);
    }
}

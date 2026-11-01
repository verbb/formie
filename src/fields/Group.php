<?php
namespace verbb\formie\fields;

use verbb\formie\Formie;
use verbb\formie\base\ContainerParentField;
use verbb\formie\base\ContainerParentFieldInterface;
use verbb\formie\base\Field;
use verbb\formie\fields\definitions\FieldReferenceValue;
use verbb\formie\gql\types\generators\NestedFieldGenerator;
use verbb\formie\gql\types\input\GroupInputType;
use verbb\formie\helpers\SchemaHelper;
use verbb\formie\models\DynamicModel;
use verbb\formie\models\SlotTag;
use verbb\formie\positions\Hidden as HiddenPosition;
use verbb\formie\references\ReferenceShape;
use verbb\formie\theme\context\RenderContext;

use Craft;
use craft\base\Element;
use craft\base\ElementInterface;

use Faker\Generator as FakerFactory;
use GraphQL\Type\Definition\Type;

class Group extends ContainerParentField implements ContainerParentFieldInterface
{
    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('formie', 'Group');
    }

    public static function getSvgIconPath(): string
    {
        return 'formie/_formfields/group/icon.svg';
    }

    public static function gqlTypeNameByContext(mixed $context): string
    {
        return Formie::$plugin->getFields()->getFieldGqlTypeName($context, 'FormieGroupField');
    }

    public static function supportsGqlConfigProvider(): bool
    {
        return true;
    }

    public static function gqlContentMutationArgumentTypeFromConfig(array $config): Type|array
    {
        return GroupInputType::getTypeFromConfig($config);
    }

    public static function gqlContentTypeFromConfig(array $config): array|Type
    {
        $handle = $config['handle'] ?? '';

        return [
            'name' => $handle,
            'type' => NestedFieldGenerator::generateTypeFromConfig($config),
            'resolve' => static fn(mixed $source): DynamicModel => self::_gqlContentValue($source, $handle),
        ];
    }

    private static function _gqlContentValue(mixed $source, string $handle): DynamicModel
    {
        // Nested fields resolve from a model of their parent's values, not an element.
        $value = $source instanceof ElementInterface ? $source->getFieldValue($handle) : ($source[$handle] ?? null);

        // Some fields like the in-built elements (Assets, Entries, etc) will assume the value of a repeater row
        // in an element, but it's not. Instead make it a dynamic model that'll work for the most part.
        return new DynamicModel(is_array($value) ? $value : []);
    }


    // Public Methods
    // =========================================================================

    public function defineFormBuilderPreviewSchema(): array
    {
        return [
            SchemaHelper::previewGroup(),
        ];
    }

    public function defineFormBuilderGeneralSchema(): array
    {
        return [
            SchemaHelper::labelField(),
        ];
    }

    public function defineFormBuilderAppearanceSchema(): array
    {
        return [
            SchemaHelper::visibility(),
            SchemaHelper::labelPosition($this),
            SchemaHelper::instructions(),
            SchemaHelper::instructionsPosition($this),
            SchemaHelper::errorMessagePosition($this),
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

    public function getContentGqlMutationArgumentType(): Type|array
    {
        return GroupInputType::getType($this);
    }

    public function getContentGqlType(): array|Type
    {
        return [
            'name' => $this->handle,
            'type' => NestedFieldGenerator::generateType($this),
            'resolve' => fn(mixed $source): DynamicModel => self::_gqlContentValue($source, $this->handle),
        ];
    }


    // Protected Methods
    // =========================================================================

    protected function normalizeDefaultValue(mixed $value): mixed
    {
        // An absent group default must not become empty child values that
        // override the children's own defaults when they render independently.
        return $value === null ? [] : parent::normalizeDefaultValue($value);
    }

    protected function defineSlotTag(string $key, RenderContext $context): ?SlotTag
    {
        $form = $context->form;

        $id = $this->getHtmlId($form);

        if ($key === 'fieldLayout') {
            return SlotTag::make('fieldset')
                ->core([
                    'data-formie-field-layout' => true,
                    'data-formie-group-field-layout' => true,
                    'aria-describedby' => $this->hasInstructions() ? "{$id}-instructions" : null,
                ])
                ->theme([
                    'class' => [
                        'formie-field-layout',
                        'formie-group-field-layout',
                    ],
                ]);
        }

        if ($key === 'fieldLabel') {
            $labelPosition = $context->get('labelPosition');

            return SlotTag::make('legend')
                ->core([
                    'data-formie-label' => true,
                    'data-formie-field-label' => true,
                    'data-formie-group-field-label' => true,
                    'data-formie-sr-only' => $labelPosition instanceof HiddenPosition ? true : false,
                ])
                ->theme([
                    'class' => [
                        'formie-label',
                        'formie-field-label',
                        'formie-group-field-label',
                        $labelPosition instanceof HiddenPosition ? 'formie-sr-only' : false,
                    ],
                ]);
        }

        if ($key === 'nestedFieldContainer') {
            return SlotTag::make('div')
                ->core([
                    'data-formie-nested-field-container' => true,
                    'data-formie-group-container' => true,
                ])
                ->theme([
                    'class' => [
                        'formie-nested-field-container',
                        'formie-group-container',
                    ],
                ]);
        }

        return parent::defineSlotTag($key, $context);
    }

    protected function defineSubmissionHtml(mixed $value, ?ElementInterface $element, bool $inline): string
    {
        return Craft::$app->getView()->renderTemplate('formie/_formfields/group/input', [
            'element' => $element,
            'name' => $this->handle,
            'value' => $value,
            'field' => $this,
        ]);
    }

    protected function defineValueForEmailPreview(FakerFactory $faker): mixed
    {
        return Formie::$plugin->getSubmissions()->getFakeFieldContent($this->getFields());
    }

    protected function getNestedLayoutBuilderDisallowedFieldTypes(): array
    {
        return [
            self::class,
            Repeater::class,
        ];
    }

    protected function defineAllowPrimaryReference(): bool
    {
        return false;
    }

    protected function defineReferenceValues(): array
    {
        return [FieldReferenceValue::selector('summary', Craft::t('formie', 'Formatted Summary'), shape: ReferenceShape::Block, supportsFieldSelect: false, supportsBrowser: false, allowTransforms: false)];
    }
}

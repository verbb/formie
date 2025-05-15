<?php
namespace verbb\formie\fields\formfields;

use verbb\formie\base\FormField;
use verbb\formie\base\NestedFieldInterface;
use verbb\formie\base\NestedFieldTrait;
use verbb\formie\elements\db\NestedFieldRowQuery;
use verbb\formie\elements\NestedFieldRow;
use verbb\formie\gql\resolvers\elements\NestedFieldRowResolver;
use verbb\formie\gql\types\generators\NestedFieldGenerator;
use verbb\formie\gql\types\input\GroupInputType;
use verbb\formie\helpers\SchemaHelper;
use verbb\formie\models\HtmlTag;
use verbb\formie\positions\Hidden as HiddenPosition;

use Craft;
use craft\base\EagerLoadingFieldInterface;
use craft\base\Element;
use craft\base\ElementInterface;
use craft\gql\GqlEntityRegistry;
use craft\helpers\Gql;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

class Group extends FormField implements NestedFieldInterface, EagerLoadingFieldInterface
{
    // Traits
    // =========================================================================

    use NestedFieldTrait;


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

    public static function hasContentColumn(): bool
    {
        return false;
    }

    public static function gqlTypeNameByContext(mixed $context): string
    {
        return ($context->getForm()->handle ?? '') . '_' . $context->handle . '_FormieGroupField';
    }


    // Public Methods
    // =========================================================================

    public function getElementValidationRules(): array
    {
        $rules = parent::getElementValidationRules();

        $rules[] = [
            'validateRows',
            'on' => [Element::SCENARIO_ESSENTIALS, Element::SCENARIO_DEFAULT, Element::SCENARIO_LIVE],
            'skipOnEmpty' => false,
        ];

        return $rules;
    }

    public function getInputHtml(mixed $value, ?ElementInterface $element = null): string
    {
        return Craft::$app->getView()->renderTemplate('formie/_formfields/group/input', [
            'name' => $this->handle,
            'value' => $value,
            'field' => $this,
        ]);
    }

    public function getPreviewInputHtml(): string
    {
        return Craft::$app->getView()->renderTemplate('formie/_formfields/group/preview', [
            'field' => $this,
        ]);
    }

    public function populateValue($value): void
    {
        if (!is_array($value)) {
            return;
        }

        $blocks = [];

        $row = new NestedFieldRow();
        $row->fieldId = $this->id;

        // Ensure that the siteId is set to the current site
        $row->siteId = Craft::$app->getSites()->getCurrentSite()->id;

        $row->setFieldValues($value);

        $blocks[] = $row;

        if ($blocks) {
            $this->defaultValue = new NestedFieldRowQuery(NestedFieldRow::class);
            $this->defaultValue->setBlocks($blocks);
        }
    }

    public function parsePopulatedFieldValues($value, $element): array
    {
        // For when parsing populated content from the cache, when the field is visibly disabled
        // It's supplied in a format that makes sense for `populateValue()` but not for `$element->setFieldValue()`.
        return [
            'new1' => [
                'fields' => $value,
            ],
        ];
    }

    public function getConfigJson(): ?string
    {
        // Group fields themselves should not contain the inner field's JS
        return null;
    }

    public function defineGeneralSchema(): array
    {
        return [
            SchemaHelper::labelField(),
        ];
    }

    public function defineAppearanceSchema(): array
    {
        return [
            SchemaHelper::visibility(),
            SchemaHelper::labelPosition($this),
            SchemaHelper::instructions(),
            SchemaHelper::instructionsPosition($this),
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

    public function getContentGqlMutationArgumentType(): array|Type
    {
        return GroupInputType::getType($this);
    }

    public function getContentGqlType(): array|Type
    {
        $typeArray = NestedFieldGenerator::generateTypes($this);
        $typeName = self::gqlTypeNameByContext($this);

        return [
            'name' => $this->handle,
            'type' => Gql::getUnionType($typeName, $typeArray),
            'resolve' => NestedFieldRowResolver::class . '::resolve',
            'complexity' => Gql::eagerLoadComplexity(),
        ];
    }

    public function defineHtmlTag(string $key, array $context = []): ?HtmlTag
    {
        $form = $context['form'] ?? null;

        $id = $this->getHtmlId($form);

        if ($key === 'fieldContainer') {
            return new HtmlTag('fieldset', [
                'class' => 'fui-fieldset',
                'aria-describedby' => $this->instructions ? "{$id}-instructions" : null,
            ]);
        }

        if ($key === 'fieldLabel') {
            $labelPosition = $context['labelPosition'] ?? null;

            return new HtmlTag('legend', [
                'class' => [
                    'fui-legend',
                ],
                'data' => [
                    'fui-sr-only' => $labelPosition instanceof HiddenPosition ? true : false,
                ],
            ]);
        }

        if ($key === 'nestedFieldContainer') {
            return new HtmlTag('div', [
                'class' => 'fui-group',
            ]);
        }

        return parent::defineHtmlTag($key, $context);
    }
}

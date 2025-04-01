<?php
namespace verbb\formie\fields;

use verbb\formie\Formie;
use verbb\formie\base\Field;
use verbb\formie\base\Integration;
use verbb\formie\base\IntegrationInterface;
use verbb\formie\base\SingleNestedField;
use verbb\formie\base\SingleNestedFieldInterface;
use verbb\formie\elements\Submission;
use verbb\formie\gql\resolvers\elements\NestedFieldRowResolver;
use verbb\formie\gql\types\generators\NestedFieldGenerator;
use verbb\formie\gql\types\input\GroupInputType;
use verbb\formie\helpers\ArrayHelper;
use verbb\formie\helpers\SchemaHelper;
use verbb\formie\helpers\Variables;
use verbb\formie\models\DynamicModel;
use verbb\formie\models\HtmlTag;
use verbb\formie\models\IntegrationField;
use verbb\formie\models\Notification;
use verbb\formie\positions\Hidden as HiddenPosition;

use Craft;
use craft\base\EagerLoadingFieldInterface;
use craft\base\Element;
use craft\base\ElementInterface;
use craft\gql\GqlEntityRegistry;
use craft\helpers\Gql;
use craft\helpers\Template;

use Faker\Generator as FakerFactory;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

use yii\validators\Validator;

class Group extends SingleNestedField
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
        return ($context->getForm()->handle ?? '') . '_' . $context->handle . '_FormieGroupField';
    }


    // Public Methods
    // =========================================================================

    public function getPreviewInputHtml(): string
    {
        return Craft::$app->getView()->renderTemplate('formie/_formfields/group/preview', [
            'field' => $this,
        ]);
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

    public function getContentGqlMutationArgumentType(): Type|array
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
            'resolve' => function($submission) {
                // Some fields like the in-built elements (Assets, Entries, etc) will assume the value of a repeater row
                // in an element, but it's not. Instead make it a dynamic model that'll work for the most part.
                $value = $submission->getFieldValue($this->fieldKey);

                return new DynamicModel($value);
            },
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
                    'field-label' => true,
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


    // Protected Methods
    // =========================================================================

    protected function cpInputHtml(mixed $value, ?ElementInterface $element, bool $inline): string
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

    protected function defineValueForVariable(mixed $value, Submission $submission, Notification $notification): mixed
    {
        $values = [];

        foreach ($this->getFields() as $nestedField) {
            $value = $submission->getFieldValue($nestedField->fieldKey);
            $fieldValues = Variables::getParsedFieldValue($nestedField, $value, $submission, $notification);

            if (is_array($fieldValues)) {
                foreach ($fieldValues as $key => $fieldValue) {
                    $values[$nestedField->handle][$key] = $fieldValue;
                }
            } else {
                $values[$nestedField->handle] = $fieldValues;
            }
        }

        return $values;
    }
}

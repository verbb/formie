<?php
namespace verbb\formie\base;

use Craft;
use craft\base\ElementInterface;
use craft\base\PreviewableFieldInterface;
use craft\elements\db\ElementQueryInterface;
use craft\enums\AttributeStatus;
use craft\gql\types\QueryArgument;
use craft\helpers\ElementHelper;
use craft\helpers\Html;
use craft\helpers\UrlHelper;
use craft\models\GqlSchema;

use GraphQL\Type\Definition\Type;

use yii\base\Component as YiiComponent;
use yii\db\ExpressionInterface;
use yii\db\Schema;
use yii\validators\Validator;

trait FieldLegacy
{
    // Misc - To remove at next breakpoint (3.1), but required for the Formie 2 > 3 migration
    public mixed $layoutElement = null;
    public mixed $columnType = null;
    public ?string $context = null;
    public ?string $columnSuffix = null;

    public static function get(int|string $id): ?static
    {
        return null;
    }

    public static function icon(): string
    {
        return '';
    }

    public static function isMultiInstance(): bool
    {
        return false;
    }

    public static function isRequirable(): bool
    {
        return true;
    }

    public static function supportedTranslationMethods(): array
    {
        return [];
    }

    public static function phpType(): string
    {
        return 'mixed';
    }

    public static function dbType(): array|string|null
    {
        return Schema::TYPE_TEXT;
    }

    public static function queryCondition(array $instances, mixed $value, array &$params): array|string|ExpressionInterface|false|null {
        $valueSql = static::valueSql($instances);

        if ($valueSql === null) {
            return false;
        }

        return Db::parseParam($valueSql, $value, columnType: Schema::TYPE_JSON);
    }

    public function getPreviewHtml(mixed $value, ElementInterface $element): string
    {
        return ElementHelper::attributeHtml($value);
    }

    public function previewPlaceholderHtml(mixed $value, ?ElementInterface $element): string
    {
        if (!$this instanceof PreviewableFieldInterface) {
            return '';
        }

        if ($value !== null) {
            return $value;
        }

        if ($element !== null) {
            return $element->getFieldValue($this->handle);
        }

        return $this->getUiLabel();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getHandle(): ?string
    {
        return $this->handle;
    }

    public function getCpEditUrl(): ?string
    {
        return $this->id ? UrlHelper::cpUrl("settings/fields/edit/$this->id") : null;
    }

    public function getUiLabel(): string
    {
        return Craft::t('site', $this->name);
    }

    public function getOrientation(?ElementInterface $element): string
    {
        return '';
    }

    public function getIsTranslatable(?ElementInterface $element): bool
    {
        return false;
    }

    public function getTranslationDescription(?ElementInterface $element): ?string
    {
        return null;
    }

    public function getTranslationKey(ElementInterface $element): string
    {
        return '';
    }

    public function getStatus(ElementInterface $element): ?array
    {
        return null;
    }

    public function getInputId(): string
    {
        return Html::id($this->handle);
    }

    public function getLabelId(): string
    {
        return sprintf('%s-label', $this->getInputId());
    }

    public function useFieldset(): bool
    {
        return false;
    }

    public function getInputHtml(mixed $value, ?ElementInterface $element): string
    {
        return '';
    }

    public function getStaticHtml(mixed $value, ElementInterface $element): string
    {
        return '';
    }

    public function getElementValidationRules(): array
    {
        return [];
    }

    public function isValueEmpty(mixed $value, ElementInterface $element): bool
    {
        return $value === null || $value === [] || $value === '';
    }

    public function getSearchKeywords(mixed $value, ElementInterface $element): string
    {
        return '';
    }

    public function normalizeValue(mixed $value, ?ElementInterface $element): mixed
    {
        return $value;
    }

    public function normalizeValueFromRequest(mixed $value, ?ElementInterface $element): mixed
    {
        return $this->normalizeValue($value, $element);
    }

    public function serializeValue(mixed $value, ?ElementInterface $element): mixed
    {
        return $value;
    }

    public function copyValue(ElementInterface $from, ElementInterface $to): void
    {

    }

    public function getElementConditionRuleType(): array|string|null
    {
        return null;
    }

    public function getValueSql(?string $key = null): ?string
    {
        return null;
    }

    public function modifyElementIndexQuery(ElementQueryInterface $query): void
    {

    }

    public function setIsFresh(?bool $isFresh = null): void
    {

    }

    public function includeInGqlSchema(GqlSchema $schema): bool
    {
        return true;
    }

    public function getContentGqlType(): Type|array
    {
        return Type::string();
    }

    public function getContentGqlMutationArgumentType(): Type|array
    {
        return [
            'name' => $this->handle,
            'type' => Type::string(),
            'description' => $this->instructions,
        ];
    }

    public function getContentGqlQueryArgumentType(): Type|array
    {
        return [
            'name' => $this->handle,
            'type' => Type::listOf(QueryArgument::getType()),
        ];
    }

    public function beforeElementSave(ElementInterface $element, bool $isNew): bool
    {
        return true;
    }

    public function afterElementSave(ElementInterface $element, bool $isNew): void
    {
        
    }

    public function afterElementPropagate(ElementInterface $element, bool $isNew): void
    {
        
    }

    public function beforeElementDelete(ElementInterface $element): bool
    {
        return true;
    }

    public function afterElementDelete(ElementInterface $element): void
    {
        
    }

    public function beforeElementDeleteForSite(ElementInterface $element): bool
    {
        return true;
    }

    public function afterElementDeleteForSite(ElementInterface $element): void
    {
        
    }

    public function beforeElementRestore(ElementInterface $element): bool
    {
        return true;
    }

    public function afterElementRestore(ElementInterface $element): void
    {
        
    }

    public function serializeValueForDb(mixed $value, ElementInterface $element): mixed
    {
        return $this->serializeValue($value, $element);
    }

    public function showStatus(): bool
    {
        return true;
    }

    public function propagateValue(ElementInterface $from, ElementInterface $to): void
    {
        $to->setFieldValue($this->handle, $from->getFieldValue($this->handle));
    }
}

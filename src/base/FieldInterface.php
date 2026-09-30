<?php
namespace verbb\formie\base;

use verbb\formie\models\BrowserModuleContext;
use craft\base\ElementInterface;
use craft\base\SavableComponentInterface;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\fields\definitions\FieldClientRenderedChildren;
use verbb\formie\fields\definitions\FieldClientRenderedDefinition;
use verbb\formie\conditions\ConditionSet;
use verbb\formie\fields\definitions\FieldReferenceValue;
use verbb\formie\fields\definitions\FieldValueType;
use verbb\formie\models\Notification;

use Twig\Markup;

interface FieldInterface extends SavableComponentInterface, FieldTypeDefinitionInterface
{
    // Static Methods
    // =========================================================================

    public static function getSvgIcon(): string;
    public static function getSvgIconPath(): string;
    public static function getInputTemplatePath(): string;
    public static function getReferenceBlockTemplatePath(): string;


    // Public Methods
    // =========================================================================

    public function themeConfigKey(): string;
    public function getFormBuilderSchema(): array;
    public function getCpEditConfig(): array;
    public function getClientRenderedDefinition(): array;
    public function getClientRenderedInput(): array;
    public function browserValidationRules(): array;
    public function fieldKind(): string;
    public function valueType(): FieldValueType;
    public function clientRenderedChildren(): FieldClientRenderedChildren;
    public function clientRenderedDefinition(): FieldClientRenderedDefinition;
    public function browserModules(BrowserModuleContext $context): array;
    /** @return FieldReferenceValue[] */
    public function referenceValues(): array;
    public function conditions(): ConditionSet;
    public function hasLabel(): bool;
    public function getIsCosmetic(): bool;
    public function getIsHidden(): bool;
    public function getContainerAttributes(): array;
    public function getInputAttributes(): array;
    public function getDefaultValue(): mixed;
    public function getPrefillValue(?ElementInterface $element = null, ?bool &$found = null): mixed;
    public function getInitialValue(?ElementInterface $element = null): mixed;
    public function populateValue(mixed $value, ?Submission $submission): void;
    public function getFormBuilderPreviewSchema(): array;
    public function defineFormBuilderPreviewSchema(): array;
    public function getFormBuilderPreviewHtml(): string;
    public function withParentField(FieldInterface $parent, string|int|null $namespace = null): static;
    public function getInputTemplateVariables(Form $form, mixed $value): array;
    public function renderInput(Form $form, mixed $value): Markup;
    public function getReferenceBlockOptions(Submission $submission, Notification $notification, mixed $value, array $renderOptions = []): array;
    public function getReferenceBlockHtml(Submission $submission, Notification $notification, mixed $value, array $renderOptions = []): string|null|bool;
    public function getNamespace(): string;
    public function defineFormBuilderGeneralSchema(): array;
    public function defineFormBuilderSettingsSchema(): array;
    public function defineFormBuilderAppearanceSchema(): array;
    public function defineFormBuilderAdvancedSchema(): array;
    public function afterCreateField(array $data);
}

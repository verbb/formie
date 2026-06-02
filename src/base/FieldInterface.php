<?php
namespace verbb\formie\base;

use craft\base\ElementInterface;
use craft\base\SavableComponentInterface;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\fields\definitions\FieldClientModules;
use verbb\formie\fields\definitions\FieldConditions;
use verbb\formie\fields\definitions\FieldClientDefinition;
use verbb\formie\fields\definitions\FieldReferences;
use verbb\formie\fields\definitions\FieldClientChildren;
use verbb\formie\fields\definitions\FieldValueClass;
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
    public function getClientConfig(): array;
    public function getClientPayload(): array;
    public function getClientInputDefinition(): array;
    public function validationRules(): array;
    public function fieldKind(): string;
    public function valueClass(): FieldValueClass;
    public function clientChildren(): FieldClientChildren;
    public function clientDefinition(): FieldClientDefinition;
    public function clientModules(): FieldClientModules;
    public function references(): FieldReferences;
    public function variableSources(): array;
    public function conditions(): FieldConditions;
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
    public function supportsValueCapability(string $capabilityType): bool;
    public function supportsStringValue(): bool;
    public function supportsArrayValue(): bool;
    public function defineFormBuilderGeneralSchema(): array;
    public function defineFormBuilderSettingsSchema(): array;
    public function defineFormBuilderAppearanceSchema(): array;
    public function defineFormBuilderAdvancedSchema(): array;
    public function afterCreateField(array $data);
}

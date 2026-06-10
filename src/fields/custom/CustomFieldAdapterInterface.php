<?php
namespace verbb\formie\fields\custom;

use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\base\IntegrationInterface;
use verbb\formie\fields\CustomField;
use verbb\formie\models\IntegrationField;
use verbb\formie\models\Notification;

use craft\base\ElementInterface;

use GraphQL\Type\Definition\Type;

interface CustomFieldAdapterInterface
{
    public static function handle(): string;
    public static function displayName(): string;
    public static function craftFieldClasses(): array;
    public static function isAvailable(): bool;

    public function getFieldTypeDefinition(): array;
    public function getDefaultSettings(): array;
    public function getFormBuilderSettingsSchema(CustomField $field): array;
    public function getFormBuilderPreviewSchema(CustomField $field): array;
    public function getSettingGqlTypes(CustomField $field): array;
    public function getContentGqlType(CustomField $field): Type|array;
    public function getContentGqlMutationArgumentType(CustomField $field): Type|array;
    public function getClientInput(CustomField $field): array;
    public function getClientModules(CustomField $field): array;
    public function getDefaultValue(CustomField $field): mixed;
    public function getValueClass(CustomField $field): ?string;

    public function normalizeValue(mixed $value, CustomField $field, ?ElementInterface $element): mixed;
    public function serializeValue(mixed $value, CustomField $field, ?ElementInterface $element): mixed;
    public function isValueEmpty(mixed $value, CustomField $field, ?ElementInterface $element): bool;
    public function validateValue(ElementInterface $element, CustomField $field): void;

    public function getInputHtml(CustomField $field, Form $form, mixed $value): string;
    public function getCpInputHtml(CustomField $field, mixed $value, ?ElementInterface $element, bool $inline): string;
    public function getPreviewHtml(CustomField $field, mixed $value, ElementInterface $element): string;

    public function getValueAsString(mixed $value, CustomField $field, ?ElementInterface $element = null): string;
    public function getValueAsArray(mixed $value, CustomField $field, ?ElementInterface $element = null): mixed;
    public function getValueForExport(mixed $value, CustomField $field, ?ElementInterface $element = null): mixed;
    public function getValueForIntegration(mixed $value, CustomField $field, IntegrationField $integrationField, IntegrationInterface $integration, ?ElementInterface $element = null, string $fieldKey = ''): mixed;
    public function getValueForSummary(mixed $value, CustomField $field, ?ElementInterface $element = null): mixed;
    public function getValueForReference(mixed $value, CustomField $field, ?ElementInterface $element = null): mixed;
    public function getValueForReferenceBlock(mixed $value, CustomField $field, Notification $notification, ?ElementInterface $element = null): mixed;
    public function getValueForCondition(mixed $value, CustomField $field, Submission $submission): mixed;
}

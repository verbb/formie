<?php
namespace verbb\formie\base;

use craft\base\ComponentInterface;
use craft\base\ElementInterface;
use Twig\Markup;

use verbb\formie\elements\Form;

use verbb\formie\elements\Submission;
use verbb\formie\models\Notification;

interface FormFieldInterface extends ComponentInterface
{
    // Constants
    // =========================================================================

    public const EVENT_MODIFY_DEFAULT_VALUE = 'modifyDefaultValue';
    public const EVENT_MODIFY_FIELD_CONFIG = 'modifyFieldConfig';
    public const EVENT_MODIFY_HTML_TAG = 'modifyHtmlTag';
    public const EVENT_MODIFY_VALUE_AS_STRING = 'modifyValueAsString';
    public const EVENT_MODIFY_VALUE_AS_JSON = 'modifyValueAsJson';
    public const EVENT_MODIFY_VALUE_FOR_EXPORT = 'modifyValueForExport';
    public const EVENT_MODIFY_VALUE_FOR_INTEGRATION = 'modifyValueForIntegration';
    public const EVENT_MODIFY_VALUE_FOR_SUMMARY = 'modifyValueForSummary';
    public const EVENT_MODIFY_VALUE_FOR_EMAIL = 'modifyValueForEmail';


    // Public Methods
    // =========================================================================

    public static function getSvgIcon(): string;
    public static function getSvgIconPath(): string;
    public static function getFrontEndInputTemplatePath(): string;
    public static function getEmailTemplatePath(): string;
    public function getFieldSchema(): array;
    public function hasLabel(): bool;
    public function hasSubFields(): bool;
    public function hasNestedFields(): bool;
    public function getIsCosmetic(): bool;
    public function getIsHidden(): bool;
    public function getContainerAttributes(): array;
    public function getInputAttributes(): array;
    public function getPreviewInputHtml(): string;
    public function getFrontEndInputOptions(Form $form, mixed $value, array $renderOptions = []): array;
    public function getFrontEndInputHtml(Form $form, mixed $value, array $renderOptions = []): Markup;
    public function getEmailOptions(Submission $submission, Notification $notification, mixed $value, array $renderOptions = []): array;
    public function getEmailHtml(Submission $submission, Notification $notification, mixed $value, array $renderOptions = []): string|null|bool;
    public function getNamespace(): string;
    public function defineGeneralSchema(): array;
    public function defineSettingsSchema(): array;
    public function defineAppearanceSchema(): array;
    public function defineAdvancedSchema(): array;
    public function afterCreateField(array $data);
}

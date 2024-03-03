<?php
namespace verbb\formie\base;

use craft\base\SavableComponentInterface;

use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\models\Notification;

use Twig\Markup;

interface FieldInterface extends SavableComponentInterface
{
    // Static Methods
    // =========================================================================

    public static function getSvgIcon(): string;
    public static function getSvgIconPath(): string;
    public static function getFrontEndInputTemplatePath(): string;
    public static function getEmailTemplatePath(): string;


    // Public Methods
    // =========================================================================

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

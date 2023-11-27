<?php
namespace verbb\formie\fields\formfields;

use verbb\formie\base\FormField;
use verbb\formie\elements\Submission;
use verbb\formie\models\Notification;

use Craft;
use craft\base\ElementInterface;
use craft\base\MissingComponentTrait;
use craft\base\MissingComponentInterface;

class MissingField extends FormField implements MissingComponentInterface
{
    // Traits
    // =========================================================================

    use MissingComponentTrait;


    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('formie', 'Missing Field');
    }

    public static function getFrontEndInputTemplatePath(): string
    {
        return '';
    }

    public function getFormBuilderSettings(): array
    {
        return [];
    }


    // Public Methods
    // =========================================================================

    public function getPreviewInputHtml(): string
    {
        return Craft::$app->getView()->renderTemplate('formie/_formfields/missing/preview', [
            'field' => $this,
        ]);
    }

    public function getEmailHtml(Submission $submission, Notification $notification, mixed $value, array $renderOptions = []): string|null|bool
    {
        return false;
    }


    // Protected Methods
    // =========================================================================

    protected function inputHtml(mixed $value, ?ElementInterface $element, bool $inline): string
    {
        $error = $this->errorMessage ?? "Unable to find component class '{$this->expectedType}'.";

        return Craft::$app->getView()->renderTemplate('formie/_formfields/missing/input', [
            'error' => $error,
        ]);
    }
}

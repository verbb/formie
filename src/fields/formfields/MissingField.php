<?php
namespace verbb\formie\fields\formfields;

use Craft;
use craft\base\ElementInterface;
use craft\base\MissingComponentTrait;
use craft\base\MissingComponentInterface;

use verbb\formie\base\FormField;
use verbb\formie\elements\Submission;

class MissingField extends FormField implements MissingComponentInterface
{
    // Traits
    // =========================================================================

    use MissingComponentTrait;


    // Static Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public static function displayName(): string
    {
        return Craft::t('formie', 'Missing Field');
    }

    /**
     * @inheritDoc
     */
    public static function getFrontEndInputTemplatePath(): string
    {
        return '';
    }

    /**
     * @inheritDoc
     */
    public static function getSvgIconPath(): string
    {
        return '';
    }

    /**
     * @inheritDoc
     */
    public static function hasContentColumn(): bool
    {
        return false;
    }


    // Public Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public function getInputHtml($value, ElementInterface $element = null): string
    {
        $error = $this->errorMessage ?? "Unable to find component class '{$this->expectedType}'.";

        return Craft::$app->getView()->renderTemplate('formie/_formfields/missing/input', [
            'error' => $error,
        ]);
    }

    /**
     * @inheritDoc
     */
    public function getPreviewInputHtml(): string
    {
        return Craft::$app->getView()->renderTemplate('formie/_formfields/missing/preview', [
            'field' => $this,
        ]);
    }

    /**
     * @inheritDoc
     */
    public function getEmailHtml(Submission $submission, $value, array $options = null)
    {
        return false;
    }
}

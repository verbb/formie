<?php
namespace verbb\formie\fields;

use verbb\formie\Formie;
use verbb\formie\base\Field;
use verbb\formie\elements\Submission;
use verbb\formie\models\Notification;

use Craft;
use craft\base\ElementInterface;
use craft\base\MissingComponentTrait;
use craft\base\MissingComponentInterface;

use Throwable;

class MissingField extends Field implements MissingComponentInterface
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
        $settings = [];
        $settings['layoutId'] = $this->layoutId;
        $settings['pageId'] = $this->pageId;
        $settings['rowId'] = $this->rowId;
        $settings['syncId'] = $this->syncId;
        $settings['label'] = $this->label;
        $settings['handle'] = $this->handle;
        $settings['sortOrder'] = $this->sortOrder;

        return $settings;
    }


    // Public Methods
    // =========================================================================

    public function __set($name, $value)
    {
        try {
            // Trying to set things on a missing field will thrown an error, so ignore things
            parent::__set($name, $value);
        } catch (Throwable $e) {
            // Let it slide, but log it, _just_ in case.
            Formie::info('{message} {file}:{line}', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
        }
    }

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

    protected function cpInputHtml(mixed $value, ?ElementInterface $element, bool $inline): string
    {
        $error = $this->errorMessage ?? "Unable to find component class '{$this->expectedType}'.";

        return Craft::$app->getView()->renderTemplate('formie/_formfields/missing/input', [
            'error' => $error,
        ]);
    }
}

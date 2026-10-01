<?php
namespace verbb\formie\fields;

use verbb\formie\Formie;
use verbb\formie\base\Field;
use verbb\formie\elements\Submission;
use verbb\formie\fields\definitions\FieldValueType;
use verbb\formie\helpers\SchemaHelper;
use verbb\formie\models\Notification;

use Craft;
use craft\base\ElementInterface;
use craft\base\MissingComponentTrait;
use craft\base\MissingComponentInterface;

use Throwable;

class MissingField extends Field implements MissingComponentInterface
{
    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('formie', 'Missing Field');
    }

    public static function getInputTemplatePath(): string
    {
        return '';
    }

    public function getFormBuilderSettings(): array
    {
        $settings = [];
        $settings['id'] = $this->id;
        $settings['uid'] = $this->uid;
        $settings['reference'] = $this->reference;
        $settings['type'] = static::class;
        $settings['settings'] = $this->settings;
        $settings['isMissing'] = true;
        $settings['expectedType'] = $this->expectedType ?? null;
        $settings['errorMessage'] = $this->errorMessage ?? null;
        $settings['definitionId'] = $this->definitionId;
        $settings['definitionUid'] = $this->definitionUid;
        $settings['layoutId'] = $this->layoutId;
        $settings['pageId'] = $this->pageId;
        $settings['rowId'] = $this->rowId;
        $settings['label'] = $this->label;
        $settings['handle'] = $this->handle;
        $settings['sortOrder'] = $this->sortOrder;

        return $settings;
    }


    // Traits
    // =========================================================================

    use MissingComponentTrait;


    // Public Methods
    // =========================================================================

    public function getSettings(): array
    {
        return $this->settings ?? [];
    }

    public function getDefinitionSettings(): array
    {
        return $this->settings ?? [];
    }

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

    public function defineFormBuilderPreviewSchema(): array
    {
        return [
            SchemaHelper::previewMessage(Craft::t('formie', 'Unable to find component class')),
        ];
    }

    public function getReferenceBlockHtml(Submission $submission, Notification $notification, mixed $value, array $renderOptions = []): string|null|bool
    {
        return false;
    }


    // Protected Methods
    // =========================================================================

    protected function defineValueType(): FieldValueType
    {
        return FieldValueType::storageSafe();
    }

    protected function defineSubmissionHtml(mixed $value, ?ElementInterface $element, bool $inline): string
    {
        $error = $this->errorMessage ?? "Unable to find component class '{$this->expectedType}'.";

        return Craft::$app->getView()->renderTemplate('formie/_formfields/missing/input', [
            'error' => $error,
        ]);
    }


    public function normalizeValue(mixed $value, ?\craft\base\ElementInterface $element): mixed
    {
        return $value;
    }
}

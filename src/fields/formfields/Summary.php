<?php
namespace verbb\formie\fields\formfields;

use verbb\formie\base\FormField;
use verbb\formie\elements\Submission;
use verbb\formie\helpers\SchemaHelper;
use verbb\formie\models\Notification;

use Craft;
use craft\base\ElementInterface;
use craft\helpers\Html;
use craft\helpers\StringHelper;

class Summary extends FormField
{
    // Static Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public static function displayName(): string
    {
        return Craft::t('formie', 'Summary');
    }

    /**
     * @inheritDoc
     */
    public static function getSvgIconPath(): string
    {
        return 'formie/_formfields/summary/icon.svg';
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
    public function getIsCosmetic(): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function renderLabel(): bool
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function hasLabel(): bool
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function getInputHtml($value, ElementInterface $element = null): string
    {
        return Craft::$app->getView()->renderTemplate('formie/_formfields/summary/input', [
            'name' => $this->handle,
            'value' => $value,
            'field' => $this,
        ]);
    }

    /**
     * @inheritDoc
     */
    public function getPreviewInputHtml(): string
    {
        return Craft::$app->getView()->renderTemplate('formie/_formfields/summary/preview', [
            'field' => $this,
        ]);
    }

    /**
     * @inheritDoc
     */
    public function getEmailHtml(Submission $submission, Notification $notification, $value, array $options = null)
    {
        return false;
    }

    /**
     * @inheritdoc
     */
    public function getFrontEndJsModules()
    {
        return [
            'src' => Craft::$app->getAssetManager()->getPublishedUrl('@verbb/formie/web/assets/frontend/dist/js/fields/summary.js', true),
            'module' => 'FormieSummary',
            'settings' => [
                'fieldId' => $this->id,
            ],
        ];
    }

    /**
     * @inheritDoc
     */
    public function afterCreateField(array $data)
    {
        $this->name = StringHelper::appendUniqueIdentifier(Craft::t('formie', 'Summary '));
        $this->handle = StringHelper::appendUniqueIdentifier(Craft::t('formie', 'summaryHandle'));
    }

    /**
     * @inheritDoc
     */
    public function defineAdvancedSchema(): array
    {
        return [
            SchemaHelper::cssClasses(),
            SchemaHelper::containerAttributesField(),
        ];
    }

    /**
     * @inheritDoc
     */
    public function defineConditionsSchema(): array
    {
        return [
            SchemaHelper::enableConditionsField(),
            SchemaHelper::conditionsField(),
        ];
    }
}

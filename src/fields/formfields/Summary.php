<?php
namespace verbb\formie\fields\formfields;

use verbb\formie\base\FormField;
use verbb\formie\elements\Submission;
use verbb\formie\helpers\SchemaHelper;
use verbb\formie\models\HtmlTag;
use verbb\formie\models\Notification;

use Craft;
use craft\base\ElementInterface;
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

    public function getIsCosmetic(): bool
    {
        return true;
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
    public function getInputHtml(mixed $value, ?ElementInterface $element = null): string
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
    public function getEmailHtml(Submission $submission, Notification $notification, mixed $value, array $renderOptions = []): string|null|bool
    {
        return false;
    }

    public function getFrontEndJsModules(): ?array
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
    public function afterCreateField(array $data): void
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

    public function defineConditionsSchema(): array
    {
        return [
            SchemaHelper::enableConditionsField(),
            SchemaHelper::conditionsField(),
        ];
    }

    public function defineHtmlTag(string $key, array $context = []): ?HtmlTag
    {
        $form = $context['form'] ?? null;

        $id = $this->getHtmlId($form);
        $dataId = $this->getHtmlDataId($form);

        if ($key === 'fieldSummaryBlocks') {
            return new HtmlTag('div', [
                'class' => 'fui-summary-blocks',
                'data-summary-blocks' => true,
            ]);
        }

        if ($key === 'fieldSummaryHeading') {
            return new HtmlTag('h3', [
                'class' => 'fui-heading-h3',
                'text' => Craft::t('formie', 'Your submission is being prepared. Please review below before proceeding.'),
            ]);
        }

        if ($key === 'fieldSummaryBlock') {
            return new HtmlTag('div', [
                'class' => 'fui-summary-block',
            ]);
        }

        if ($key === 'fieldSummaryLabel') {
            return new HtmlTag('strong');
        }

        if ($key === 'fieldSummaryValue') {
            return new HtmlTag('span');
        }

        return parent::defineHtmlTag($key, $context);
    }
}

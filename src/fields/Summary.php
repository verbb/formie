<?php
namespace verbb\formie\fields;

use verbb\formie\base\CosmeticField;
use verbb\formie\elements\Submission;
use verbb\formie\helpers\SchemaHelper;
use verbb\formie\helpers\StringHelper;
use verbb\formie\models\HtmlTag;
use verbb\formie\models\Notification;

use Craft;
use craft\base\ElementInterface;

class Summary extends CosmeticField
{
    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('formie', 'Summary');
    }

    public static function getSvgIconPath(): string
    {
        return 'formie/_formfields/summary/icon.svg';
    }


    // Properties
    // =========================================================================

    public ?string $description = 'Your submission is being prepared. Please review below before proceeding.';


    // Public Methods
    // =========================================================================

    public function getPreviewInputHtml(): string
    {
        return Craft::$app->getView()->renderTemplate('formie/_formfields/summary/preview', [
            'field' => $this,
        ]);
    }

    public function getEmailHtml(Submission $submission, Notification $notification, mixed $value, array $renderOptions = []): string|null|bool
    {
        return false;
    }

    public function getFrontEndJsModules(): ?array
    {
        return [
            'src' => Craft::$app->getAssetManager()->getPublishedUrl('@verbb/formie/web/assets/frontend/dist/', true, 'js/fields/summary.js'),
            'module' => 'FormieSummary',
            'settings' => [
                'fieldId' => $this->id,
            ],
        ];
    }

    public function afterCreateField(array $data): void
    {
        $this->label = $this->label ?? StringHelper::appendUniqueIdentifier(Craft::t('formie', 'Summary '));
        $this->handle = $this->handle ?? StringHelper::appendUniqueIdentifier('summaryHandle');
    }

    public function defineGeneralSchema(): array
    {
        return [
            SchemaHelper::textField([
                'label' => Craft::t('formie', 'Description'),
                'help' => Craft::t('formie', 'The description text shown at the top of the field.'),
                'name' => 'description',
            ]),
            SchemaHelper::includeInEmailField(),
        ];
    }

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
                'text' => Craft::t('formie', $this->description),
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


    // Protected Methods
    // =========================================================================

    protected function cpInputHtml(mixed $value, ?ElementInterface $element, bool $inline): string
    {
        return Craft::$app->getView()->renderTemplate('formie/_formfields/summary/input', [
            'name' => $this->handle,
            'value' => $value,
            'field' => $this,
        ]);
    }
}

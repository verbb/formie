<?php
namespace verbb\formie\fields;

use verbb\formie\base\CosmeticField;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\fields\definitions\FieldClientModules;
use verbb\formie\helpers\FieldAccess;
use verbb\formie\helpers\SchemaHelper;
use verbb\formie\helpers\StringHelper;
use verbb\formie\models\ClientModule;
use verbb\formie\models\SlotTag;
use verbb\formie\models\Notification;

use verbb\formie\theme\context\RenderContext;

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

    public static function translatableProperties(): array
    {
        return ['description'];
    }

    public static function translatableRichTextProperties(): array
    {
        return ['description'];
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

    public function defineFormBuilderPreviewSchema(): array
    {
        return [
            SchemaHelper::previewSummary(),
        ];
    }

    public function getReferenceBlockHtml(Submission $submission, Notification $notification, mixed $value, array $renderOptions = []): string|null|bool
    {
        return false;
    }

    public function getFieldAccessToken(?Submission $submission): ?string
    {
        if (!$submission) {
            return null;
        }

        return FieldAccess::issueAccessToken($submission, (int)$this->id);
    }

    public function getInputTemplateVariables(Form $form, mixed $value): array
    {
        $variables = parent::getInputTemplateVariables($form, $value);
        $submission = $variables['submission'] ?? null;
        $variables['fieldAccessToken'] = $submission instanceof Submission ? $this->getFieldAccessToken($submission) : null;

        return $variables;
    }

    public function afterCreateField(array $data): void
    {
        $this->label = $this->label ?? StringHelper::appendRandomString(Craft::t('formie', 'Summary '), 15);
        $this->handle = $this->handle ?? StringHelper::appendRandomString('summaryHandle', 15);
    }

    public function defineFormBuilderGeneralSchema(): array
    {
        return [
            SchemaHelper::textField([
                'label' => Craft::t('formie', 'Description'),
                'instructions' => Craft::t('formie', 'The description text shown at the top of the field.'),
                'name' => 'description',
            ]),
            SchemaHelper::includeInEmailFieldSummariesField(),
        ];
    }

    public function defineFormBuilderAdvancedSchema(): array
    {
        return [
            SchemaHelper::cssClasses(),
            SchemaHelper::containerAttributesField(),
        ];
    }

    public function defineFormBuilderConditionsSchema(): array
    {
        return [
            SchemaHelper::enableConditionsField(),
            SchemaHelper::conditionsField(),
        ];
    }

    // Protected Methods
    // =========================================================================

    protected function defineFieldSlotTag(string $key, RenderContext $context): ?SlotTag
    {
        $form = $context->form;

        $id = $this->getHtmlId($form);
        $dataId = $this->getHtmlDataId($form);

        if ($key === 'fieldSummaryContainer') {
            return SlotTag::make('div')
                ->core([
                    'data-formie-summary-container' => true,
                    'data-formie-summary-token' => $this->getFieldAccessToken($context->submission),
                ])
                ->theme([
                    'class' => [
                        'formie-summary-container',
                    ],
                ]);
        }

        if ($key === 'fieldSummaryBlocks') {
            $isLoading = $context->submission !== null;

            return SlotTag::make('div')
                ->core([
                    'data-formie-summary-blocks' => true,
                    'data-formie-loading' => $isLoading ? true : null,
                    'aria-busy' => $isLoading ? 'true' : null,
                ])
                ->theme([
                    'class' => [
                        'formie-summary-blocks',
                    ],
                ]);
        }

        if ($key === 'fieldSummaryHeading') {
            return SlotTag::make('p')
                ->core([
                    'data-formie-summary-heading' => true,
                    'text' => $this->description,
                ])
                ->theme([
                    'class' => [
                        'formie-summary-heading',
                    ],
                ]);
        }

        if ($key === 'fieldSummaryBlock') {
            return SlotTag::make('div')
                ->core([
                    'data-formie-summary-block' => true,
                ])
                ->theme([
                    'class' => [
                        'formie-summary-block',
                    ],
                ]);
        }

        if ($key === 'fieldSummaryLabel') {
            return SlotTag::make('strong')
                ->core([
                    'data-formie-summary-label' => true,
                ])
                ->theme([
                    'class' => [
                        'formie-summary-label',
                    ],
                ]);
        }

        if ($key === 'fieldSummaryValue') {
            return SlotTag::make('span')
                ->core([
                    'data-formie-summary-value' => true,
                ])
                ->theme([
                    'class' => [
                        'formie-summary-value',
                    ],
                ]);
        }

        return parent::defineFieldSlotTag($key, $context);
    }

    protected function defineSubmissionHtml(mixed $value, ?ElementInterface $element, bool $inline): string
    {
        return Craft::$app->getView()->renderTemplate('formie/_formfields/summary/input', [
            'name' => $this->handle,
            'value' => $value,
            'field' => $this,
        ]);
    }

    protected function defineClientModules(): array
    {
        $modules = parent::defineClientModules();
        
        $modules[] = new ClientModule([
            'id' => 'summary',
            'config' => [
                'fieldId' => (string)$this->id,
            ],
        ]);

        return $modules;
    }
}

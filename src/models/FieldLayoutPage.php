<?php
namespace verbb\formie\models;

use verbb\formie\Formie;
use verbb\formie\base\FieldInterface;
use verbb\formie\base\TranslatablePropertiesInterface;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\helpers\ArrayHelper;
use verbb\formie\helpers\ConditionsHelper;
use verbb\formie\helpers\FieldTraversal;
use verbb\formie\helpers\StringHelper;
use verbb\formie\helpers\ValidationHelper;

use craft\base\SavableComponent;
use craft\helpers\Json;

class FieldLayoutPage extends SavableComponent implements TranslatablePropertiesInterface
{
    // Static Methods
    // =========================================================================

    public static function translatableProperties(): array
    {
        return ['label'];
    }


    // Properties
    // =========================================================================

    public ?int $layoutId = null;
    public ?string $label = null;
    public ?int $sortOrder = null;
    public ?string $uid = null;

    private ?Form $_form = null;
    private ?FieldLayout $_layout = null;
    private ?FieldLayoutPageSettings $_pageSettings = null;
    private array $_rows = [];


    // Public Methods
    // =========================================================================

    public function __construct($config = [])
    {
        if (array_key_exists('settings', $config)) {
            // Swap `settings` to `pageSettings` due to conflict with `SavableComponent::getSettings()` handling
            $config['pageSettings'] = ArrayHelper::remove($config, 'settings', []);
        }

        unset($config['enableConditions']);
        unset($config['notificationFlag']);

        parent::__construct($config);
    }

    public function getForm(): ?Form
    {
        if ($this->_form || !$this->layoutId) {
            return $this->_form;
        }

        return $this->_form = Formie::$plugin->getForms()->getFormByLayoutId($this->layoutId);
    }

    public function getLayout(): ?FieldLayout
    {
        if ($this->_layout || !$this->layoutId) {
            return $this->_layout;
        }

        return $this->_layout = Formie::$plugin->getFields()->getLayoutById($this->layoutId);
    }

    public function getHandle(): ?string
    {
        // Auto-generated for the moment.
        return StringHelper::toHandle((string)$this->label);
    }

    public function getSettings(): array
    {
        // Override `SavableComponent::getSettings()` behaviour to use our settings
        return $this->getPageSettings()?->toArray() ?? [];
    }

    public function getPageSettings(): ?FieldLayoutPageSettings
    {
        return $this->_pageSettings;
    }

    public function setPageSettings(array|string|null $pageSettings): void
    {
        if (is_string($pageSettings)) {
            $pageSettings = new FieldLayoutPageSettings(Json::decodeIfJson($pageSettings));
        }

        if (!($pageSettings instanceof FieldLayoutPageSettings)) {
            $pageSettings = new FieldLayoutPageSettings(($pageSettings ?? []));
        }

        $this->_pageSettings = $pageSettings;
    }

    public function getRows(): array
    {
        return $this->_rows;
    }

    public function getEnabledRows(): array
    {
        return array_values(array_filter($this->getRows(), static fn(FieldLayoutRow $row) => $row->getEnabledFields() !== []));
    }

    public function setRows(array $rows): void
    {
        $this->_rows = [];

        foreach ($rows as $row) {
            $this->_rows[] = (!($row instanceof FieldLayoutRow)) ? new FieldLayoutRow($row) : $row;
        }
    }

    public function getFields(): array
    {
        $fields = [];

        foreach ($this->getRows() as $row) {
            array_push($fields, ...$row->getFields());
        }

        return $fields;
    }

    public function getEnabledFields(): array
    {
        return array_values(array_filter($this->getFields(), static fn(FieldInterface $field) => !$field->getIsDisabled()));
    }

    public function getFieldsRecursively(): array
    {
        return FieldTraversal::recursively($this->getFields());
    }

    public function getFieldByHandle(string $handle): ?FieldInterface
    {
        foreach ($this->getFields() as $field) {
            if ($field->handle === $handle) {
                return $field;
            }
        }

        return null;
    }

    public function getFormBuilderConfig(): array
    {
        return [
            'id' => $this->id,
            'uid' => (string)$this->uid,
            'layoutId' => $this->layoutId,
            'label' => $this->label,
            '_handle' => $this->getHandle(),
            'settings' => $this->getPageSettings()?->toArray(),
            'sortOrder' => $this->sortOrder,
            'errors' => $this->getErrors(),
            'rows' => array_map(function($row) {
                return $row->getFormBuilderConfig();
            }, $this->getRows()),
        ];
    }

    public function getCpEditConfig(): array
    {
        return [
            'id' => (string)$this->id,
            'uid' => (string)$this->uid,
            'label' => $this->label,
            'settings' => $this->getSettings(),
            'fields' => array_map(static function(FieldInterface $field) {
                return $field->getCpEditConfig();
            }, $this->getEnabledFields()),
        ];
    }

    public function getClientRenderedDefinition(Form $form, int $index): array
    {
        $pageSettings = $this->getPageSettings();
        // Prefer submission-aware last-page when editing/resuming so primary action is submit
        // when later pages are conditionally hidden for this submission.
        $isLastPage = $form->isLastPage($this, $form->getCurrentSubmission());
        $secondaryActions = [];

        if ($index > 0 && $pageSettings->showBackButton) {
            $secondaryActions[] = [
                'type' => 'back',
                'label' => $pageSettings->backButtonLabel,
            ];
        }

        if ($pageSettings->showSaveButton) {
            $secondaryActions[] = [
                'type' => 'save',
                'label' => $pageSettings->saveButtonLabel,
            ];
        }

        return [
            'id' => (string)$this->id,
            'key' => 'page-' . ($index + 1),
            'label' => $this->label,
            'condition' => ConditionsHelper::toComponentConditionDefinition($this->getBrowserConditions()),
            'rows' => array_values(array_map(static function(FieldLayoutRow $row) {
                return $row->getClientRenderedDefinition();
            }, $this->getRows())),
            'actions' => [
                'primary' => [
                    'type' => $isLastPage ? 'submit' : 'next',
                    'label' => $pageSettings->submitButtonLabel,
                    'condition' => $this->hasSubmitButtonConditions() ? ConditionsHelper::toComponentConditionDefinition($this->getSubmitButtonClientConditions()) : null,
                ],
                'secondary' => $secondaryActions,
            ],
        ];
    }

    public function validateSettings(): void
    {
        $settings = $this->getPageSettings();

        if (!$settings->validate()) {
            $this->addError('settings', $settings->getErrors());
        }
    }

    public function validateRows(): void
    {
        foreach ($this->getRows() as $rowKey => $row) {
            if (!$row->validate()) {
                ValidationHelper::addPrefixedErrors($this, $row->getErrors(), "rows.$rowKey");
            }
        }
    }

    public function isConditionallyHidden(Submission $submission): bool
    {
        if (!$this->hasConditions()) {
            return false;
        }
        $settings = $this->getConditions();
        return ConditionsHelper::evaluate($settings, $submission)->hides($settings['showRule'] ?? $settings['effect'] ?? 'show');
    }

    public function hasConditions(): bool
    {
        return ($this->getPageSettings()->enablePageConditions && $this->getConditions());
    }

    public function getConditions(): array
    {
        return $this->getPageSettings()->pageConditions ?? [];
    }

    public function getBrowserConditions(): array
    {
        $conditions = $this->getConditions();

        if (!$conditions) {
            return [];
        }

        if ($form = $this->getForm()) {
            $conditions = ConditionsHelper::normalizeClientConditions($conditions, $form);
        }

        $conditions['clearOnHide'] = true;

        return $conditions;
    }

    public function getConditionsJson(): ?string
    {
        if (!$this->getPageSettings()->enablePageConditions) {
            return null;
        }

        $conditions = $this->getBrowserConditions();

        if (!$conditions) {
            return null;
        }

        return Json::encode($conditions);
    }

    public function hasSubmitButtonConditions(): bool
    {
        $pageSettings = $this->getPageSettings();

        return ($pageSettings->enableNextButtonConditions && $pageSettings->getConditions());
    }

    public function shouldRenderSubmitOnLastRow(bool $hasRows): bool
    {
        return $this->getPageSettings()?->shouldRenderSubmitOnLastRow($hasRows) ?? false;
    }

    public function isLastRow(FieldLayoutRow $row): bool
    {
        $rows = $this->getEnabledRows();

        if (!$rows) {
            return false;
        }

        $lastRow = $rows[array_key_last($rows)];

        if ($row === $lastRow) {
            return true;
        }

        if ($row->id !== null && $lastRow->id !== null && (string)$row->id === (string)$lastRow->id) {
            return true;
        }

        if ($row->uid !== null && $lastRow->uid !== null && (string)$row->uid === (string)$lastRow->uid) {
            return true;
        }

        return false;
    }

    public function getSubmitButtonConditions(): array
    {
        return $this->getPageSettings()->getConditions();
    }

    public function getSubmitButtonClientConditions(): array
    {
        $conditions = $this->getSubmitButtonConditions();

        if (!$conditions) {
            return [];
        }

        if ($form = $this->getForm()) {
            $conditions = ConditionsHelper::normalizeClientConditions($conditions, $form);
        }

        $conditions['clearOnHide'] = true;

        return $conditions;
    }

    public function getSubmitButtonConditionsJson(): ?string
    {
        if (!$this->getPageSettings()->enableNextButtonConditions) {
            return null;
        }

        $conditions = $this->getSubmitButtonClientConditions();

        if (!$conditions) {
            return null;
        }

        return Json::encode($conditions);
    }

    public function getFieldErrors(?Submission $submission): array
    {
        $errors = [];

        foreach ($submission?->getSubmissionErrors()->forPage((int)$this->id) ?? [] as $item) {
            $errors[$item['valuePath']][] = $item['message'];
        }
        return $errors;
    }


    // Protected Methods
    // =========================================================================

    protected function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['label'], 'required'];
        $rules[] = [['settings'], 'validateSettings'];
        $rules[] = [['rows'], 'validateRows'];

        return $rules;
    }
}

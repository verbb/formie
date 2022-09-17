<?php
namespace verbb\formie\models;

use Craft;
use craft\base\Model;
use craft\helpers\ArrayHelper;
use craft\helpers\Json;

class PageSettings extends Model
{
    // Properties
    // =========================================================================

    public ?string $submitButtonLabel = null;
    public ?string $backButtonLabel = null;
    public bool $showBackButton = false;
    public ?string $saveButtonLabel = null;
    public bool $showSaveButton = false;
    public string $saveButtonStyle = 'link';
    public string $buttonsPosition = 'left';
    public ?string $cssClasses = null;
    public ?array $containerAttributes = null;
    public ?array $inputAttributes = null;
    public bool $enableNextButtonConditions = false;
    public array $nextButtonConditions = [];
    public bool $enablePageConditions = false;
    public array $pageConditions = [];
    public bool $enableJsEvents = false;
    public array $jsGtmEventOptions = [];


    // Public Methods
    // =========================================================================
    
    /**
     * @inheritDoc
     */
    public function init(): void
    {
        if (!$this->submitButtonLabel) {
            $this->submitButtonLabel = Craft::t('formie', 'Submit');
        }

        if (!$this->backButtonLabel) {
            $this->backButtonLabel = Craft::t('formie', 'Back');
        }

        if (!$this->saveButtonLabel) {
            $this->saveButtonLabel = Craft::t('formie', 'Save');
        }
    }

    public function getContainerAttributes(): array
    {
        if (!$this->containerAttributes) {
            return [];
        }

        return ArrayHelper::map($this->containerAttributes, 'label', 'value');
    }

    public function getInputAttributes(): array
    {
        if (!$this->inputAttributes) {
            return [];
        }

        return ArrayHelper::map($this->inputAttributes, 'label', 'value');
    }

    public function hasConditions(): bool
    {
        return ($this->enableNextButtonConditions && $this->getConditions());
    }

    public function getConditions(): array
    {
        // Filter out any un-set conditions
        $conditions = $this->nextButtonConditions ?? [];
        $conditionRows = $conditions['conditions'] ?? [];

        foreach ($conditionRows as $key => $condition) {
            if (!($condition['condition'] ?? null)) {
                unset($conditions['conditions'][$key]);
            }
        }

        return $conditions;
    }

    public function getConditionsJson(): ?string
    {
        if ($this->hasConditions()) {
            $conditionSettings = $this->getConditions();
            $conditions = $conditionSettings['conditions'] ?? [];

            // Prep the conditions for JS
            foreach ($conditions as &$condition) {
                ArrayHelper::remove($condition, 'id');

                // Dot-notation to name input syntax
                $condition['field'] = 'fields[' . str_replace(['{', '}', '.'], ['', '', ']['], $condition['field']) . ']';
            }

            unset($condition);

            $conditionSettings['conditions'] = $conditions;

            return Json::encode($conditionSettings);
        }

        return null;
    }
}

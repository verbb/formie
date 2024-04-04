<?php
namespace verbb\formie\models;

use verbb\formie\helpers\ArrayHelper;
use verbb\formie\helpers\ConditionsHelper;

use Craft;
use craft\base\Model;
use craft\helpers\Json;

class FieldLayoutPageSettings extends Model
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

    public function __construct($config = [])
    {
        unset($config['label']);

        parent::__construct($config);
    }
    
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

        parent::init();
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
            $conditionSettings['conditions'] = ConditionsHelper::prepConditionsForJs($conditions);

            return Json::encode($conditionSettings);
        }

        return null;
    }
}

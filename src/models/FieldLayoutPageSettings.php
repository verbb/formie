<?php
namespace verbb\formie\models;

use verbb\formie\base\TranslatablePropertiesInterface;
use verbb\formie\helpers\ArrayHelper;
use verbb\formie\helpers\ClientEventsHelper;
use verbb\formie\helpers\ConditionsHelper;

use Craft;
use craft\base\Model;
use craft\helpers\Json;

class FieldLayoutPageSettings extends Model implements TranslatablePropertiesInterface
{
    // Static Methods
    // =========================================================================

    public static function translatableProperties(): array
    {
        return [
            'submitButtonLabel',
            'backButtonLabel',
            'saveButtonLabel',
        ];
    }



    // Properties
    // =========================================================================

    public ?string $submitButtonLabel = null;
    public ?string $backButtonLabel = null;
    public bool $showBackButton = false;
    public ?string $saveButtonLabel = null;
    public bool $showSaveButton = false;
    public string $saveButtonStyle = 'link';
    public string $buttonsPosition = 'left';
    public string $submitButtonPlacement = 'page-footer';
    public ?string $cssClasses = null;
    public ?array $containerAttributes = null;
    public ?array $inputAttributes = null;
    public bool $enableNextButtonConditions = false;
    public array $nextButtonConditions = [];
    public bool $enablePageConditions = false;
    public array $pageConditions = [];
    public bool $enableClientEvents = false;
    public array $clientEventFields = [];
    public array $clientEvents = [];


    // Public Methods
    // =========================================================================

    public function __construct($config = [])
    {
        unset($config['label']);

        if (is_array($config)) {
            if (array_key_exists('enableJsEvents', $config) && !array_key_exists('enableClientEvents', $config)) {
                $config['enableClientEvents'] = (bool)$config['enableJsEvents'];
            }

            unset($config['enableJsEvents']);

            if (array_key_exists('jsGtmEventOptions', $config) && !array_key_exists('clientEventFields', $config)) {
                $legacy = $config['jsGtmEventOptions'];
                $config['clientEventFields'] = is_array($legacy) ? $legacy : [];
            }

            unset($config['jsGtmEventOptions']);

            if (empty($config['clientEvents']) && !empty($config['clientEventFields']) && is_array($config['clientEventFields'])) {
                $config['clientEvents'] = ClientEventsHelper::migrateLegacyEventFields($config['clientEventFields']);
            }
        }

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

    public function shouldRenderSubmitOnLastRow(bool $hasRows): bool
    {
        return $this->submitButtonPlacement === 'end-of-last-row' && $hasRows;
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
        if (!$this->enableNextButtonConditions) {
            return null;
        }

        $conditions = $this->getConditions();

        if (!$conditions) {
            return null;
        }

        $conditions['clearOnHide'] = true;

        return Json::encode($conditions);
    }
}

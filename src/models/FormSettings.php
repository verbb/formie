<?php
namespace verbb\formie\models;

use Craft;
use craft\base\Model;
use verbb\formie\positions\AboveInput;
use verbb\formie\positions\BelowInput;
use yii\behaviors\AttributeTypecastBehavior;

class FormSettings extends Model
{
    // Public Properties
    // =========================================================================

    public $displayFormTitle = false;
    public $displayPageTabs = false;
    public $displayCurrentPageTitle = false;
    public $displayPageProgress = false;
    public $submitMethod;
    public $submitAction;
    public $submitActionTab;
    public $submitActionUrl;
    public $submitActionFormHide;
    public $submitActionMessage;
    public $submitActionMessageTimeout;
    public $errorMessage;
    public $loadingIndicator;
    public $loadingIndicatorText;
    public $validationOnSubmit;
    public $validationOnFocus;
    public $submissionTitleFormat = '{timestamp}';
    public $collectIp;
    public $storeData;
    public $availabilityMessage;
    public $availabilityMessageDate;
    public $availabilityMessageSubmissions;
    public $defaultLabelPosition;
    public $defaultInstructionsPosition;
    public $progressPosition = 'end';
    public $integrations = [];

    // TODO: remove once we've re-saved forms
    public $displayPageTitles;
    public $submitButtonText;


    // Public Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public function init()
    {
        parent::init();

        if (!$this->errorMessage) {
            $this->errorMessage = Craft::t('formie', 'Couldn’t save submission due to errors.');
        }

        if (!$this->submitActionMessage) {
            $this->submitActionMessage = Craft::t('formie', 'Submission saved.');
        }

        if (!$this->defaultLabelPosition) {
            $this->defaultLabelPosition = AboveInput::class;
        }

        if (!$this->defaultInstructionsPosition) {
            $this->defaultInstructionsPosition = BelowInput::class;
        }
    }

    /**
     * @inheritDoc
     */
    public function behaviors(): array
    {
        return [
            'typecast' => [
                'class' => AttributeTypecastBehavior::class,
                'attributeTypes' => [
                    'displayFormTitle' => AttributeTypecastBehavior::TYPE_BOOLEAN,
                    'displayPageTabs' => AttributeTypecastBehavior::TYPE_BOOLEAN,
                    'displayCurrentPageTitle' => AttributeTypecastBehavior::TYPE_BOOLEAN,
                    'displayPageProgress' => AttributeTypecastBehavior::TYPE_BOOLEAN,
                    'submitMethod' => AttributeTypecastBehavior::TYPE_STRING,
                    'submitAction' => AttributeTypecastBehavior::TYPE_STRING,
                    'submitActionTab' => AttributeTypecastBehavior::TYPE_STRING,
                    'submitActionFormHide' => AttributeTypecastBehavior::TYPE_BOOLEAN,
                    'submitActionMessage' => AttributeTypecastBehavior::TYPE_STRING,
                    'submitActionMessageTimeout' => AttributeTypecastBehavior::TYPE_INTEGER,
                    'submitActionUrl' => AttributeTypecastBehavior::TYPE_STRING,
                    'errorMessage' => AttributeTypecastBehavior::TYPE_STRING,
                    'loadingIndicator' => AttributeTypecastBehavior::TYPE_STRING,
                    'loadingIndicatorText' => AttributeTypecastBehavior::TYPE_STRING,
                    'validationOnSubmit' => AttributeTypecastBehavior::TYPE_BOOLEAN,
                    'validationOnFocus' => AttributeTypecastBehavior::TYPE_BOOLEAN,
                    'submissionTitleFormat' => AttributeTypecastBehavior::TYPE_STRING,
                    'collectIp' => AttributeTypecastBehavior::TYPE_BOOLEAN,
                    'storeData' => AttributeTypecastBehavior::TYPE_BOOLEAN,
                    'availabilityMessage' => AttributeTypecastBehavior::TYPE_STRING,
                    'defaultLabelPosition' => AttributeTypecastBehavior::TYPE_STRING,
                    'defaultInstructionsPosition' => AttributeTypecastBehavior::TYPE_STRING,
                    'progressPosition' => AttributeTypecastBehavior::TYPE_STRING,
                ]
            ]
        ];
    }
}

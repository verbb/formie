<?php
namespace verbb\formie\models;

use Craft;
use craft\base\Model;
use craft\db\SoftDeleteTrait;
use craft\helpers\ArrayHelper;
use craft\helpers\Json;
use craft\helpers\UrlHelper;
use craft\validators\HandleValidator;
use craft\validators\UniqueValidator;

use DateTime;
use verbb\formie\Formie;
use verbb\formie\records\Stencil as StencilRecord;

use yii\behaviors\AttributeTypecastBehavior;

class Stencil extends Model
{
    // Traits
    // =========================================================================

    use SoftDeleteTrait {
        behaviors as softDeleteBehaviors;
    }


    // Public Properties
    // =========================================================================

    /**
     * @var int
     */
    public $id;

    /**
     * @var string
     */
    public $name;

    /**
     * @var string
     */
    public $handle;

    /**
     * @var StencilData
     */
    public $data;

    /**
     * @var int
     */
    public $templateId;

    /**
     * @var int
     */
    public $defaultStatusId;

    /**
     * @var DateTime
     */
    public $dateDeleted;

    /**
     * @var string
     */
    public $uid;


    // Public Properties
    // =========================================================================

    private $_template;
    private $_defaultStatus;


    // Public Methods
    // =========================================================================

    /**
     * @return string
     */
    public function __toString()
    {
        return (string)$this->getDisplayName();
    }

    /**
     * @inheritDoc
     */
    public function init()
    {
        parent::init();

        if (empty($this->data)) {
            $this->data = new StencilData();
        } else {
            $data = Json::decodeIfJson($this->data);
            $this->data = new StencilData($data);
        }
    }

    /**
     * @inheritDoc
     */
    public function behaviors(): array
    {
        $behaviors = $this->softDeleteBehaviors();

        $behaviors['typecast'] = [
            'class' => AttributeTypecastBehavior::class,
            'attributeTypes' => [
                'id' => AttributeTypecastBehavior::TYPE_INTEGER,
                'name' => AttributeTypecastBehavior::TYPE_STRING,
                'handle' => AttributeTypecastBehavior::TYPE_STRING,
                'templateId' => AttributeTypecastBehavior::TYPE_INTEGER,
                'defaultStatusId' => AttributeTypecastBehavior::TYPE_INTEGER,
                'uid' => AttributeTypecastBehavior::TYPE_STRING,
            ]
        ];

        return $behaviors;
    }

    /**
     * Returns the stencil name.
     *
     * @return string
     */
    public function getTitle(): string
    {
        return $this->name;
    }

    /**
     * Sets the stencil name.
     *
     * @param string $value
     */
    public function setTitle(string $value)
    {
        $this->name = $value;
    }

    /**
     * Returns the form settings.
     *
     * @return FormSettings
     */
    public function getSettings(): FormSettings
    {
        return $this->data->settings;
    }

    /**
     * Sets the form settings.
     *
     * @param FormSettings|array|string $settings
     */
    public function setSettings($settings)
    {
        if ($settings instanceof FormSettings) {
            $this->data->settings = $settings;
        } else {
            $settings = Json::decodeIfJson($settings);
            $this->data->settings = new FormSettings($settings);
        }
    }

    /**
     * Gets the display name for the status.
     *
     * @return string
     */
    public function getDisplayName(): string
    {
        if ($this->dateDeleted !== null)
        {
            return $this->name . Craft::t('formie', ' (Trashed)');
        }

        return $this->name;
    }

    /**
     * @inheritDoc
     */
    public function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['name', 'handle'], 'required'];
        $rules[] = [['name', 'handle'], 'string', 'max' => 255];
        $rules[] = [
            ['handle'],
            HandleValidator::class,
            'reservedWords' => ['id', 'dateCreated', 'dateUpdated', 'uid', 'title']
        ];
        $rules[] = [
            ['handle'],
            UniqueValidator::class,
            'targetClass' => StencilRecord::class
        ];

        return $rules;
    }

    /**
     * Returns the CP URL for editing the stencil.
     *
     * @return string
     */
    public function getCpEditUrl(): string
    {
        return UrlHelper::cpUrl('formie/settings/stencils/edit/' . $this->id);
    }

    /**
     * Returns the stencils config for the form builder.
     *
     * @return array
     */
    public function getConfig(): array
    {
        return ArrayHelper::merge($this->data->getAttributes(), [
            'title' => $this->getTitle(),
            'handle' => $this->handle,
            'templateId' => $this->templateId,
            'defaultStatusId' => $this->defaultStatusId,
        ]);
    }

    /**
     * Returns the form's template, or null if not set.
     *
     * @return FormTemplate|null
     */
    public function getTemplate()
    {
        if (!$this->_template) {
            if ($this->templateId) {
                $this->_template = Formie::$plugin->getFormTemplates()->getTemplateById($this->templateId);
            } else {
                return null;
            }
        }

        return $this->_template;
    }

    /**
     * Sets the form template.
     *
     * @param FormTemplate|null $template
     */
    public function setTemplate($template)
    {
        if ($template) {
            $this->_template = $template;
            $this->templateId = $template->id;
        } else {
            $this->_template = $this->templateId = null;
        }
    }

    /**
     * Returns the default status for a form.
     *
     * @return Status
     */
    public function getDefaultStatus(): Status
    {
        if (!$this->_defaultStatus) {
            if ($this->defaultStatusId) {
                $this->_defaultStatus = Formie::$plugin->getStatuses()->getStatusById($this->defaultStatusId);
            } else {
                $this->_defaultStatus = Formie::$plugin->getStatuses()->getAllStatuses()[0];
            }
        }

        return $this->_defaultStatus;
    }

    /**
     * Sets the default status.
     *
     * @param Status|null $status
     */
    public function setDefaultStatus($status)
    {
        if ($status) {
            $this->_defaultStatus = $status;
            $this->defaultStatusId = $status->id;
        } else {
            $this->_defaultStatus = $this->defaultStatusId = null;
        }
    }
}

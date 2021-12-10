<?php
namespace verbb\formie\models;

use verbb\formie\Formie;
use verbb\formie\records\Stencil as StencilRecord;
use verbb\formie\services\Statuses;

use Craft;
use craft\base\Model;
use craft\db\SoftDeleteTrait;
use craft\helpers\ArrayHelper;
use craft\helpers\Json;
use craft\helpers\UrlHelper;
use craft\validators\HandleValidator;
use craft\validators\UniqueValidator;

use DateTime;

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

        $data = Json::decodeIfJson($this->data);

        if (!is_array($data)) {
            $this->data = new StencilData();
        } else {
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
     * Returns the stencils config for form builder.
     *
     * @return array
     */
    public function getFormConfig(): array
    {
        return ArrayHelper::merge($this->data->getAttributes(), [
            'title' => $this->getTitle(),
            'handle' => $this->handle,
            'templateId' => $this->templateId,
            'defaultStatusId' => $this->defaultStatusId,
        ]);
    }

    /**
     * Returns the stencils config for project config.
     *
     * @return array
     */
    public function getConfig(): array
    {
        $data = $this->data->getAttributes();
        $data['settings'] = $data['settings']->getAttributes();

        // It's important to not store actual IDs in stencil data. Ensure they're marked as 'new'
        // for pages, rows and fields.
        $pages = $data['pages'] ?? [];

        foreach ($pages as $pageKey => $page) {
            $pageId = $page['id'] ?? '';

            if (strpos($pageId, 'new') !== 0) {
                $pages[$pageKey]['id'] = 'new' . rand();
            }

            $rows = $page['rows'] ?? [];

            foreach ($rows as $rowKey => $row) {
                $rowId = $row['id'] ?? '';

                if (strpos($rowId, 'new') !== 0) {
                    $pages[$pageKey]['rows'][$rowKey]['id'] = 'new' . rand();
                }

                $fields = $row['fields'] ?? [];

                foreach ($fields as $fieldKey => $field) {
                    $fieldId = $field['id'] ?? '';

                    if (strpos($fieldId, 'new') !== 0) {
                        $pages[$pageKey]['rows'][$rowKey]['fields'][$fieldKey]['id'] = 'new' . rand();
                    }
                }
            }
        }

        $data['pages'] = $pages;

        return [
            'name' => $this->name,
            'handle' => $this->handle,
            'template' => $this->getTemplate()->uid ?? null,
            'defaultStatus' => $this->getDefaultStatus()->uid ?? null,
            'data' => $data,
        ];
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
                $this->_defaultStatus = Formie::$plugin->getStatuses()->getAllStatuses()[0] ?? null;
            }
        }

        // Check if for whatever reason there isn't a default status - create it
        if ($this->_defaultStatus === null) {
            // But check for admin changes, as it's a project config setting change to make.
            if (Craft::$app->getConfig()->getGeneral()->allowAdminChanges) {
                $projectConfig = Craft::$app->projectConfig;

                // Maybe the project config didn't get applied? Check for existing values
                // This can likely be removed later, as this fix is already in place when installing Formie
                $statuses = $projectConfig->get(Statuses::CONFIG_STATUSES_KEY, true) ?? [];

                foreach ($statuses as $statusUid => $statusData) {
                    $projectConfig->processConfigChanges(Statuses::CONFIG_STATUSES_KEY . '.' . $statusUid, true);
                }

                // If there's _still_ not a status, just go ahead and create it...
                $this->_defaultStatus = Formie::$plugin->getStatuses()->getAllStatuses()[0] ?? null;

                if ($this->_defaultStatus === null) {
                    $this->_defaultStatus = new Status([
                        'name' => 'New',
                        'handle' => 'new',
                        'color' => 'green',
                        'sortOrder' => 1,
                        'isDefault' => 1
                    ]);

                    Formie::getInstance()->getStatuses()->saveStatus($this->_defaultStatus);
                }
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

    /**
     * Returns a collection of notification models, from their serialized data.
     *
     * @return Status
     */
    public function getNotifications()
    {
        $notificationsData = $this->data->notifications ?? [];

        if ($notificationsData) {
            $notifications = [];

            foreach ($notificationsData as $notificationData) {
                $notifications[] = new Notification($notificationData);
            }

            return $notifications;
        }

        return [];
    }
}

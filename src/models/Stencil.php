<?php
namespace verbb\formie\models;

use verbb\formie\Formie;
use verbb\formie\records\Stencil as StencilRecord;
use verbb\formie\services\Statuses;

use Craft;
use craft\base\Model;
use craft\db\SoftDeleteTrait;
use craft\elements\Entry;
use craft\helpers\ArrayHelper;
use craft\helpers\Json;
use craft\helpers\UrlHelper;
use craft\validators\HandleValidator;
use craft\validators\UniqueValidator;

use DateTime;
use yii\web\ServerErrorHttpException;
use yii\base\NotSupportedException;
use yii\base\Exception;
use yii\base\ErrorException;

class Stencil extends Model
{
    // Traits
    // =========================================================================

    use SoftDeleteTrait {
        behaviors as softDeleteBehaviors;
    }


    // Properties
    // =========================================================================

    public ?int $id = null;
    public ?string $name = null;
    public ?string $handle = null;
    public ?StencilData $data = null;

    public ?int $templateId = null;
    public ?int $submitActionEntryId = null;
    public ?int $submitActionEntrySiteId = null;
    public ?int $defaultStatusId = null;
    public ?DateTime $dateDeleted = null;
    public ?string $uid = null;

    private ?FormTemplate $_template = null;
    private ?Status $_defaultStatus = null;
    private mixed $_submitActionEntry = null;


    // Public Methods
    // =========================================================================

    public function __construct($config = [])
    {
        // Config normalization
        if (array_key_exists('data', $config)) {
            if (is_string($config['data'])) {
                $config['data'] = new StencilData(Json::decodeIfJson($config['data']));
            }

            if (!($config['data'] instanceof StencilData)) {
                $config['data'] = new StencilData();
            }
        } else {
            $config['data'] = new StencilData();
        }

        parent::__construct($config);
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->getDisplayName();
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
    public function setTitle(string $value): void
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
     * @param array|string|FormSettings $settings
     */
    public function setSettings(FormSettings|array|string $settings): void
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
        if ($this->dateDeleted !== null) {
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
            'reservedWords' => ['id', 'dateCreated', 'dateUpdated', 'uid', 'title'],
        ];
        $rules[] = [
            ['handle'],
            UniqueValidator::class,
            'targetClass' => StencilRecord::class,
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
     * Returns the stencils' config for form builder.
     *
     * @return array
     */
    public function getFormConfig(): array
    {
        return ArrayHelper::merge($this->data->getAttributes(), [
            'id' => $this->id,
            'title' => $this->getTitle(),
            'handle' => $this->handle,
            'templateId' => $this->templateId,
            'defaultStatusId' => $this->defaultStatusId,
        ]);
    }

    /**
     * Returns the stencils' config for project config.
     *
     * @return array
     * @throws ErrorException
     * @throws Exception
     * @throws NotSupportedException
     * @throws ServerErrorHttpException
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

            if (!str_starts_with($pageId, 'new')) {
                $pages[$pageKey]['id'] = 'new' . mt_rand();
            }

            $rows = $page['rows'] ?? [];

            foreach ($rows as $rowKey => $row) {
                $rowId = $row['id'] ?? '';

                if (!str_starts_with($rowId, 'new')) {
                    $pages[$pageKey]['rows'][$rowKey]['id'] = 'new' . mt_rand();
                }

                $fields = $row['fields'] ?? [];

                foreach ($fields as $fieldKey => $field) {
                    $fieldId = $field['id'] ?? '';

                    if (!str_starts_with($fieldId, 'new')) {
                        $pages[$pageKey]['rows'][$rowKey]['fields'][$fieldKey]['id'] = 'new' . mt_rand();
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
            'submitActionEntry' => $this->getRedirectEntry()->uid ?? null,
            'data' => $data,
        ];
    }

    /**
     * Returns the form's template, or null if not set.
     *
     * @return FormTemplate|null
     */
    public function getTemplate(): ?FormTemplate
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
    public function setTemplate(?FormTemplate $template): void
    {
        if ($template) {
            $this->_template = $template;
            $this->templateId = $template->id;
        } else {
            $this->_template = $this->templateId = null;
        }
    }

    /**
     * Gets the stencil's redirect entry, or null if not set.
     *
     * @return Entry|null
     */
    public function getRedirectEntry(): ?Entry
    {
        if (!$this->submitActionEntryId) {
            return null;
        }

        if (!$this->_submitActionEntry) {
            $siteId = $this->submitActionEntrySiteId ?: '*';

            $this->_submitActionEntry = Craft::$app->getEntries()->getEntryById($this->submitActionEntryId, $siteId);
        }

        return $this->_submitActionEntry;
    }

    /**
     * Returns the default status for a form.
     *
     * @return Status
     * @throws ErrorException
     * @throws Exception
     * @throws NotSupportedException
     * @throws ServerErrorHttpException
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
                        'isDefault' => 1,
                    ]);

                    Formie::$plugin->getStatuses()->saveStatus($this->_defaultStatus);
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
    public function setDefaultStatus(?Status $status): void
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
     * @return array
     */
    public function getNotifications(): array
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

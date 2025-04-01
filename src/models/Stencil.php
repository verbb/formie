<?php
namespace verbb\formie\models;

use verbb\formie\Formie;
use verbb\formie\elements\Form;
use verbb\formie\helpers\ArrayHelper;
use verbb\formie\models\FieldLayout;
use verbb\formie\records\Stencil as StencilRecord;
use verbb\formie\services\Statuses;

use Craft;
use craft\base\Model;
use craft\db\SoftDeleteTrait;
use craft\elements\Entry;
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

    public function __toString()
    {
        return $this->getDisplayName();
    }

    public function getTitle(): string
    {
        return $this->name;
    }

    public function setTitle(string $value): void
    {
        $this->name = $value;
    }

    public function getCpEditUrl(): ?string
    {
        return UrlHelper::cpUrl('formie/settings/stencils/edit/' . $this->id);
    }

    public function getDisplayName(): string
    {
        if ($this->dateDeleted !== null) {
            return Craft::t('formie', '{title} (Trashed)', ['title' => $this->name]);
        }

        return $this->name;
    }

    public function getNotifications(): array
    {
        return $this->data->notifications ?? [];
    }

    public function getFormLayout(): FieldLayout
    {
        return $this->data->getFieldLayout();
    }

    public function getSettings(): FormSettings
    {
        return $this->data->settings;
    }

    public function getFormBuilderConfig(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->getTitle(),
            'handle' => $this->handle,
            'errors' => $this->getErrors(),
            'templateId' => $this->templateId,
            'pages' => $this->getFormLayout()->getFormBuilderConfig(),
            'settings' => $this->getSettings()->getFormBuilderConfig(),
            'isStencil' => true,
        ];
    }

    public function getNotificationsConfig(): array
    {
        return Formie::$plugin->getNotifications()->getNotificationsConfig($this->getNotifications());
    }

    public function getConfig(): array
    {
        return [
            'name' => $this->name,
            'handle' => $this->handle,
            'template' => $this->getTemplate()->uid ?? null,
            'defaultStatus' => $this->getDefaultStatus()->uid ?? null,
            'submitActionEntry' => $this->getRedirectEntry()->uid ?? null,
            'data' => $this->data->getSerializedData(),
        ];
    }

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

    public function setTemplate(?FormTemplate $template): void
    {
        if ($template) {
            $this->_template = $template;
            $this->templateId = $template->id;
        } else {
            $this->_template = $this->templateId = null;
        }
    }

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
                $projectConfig = Craft::$app->getProjectConfig();

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

    public function setDefaultStatus(?Status $status): void
    {
        if ($status) {
            $this->_defaultStatus = $status;
            $this->defaultStatusId = $status->id;
        } else {
            $this->_defaultStatus = $this->defaultStatusId = null;
        }
    }

    public function applyStencilToForm(Form $form): void
    {
        $this->data->populateToForm($form);

        $form->isApplyingStencil = true;
        $form->setTemplate($this->getTemplate());
        $form->setDefaultStatus($this->getDefaultStatus());
    }


    // Protected Methods
    // =========================================================================

    protected function defineRules(): array
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
}

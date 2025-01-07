<?php
namespace verbb\formie\models;

use verbb\formie\base\NestedField;
use verbb\formie\elements\Form;
use verbb\formie\models\FieldLayout;
use verbb\formie\models\FieldLayoutPage;
use verbb\formie\models\Notification;

use Craft;
use craft\base\Model;
use craft\helpers\Json;
use craft\helpers\StringHelper;

use DateTime;

class StencilData extends Model
{
    // Static Methods
    // =========================================================================

    public static function getSerializedNotifications(array $notifications): array
    {
        foreach ($notifications as $key => $notification) {
            if ($notification instanceof Notification) {
                $notification = $notification->toArray();
            }

            unset($notification['id']);
            unset($notification['formId']);
            unset($notification['uid']);

            $notifications[$key] = $notification;
        }

        return $notifications;
    }

    public static function getSerializedFormSettings(array|FormSettings $settings): array
    {
        if ($settings instanceof FormSettings) {
            $settings = $settings->toArray();
        }

        $integrations = $settings['integrations'] ?? [];

        $settings['integrations'] = array_filter($integrations, function($integration) {
            return isset($integration['enabled']) && $integration['enabled'];
        });

        return $settings;
    }

    public static function getSerializedLayout(FieldLayout $layout): array
    {
        $layoutData = [];

        $serializeRows = function($rows) use (&$serializeRows) {
            $pageData = [];

            foreach ($rows as $rowKey => $row) {
                $rowData = [];

                foreach ($row->getFields() as $fieldKey => $field) {
                    $settings = $field->getSettings();
                    $settings['label'] = $field->label;
                    $settings['handle'] = $field->handle;

                    if ($field instanceof NestedField) {
                        $settings['rows'] = $serializeRows($field->getRows());
                        $settings['nestedLayoutId'] = null;
                    }

                    $rowData['fields'][] = [
                        'type' => get_class($field),
                        'settings' => $settings,
                    ];
                }

                $pageData[] = $rowData;
            }

            return $pageData;
        };

        foreach ($layout->getPages() as $pageKey => $page) {
            $pageData = [
                'label' => $page->label,
                'settings' => $page->getPageSettings()?->toArray(),
            ];

            $pageData['rows'] = $serializeRows($page->getRows());

            $layoutData[] = $pageData;
        }

        return $layoutData;
    }


    // Properties
    // =========================================================================

    public string $dataRetention = 'forever';
    public ?string $dataRetentionValue = null;
    public string $userDeletedAction = 'retain';
    public string $fileUploadsAction = 'retain';
    public FormSettings|array|null $settings = null;
    public array $pages = [];
    public array $notifications = [];


    // Public Methods
    // =========================================================================

    public function __construct($config = [])
    {
        if (array_key_exists('requireUser', $config)) {
            unset($config['requireUser']);
        }

        if (array_key_exists('availability', $config)) {
            unset($config['availability']);
        }

        if (array_key_exists('availabilityFrom', $config)) {
            unset($config['availabilityFrom']);
        }

        if (array_key_exists('availabilityTo', $config)) {
            unset($config['availabilityTo']);
        }

        if (array_key_exists('availabilitySubmissions', $config)) {
            unset($config['availabilitySubmissions']);
        }
        
        // Normalize form layout
        if (array_key_exists('pages', $config)) {
            if (is_array($config['pages'])) {
                foreach ($config['pages'] as &$page) {
                    if ($page instanceof FieldLayoutPage) {
                        continue;
                    }

                    $page = new FieldLayoutPage($page);
                }
            }
        }

        // Normalize notifications
        if (array_key_exists('notifications', $config) && $config['notifications']) {
            if (is_array($config['notifications'])) {
                foreach ($config['notifications'] as &$notification) {
                    if ($notification instanceof Notification) {
                        continue;
                    }

                    // Just in case there's no handle for older notification data
                    if (!isset($notification['handle'])) {
                        $notification['handle'] = StringHelper::toHandle($notification['name']);
                    }

                    $notification = new Notification($notification);
                }
            }
        }

        // Normalize form settings
        if (array_key_exists('settings', $config)) {
            if (is_string($config['settings'])) {
                $config['settings'] = new FormSettings(Json::decodeIfJson($config['settings']));
            }

            if (is_array($config['settings'])) {
                $config['settings'] = new FormSettings($config['settings']);
            }

            if (!($config['settings'] instanceof FormSettings)) {
                $config['settings'] = new FormSettings();
            }
        } else {
            $config['settings'] = new FormSettings();
        }

        parent::__construct($config);
    }

    public function getFieldLayout(): FieldLayout
    {
        return new FieldLayout(['pages' => $this->pages]);
    }

    public function getSerializedData(): array
    {
        $data = $this->getAttributes();
        
        $data['settings'] = static::getSerializedFormSettings($this->settings);
        $data['notifications'] = static::getSerializedNotifications($this->notifications);
        $data['pages'] = static::getSerializedLayout($this->getFieldLayout());

        return $data;
    }

    public function populateFormData(Form $form): void
    {
        $notifications = $form->getNotifications();
        $layout = $form->getFormLayout();
        $settings = $form->getSettings()->toArray();

        // Serialize the data first, then convert back to proper models. This just strips out things like IDs, etc
        // because we are duplicating them from a form. This is run either when saving a stencil (and we have a temp
        // form with no IDs), or when creating a stencil from a form. It's the latter case we need to strip out things.
        $settings = static::getSerializedFormSettings($settings);
        $notifications = static::getSerializedNotifications($notifications);
        $pages = static::getSerializedLayout($layout);

        $this->settings = new FormSettings($settings);

        $this->notifications = array_map(function($notification) {
            return new Notification($notification);
        }, $notifications);

        $this->pages = array_map(function($page) {
            return new FieldLayoutPage($page);
        }, $pages);
    }

    public function populateToForm(Form $form): void
    {
        $form->settings = $this->settings;
        $form->userDeletedAction = $this->userDeletedAction;
        $form->fileUploadsAction = $this->fileUploadsAction;
        $form->dataRetention = $this->dataRetention;
        $form->dataRetentionValue = $this->dataRetentionValue;

        $form->setNotifications($this->notifications);

        $form->setFormLayout(new FieldLayout(['pages' => $this->pages]));
    }
}

<?php
namespace verbb\formie\models;

use verbb\formie\base\NestedField;
use verbb\formie\elements\Form;
use verbb\formie\models\FieldLayout;
use verbb\formie\models\Notification;

use Craft;
use craft\base\Model;
use craft\helpers\Json;

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

    public static function getSerializedFormSettings(array $settings): array
    {
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

        parent::__construct($config);
    }

    public function init(): void
    {
        parent::init();

        if (empty($this->settings)) {
            $this->settings = new FormSettings();
        } else {
            $settings = Json::decodeIfJson($this->settings);
            $this->settings = new FormSettings($settings);
        }
    }

    public function populateFormData(Form $form): void
    {
        $notifications = $form->getNotifications();
        $layout = $form->getFormLayout();
        $settings = $form->getSettings()->toArray();

        // Serialize the form settings
        $this->settings = static::getSerializedFormSettings($settings);

        // Serialize the notifications
        $this->notifications = static::getSerializedNotifications($notifications);

        // Serialize the form layout
        $this->pages = static::getSerializedLayout($layout);
    }
}

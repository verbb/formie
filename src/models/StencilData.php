<?php
namespace verbb\formie\models;

use Craft;
use craft\base\Model;
use craft\helpers\Json;
use DateTime;

class StencilData extends Model
{
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

    /**
     * @inheritDoc
     */
    public function init(): void
    {
        parent::init();

        if (empty($this->settings)) {
            $this->settings = new FormSettings();
        } else {
            $settings = Json::decodeIfJson($this->settings);
            $this->settings = new FormSettings($settings);
        }

        if (empty($this->pages)) {
            $this->pages = [
                [
                    'id' => 'new' . mt_rand(),
                    'label' => Craft::t('formie', 'Page 1'),
                    'sortOrder' => 0,
                    'rows' => [],
                ],
            ];
        }

        // Ensure all pages have a rows array as project config strips
        // out empty arrays.
        foreach ($this->pages as &$page) {
            if (empty($page['rows'])) {
                $page['rows'] = [];
            }
        }
    }
}

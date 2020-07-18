<?php
namespace verbb\formie\models;

use Craft;
use craft\base\Model;
use craft\helpers\Json;
use yii\behaviors\AttributeTypecastBehavior;

class StencilData extends Model
{
    // Public Properties
    // =========================================================================

    public $requireUser = false;
    public $availability = 'always';
    public $availabilityFrom;
    public $availabilityTo;
    public $availabilitySubmissions;
    public $dataRetention = 'forever';
    public $dataRetentionValue;
    public $userDeletedAction = 'retain';

    /**
     * @var FormSettings
     */
    public $settings;

    /**
     * @var array
     */
    public $pages;

    /**
     * @var array
     */
    public $notifications = [];


    // Public Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public function init()
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
                    'id' => 'new' . rand(),
                    'label' => Craft::t('site', 'Page 1'),
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

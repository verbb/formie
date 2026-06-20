<?php
namespace verbb\formie\models;

use craft\base\Model;

class FormSiteOverride extends Model
{
    // Properties
    // =========================================================================

    public ?int $id = null;
    public ?int $formId = null;
    public ?int $siteId = null;
    public array $overrides = [];


    // Public Methods
    // =========================================================================

    public function defineRules(): array
    {
        return [
            [['formId', 'siteId'], 'required'],
            [['formId', 'siteId'], 'integer'],
            [['overrides'], 'safe'],
        ];
    }
}

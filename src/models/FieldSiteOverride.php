<?php
namespace verbb\formie\models;

use craft\base\Model;

class FieldSiteOverride extends Model
{
    // Properties
    // =========================================================================

    public ?int $id = null;
    public ?int $fieldId = null;
    public ?int $siteId = null;
    public array $overrides = [];


    // Public Methods
    // =========================================================================

    public function defineRules(): array
    {
        return [
            [['fieldId', 'siteId'], 'required'],
            [['fieldId', 'siteId'], 'integer'],
            [['overrides'], 'safe'],
        ];
    }
}

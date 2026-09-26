<?php
namespace verbb\formie\models;

use verbb\formie\base\FieldInterface;
use verbb\formie\base\IntegrationInterface;
use verbb\formie\elements\Form;
use verbb\formie\models\FieldLayoutPage;

use yii\base\BaseObject;

class BrowserModuleContext extends BaseObject
{
    // Properties
    // =========================================================================

    public ?Form $form = null;
    public ?FieldInterface $field = null;
    public ?IntegrationInterface $integration = null;
    public ?FieldLayoutPage $page = null;
    public string $surface = BrowserModuleEntry::SURFACE_SERVER_RENDERED;


    // Public Methods
    // =========================================================================
    public function getTargets(): array
    {
        if ($this->field) {
            return [[
                'targetType' => 'field',
                'targetId' => (string)$this->field->uid,
            ]];
        }

        return [[
            'targetType' => 'form',
            'targetId' => 'form',
        ]];
    }
}

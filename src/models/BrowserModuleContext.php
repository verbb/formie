<?php
namespace verbb\formie\models;

use verbb\formie\base\FieldInterface;
use verbb\formie\base\IntegrationInterface;
use verbb\formie\elements\Form;

use yii\base\BaseObject;

class BrowserModuleContext extends BaseObject
{
    // Properties
    // =========================================================================

    public ?Form $form = null;
    public ?FieldInterface $field = null;
    public ?IntegrationInterface $integration = null;
    public ?FieldLayoutPage $page = null;
    public string $surface = BrowserModule::SURFACE_SERVER_RENDERED;


    // Public Methods
    // =========================================================================

    public function getTargets(): array
    {
        if ($this->field) {
            return [[
                'type' => 'field',
                'uid' => (string)$this->field->uid,
            ]];
        }

        return [[
            'type' => 'form',
        ]];
    }
}

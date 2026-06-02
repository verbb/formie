<?php
namespace verbb\formie\models;

use verbb\formie\base\FieldInterface;
use verbb\formie\base\IntegrationInterface;
use verbb\formie\elements\Form;
use verbb\formie\models\FieldLayoutPage;

use yii\base\BaseObject;

class ClientModuleContext extends BaseObject
{
    // Properties
    // =========================================================================

    public ?Form $form = null;
    public ?FieldInterface $field = null;
    public ?IntegrationInterface $integration = null;
    public ?FieldLayoutPage $page = null;
    public string $renderTarget = ClientModule::RENDER_TARGET_FRONTEND;


    // Public Methods
    // =========================================================================
    public function getTargets(): array
    {
        if ($this->field) {
            return [[
                'targetType' => 'field',
                'targetId' => (string)$this->field->valueKey(),
            ]];
        }

        return [[
            'targetType' => 'form',
            'targetId' => 'form',
        ]];
    }
}

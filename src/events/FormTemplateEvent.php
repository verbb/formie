<?php
namespace verbb\formie\events;

use verbb\formie\models\EmailTemplate;
use verbb\formie\models\FormTemplate;

use yii\base\Event;

class FormTemplateEvent extends Event
{
    // Properties
    // =========================================================================

    public FormTemplate|EmailTemplate|null $template = null;
    public bool $isNew = false;
    
}

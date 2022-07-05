<?php
namespace verbb\formie\events;

use verbb\formie\elements\Form;
use verbb\formie\models\HtmlTag;

use yii\base\Event;

class ModifyFormHtmlTagEvent extends Event
{
    // Properties
    // =========================================================================

    public ?Form $form = null;
    public ?HtmlTag $tag = null;
    public ?string $key = null;
    public ?array $context = null;
    
}

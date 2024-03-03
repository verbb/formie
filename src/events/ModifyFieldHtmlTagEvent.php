<?php
namespace verbb\formie\events;

use verbb\formie\base\FieldInterface;
use verbb\formie\models\HtmlTag;

use yii\base\Event;

class ModifyFieldHtmlTagEvent extends Event
{
    // Properties
    // =========================================================================

    public ?FieldInterface $field = null;
    public ?HtmlTag $tag = null;
    public ?string $key = null;
    public ?array $context = null;
    
}

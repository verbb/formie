<?php
namespace verbb\formie\events;

use verbb\formie\models\Token;

use yii\base\Event;

class TokenEvent extends Event
{
    // Properties
    // =========================================================================

    public ?Token $token = null;
    public bool $isNew = false;

}

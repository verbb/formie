<?php
namespace verbb\formie\events;

use verbb\formie\models\Token;

use yii\base\Event;

class OauthTokenEvent extends Event
{
    // Properties
    // =========================================================================

    public ?Token $token = null;

}

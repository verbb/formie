<?php
namespace verbb\formie\events;

use verbb\formie\base\FieldInterface;
use verbb\formie\elements\Submission;
use verbb\formie\models\Notification;

use yii\base\Event;

use Faker\Generator as FakerFactory;

class ModifyFieldEmailValueEvent extends Event
{
    // Properties
    // =========================================================================

    public mixed $value = null;
    public ?FieldInterface $field = null;
    public ?Submission $submission = null;
    public ?Notification $notification = null;
    public ?FakerFactory $faker = null;
    
}

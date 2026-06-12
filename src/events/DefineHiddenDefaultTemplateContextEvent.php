<?php
namespace verbb\formie\events;

use craft\base\ElementInterface;

use verbb\formie\base\FieldInterface;
use verbb\formie\models\HiddenDefaultTemplateContext;

use yii\base\Event;

class DefineHiddenDefaultTemplateContextEvent extends Event
{
    public ?FieldInterface $field = null;
    public ?ElementInterface $element = null;
    public ?HiddenDefaultTemplateContext $context = null;

    /** @var array<string, mixed> */
    public array $variables = [];
}

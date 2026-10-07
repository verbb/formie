<?php
namespace verbb\formie\events;

use verbb\formie\base\FieldInterface;
use verbb\formie\models\HiddenDefaultTemplateContext;

use craft\base\ElementInterface;

use yii\base\Event;

class DefineHiddenDefaultTemplateContextEvent extends Event
{
    // Properties
    // =========================================================================

    public ?FieldInterface $field = null;
    public ?ElementInterface $element = null;
    public ?HiddenDefaultTemplateContext $context = null;
    /** @var array<string, mixed> */
    public array $variables = [];
}

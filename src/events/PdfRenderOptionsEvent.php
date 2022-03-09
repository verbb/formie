<?php
namespace verbb\formie\events;

use yii\base\Event;

class PdfRenderOptionsEvent extends Event
{
    public ?array $options;
}

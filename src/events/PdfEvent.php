<?php
namespace verbb\formie\events;

use yii\base\Event;

class PdfEvent extends Event
{
    public ?string $template = null;
    public ?array $variables = null;
    public mixed $pdf = null;
}

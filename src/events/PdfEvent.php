<?php
namespace verbb\formie\events;

use yii\base\Event;

class PdfEvent extends Event
{
    public $template;
    public $variables;
    public $pdf;
}

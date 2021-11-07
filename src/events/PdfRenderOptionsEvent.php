<?php
namespace verbb\formie\events;

use Dompdf\Options;

use yii\base\Event;

class PdfRenderOptionsEvent extends Event
{
    public $options;
}

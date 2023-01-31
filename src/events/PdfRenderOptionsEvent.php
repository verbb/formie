<?php
namespace verbb\formie\events;

use yii\base\Event;

use Dompdf\Options;

class PdfRenderOptionsEvent extends Event
{
    public Options $options;
}

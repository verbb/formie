<?php
namespace verbb\formie\fields\subfields\traits;

use Craft;
use craft\base\ElementInterface;

trait AddressTextSubmissionTrait
{
    // Protected Methods
    // =========================================================================

    protected function defineSubmissionHtml(mixed $value, ?ElementInterface $element, bool $inline): string
    {
        return Craft::$app->getView()->renderTemplate('formie/_formfields/single-line-text/input', [
            'name' => $this->handle,
            'value' => $value,
            'field' => $this,
            'textLimitConfig' => $this->getTextLimitBrowserConfig(true),
            'inputAttributes' => ['id' => $this->handle] + static::SUBMISSION_INPUT_ATTRIBUTES,
        ]);
    }
}

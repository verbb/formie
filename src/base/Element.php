<?php
namespace verbb\formie\base;

use craft\base\Model;
use craft\helpers\UrlHelper;

use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;

abstract class Element extends Integration implements IntegrationInterface
{
    // Properties
    // =========================================================================


    // Static Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public static function isSelectable(): bool
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function getSettingsHtml(): string
    {
        return '';
    }

    /**
     * @inheritDoc
     */
    public function getFormSettingsHtml(Form $form): string
    {
        return '';
    }

    /**
     * @inheritDoc
     */
    public function getFrontEndHtml($field, $options): string
    {
        return '';
    }

    /**
     * @inheritDoc
     */
    public function hasValidSettings(): bool
    {
        return true;
    }
}

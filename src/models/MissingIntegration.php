<?php
namespace verbb\formie\models;

use verbb\formie\base\Integration;

use Craft;
use craft\base\MissingComponentInterface;
use craft\base\MissingComponentTrait;

class MissingIntegration extends Integration implements MissingComponentInterface
{
    // Traits
    // =========================================================================

    use MissingComponentTrait;


    // Public Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public static function typeName(): string
    {
        return Craft::t('formie', 'Missing Integration');
    }

    /**
     * @inheritDoc
     */
    public function getDescription(): string
    {
        return '';
    }
}

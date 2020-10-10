<?php
namespace verbb\formie\base;

use craft\base\ElementInterface;
use craft\base\Field;
use craft\base\Serializable;
use craft\helpers\DateTimeHelper;
use craft\helpers\Db;
use craft\helpers\Html;
use craft\helpers\StringHelper;

use yii\base\Arrayable;

abstract class FormField extends Field implements FormFieldInterface
{
    // Traits
    // =========================================================================

    use FormFieldTrait {
        defineRules as traitDefineRules;
    }


    // Properties
    // =========================================================================

    /**
     * @var bool
     */
    public $searchable = true;


    // Public Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    protected function defineRules(): array
    {
        // These are already populated with base Field rules
        return $this->traitDefineRules();
    }
}

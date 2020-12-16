<?php
namespace verbb\formie\base;

use verbb\formie\Formie;

use Craft;
use craft\base\ElementInterface;
use craft\base\Field;
use craft\base\Serializable;
use craft\helpers\DateTimeHelper;
use craft\helpers\Db;
use craft\helpers\Html;
use craft\helpers\StringHelper;

use yii\base\Arrayable;
use yii\base\UnknownPropertyException;

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
    public function __set($name, $value)
    {
        // Prevent deprecated (removed) model attributes from killing things, particularly when migrating to actually
        // remove them. Been bitten by this issue a number of times...
        try {
            return parent::__set($name, $value);
        } catch (UnknownPropertyException $e) {
            // Let it slide, but log it, _just_ in case.
            Formie::log(Craft::t('app', '{message} {file}:{line}', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]));
        }
    }

    /**
     * @inheritDoc
     */
    protected function defineRules(): array
    {
        // These are already populated with base Field rules
        return $this->traitDefineRules();
    }
}

<?php
namespace verbb\formie\base;

use verbb\formie\Formie;

use Craft;
use craft\base\Field;

use yii\base\UnknownPropertyException;

abstract class FormField extends Field implements FormFieldInterface
{
    // Traits
    // =========================================================================

    use FormFieldTrait {
        defineRules as traitDefineRules;
        getElementValidationRules as traitGetElementValidationRules;
    }


    // Properties
    // =========================================================================

    /**
     * @var bool
     */
    public bool $searchable = true;


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
            parent::__set($name, $value);
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
    public function getElementValidationRules(): array
    {
        // These are already populated with base Field rules
        return $this->traitGetElementValidationRules();
    }


    // Protected
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

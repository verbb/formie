<?php
namespace verbb\formie\behaviors;

use craft\behaviors\FieldLayoutBehavior as CraftFieldLayoutBehavior;
use craft\models\FieldLayout as CraftFieldLayout;

use verbb\formie\Formie;
use verbb\formie\models\FieldLayout;

use yii\base\InvalidConfigException;

class FieldLayoutBehavior extends CraftFieldLayoutBehavior
{
    // Properties
    // =========================================================================

    /**
     * @var FieldLayout|null The field layout associated with the owner
     */
    private ?FieldLayout $_fieldLayout = null;


    // Public Methods
    // =========================================================================

    /**
     * Returns the owner's field layout.
     *
     * @return FieldLayout
     * @throws InvalidConfigException if the configured field layout ID is invalid
     */
    public function getFieldLayout(): CraftFieldLayout
    {
        if ($this->_fieldLayout !== null) {
            return $this->_fieldLayout;
        }

        try {
            $id = $this->getFieldLayoutId();
        } catch (InvalidConfigException) {
            return $this->_fieldLayout = new FieldLayout([
                'type' => $this->elementType,
            ]);
        }

        if (($fieldLayout = Formie::$plugin->getFields()->getLayoutById($id)) === null) {
            // Ignore if this is a trashed form
            if ($this->owner->trashed) {
                return $this->_fieldLayout = new FieldLayout([
                    'type' => $this->elementType,
                ]);
            }

            throw new InvalidConfigException('Invalid field layout ID: ' . $id);
        }

        return $this->_fieldLayout = $fieldLayout;
    }

    /**
     * Sets the owner's field layout.
     *
     * @param FieldLayout $fieldLayout
     */
    public function setFieldLayout(CraftFieldLayout $fieldLayout): void
    {
        $this->_fieldLayout = $fieldLayout;
    }
}

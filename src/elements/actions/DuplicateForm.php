<?php
namespace verbb\formie\elements\actions;

use verbb\formie\Formie;
use verbb\formie\elements\Form;
use verbb\formie\helpers\HandleHelper;
use verbb\formie\helpers\StringHelper;
use verbb\formie\models\FieldLayout;

use Craft;
use craft\base\ElementAction;
use craft\base\ElementInterface;
use craft\db\Query;
use craft\elements\db\ElementQueryInterface;

use Throwable;

use craft\elements\actions\Duplicate;

class DuplicateForm extends Duplicate
{
    // Properties
    // =========================================================================

    public ?string $successMessage = null;


    // Public Methods
    // =========================================================================

    public function getTriggerLabel(): string
    {
        return Craft::t('app', 'Duplicate');
    }

    public function performAction(ElementQueryInterface $query): bool
    {
        $elements = $query->all();
        $successCount = 0;
        $failCount = 0;

        $this->_duplicateElements($query, $elements, $successCount, $failCount);

        // Did all of them fail?
        if ($successCount === 0) {
            $this->setMessage(Craft::t('app', 'Could not duplicate elements due to validation errors.'));
            return false;
        }

        if ($failCount !== 0) {
            $this->setMessage(Craft::t('app', 'Could not duplicate all elements due to validation errors.'));
        } else {
            $this->setMessage(Craft::t('app', 'Elements duplicated.'));
        }

        return true;
    }

    private function _duplicateElements(ElementQueryInterface $query, array $elements, int &$successCount, int &$failCount): void
    {
        $elementsService = Craft::$app->getElements();

        foreach ($elements as $element) {
            // Make sure this element wasn't already duplicated, which could
            // happen if it's the descendant of a previously duplicated element
            // and $this->deep == true.
            if (isset($duplicatedElementIds[$element->id])) {
                continue;
            }

            $attributes = $element->getDuplicateAttributes();

            try {
                $duplicate = $elementsService->duplicateElement($element, $attributes);
            } catch (Throwable) {
                // Validation error
                $failCount++;
                continue;
            }

            $successCount++;
            $duplicatedElementIds[$element->id] = true;
        }
    }
}

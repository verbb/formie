<?php
namespace verbb\formie\elements\actions;

use Craft;
use craft\elements\actions\Duplicate;
use craft\elements\db\ElementQueryInterface;

use Throwable;

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


    // Private Methods
    // =========================================================================

    private function _duplicateElements(ElementQueryInterface $query, array $elements, int &$successCount, int &$failCount): void
    {
        $elementsService = Craft::$app->getElements();
        $currentUser = Craft::$app->getUser()->getIdentity();

        foreach ($elements as $element) {
            if (!$elementsService->canDuplicate($element, $currentUser)) {
                $failCount++;
                continue;
            }

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

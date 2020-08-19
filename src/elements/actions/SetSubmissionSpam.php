<?php
namespace verbb\formie\elements\actions;

use Craft;
use craft\base\ElementAction;
use craft\elements\db\ElementQueryInterface;
use craft\helpers\ArrayHelper;

use verbb\formie\elements\Submission;
use verbb\formie\Formie;

class SetSubmissionSpam extends ElementAction
{
    public $spam;

    /**
     * @inheritdoc
     */
    public function getTriggerLabel(): string
    {
        return Craft::t('app', 'Set spam');
    }

    /**
     * @inheritdoc
     */
    public function getTriggerHtml()
    {
        return Craft::$app->getView()->renderTemplate('formie/_components/actions/mark-spam/trigger');
    }

    /**
     * @inheritdoc
     */
    public function performAction(ElementQueryInterface $query): bool
    {
        $elementsService = Craft::$app->getElements();

        /** @var Submission[] $elements */
        $elements = $query->all();
        $failCount = 0;

        foreach ($elements as $element) {
            // Without this, when updating submissions for "All forms", this will reset the content
            // of a submission. This is because the query to fetch element's can't resolve the correct
            // content table across multiple queries. This does add an extra query, but its pretty unavoidable
            Craft::$app->getContent()->populateElementContent($element);

            $element->isSpam = ($this->spam === 'markSpam') ? true : false;

            if ($elementsService->saveElement($element) === false) {
                // Validation error
                $failCount++;
            }
        }

        // Did all of them fail?
        if ($failCount === count($elements)) {
            if (count($elements) === 1) {
                $this->setMessage(Craft::t('app', 'Could not update spam state due to a validation error.'));
            } else {
                $this->setMessage(Craft::t('app', 'Could not update spam state due to validation errors.'));
            }

            return false;
        }

        if ($failCount !== 0) {
            $this->setMessage(Craft::t('app', 'Spam state updated, with some failures due to validation errors.'));
        } else {
            if (count($elements) === 1) {
                $this->setMessage(Craft::t('app', 'Spam state updated.'));
            } else {
                $this->setMessage(Craft::t('app', 'Spam state updated.'));
            }
        }

        return true;
    }
}

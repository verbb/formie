<?php
namespace verbb\formie\elements\actions;

use Craft;
use craft\base\ElementAction;
use craft\elements\db\ElementQueryInterface;
use craft\helpers\ArrayHelper;

use verbb\formie\elements\Submission;
use verbb\formie\Formie;

class SetSubmissionStatus extends ElementAction
{
    public ?int $statusId = null;
    public ?array $statuses = null;

    /**
     * @inheritdoc
     */
    public function getTriggerLabel(): string
    {
        return Craft::t('app', 'Set Status');
    }

    /**
     * @inheritdoc
     */
    protected function defineRules(): array
    {
        $rules = parent::defineRules();

        $statusIds = ArrayHelper::getColumn($this->statuses, 'id');

        $rules[] = [['statusId'], 'required'];
        $rules[] = [['statusId'], 'in', 'range' => $statusIds];

        return $rules;
    }

    /**
     * @inheritdoc
     */
    public function getTriggerHtml(): ?string
    {
        return Craft::$app->getView()->renderTemplate('formie/_components/actions/set-status/trigger', [
            'statuses' => $this->statuses,
        ]);
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

        $status = Formie::$plugin->getStatuses()->getStatusById($this->statusId);

        foreach ($elements as $element) {
            // Without this, when updating submissions for "All forms", this will reset the content
            // of a submission. This is because the query to fetch element's can't resolve the correct
            // content table across multiple queries. This does add an extra query, but it's pretty unavoidable
            Craft::$app->getContent()->populateElementContent($element);

            $element->setStatus($status);

            if ($elementsService->saveElement($element) === false) {
                // Validation error
                $failCount++;
            }
        }

        // Did all of them fail?
        if ($failCount === count($elements)) {
            if (count($elements) === 1) {
                $this->setMessage(Craft::t('app', 'Could not update status due to a validation error.'));
            } else {
                $this->setMessage(Craft::t('app', 'Could not update statuses due to validation errors.'));
            }

            return false;
        }

        if ($failCount !== 0) {
            $this->setMessage(Craft::t('app', 'Status updated, with some failures due to validation errors.'));
        } else if (count($elements) === 1) {
            $this->setMessage(Craft::t('app', 'Status updated.'));
        } else {
            $this->setMessage(Craft::t('app', 'Statuses updated.'));
        }

        return true;
    }
}

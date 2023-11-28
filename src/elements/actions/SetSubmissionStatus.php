<?php
namespace verbb\formie\elements\actions;

use verbb\formie\Formie;
use verbb\formie\elements\Submission;
use verbb\formie\helpers\ArrayHelper;

use Craft;
use craft\base\ElementAction;
use craft\elements\actions\SetStatus;
use craft\elements\db\ElementQueryInterface;
use craft\helpers\Json;

class SetSubmissionStatus extends SetStatus
{
    // Properties
    // =========================================================================

    public ?int $statusId = null;
    public ?array $statuses = null;


    // Public Methods
    // =========================================================================

    public function getTriggerLabel(): string
    {
        return Craft::t('app', 'Set Status');
    }

    public function getTriggerHtml(): ?string
    {
        return Craft::$app->getView()->renderTemplate('formie/_components/actions/set-status/trigger', [
            'statuses' => $this->statuses,
        ]);
    }

    public function performAction(ElementQueryInterface $query): bool
    {
        $elementsService = Craft::$app->getElements();

        /** @var Submission[] $elements */
        $elements = $query->all();
        $failCount = 0;

        $status = Formie::$plugin->getStatuses()->getStatusById($this->statusId);

        foreach ($elements as $element) {
            // Unfortunately, we need to fetch the submission _again_ to ensure custom fields are grabbed. This is because we can't query
            // across multiple content tables from the "All Forms" option.
            $element = Submission::find()->uid($element->uid)->isSpam(null)->isIncomplete(null)->one();

            if ($element) {
                $element->setStatus($status);

                if ($elementsService->saveElement($element) === false) {
                    Formie::error('Unable to set status: {error}', ['error' => Json::encode($element->getErrors())]);

                    // Validation error
                    $failCount++;
                }
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


    // Protected Methods
    // =========================================================================

    protected function defineRules(): array
    {
        // Don't include the parent rules from `SetStatus`
        $rules = [];

        $statusIds = ArrayHelper::getColumn($this->statuses, 'id');

        $rules[] = [['statusId'], 'required'];
        $rules[] = [['statusId'], 'in', 'range' => $statusIds];

        return $rules;
    }
}

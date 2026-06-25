<?php
namespace verbb\formie\elements\actions;

use verbb\formie\Formie;
use verbb\formie\elements\Submission;
use verbb\formie\helpers\ArrayHelper;

use Craft;
use craft\elements\actions\SetStatus;
use craft\elements\db\ElementQueryInterface;
use craft\helpers\Html;
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
        Craft::$app->getView()->registerJsWithVars(fn($type) => <<<JS
(() => {
    const trigger = new Craft.ElementActionTrigger({
        type: $type,
    });
    const \$statusOptions = trigger.\$trigger.find('[data-formie-submission-status-id]');

    \$statusOptions.on('click', (ev) => {
        ev.preventDefault();
    });

    \$statusOptions.on('activate', async (ev) => {
        ev.preventDefault();
        ev.stopImmediatePropagation();

        if (!trigger.triggerEnabled) {
            return;
        }

        try {
            await trigger.elementIndex.submitAction($type, {
                statusId: ev.currentTarget.dataset.formieSubmissionStatusId,
            });
        } catch (error) {
            // Craft can cancel in-flight element index requests while refreshing the index.
            if (error && !axios.isCancel(error)) {
                throw error;
            }
        }
    });
})();
JS, [static::class]);

        $label = Craft::t('app', 'Set status');
        $items = [];

        foreach ($this->statuses ?? [] as $status) {
            $items[] = Html::tag('li', Html::a(
                Html::tag('span', '', ['class' => ['status', $status->color]])
                . ' ' . Html::encode($status->name),
                '#',
                [
                    'data-formie-submission-status-id' => $status->id,
                ],
            ));
        }

        return Html::tag('button', $label, [
                'type' => 'button',
                'class' => ['btn', 'menubtn'],
                'aria-label' => $label,
            ])
            . Html::tag('div', Html::tag('ul', implode("\n", $items)), ['class' => 'menu']);
    }

    public function performAction(ElementQueryInterface $query): bool
    {
        $elementsService = Craft::$app->getElements();

        /** @var Submission[] $elements */
        $elements = $query->all();
        $failCount = 0;

        $status = Formie::$plugin->getSubmissionStatuses()->getStatusById($this->statusId);

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
                } else {
                    Formie::$plugin->getIntegrationTriggers()->dispatchCpElementSave($element);
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

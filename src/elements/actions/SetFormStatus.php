<?php
namespace verbb\formie\elements\actions;

use verbb\formie\elements\Form;
use verbb\formie\Formie;
use verbb\formie\helpers\StringHelper;
use verbb\formie\models\FormStatus;

use Craft;
use craft\base\ElementAction;
use craft\elements\db\ElementQueryInterface;
use craft\helpers\Html;
use craft\helpers\Json;

class SetFormStatus extends ElementAction
{
    // Properties
    // =========================================================================

    public mixed $formStatusId = null;


    // Public Methods
    // =========================================================================

    public function getTriggerLabel(): string
    {
        return Craft::t('formie', 'Set form status');
    }

    public function getTriggerHtml(): ?string
    {
        Craft::$app->getView()->registerJsWithVars(fn($type) => <<<JS
(() => {
    const trigger = new Craft.ElementActionTrigger({
        type: $type,
    });
    const \$statusOptions = trigger.\$trigger.find('[data-formie-form-status-id]');

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
                formStatusId: ev.currentTarget.dataset.formieFormStatusId,
            });
        } catch (error) {
            if (error && !axios.isCancel(error)) {
                throw error;
            }
        }
    });
})();
JS, [static::class]);

        $label = Craft::t('formie', 'Set form status');
        $items = [];

        foreach (Formie::$plugin->getFormStatuses()->getAllStatuses() as $status) {
            $items[] = $this->_menuItem($status->name, (string)$status->id);
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
        $targetStatusId = StringHelper::toId($this->formStatusId);

        if ($targetStatusId === null || !Formie::$plugin->getFormStatuses()->getStatusById($targetStatusId)) {
            $this->setMessage(Craft::t('formie', 'Could not set the selected form status.'));

            return false;
        }

        $elementsService = Craft::$app->getElements();
        $permissions = Formie::$plugin->getPermissions();
        $currentUser = Craft::$app->getUser()->getIdentity();

        /** @var Form[] $elements */
        $elements = $query->all();
        $successCount = 0;
        $failCount = 0;

        foreach ($elements as $element) {
            if (!$permissions->canManageForm($currentUser, $element)) {
                $failCount++;
                continue;
            }

            $element->formStatusId = $targetStatusId;

            if ($elementsService->saveElement($element) === false) {
                Formie::error('Unable to set form status: {error}', ['error' => Json::encode($element->getErrors())]);
                $failCount++;
                continue;
            }

            $successCount++;
        }

        if ($successCount === 0) {
            $this->setMessage(Craft::t('formie', 'Could not set the form status for the selected forms.'));

            return false;
        }

        if ($failCount !== 0) {
            $this->setMessage(Craft::t('formie', 'Some forms could not be updated.'));
        } elseif (count($elements) === 1) {
            $this->setMessage(Craft::t('formie', 'Form status updated.'));
        } else {
            $this->setMessage(Craft::t('formie', 'Form statuses updated.'));
        }

        return true;
    }


    // Protected Methods
    // =========================================================================

    protected function defineRules(): array
    {
        $rules = parent::defineRules();

        $statusIds = array_map(static fn(FormStatus $status) => (string)$status->id, Formie::$plugin->getFormStatuses()->getAllStatuses());
        $rules[] = [['formStatusId'], 'in', 'range' => $statusIds];

        return $rules;
    }


    // Private Methods
    // =========================================================================

    private function _menuItem(string $label, string $formStatusId): string
    {
        return Html::tag('li', Html::a(
            Html::encode($label),
            '#',
            [
                'data-formie-form-status-id' => $formStatusId,
            ],
        ));
    }
}

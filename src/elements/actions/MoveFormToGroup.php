<?php
namespace verbb\formie\elements\actions;

use verbb\formie\elements\Form;
use verbb\formie\Formie;
use verbb\formie\helpers\StringHelper;
use verbb\formie\models\FormGroup;

use Craft;
use craft\base\ElementAction;
use craft\elements\db\ElementQueryInterface;
use craft\helpers\Html;
use craft\helpers\Json;

class MoveFormToGroup extends ElementAction
{
    // Properties
    // =========================================================================

    public mixed $groupId = null;


    // Public Methods
    // =========================================================================

    public function getTriggerLabel(): string
    {
        return Craft::t('formie', 'Move form');
    }

    public function getTriggerHtml(): ?string
    {
        Craft::$app->getView()->registerJsWithVars(fn($type) => <<<JS
(() => {
    const trigger = new Craft.ElementActionTrigger({
        type: $type,
    });
    const \$groupOptions = trigger.\$trigger.find('[data-formie-form-group-id]');

    \$groupOptions.on('click', (ev) => {
        ev.preventDefault();
    });

    \$groupOptions.on('activate', async (ev) => {
        ev.preventDefault();
        ev.stopImmediatePropagation();

        if (!trigger.triggerEnabled) {
            return;
        }

        try {
            await trigger.elementIndex.submitAction($type, {
                groupId: ev.currentTarget.dataset.formieFormGroupId,
            });
        } catch (error) {
            if (error && !axios.isCancel(error)) {
                throw error;
            }
        }
    });
})();
JS, [static::class]);

        $label = Craft::t('formie', 'Move form');
        $items = [];

        foreach (Formie::$plugin->getFormGroups()->getAllGroups() as $group) {
            $items[] = $this->_menuItem($group->name, (string)$group->id);
        }

        $items[] = $this->_menuItem(Craft::t('formie', 'Ungrouped'), '');

        return Html::tag('button', $label, [
                'type' => 'button',
                'class' => ['btn', 'menubtn'],
                'aria-label' => $label,
            ])
            . Html::tag('div', Html::tag('ul', implode("\n", $items)), ['class' => 'menu']);
    }

    public function performAction(ElementQueryInterface $query): bool
    {
        $targetGroupId = $this->_normalizeGroupId($this->groupId);

        if ($targetGroupId !== null && !Formie::$plugin->getFormGroups()->getGroupById($targetGroupId)) {
            $this->setMessage(Craft::t('formie', 'Could not move forms to the selected group.'));

            return false;
        }

        $elementsService = Craft::$app->getElements();
        $currentUser = Craft::$app->getUser()->getIdentity();
        $canManageAll = $currentUser->can('formie-manageForms');

        /** @var Form[] $elements */
        $elements = $query->all();
        $successCount = 0;
        $failCount = 0;

        foreach ($elements as $element) {
            if (!$canManageAll && !$currentUser->can('formie-manageForms:' . $element->uid)) {
                $failCount++;
                continue;
            }

            $element->groupId = $targetGroupId;

            if ($elementsService->saveElement($element) === false) {
                Formie::error('Unable to move form to group: {error}', ['error' => Json::encode($element->getErrors())]);
                $failCount++;
                continue;
            }

            $successCount++;
        }

        if ($successCount === 0) {
            $this->setMessage(Craft::t('formie', 'Could not move the selected forms.'));

            return false;
        }

        if ($failCount !== 0) {
            $this->setMessage(Craft::t('formie', 'Some forms could not be moved.'));
        } elseif (count($elements) === 1) {
            $this->setMessage(Craft::t('formie', 'Form moved.'));
        } else {
            $this->setMessage(Craft::t('formie', 'Forms moved.'));
        }

        return true;
    }


    // Protected Methods
    // =========================================================================

    protected function defineRules(): array
    {
        $rules = parent::defineRules();

        $groupIds = array_map(static fn(FormGroup $group) => (string)$group->id, Formie::$plugin->getFormGroups()->getAllGroups());
        $rules[] = [['groupId'], 'in', 'range' => array_merge([''], $groupIds)];

        return $rules;
    }


    // Private Methods
    // =========================================================================

    private function _menuItem(string $label, string $groupId): string
    {
        return Html::tag('li', Html::a(
            Html::encode($label),
            '#',
            [
                'data-formie-form-group-id' => $groupId,
            ],
        ));
    }

    private function _normalizeGroupId(mixed $groupId): ?int
    {
        if ($groupId === null || $groupId === '') {
            return null;
        }

        return StringHelper::toId($groupId);
    }
}

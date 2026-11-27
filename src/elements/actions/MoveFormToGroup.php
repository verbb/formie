<?php
namespace verbb\formie\elements\actions;

use verbb\formie\Formie;
use verbb\formie\elements\Form;
use verbb\formie\helpers\StringHelper;
use verbb\formie\models\FormGroup;

use Craft;
use craft\base\ElementAction;
use craft\elements\db\ElementQueryInterface;
use craft\errors\UnsupportedSiteException;
use craft\helpers\Cp;
use craft\helpers\Html;
use craft\helpers\Json;

class MoveFormToGroup extends ElementAction
{
    // Static Methods
    // =========================================================================

    /**
     * Returns the groups the current user can move forms into, keyed by group ID, with an empty key for Ungrouped.
     */
    public static function getTargetOptions(): array
    {
        $permissions = Formie::$plugin->getPermissions();
        $propagation = Formie::$plugin->getFormSitePropagation();
        $currentUser = Craft::$app->getUser()->getIdentity();
        $activeSiteId = null;
        $options = [];

        if ($propagation->isEnabled()) {
            $requestedSite = Cp::requestedSite();
            $activeSiteId = (int)($requestedSite?->id ?? Craft::$app->getSites()->getCurrentSite()->id);
        }

        foreach (Formie::$plugin->getFormGroups()->getAllGroups() as $group) {
            if ($activeSiteId && !$propagation->isGroupAvailableForSite($group, $activeSiteId)) {
                continue;
            }

            if (!$permissions->canManageFormInGroup($currentUser, $group)) {
                continue;
            }

            $options[$group->id] = $group->name;
        }

        if ($permissions->canManageFormInGroup($currentUser, null)) {
            $options[''] = Craft::t('formie', 'Ungrouped');
        }

        return $options;
    }


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

        foreach (static::getTargetOptions() as $groupId => $groupName) {
            $items[] = $this->_menuItem($groupName, (string)$groupId);
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
        $targetGroupId = $this->_normalizeGroupId($this->groupId);
        $targetGroup = $targetGroupId !== null ? Formie::$plugin->getFormGroups()->getGroupById($targetGroupId) : null;

        if ($targetGroupId !== null && !$targetGroup) {
            $this->setMessage(Craft::t('formie', 'Could not move forms to the selected group.'));

            return false;
        }

        $elementsService = Craft::$app->getElements();
        $permissions = Formie::$plugin->getPermissions();
        $currentUser = Craft::$app->getUser()->getIdentity();

        /** @var Form[] $elements */
        $elements = $query->all();
        $successCount = 0;
        $failCount = 0;
        $failureMessage = null;

        foreach ($elements as $element) {
            if (!$permissions->canManageForm($currentUser, $element)) {
                $failCount++;

                continue;
            }

            // A form's own manage grant doesn't extend to other groups, as in the form builder.
            $element->setGroup($targetGroup);

            if (!$permissions->canManageFormInGroup($currentUser, $targetGroup)) {
                $failureMessage = Craft::t('formie', 'You don’t have permission to move forms to the selected group.');
                $failCount++;

                continue;
            }

            if ($sitePolicyError = $this->_validateSitePolicy($element)) {
                $failureMessage = $sitePolicyError;
                $failCount++;

                continue;
            }

            try {
                $saved = $elementsService->saveElement($element);
            } catch (UnsupportedSiteException $e) {
                // The group's policy can still leave out the site the form is being saved in.
                Formie::error('Unable to move form {id} to group: {error}', ['id' => $element->id, 'error' => $e->getMessage()]);
                $failureMessage = Craft::t('formie', 'This form can’t be enabled on the selected site under the group’s propagation policy.');
                $failCount++;

                continue;
            }

            if ($saved === false) {
                Formie::error('Unable to move form to group: {error}', ['error' => Json::encode($element->getErrors())]);
                $failCount++;

                continue;
            }

            $successCount++;
        }

        if ($successCount === 0) {
            $this->setMessage($failureMessage ?? Craft::t('formie', 'Could not move the selected forms.'));

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

    /**
     * Applies the form builder's group site checks to a form that has been given its new group.
     */
    private function _validateSitePolicy(Form $form): ?string
    {
        $propagation = Formie::$plugin->getFormSitePropagation();

        if (!$propagation->isEnabled()) {
            return null;
        }

        if (!$propagation->isGroupAvailableForSite($form->getGroup(), (int)$form->siteId)) {
            return Craft::t('formie', 'This form group is not available for the selected site.');
        }

        // The check fills in a missing source site, which a move shouldn't store.
        $sourceSiteId = $form->sourceSiteId;
        $error = $propagation->validateFormSiteAvailability($form);
        $form->sourceSiteId = $sourceSiteId;

        return $error;
    }

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

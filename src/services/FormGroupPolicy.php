<?php
namespace verbb\formie\services;

use verbb\formie\elements\Form;
use verbb\formie\Formie;
use verbb\formie\models\FormGroup;
use verbb\formie\models\FormGroupSettings;
use verbb\formie\models\Status;

use Craft;

use yii\base\Component;

class FormGroupPolicy extends Component
{
    // Public Methods
    // =========================================================================

    public function getSettings(?FormGroup $group): FormGroupSettings
    {
        if (!$group) {
            return new FormGroupSettings();
        }

        return FormGroupSettings::fromArray($group->settings ?? []);
    }

    public function getResolvedAllowedStatusIds(?Form $form): ?array
    {
        $formSettings = $form?->getSettings();

        if ($formSettings && $formSettings->allowedStatusIds !== null) {
            return $formSettings->allowedStatusIds;
        }

        $group = $form?->getGroup();

        if (!$group) {
            return null;
        }

        return $this->getSettings($group)->allowedStatusIds;
    }

    public function getStatusesForForm(?Form $form): array
    {
        $allStatuses = Formie::$plugin->getStatuses()->getAllStatuses();
        $allowedIds = $this->getResolvedAllowedStatusIds($form);

        if ($allowedIds === null) {
            return $allStatuses;
        }

        $allowedSet = array_flip($allowedIds);

        return array_values(array_filter(
            $allStatuses,
            fn(Status $status) => isset($allowedSet[(int)$status->id]),
        ));
    }

    public function isStatusAllowed(?Form $form, int $statusId): bool
    {
        $allowedIds = $this->getResolvedAllowedStatusIds($form);

        if ($allowedIds === null) {
            return true;
        }

        return in_array($statusId, $allowedIds, true);
    }

    public function getStatusSelectOptions(?Form $form): array
    {
        return array_map(function(Status $status) {
            return [
                'value' => (int)$status->id,
                'label' => $status->name,
                'status' => $status->color,
            ];
        }, $this->getStatusesForForm($form));
    }

    public function getMergedFormDefaults(?FormGroup $group): array
    {
        return Formie::$plugin->getFormGroupDefaults()->getMergedFormDefaults($group);
    }

    public function getFieldPaletteConfig(?FormGroup $group): ?array
    {
        $palette = $this->getSettings($group)->fieldPalette;

        return is_array($palette) ? $palette : null;
    }

    public function describeAllowedStatusSource(?Form $form): ?string
    {
        $formSettings = $form?->getSettings();

        if ($formSettings && $formSettings->allowedStatusIds !== null) {
            return Craft::t('formie', 'This form');
        }

        $group = $form?->getGroup();

        if ($group && $this->getSettings($group)->allowedStatusIds !== null) {
            return Craft::t('formie', '{name} group settings', ['name' => $group->name]);
        }

        return null;
    }


    // Private Methods
    // =========================================================================

    private function _shouldInheritDefaultValue(mixed $value): bool
    {
        return $value === null || $value === '';
    }
}

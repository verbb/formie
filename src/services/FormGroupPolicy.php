<?php
namespace verbb\formie\services;

use verbb\formie\deprecations\FormGroupPolicyDeprecations;
use verbb\formie\elements\Form;
use verbb\formie\Formie;
use verbb\formie\models\FormGroup;
use verbb\formie\models\FormGroupSettings;
use verbb\formie\models\SubmissionStatus;

use Craft;

use yii\base\Component;

class FormGroupPolicy extends Component
{
    // Traits
    // =========================================================================

    use FormGroupPolicyDeprecations;


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
        $group = $form?->getGroup();

        if (!$group) {
            return null;
        }

        return $this->getSettings($group)->allowedStatusIds;
    }

    public function getSubmissionStatusesForForm(?Form $form): array
    {
        $allStatuses = Formie::$plugin->getSubmissionStatuses()->getAllStatuses();
        $allowedIds = $this->getResolvedAllowedStatusIds($form);

        if ($allowedIds === null) {
            return $allStatuses;
        }

        $allowedSet = array_flip($allowedIds);

        return array_values(array_filter(
            $allStatuses,
            fn(SubmissionStatus $status) => isset($allowedSet[(int)$status->id]),
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

    public function getSubmissionStatusSelectOptions(?Form $form): array
    {
        return array_map(function(SubmissionStatus $status) {
            return [
                'value' => (int)$status->id,
                'label' => $status->name,
                'status' => $status->color,
            ];
        }, $this->getSubmissionStatusesForForm($form));
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

    public function describeAllowedSubmissionStatusSource(?Form $form): ?string
    {
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

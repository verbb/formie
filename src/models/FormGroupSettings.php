<?php
namespace verbb\formie\models;

use verbb\formie\Formie;

use Craft;
use craft\base\Model;

class FormGroupSettings extends Model
{
    // Static Methods
    // =========================================================================

    public static function fromArray(mixed $config): self
    {
        if (!is_array($config)) {
            return new self();
        }

        $settings = new self();

        if (array_key_exists('allowedStatusIds', $config)) {
            $settings->allowedStatusIds = self::_normalizeStatusIds($config['allowedStatusIds']);
        }

        if (array_key_exists('fieldPalette', $config)) {
            $palette = $config['fieldPalette'];
            $settings->fieldPalette = is_array($palette) ? $palette : null;
        }

        if (isset($config['defaults']) && is_array($config['defaults'])) {
            $settings->defaults = $config['defaults'];
        }

        if (array_key_exists('sitePolicy', $config)) {
            $settings->sitePolicy = is_array($config['sitePolicy']) ? $config['sitePolicy'] : [];
        }

        return $settings;
    }


    // Properties
    // =========================================================================

    public ?array $allowedStatusIds = null;
    public ?array $fieldPalette = null;
    public array $defaults = [];
    public array $sitePolicy = [];


    // Public Methods
    // =========================================================================

    public function toStorageArray(): array
    {
        $data = [];

        if ($this->allowedStatusIds !== null) {
            $data['allowedStatusIds'] = $this->allowedStatusIds;
        }

        if ($this->fieldPalette !== null) {
            $data['fieldPalette'] = $this->fieldPalette;
        }

        if ($this->defaults !== []) {
            $data['defaults'] = $this->defaults;
        }

        $sitePolicy = FormSitePolicy::fromArray($this->sitePolicy)->toStorageArray();

        if ($sitePolicy !== []) {
            $data['sitePolicy'] = $sitePolicy;
        }

        return $data;
    }

    public function usesCustomFieldPalette(): bool
    {
        return $this->fieldPalette !== null;
    }

    public function getNormalizedFormDefaults(): array
    {
        $defaults = $this->defaults['formDefaults'] ?? [];

        return is_array($defaults) ? $defaults : [];
    }

    public function getDefaultFormStencil(): string
    {
        return trim((string)($this->defaults['defaultFormStencil'] ?? ''));
    }

    public function getDefaultFormTemplate(): string
    {
        return trim((string)($this->defaults['defaultFormTemplate'] ?? ''));
    }

    public function getDefaultEmailTemplate(): string
    {
        return trim((string)($this->defaults['defaultEmailTemplate'] ?? ''));
    }

    public function getSitePolicyModel(): FormSitePolicy
    {
        return FormSitePolicy::fromArray($this->sitePolicy);
    }


    // Protected Methods
    // =========================================================================

    protected function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['allowedStatusIds'], 'validateAllowedStatusIds'];
        $rules[] = [['sitePolicy'], 'validateSitePolicy'];

        return $rules;
    }

    public function validateSitePolicy(): void
    {
        $policy = FormSitePolicy::fromArray($this->sitePolicy);

        if (!$policy->validate()) {
            foreach ($policy->getErrors() as $attribute => $errors) {
                foreach ($errors as $error) {
                    $this->addError('sitePolicy', $error);
                }
            }
        }
    }

    public function validateAllowedStatusIds(): void
    {
        if ($this->allowedStatusIds === null) {
            return;
        }

        if ($this->allowedStatusIds === []) {
            $this->addError('allowedStatusIds', Craft::t('formie', 'Select at least one submission status, or choose “All” to allow every status.'));

            return;
        }

        $validIds = [];

        foreach (Formie::$plugin->getSubmissionStatuses()->getAllStatuses() as $status) {
            $validIds[] = (int)$status->id;
        }

        foreach ($this->allowedStatusIds as $statusId) {
            if (!in_array((int)$statusId, $validIds, true)) {
                $this->addError('allowedStatusIds', Craft::t('formie', 'One or more selected submission statuses are invalid.'));

                return;
            }
        }
    }


    // Private Methods
    // =========================================================================

    private static function _normalizeStatusIds(mixed $value): ?array
    {
        if ($value === null || $value === '' || $value === '*' || $value === []) {
            return null;
        }

        if (!is_array($value)) {
            $value = [$value];
        }

        if (in_array('*', $value, true)) {
            return null;
        }

        $ids = array_values(array_unique(array_filter(array_map('intval', $value))));

        return $ids === [] ? null : $ids;
    }
}

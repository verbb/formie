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

    /**
     * Converts settings stored in project config to this environment's status and site IDs.
     */
    public static function fromConfig(array $config): array
    {
        // Older config stored `allowedStatusIds`, which are read as they are.
        if (array_key_exists('allowedStatuses', $config)) {
            $statusIds = [];

            foreach ((array)$config['allowedStatuses'] as $statusUid) {
                if (is_string($statusUid) && $status = Formie::$plugin->getSubmissionStatuses()->getStatusByUid($statusUid)) {
                    $statusIds[] = (int)$status->id;
                }
            }

            // A restriction whose statuses no longer exist still matches nothing, as a stale ID did.
            if (!$statusIds && (array)$config['allowedStatuses']) {
                $statusIds = [self::MISSING_ID];
            }

            unset($config['allowedStatuses']);
            $config['allowedStatusIds'] = $statusIds;
        }

        if (is_array($config['sitePolicy'] ?? null)) {
            $config['sitePolicy'] = FormSitePolicy::fromConfig($config['sitePolicy']);
        }

        return $config;
    }

    /**
     * Converts settings to the form stored in project config, where statuses and sites are referenced by UID.
     */
    public static function toConfig(array $settings): array
    {
        if (array_key_exists('allowedStatusIds', $settings)) {
            $statusIds = self::_normalizeStatusIds($settings['allowedStatusIds']);
            unset($settings['allowedStatusIds']);

            if ($statusIds !== null) {
                $settings['allowedStatuses'] = [];

                foreach ($statusIds as $statusId) {
                    if ($status = Formie::$plugin->getSubmissionStatuses()->getStatusById($statusId)) {
                        $settings['allowedStatuses'][] = $status->uid;
                    }
                }

                // Craft drops an empty list, which would read back as every status.
                $settings['allowedStatuses'] = $settings['allowedStatuses'] ?: [self::MISSING_UID];
            }
        }

        if (is_array($settings['sitePolicy'] ?? null)) {
            $settings['sitePolicy'] = FormSitePolicy::toConfig($settings['sitePolicy']);
        }

        return $settings;
    }

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


    // Constants
    // =========================================================================

    // Stand-ins for a status or site restriction whose statuses or sites have all been deleted.
    public const MISSING_ID = -1;
    public const MISSING_UID = 'missing';


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


    // Protected Methods
    // =========================================================================

    protected function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['allowedStatusIds'], 'validateAllowedStatusIds'];
        $rules[] = [['sitePolicy'], 'validateSitePolicy'];

        return $rules;
    }
}

<?php
namespace verbb\formie\models;

use Craft;
use craft\base\Model;

class FormSitePolicy extends Model
{
    // Static Methods
    // =========================================================================

    public static function fromArray(mixed $config): self
    {
        $policy = new self();

        if (!is_array($config)) {
            return $policy;
        }

        if (array_key_exists('enabledSiteIds', $config)) {
            $policy->enabledSiteIds = self::_normalizeSiteIds($config['enabledSiteIds']);
        }

        if (!empty($config['propagation']) && is_string($config['propagation'])) {
            $policy->propagation = $config['propagation'];
        }

        return $policy;
    }

    public static function propagationOptions(): array
    {
        return [
            self::PROPAGATION_ALL_ENABLED => Craft::t('formie', 'All enabled sites'),
            self::PROPAGATION_SAME_LANGUAGE => Craft::t('formie', 'Same language as primary site'),
            self::PROPAGATION_SAME_SITE_GROUP => Craft::t('formie', 'Same site group as primary site'),
            self::PROPAGATION_CREATED_SITE_ONLY => Craft::t('formie', 'Created site only'),
        ];
    }



    // Constants
    // =========================================================================

    public const PROPAGATION_ALL_ENABLED = 'allEnabled';
    public const PROPAGATION_SAME_LANGUAGE = 'sameLanguage';
    public const PROPAGATION_SAME_SITE_GROUP = 'sameSiteGroup';
    public const PROPAGATION_CREATED_SITE_ONLY = 'createdSiteOnly';


    // Properties
    // =========================================================================

    public ?array $enabledSiteIds = null;
    public string $propagation = self::PROPAGATION_ALL_ENABLED;


    // Public Methods
    // =========================================================================

    public function toStorageArray(): array
    {
        $data = [
            'propagation' => $this->propagation,
        ];

        if ($this->enabledSiteIds !== null) {
            $data['enabledSiteIds'] = $this->enabledSiteIds;
        }

        return $data;
    }


    // Protected Methods
    // =========================================================================

    protected function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['propagation'], 'in', 'range' => array_keys(self::propagationOptions())];
        $rules[] = [['enabledSiteIds'], 'validateEnabledSiteIds'];

        return $rules;
    }

    public function validateEnabledSiteIds(): void
    {
        if ($this->enabledSiteIds === null) {
            return;
        }

        if ($this->enabledSiteIds === []) {
            $this->addError('enabledSiteIds', Craft::t('formie', 'Select at least one site, or leave empty to allow all editable sites.'));

            return;
        }

        $validIds = Craft::$app->getSites()->getAllSiteIds();

        foreach ($this->enabledSiteIds as $siteId) {
            if (!in_array((int)$siteId, $validIds, true)) {
                $this->addError('enabledSiteIds', Craft::t('formie', 'One or more selected sites are invalid.'));

                return;
            }
        }
    }


    // Private Methods
    // =========================================================================

    private static function _normalizeSiteIds(mixed $value): ?array
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

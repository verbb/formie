<?php
namespace verbb\formie\options;

use Craft;
use verbb\formie\base\PredefinedOption;

class Industry extends PredefinedOption
{
    // Static Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public static function displayName(): string
    {
        return Craft::t('formie', 'Industry');
    }

    public static function getDataOptions(): array
    {
        return [
            Craft::t('formie', 'Accounting/Finance'),
            Craft::t('formie', 'Advertising/Public Relations'),
            Craft::t('formie', 'Aerospace/Aviation'),
            Craft::t('formie', 'Arts/Entertainment/Publishing'),
            Craft::t('formie', 'Automotive'),
            Craft::t('formie', 'Banking/Mortgage'),
            Craft::t('formie', 'Business Development'),
            Craft::t('formie', 'Business Opportunity'),
            Craft::t('formie', 'Clerical/Administrative'),
            Craft::t('formie', 'Construction/Facilities'),
            Craft::t('formie', 'Consumer Goods'),
            Craft::t('formie', 'Customer Service'),
            Craft::t('formie', 'Education/Training'),
            Craft::t('formie', 'Energy/Utilities'),
            Craft::t('formie', 'Engineering'),
            Craft::t('formie', 'Government/Military'),
            Craft::t('formie', 'Green'),
            Craft::t('formie', 'Healthcare'),
            Craft::t('formie', 'Hospitality/Travel'),
            Craft::t('formie', 'Human Resources'),
            Craft::t('formie', 'Installation/Maintenance'),
            Craft::t('formie', 'Insurance'),
            Craft::t('formie', 'Internet'),
            Craft::t('formie', 'Job Search Aids'),
            Craft::t('formie', 'Law Enforcement/Security'),
            Craft::t('formie', 'Legal'),
            Craft::t('formie', 'Management/Executive'),
            Craft::t('formie', 'Manufacturing/Operations'),
            Craft::t('formie', 'Marketing'),
            Craft::t('formie', 'Non-Profit/Volunteer'),
            Craft::t('formie', 'Pharmaceutical/Biotech'),
            Craft::t('formie', 'Professional Services'),
            Craft::t('formie', 'QA/Quality Control'),
            Craft::t('formie', 'Real Estate'),
            Craft::t('formie', 'Restaurant/Food Service'),
            Craft::t('formie', 'Retail'),
            Craft::t('formie', 'Sales'),
            Craft::t('formie', 'Science/Research'),
            Craft::t('formie', 'Skilled Labor'),
            Craft::t('formie', 'Technology'),
            Craft::t('formie', 'Telecommunications'),
            Craft::t('formie', 'Transportation/Logistics'),
            Craft::t('formie', 'Other'),
        ];
    }
}

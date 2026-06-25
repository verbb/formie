<?php
namespace verbb\formie\deprecations;

use verbb\formie\services\OptionSources;
use verbb\formie\services\SubmissionStatuses;

use Craft;

trait PluginDeprecations
{
    // Public Methods
    // =========================================================================

    public function getPredefinedOptions(): OptionSources
    {
        // Deprecated in 4.0.0
        Craft::$app->getDeprecator()->log(__METHOD__, 'Formie `getPredefinedOptions()` has been deprecated. Use `getOptionSources()` instead.');

        return $this->getOptionSources();
    }

    public function getStatuses(): SubmissionStatuses
    {
        // Deprecated in 4.0.0
        Craft::$app->getDeprecator()->log(__METHOD__, 'Formie `getStatuses()` has been deprecated. Use `getSubmissionStatuses()` instead.');

        return $this->get('statuses');
    }
}

<?php
namespace verbb\formie\base;

use verbb\formie\Formie;
use verbb\formie\helpers\SchemaHelper;

use Craft;

trait FormDefaultableTrait
{
    // Static Methods
    // =========================================================================

    public static function supportedFormDefaults(): array
    {
        return [
            'defaultStatus',
            'submissionTitleFormat',
            'collectIp',
            'collectUser',
            'cpSubmissionFieldConditions',
            'submitMethod',
            'dataRetention',
            'dataRetentionValue',
            'fileUploadsAction',
            'displayFormTitle',
            'displayCurrentPageTitle',
            'displayPageTabs',
            'displayPageProgress',
            'progressCalculation',
            'progressPosition',
            'scrollToTop',
            'requiredIndicator',
        ];
    }


    // Public Methods
    // =========================================================================

    public function getDefaultableSettingsSchema(): array
    {
        $fields = [
            'defaultStatus' => 'defaultStatusId',
            'submissionTitleFormat' => 'settings.submissionTitleFormat',
            'collectIp' => 'settings.collectIp',
            'collectUser' => 'settings.collectUser',
            'cpSubmissionFieldConditions' => 'settings.cpSubmissionFieldConditions',
            'submitMethod' => 'settings.submitMethod',
            'dataRetention' => 'dataRetention',
            'dataRetentionValue' => 'dataRetentionValue',
            'fileUploadsAction' => 'fileUploadsAction',
            'displayFormTitle' => 'settings.displayFormTitle',
            'displayCurrentPageTitle' => 'settings.displayCurrentPageTitle',
            'displayPageTabs' => 'settings.displayPageTabs',
            'displayPageProgress' => 'settings.displayPageProgress',
            'progressCalculation' => 'settings.progressCalculation',
            'progressPosition' => 'settings.progressPosition',
            'scrollToTop' => 'settings.scrollToTop',
            'requiredIndicator' => 'settings.requiredIndicator',
        ];

        $schema = SchemaHelper::extractDefaultsSchema([
            $this->defineFormBuilderSettingsSchema(),
            $this->defineBehaviourSchema(),
            $this->defineFormBuilderAppearanceSchema(),
        ], $fields);

        foreach ($schema as &$node) {
            if (($node['name'] ?? null) !== 'defaultStatus') {
                continue;
            }

            $options = [
                ['label' => Craft::t('formie', 'System default status'), 'value' => ''],
            ];

            foreach (Formie::$plugin->getStatuses()->getAllStatuses() as $status) {
                $options[] = [
                    'label' => $status->name,
                    'value' => $status->handle,
                    'status' => $status->color,
                ];
            }

            $node['$field'] = 'select';
            $node['options'] = $options;
        }
        unset($node);

        return $schema;
    }
}

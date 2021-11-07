<?php
namespace verbb\formie\helpers;

use verbb\formie\Formie;

use Craft;
use craft\db\Query;
use craft\helpers\Json;

class ProjectConfigHelper
{
    // Public Methods
    // =========================================================================

    public static function rebuildProjectConfig(): array
    {
        $output = [];

        $output['statuses'] = self::_getStatusData();
        $output['stencils'] = self::_getStencilsData();
        $output['formTemplates'] = self::_getFormTemplatesData();
        $output['emailTemplates'] = self::_getEmailTemplatesData();
        $output['pdfTemplates'] = self::_getPdfTemplatesData();
        $output['integrations'] = self::_getIntegrationsData();

        return array_filter($output);
    }


    // Private Methods
    // =========================================================================

    private static function _getStatusData(): array
    {
        $statusData = [];

        $statusRows = (new Query())
            ->select([
                'id',
                'uid',
                'name',
                'handle',
                'color',
                'description',
                'sortOrder',
                'isDefault',
            ])
            ->indexBy('id')
            ->orderBy('sortOrder')
            ->from(['{{%formie_statuses}}'])
            ->all();

        foreach ($statusRows as &$statusRow) {
            $statusUid = $statusRow['uid'];
            unset($statusRow['id']);
            unset($statusRow['uid']);

            $statusRow['sortOrder'] = (int)$statusRow['sortOrder'];
            $statusRow['isDefault'] = (bool)$statusRow['isDefault'];

            $statusData[$statusUid] = $statusRow;
        }

        return $statusData;
    }

    private static function _getStencilsData(): array
    {
        $stencilsData = [];

        $stencilsRows = (new Query())
            ->select([
                'id',
                'uid',
                'name',
                'handle',
                'data',
                'templateId',
                'defaultStatusId',
            ])
            ->indexBy('id')
            ->orderBy('name ASC')
            ->from(['{{%formie_stencils}}'])
            ->all();

        foreach ($stencilsRows as &$stencilRow) {
            $statusUid = $stencilRow['uid'];
            unset($stencilRow['id']);
            unset($stencilRow['uid']);

            $data = Json::decodeIfJson($stencilRow['data']);
            $stencilRow['data'] = $data;
            $stencilRow['defaultStatusId'] = (int)$stencilRow['defaultStatusId'];

            $stencilsData[$statusUid] = $stencilRow;
        }

        return $stencilsData;
    }

    private static function _getFormTemplatesData(): array
    {
        $templatesData = [];

        $templateRows = (new Query())
            ->select([
                'id',
                'uid',
                'name',
                'handle',
                'template',
                'useCustomTemplates',
                'outputCssLayout',
                'outputCssTheme',
                'outputJsBase',
                'outputJsTheme',
                'outputCssLocation',
                'outputJsLocation',
                'sortOrder',
                'fieldLayoutId',
            ])
            ->indexBy('id')
            ->orderBy('sortOrder')
            ->from(['{{%formie_formtemplates}}'])
            ->all();

        foreach ($templateRows as &$templateRow) {
            if (!empty($templateRow['fieldLayoutId'])) {
                $layout = Craft::$app->getFields()->getLayoutById($templateRow['fieldLayoutId']);

                if ($layout) {
                    $templateRow['fieldLayouts'] = [$layout->uid => $layout->getConfig()];
                }
            }

            $templateUid = $templateRow['uid'];
            unset($templateRow['id']);
            unset($templateRow['uid']);
            unset($templateRow['fieldLayoutId']);

            $templateRow['sortOrder'] = (int)$templateRow['sortOrder'];
            $templateRow['useCustomTemplates'] = (bool)$templateRow['useCustomTemplates'];
            $templateRow['outputCssTheme'] = (bool)$templateRow['outputCssTheme'];
            $templateRow['outputCssLayout'] = (bool)$templateRow['outputCssLayout'];
            $templateRow['outputJsBase'] = (bool)$templateRow['outputJsBase'];
            $templateRow['outputJsTheme'] = (bool)$templateRow['outputJsTheme'];

            $templatesData[$templateUid] = $templateRow;
        }

        return $templatesData;
    }

    private static function _getEmailTemplatesData(): array
    {
        $templatesData = [];

        $templateRows = (new Query())
            ->select([
                'id',
                'uid',
                'name',
                'handle',
                'template',
                'sortOrder',
            ])
            ->indexBy('id')
            ->orderBy('sortOrder')
            ->from(['{{%formie_emailtemplates}}'])
            ->all();

        foreach ($templateRows as &$templateRow) {
            $templateUid = $templateRow['uid'];
            unset($templateRow['id']);
            unset($templateRow['uid']);

            $templateRow['sortOrder'] = (int)$templateRow['sortOrder'];

            $templatesData[$templateUid] = $templateRow;
        }

        return $templatesData;
    }

    private static function _getPdfTemplatesData(): array
    {
        $templatesData = [];

        $templateRows = (new Query())
            ->select([
                'id',
                'uid',
                'name',
                'handle',
                'template',
                'filenameFormat',
                'sortOrder',
            ])
            ->indexBy('id')
            ->orderBy('sortOrder')
            ->from(['{{%formie_pdftemplates}}'])
            ->all();

        foreach ($templateRows as &$templateRow) {
            $templateUid = $templateRow['uid'];
            unset($templateRow['id']);
            unset($templateRow['uid']);

            $templateRow['sortOrder'] = (int)$templateRow['sortOrder'];

            $templatesData[$templateUid] = $templateRow;
        }

        return $templatesData;
    }

    private static function _getIntegrationsData(): array
    {
        $data = [];

        $integrationsService = Formie::$plugin->getIntegrations();

        foreach ($integrationsService->getAllIntegrations() as $integration) {
            $data[$integration->uid] = $integrationsService->createIntegrationConfig($integration);
        }

        return $data;
    }
}

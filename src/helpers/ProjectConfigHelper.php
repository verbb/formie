<?php
namespace verbb\formie\helpers;

use verbb\formie\Formie;

use Craft;
use craft\db\Query;

class ProjectConfigHelper
{
    // Static Methods
    // =========================================================================

    public static function rebuildProjectConfig(): array
    {
        $configData = [];

        $configData['statuses'] = self::_getStatusData();
        $configData['stencils'] = self::_getStencilsData();
        $configData['formGroups'] = self::_getFormGroupsData();
        $configData['formTemplates'] = self::_getFormTemplatesData();
        $configData['emailTemplates'] = self::_getEmailTemplatesData();
        $configData['pdfTemplates'] = self::_getPdfTemplatesData();
        $configData['integrations'] = self::_getIntegrationsData();
        $configData['fieldPalette'] = self::_getFieldPaletteData();

        return array_filter($configData);
    }

    
    // Private Methods
    // =========================================================================

    private static function _getStatusData(): array
    {
        $data = [];

        foreach (Formie::$plugin->getStatuses()->getAllStatuses() as $status) {
            $data[$status->uid] = $status->getConfig();
        }

        return $data;
    }

    private static function _getStencilsData(): array
    {
        $data = [];

        foreach (Formie::$plugin->getStencils()->getAllStencils() as $stencil) {
            if (!$stencil->isProjectScope()) {
                continue;
            }

            $data[$stencil->uid] = $stencil->getConfig();
        }

        return $data;
    }

    private static function _getFormGroupsData(): array
    {
        $data = [];

        foreach (Formie::$plugin->getFormGroups()->getAllGroups() as $group) {
            $data[$group->uid] = $group->getConfig();
        }

        return $data;
    }

    private static function _getFormTemplatesData(): array
    {
        $data = [];

        foreach (Formie::$plugin->getFormTemplates()->getAllTemplates() as $template) {
            $data[$template->uid] = $template->getConfig();
        }

        return $data;
    }

    private static function _getEmailTemplatesData(): array
    {
        $data = [];

        foreach (Formie::$plugin->getEmailTemplates()->getAllTemplates() as $template) {
            $data[$template->uid] = $template->getConfig();
        }

        return $data;
    }

    private static function _getPdfTemplatesData(): array
    {
        $data = [];

        foreach (Formie::$plugin->getPdfTemplates()->getAllTemplates() as $template) {
            $data[$template->uid] = $template->getConfig();
        }

        return $data;
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

    private static function _getFieldPaletteData(): array
    {
        return Formie::$plugin->getFieldPalette()->getResolvedPalette();
    }
}

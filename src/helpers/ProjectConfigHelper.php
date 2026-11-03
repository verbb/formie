<?php
namespace verbb\formie\helpers;

use verbb\formie\Formie;
use verbb\formie\services\Reports;
use verbb\formie\services\ScheduledReports;

use Craft;

class ProjectConfigHelper
{
    // Static Methods
    // =========================================================================

    public static function rebuildProjectConfig(): array
    {
        $configData = [];

        $configData['statuses'] = self::_getSubmissionStatusData();
        $configData['formStatuses'] = self::_getFormStatusData();
        $configData['stencils'] = self::_getStencilsData();
        $configData['formGroups'] = self::_getFormGroupsData();
        $configData['formTemplates'] = self::_getFormTemplatesData();
        $configData['emailTemplates'] = self::_getEmailTemplatesData();
        $configData['pdfTemplates'] = self::_getPdfTemplatesData();
        $configData['integrations'] = self::_getIntegrationsData();
        $configData['fieldPalette'] = self::_getFieldPaletteData();

        // Reports live in the database now and nothing reads these entries, but older releases
        // treat a missing entry as a deletion. Keeping them protects environments not yet upgraded.
        foreach (['reports' => Reports::CONFIG_REPORTS_KEY, 'scheduledReports' => ScheduledReports::CONFIG_SCHEDULED_REPORTS_KEY] as $key => $path) {
            $entries = Craft::$app->getProjectConfig()->get($path);

            if ($entries) {
                $configData[$key] = $entries;
            }
        }

        return array_filter($configData);
    }

    private static function _getSubmissionStatusData(): array
    {
        $data = [];

        foreach (Formie::$plugin->getSubmissionStatuses()->getAllStatuses() as $status) {
            $data[$status->uid] = $status->getConfig();
        }

        return $data;
    }

    private static function _getFormStatusData(): array
    {
        $data = [];

        foreach (Formie::$plugin->getFormStatuses()->getAllStatuses() as $status) {
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
            if (!$integration->isProjectScope()) {
                continue;
            }

            $data[$integration->uid] = $integrationsService->createProjectConfig($integration);
        }

        return $data;
    }

    private static function _getFieldPaletteData(): array
    {
        return Formie::$plugin->getFieldPalette()->getResolvedPalette();
    }
}

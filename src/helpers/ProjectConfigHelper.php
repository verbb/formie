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

        $configData['statuses'] = self::_getSubmissionStatusData();
        $configData['formStatuses'] = self::_getFormStatusData();
        $configData['stencils'] = self::_getStencilsData();
        $configData['formGroups'] = self::_getFormGroupsData();
        $configData['reports'] = self::_getReportsData();
        $configData['scheduledReports'] = self::_getScheduledReportsData();
        $configData['formTemplates'] = self::_getFormTemplatesData();
        $configData['emailTemplates'] = self::_getEmailTemplatesData();
        $configData['pdfTemplates'] = self::_getPdfTemplatesData();
        $configData['integrations'] = self::_getIntegrationsData();
        $configData['captchaProviders'] = self::_getCaptchaProvidersData();
        $configData['spamSettings'] = self::_getSpamSettingsData();
        $configData['fieldPalette'] = self::_getFieldPaletteData();

        return array_filter($configData);
    }

    
    // Private Methods
    // =========================================================================

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

    private static function _getReportsData(): array
    {
        $data = [];

        foreach (Formie::$plugin->getReports()->getAllReports() as $report) {
            $data[$report->uid] = $report->getConfig();
        }

        return $data;
    }

    private static function _getScheduledReportsData(): array
    {
        $data = [];

        foreach (Formie::$plugin->getScheduledReports()->getAllScheduledReports() as $scheduledReport) {
            $data[$scheduledReport->uid] = $scheduledReport->getConfig();
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

            $data[$integration->uid] = $integrationsService->createIntegrationConfig($integration);
        }

        return $data;
    }

    private static function _getCaptchaProvidersData(): array
    {
        $data = [];

        foreach (Formie::$plugin->getCaptchaProviders()->getProjectScopedProviders() as $handle => $integration) {
            $data[$handle] = Formie::$plugin->getCaptchaProviders()->createProviderConfig($integration);
        }

        return $data;
    }

    private static function _getSpamSettingsData(): ?array
    {
        $spamProtection = Formie::$plugin->getSpamProtection();

        if (!$spamProtection->isProjectScope()) {
            return null;
        }

        return $spamProtection->createSettingsConfig($spamProtection->getSettingsValues());
    }

    private static function _getFieldPaletteData(): array
    {
        return Formie::$plugin->getFieldPalette()->getResolvedPalette();
    }
}

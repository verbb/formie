import { createElement } from 'react';
import { AppErrorBoundary } from '@verbb/plugin-kit-react/utils';

import { ReportEditorApp, reportEditorStyles } from '@reports/components/ReportEditorApp';
import { ReportsDashboardApp, reportsDashboardStyles } from '@reports/components/ReportsDashboardApp';
import { ReportViewApp, reportViewStyles } from '@reports/components/ReportViewApp';
import { bootstrapShadowReactApp, defineFormieCpConstructor, ensureCraftNamespace, markContainerReady, mountFormieReactApp } from '@utils';

ensureCraftNamespace('Formie');

defineFormieCpConstructor('Reports', async (settings = {}) => {
    const mode = settings.mode || 'dashboard';

    const configByMode = {
        dashboard: {
            containerSelector: '.formie-reports-dashboard',
            readyClass: 'formie-reports-dashboard--ready',
            styleNamespace: 'reports-dashboard',
            App: ReportsDashboardApp,
        },
        editor: {
            containerSelector: '.formie-reports-editor',
            readyClass: 'formie-reports-editor--ready',
            styleNamespace: 'reports-editor',
            App: ReportEditorApp,
        },
        viewer: {
            containerSelector: '.formie-reports-viewer',
            readyClass: 'formie-reports-viewer--ready',
            styleNamespace: 'reports-viewer',
            App: ReportViewApp,
        },
    };

    const config = configByMode[mode] || configByMode.dashboard;

    const boot = bootstrapShadowReactApp({
        containerSelector: config.containerSelector,
        pluginHandle: 'formie',
        styleTexts: [reportsDashboardStyles, reportEditorStyles, reportViewStyles],
        styleNamespace: config.styleNamespace,
    });

    if (!boot) {
        return;
    }

    const { targetContainer } = boot;

    await mountFormieReactApp({
        mountNode: boot.mountNode,
        portalContainer: boot.portalContainer,
        shadowRootSelectors: boot.shadowRootSelectors,
        portalClassName: boot.portalClassName,
        translationCategory: boot.translationCategory,
        children: createElement(AppErrorBoundary, {
            consoleLabel: `Formie Reports ${mode} crashed:`,
            heading: Craft.t('formie', 'Something went wrong'),
            message: Craft.t('formie', 'The reports screen failed to load. Please refresh the page or try again.'),
            detailsLabel: Craft.t('formie', 'Show error details'),
            reloadLabel: Craft.t('formie', 'Reload'),
            size: 'lg',
            className: 'min-h-[320px] py-12',
        }, createElement(config.App, { settings })),
    });

    markContainerReady(targetContainer, config.readyClass);
});

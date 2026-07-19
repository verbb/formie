import { createElement } from 'react';

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
        children: createElement(config.App, { settings }),
    });

    markContainerReady(targetContainer, config.readyClass);
});

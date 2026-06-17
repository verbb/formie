import { createElement } from 'react';
import { createRoot } from 'react-dom/client';

import { ReportEditorApp, reportEditorStyles } from '@reports/components/ReportEditorApp';
import { ReportsDashboardApp, reportsDashboardStyles } from '@reports/components/ReportsDashboardApp';
import { ReportViewApp, reportViewStyles } from '@reports/components/ReportViewApp';
import { bootstrapShadowReactApp, ensureCraftNamespace, markContainerReady } from '@utils';

ensureCraftNamespace('Formie');

Craft.Formie.Reports = function(settings = {}) {
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

    const { mountNode, targetContainer } = boot;

    createRoot(mountNode).render(
        createElement(config.App, { settings }),
    );

    markContainerReady(targetContainer, config.readyClass);
};

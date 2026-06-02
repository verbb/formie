import { createElement } from 'react';
import { createRoot } from 'react-dom/client';
import { IntegrationConnectApp } from '@integration-connect/components/IntegrationConnectApp';
import { IntegrationConnectErrorBoundary } from '@integration-connect/components/IntegrationConnectErrorBoundary';
import { bootstrapShadowReactApp, ensureCraftNamespace, markContainerReady } from '@utils';
import integrationConnectStyles from '@integration-connect/css/style.css?inline';

ensureCraftNamespace('Formie');

Craft.Formie.IntegrationConnect = function(settings = {}) {
    const boot = bootstrapShadowReactApp({
        containerSelector: '.formie-integration-connect',
        pluginHandle: 'formie',
        styleTexts: [integrationConnectStyles],
        styleNamespace: 'integration-connect',
        missingContainerMessage: 'IntegrationConnect container not found: .formie-integration-connect',
    });

    if (!boot) {
        return;
    }

    const { mountNode, targetContainer } = boot;

    const root = createRoot(mountNode);
    root.render(
        createElement(IntegrationConnectErrorBoundary, null,
            createElement(IntegrationConnectApp, { settings })),
    );

    markContainerReady(targetContainer, 'formie-integration-connect--ready');
};

import { createElement } from 'react';

import { IntegrationConnectApp } from '@integration-connect/components/IntegrationConnectApp';
import { IntegrationConnectErrorBoundary } from '@integration-connect/components/IntegrationConnectErrorBoundary';
import {
    defineFormieCpConstructor,
    ensureCraftNamespace,
    injectDocumentStyleText,
    markContainerReady,
    mountFormieReactApp,
} from '@utils';

import integrationConnectStyles from '@integration-connect/css/style.css?inline';

ensureCraftNamespace('Formie');

defineFormieCpConstructor('IntegrationConnect', async (settings = {}) => {
    const targetContainer = document.querySelector('.formie-integration-connect');

    if (!targetContainer) {
        console.error('IntegrationConnect container not found: .formie-integration-connect');
        return;
    }

    // Light DOM on Craft `.field.lightswitch-field` so meta `.heading` / `.input` padding
    // matches OAuth Connect (shadow would isolate those Craft rules).
    injectDocumentStyleText(integrationConnectStyles, 'formie-integration-connect');

    await mountFormieReactApp({
        mountNode: targetContainer,
        portalContainer: document.body,
        portalClassName: 'formie-ui',
        translationCategory: 'formie',
        children: createElement(IntegrationConnectErrorBoundary, null,
            createElement(IntegrationConnectApp, { settings })),
    });

    markContainerReady(targetContainer, 'formie-integration-connect--ready');
});

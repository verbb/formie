import { createElement } from 'react';

import { NewFormApp } from '@new-form/components/NewFormApp';
import { NewFormErrorBoundary } from '@new-form/components/NewFormErrorBoundary';
import { bootstrapShadowReactApp, defineFormieCpConstructor, ensureCraftNamespace, markContainerReady, mountFormieReactApp } from '@utils';
import newFormStyles from '@new-form/css/style.css?inline';

ensureCraftNamespace('Formie');

defineFormieCpConstructor('NewForm', async (settings = {}) => {
    const boot = bootstrapShadowReactApp({
        containerSelector: '.formie-new-form',
        pluginHandle: 'formie',
        styleTexts: [newFormStyles],
        styleNamespace: 'new-form',
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
        children: createElement(NewFormErrorBoundary, null,
            createElement(NewFormApp, { settings })),
    });

    markContainerReady(targetContainer, 'formie-new-form--ready');
});

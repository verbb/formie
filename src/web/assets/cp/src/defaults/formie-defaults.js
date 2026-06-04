import { createElement } from 'react';
import { createRoot } from 'react-dom/client';

import { DefaultsApp } from '@defaults/components/DefaultsApp';
import { DefaultsErrorBoundary } from '@defaults/components/DefaultsErrorBoundary';
import { bootstrapShadowReactApp, ensureCraftNamespace, markContainerReady } from '@utils';

import defaultsStyles from '@defaults/css/style.css?inline';

ensureCraftNamespace('Formie');

Craft.Formie.Defaults = function(settings = {}) {
    const boot = bootstrapShadowReactApp({
        containerSelector: '.formie-defaults',
        pluginHandle: 'formie',
        styleTexts: [defaultsStyles],
        styleNamespace: 'defaults',
    });

    if (!boot) {
        return;
    }

    const { mountNode, targetContainer } = boot;

    createRoot(mountNode).render(
        createElement(DefaultsErrorBoundary, null,
            createElement(DefaultsApp, { settings })),
    );

    markContainerReady(targetContainer, 'formie-defaults--ready');
};

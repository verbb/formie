import { createElement } from 'react';
import { createRoot } from 'react-dom/client';
import { NewFormApp } from '@new-form/components/NewFormApp';
import { NewFormErrorBoundary } from '@new-form/components/NewFormErrorBoundary';
import { bootstrapShadowReactApp, ensureCraftNamespace, markContainerReady } from '@utils';
import newFormStyles from '@new-form/css/style.css?inline';

ensureCraftNamespace('Formie');

Craft.Formie.NewForm = function(settings = {}) {
    const boot = bootstrapShadowReactApp({
        containerSelector: '.formie-new-form',
        pluginHandle: 'formie',
        styleTexts: [newFormStyles],
        styleNamespace: 'new-form',
    });

    if (!boot) {
        return;
    }

    const { mountNode, targetContainer } = boot;

    const root = createRoot(mountNode);
    root.render(
        createElement(NewFormErrorBoundary, null,
            createElement(NewFormApp, { settings })),
    );

    markContainerReady(targetContainer, 'formie-new-form--ready');
};

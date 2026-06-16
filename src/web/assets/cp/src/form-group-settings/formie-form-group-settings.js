import { createElement } from 'react';
import { createRoot } from 'react-dom/client';

import { FormGroupSettingsApp } from '@form-group-settings/components/FormGroupSettingsApp';
import { bootstrapShadowReactApp, ensureCraftNamespace, markContainerReady } from '@utils';

import defaultsStyles from '@defaults/css/style.css?inline';
import fieldPaletteStyles from '@field-palette/css/style.css?inline';

ensureCraftNamespace('Formie');

Craft.Formie.FormGroupSettings = function(settings = {}) {
    const boot = bootstrapShadowReactApp({
        containerSelector: '.formie-form-group-settings',
        pluginHandle: 'formie',
        styleTexts: [defaultsStyles, fieldPaletteStyles],
        styleNamespace: 'form-group-settings',
    });

    if (!boot) {
        return;
    }

    const { mountNode, targetContainer } = boot;

    createRoot(mountNode).render(
        createElement(FormGroupSettingsApp, { settings }),
    );

    markContainerReady(targetContainer, 'formie-form-group-settings--ready');
};

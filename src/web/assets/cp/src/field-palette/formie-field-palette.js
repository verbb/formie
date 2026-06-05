import { createElement } from 'react';
import { createRoot } from 'react-dom/client';

import { FieldPaletteApp } from '@field-palette/components/FieldPaletteApp';
import { bootstrapShadowReactApp, ensureCraftNamespace, markContainerReady } from '@utils';

import fieldPaletteStyles from '@field-palette/css/style.css?inline';

ensureCraftNamespace('Formie');

Craft.Formie.FieldPalette = function(settings = {}) {
    const boot = bootstrapShadowReactApp({
        containerSelector: '.formie-field-palette',
        pluginHandle: 'formie',
        styleTexts: [fieldPaletteStyles],
        styleNamespace: 'field-palette',
    });

    if (!boot) {
        return;
    }

    const { mountNode, targetContainer } = boot;

    createRoot(mountNode).render(
        createElement(FieldPaletteApp, { settings }),
    );

    markContainerReady(targetContainer, 'formie-field-palette--ready');
};

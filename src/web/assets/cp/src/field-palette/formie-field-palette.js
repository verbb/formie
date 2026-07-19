import { createElement } from 'react';

import { FieldPaletteApp } from '@field-palette/components/FieldPaletteApp';
import { bootstrapShadowReactApp, defineFormieCpConstructor, ensureCraftNamespace, markContainerReady, mountFormieReactApp } from '@utils';

import fieldPaletteStyles from '@field-palette/css/style.css?inline';

ensureCraftNamespace('Formie');

defineFormieCpConstructor('FieldPalette', async (settings = {}) => {
    const boot = bootstrapShadowReactApp({
        containerSelector: '.formie-field-palette',
        pluginHandle: 'formie',
        styleTexts: [fieldPaletteStyles],
        styleNamespace: 'field-palette',
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
        children: createElement(FieldPaletteApp, { settings }),
    });

    markContainerReady(targetContainer, 'formie-field-palette--ready');
});

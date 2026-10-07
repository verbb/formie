import { createElement } from 'react';
import { AppErrorBoundary } from '@verbb/plugin-kit-react/utils';

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
        children: createElement(AppErrorBoundary, {
            consoleLabel: 'Formie Field Palette crashed:',
            heading: Craft.t('formie', 'Something went wrong'),
            message: Craft.t('formie', 'The field palette failed to load. Please refresh the page or try again.'),
            detailsLabel: Craft.t('formie', 'Show error details'),
            reloadLabel: Craft.t('formie', 'Reload'),
            size: 'lg',
            className: 'min-h-[240px] py-8',
        }, createElement(FieldPaletteApp, { settings })),
    });

    markContainerReady(targetContainer, 'formie-field-palette--ready');
});

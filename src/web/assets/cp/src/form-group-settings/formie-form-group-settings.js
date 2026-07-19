import { createElement } from 'react';

import { DefaultsErrorBoundary } from '@defaults/components/DefaultsErrorBoundary';
import { FormGroupSettingsApp } from '@form-group-settings/components/FormGroupSettingsApp';
import { registerFormieOwnedSchemaFields } from '@form-builder/fields/registerFormieOwnedSchemaFields';
import { bootstrapShadowReactApp, defineFormieCpConstructor, ensureCraftNamespace, markContainerReady, mountFormieReactApp } from '@utils';

import defaultsStyles from '@defaults/css/style.css?inline';
import fieldPaletteStyles from '@field-palette/css/style.css?inline';

ensureCraftNamespace('Formie');

defineFormieCpConstructor('FormGroupSettings', async (settings = {}) => {
    const boot = bootstrapShadowReactApp({
        containerSelector: '.formie-form-group-settings',
        pluginHandle: 'formie',
        styleTexts: [defaultsStyles, fieldPaletteStyles],
        styleNamespace: 'form-group-settings',
    });

    if (!boot) {
        return;
    }

    const { targetContainer } = boot;

    // Field / form / notification default schemas reuse Formie-owned `$field` keys
    // (variablePicker, handle, richText, …) — same registration as global Defaults.
    registerFormieOwnedSchemaFields();

    await mountFormieReactApp({
        mountNode: boot.mountNode,
        portalContainer: boot.portalContainer,
        shadowRootSelectors: boot.shadowRootSelectors,
        portalClassName: boot.portalClassName,
        translationCategory: boot.translationCategory,
        children: createElement(DefaultsErrorBoundary, null,
            createElement(FormGroupSettingsApp, { settings })),
    });

    markContainerReady(targetContainer, 'formie-form-group-settings--ready');
});

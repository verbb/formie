import { createElement } from 'react';

import { DefaultsApp } from '@defaults/components/DefaultsApp';
import { DefaultsErrorBoundary } from '@defaults/components/DefaultsErrorBoundary';
import { registerFormieOwnedSchemaFields } from '@form-builder/fields/registerFormieOwnedSchemaFields';
import { bootstrapShadowReactApp, defineFormieCpConstructor, ensureCraftNamespace, markContainerReady, mountFormieReactApp } from '@utils';

import defaultsStyles from '@defaults/css/style.css?inline';

ensureCraftNamespace('Formie');

defineFormieCpConstructor('Defaults', async (settings = {}) => {
    const boot = bootstrapShadowReactApp({
        containerSelector: '.formie-defaults',
        pluginHandle: 'formie',
        styleTexts: [defaultsStyles],
        styleNamespace: 'defaults',
    });

    if (!boot) {
        return;
    }

    const { targetContainer } = boot;

    // Defaults schemas reuse the same ex-kit `$field` keys (handle, richText, …).
    registerFormieOwnedSchemaFields();

    await mountFormieReactApp({
        mountNode: boot.mountNode,
        portalContainer: boot.portalContainer,
        shadowRootSelectors: boot.shadowRootSelectors,
        portalClassName: boot.portalClassName,
        translationCategory: boot.translationCategory,
        children: createElement(DefaultsErrorBoundary, null,
            createElement(DefaultsApp, { settings })),
    });

    markContainerReady(targetContainer, 'formie-defaults--ready');
});

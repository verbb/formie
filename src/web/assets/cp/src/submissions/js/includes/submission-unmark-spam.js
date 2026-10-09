import { createElement } from 'react';
import { AppErrorBoundary } from '@verbb/plugin-kit-react/utils';
import { bootstrapShadowReactApp, mountFormieReactApp } from '@utils';
import { UnmarkSpamDialog } from '../../UnmarkSpamDialog.jsx';

if (typeof Craft.Formie === typeof undefined) {
    Craft.Formie = {};
}

let activeUnmarkSpamDialogCleanup = null;

Craft.Formie.UnmarkSpamUserModal = function UnmarkSpamUserModal(settings = {}) {
    activeUnmarkSpamDialogCleanup?.();

    const trigger = document.activeElement instanceof HTMLElement
        ? document.activeElement
        : null;
    const container = document.createElement('div');
    container.id = `formie-unmark-spam-${crypto.randomUUID()}`;
    document.body.append(container);

    const boot = bootstrapShadowReactApp({
        containerSelector: `#${container.id}`,
        pluginHandle: 'formie',
        styleNamespace: 'submissions',
    });

    let app = null;
    let closed = false;
    const cleanup = () => {
        if (closed) {
            return;
        }

        closed = true;
        app?.unmount();
        container.remove();
        trigger?.focus();

        if (activeUnmarkSpamDialogCleanup === cleanup) {
            activeUnmarkSpamDialogCleanup = null;
        }
    };

    this.hide = cleanup;
    app = mountFormieReactApp({
        ...boot,
        children: createElement(AppErrorBoundary, {
            consoleLabel: 'Formie unmark spam dialog crashed:',
            heading: Craft.t('formie', 'Something went wrong'),
            message: Craft.t('formie', 'The unmark spam dialog could not be opened. Please refresh the page and try again.'),
            detailsLabel: Craft.t('formie', 'Show error details'),
            reloadLabel: Craft.t('formie', 'Reload'),
        }, createElement(UnmarkSpamDialog, {
            onClose: cleanup,
            onSubmit: settings.onSubmit,
        })),
    });
    activeUnmarkSpamDialogCleanup = cleanup;
};

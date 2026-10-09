// ==========================================================================

// Formie Plugin for Craft CMS
// Author: Verbb - https://verbb.io/

// ==========================================================================

import { createElement } from 'react';
import { AppErrorBoundary } from '@verbb/plugin-kit-react/utils';

// Keep a production CSS artifact for the legacy AssetBundle and inject the same
// screen-specific rules into the Plugin Kit shadow root.
import '../scss/formie-sent-notifications.scss';
import resendStyles from '../scss/formie-sent-notifications.scss?inline';

import { bootstrapShadowReactApp, mountFormieReactApp } from '@utils';
import { ResendNotificationDialog } from '../ResendNotificationDialog.jsx';

Craft.Formie ||= {};

let activeDialogCleanup = null;

const openResendDialog = ({ mode, ids }, trigger = null) => {
    activeDialogCleanup?.();

    const container = document.createElement('div');
    container.id = `formie-resend-${crypto.randomUUID()}`;
    document.body.append(container);

    const boot = bootstrapShadowReactApp({
        containerSelector: `#${container.id}`,
        pluginHandle: 'formie',
        styleTexts: [resendStyles],
        styleNamespace: 'sent-notifications',
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

        if (activeDialogCleanup === cleanup) {
            activeDialogCleanup = null;
        }
    };

    app = mountFormieReactApp({
        ...boot,
        children: createElement(AppErrorBoundary, {
            consoleLabel: 'Formie sent notification resend dialog crashed:',
            heading: Craft.t('formie', 'Something went wrong'),
            message: Craft.t('formie', 'The resend dialog could not be opened. Please refresh the page and try again.'),
            detailsLabel: Craft.t('formie', 'Show error details'),
            reloadLabel: Craft.t('formie', 'Reload'),
        }, createElement(ResendNotificationDialog, { mode, ids, onClose: cleanup })),
    });
    activeDialogCleanup = cleanup;
};

Craft.Formie.ResendNotificationModal = function ResendNotificationModal(id, trigger = null) {
    openResendDialog({ mode: 'single', ids: [String(id)] }, trigger);
};

document.addEventListener('click', (event) => {
    const trigger = event.target.closest?.('.js-fui-notification-modal-resend-btn');

    if (!trigger) {
        return;
    }

    event.preventDefault();
    new Craft.Formie.ResendNotificationModal(trigger.dataset.id, trigger);
});

Craft.Formie.BulkResendElementAction = Garnish.Base.extend({
    init(type) {
        const resizeTrigger = new Craft.ElementActionTrigger({
            type,
            batch: true,
            activate($selectedItems) {
                const ids = $selectedItems.find('.element').map((index, element) => {
                    return String($(element).data('id'));
                }).get();

                new Craft.Formie.BulkResendModal(ids);
            },
        });
    },
});

Craft.Formie.BulkResendModal = function BulkResendModal(ids) {
    openResendDialog({ mode: 'bulk', ids });
};

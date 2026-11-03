import { createElement } from 'react';
import { AppErrorBoundary } from '@verbb/plugin-kit-react/utils';

import { bootstrapShadowReactApp, mountFormieReactApp } from '@utils';
import styles from '@reports/css/style.css?inline';

import { SubmissionExportDialog } from './SubmissionExportDialog.jsx';

let activeCleanup = null;

export const openSubmissionExportDialog = (defaults, trigger = null) => {
    activeCleanup?.();

    const container = document.createElement('div');
    container.id = `formie-submission-export-${crypto.randomUUID()}`;
    document.body.append(container);

    const boot = bootstrapShadowReactApp({
        containerSelector: `#${container.id}`,
        pluginHandle: 'formie',
        styleTexts: [styles],
        styleNamespace: 'submission-export',
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

        if (activeCleanup === cleanup) {
            activeCleanup = null;
        }
    };

    app = mountFormieReactApp({
        ...boot,
        children: createElement(AppErrorBoundary, {
            consoleLabel: 'Formie export dialog crashed:',
            heading: Craft.t('formie', 'Something went wrong'),
            message: Craft.t('formie', 'The export dialog could not be opened. Please refresh the page and try again.'),
            detailsLabel: Craft.t('formie', 'Show error details'),
            reloadLabel: Craft.t('formie', 'Reload'),
        }, createElement(SubmissionExportDialog, { defaults, onClose: cleanup })),
    });
    activeCleanup = cleanup;
};

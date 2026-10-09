import { createElement } from 'react';
import { AppErrorBoundary } from '@verbb/plugin-kit-react/utils';
import { bootstrapShadowReactApp, mountFormieReactApp } from '@utils';
import { DeliveryDiagnostics } from './DeliveryDiagnostics.jsx';
import { failedDeliveryAttemptUid } from './queueDiagnostics';
import deliveryStyles from './delivery.css?inline';

function mount(container, settings) {
    container.id ||= `formie-delivery-${crypto.randomUUID()}`;
    const boot = bootstrapShadowReactApp({
        containerSelector: `#${container.id}`,
        pluginHandle: 'formie',
        styleTexts: [deliveryStyles],
        styleNamespace: 'delivery',
    });
    return mountFormieReactApp({
        ...boot,
        children: createElement(
            AppErrorBoundary,
            {
                consoleLabel: 'Formie Delivery Diagnostics crashed:',
                heading: Craft.t('formie', 'Something went wrong'),
                message: Craft.t(
                    'formie',
                    'Delivery diagnostics failed to load. Please refresh the page or try again.',
                ),
                detailsLabel: Craft.t('formie', 'Show error details'),
                reloadLabel: Craft.t('formie', 'Reload'),
                size: 'lg',
                className: 'min-h-[240px] py-8',
            },
            createElement(DeliveryDiagnostics, settings),
        ),
    });
}

function bootstrap() {
    const queue = document.getElementById('queue-manager-utility');
    if (!queue) return;
    // Craft exposes no queue detail action registration hook. Observe its rendered
    // locator field, without patching Craft's Vue state or serialized job data.
    const attach = () => {
        const detail = queue.querySelector('.readable');
        const uid = failedDeliveryAttemptUid(detail);
        const old = document.querySelector('[data-formie-diagnostics]');
        if (!uid) {
            old?.remove();
            return;
        }
        if (old?.dataset.formieDiagnostics === uid) {
            const toolbar = document.getElementById('toolbar');
            if (
                toolbar &&
                old.parentElement === toolbar &&
                toolbar.lastElementChild !== old
            ) {
                // Craft can replace or append queue actions while refreshing the job.
                // Keep this action last so those updates do not make it jump position.
                toolbar.append(old);
            }
            return;
        }
        old?.remove();
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'btn submit';
        button.dataset.formieDiagnostics = uid;
        button.textContent = Craft.t('formie', 'Diagnostics');
        button.addEventListener('click', () => {
            const container = document.createElement('div');
            document.body.append(container);
            const app = mount(container, {
                uid,
                onClose: () => {
                    app.unmount();
                    container.remove();
                    button.focus();
                },
            });
        });
        // Keep the diagnostic action at the stable far-right edge of Craft's job
        // actions. Older Queue Manager layouts fall back to the job detail heading.
        const toolbar = document.getElementById('toolbar');
        if (toolbar) {
            toolbar.append(button);
            return;
        }
        const heading = detail.querySelector('h2');
        if (heading) {
            button.style.marginBlockEnd = '16px';
            heading.insertAdjacentElement('afterend', button);
        } else {
            detail.prepend(button);
        }
    };
    const observer = new MutationObserver(attach);
    observer.observe(queue, {
        childList: true,
        subtree: true,
        characterData: true,
    });
    const toolbarObserver = new MutationObserver(attach);
    const toolbar = document.getElementById('toolbar');
    if (toolbar) {
        toolbarObserver.observe(toolbar, { childList: true });
    }
    attach();
    window.addEventListener(
        'pagehide',
        () => {
            observer.disconnect();
            toolbarObserver.disconnect();
        },
        { once: true },
    );
}

if (document.readyState === 'loading')
    document.addEventListener('DOMContentLoaded', bootstrap, { once: true });
else bootstrap();

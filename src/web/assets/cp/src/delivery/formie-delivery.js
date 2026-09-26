import { createElement } from 'react';
import { bootstrapShadowReactApp, mountFormieReactApp } from '@utils';
import { DeliveryDiagnostics } from './DeliveryDiagnostics.jsx';

function mount(container, settings) {
    container.id ||= `formie-delivery-${crypto.randomUUID()}`;
    const boot = bootstrapShadowReactApp({ containerSelector: `#${container.id}`, pluginHandle: 'formie', styleTexts: [], styleNamespace: 'delivery' });
    return mountFormieReactApp({ ...boot, children: createElement(DeliveryDiagnostics, settings) });
}

function bootstrap() {
    document.querySelectorAll('.formie-delivery-history').forEach((container) => {
        if (container.dataset.mounted) return;
        container.dataset.mounted = 'true';
        mount(container, { submissionId: Number(container.dataset.submissionId) });
    });

    const queue = document.getElementById('queue-manager-utility');
    if (!queue) return;
    // Craft exposes no queue detail action registration hook. Observe its rendered
    // locator field, without patching Craft's Vue state or serialized job data.
    const attach = () => {
        const detail = queue.querySelector('.readable');
        const code = detail?.querySelector('pre code');
        const match = code?.textContent.match(/"deliveryAttemptUid"\s*:\s*"([a-f0-9-]{36})"/i);
        const old = detail?.querySelector('[data-formie-diagnostics]');
        if (!match) { old?.remove(); return; }
        if (old?.dataset.formieDiagnostics === match[1]) return;
        old?.remove();
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'btn';
        button.dataset.formieDiagnostics = match[1];
        button.textContent = Craft.t('formie', 'Formie delivery diagnostics');
        button.addEventListener('click', () => {
            const container = document.createElement('div');
            document.body.append(container);
            const app = mount(container, { uid: match[1], onClose: () => { app.unmount(); container.remove(); button.focus(); } });
        });
        detail.prepend(button);
    };
    const observer = new MutationObserver(attach);
    observer.observe(queue, { childList: true, subtree: true, characterData: true });
    attach();
    window.addEventListener('pagehide', () => observer.disconnect(), { once: true });
}

if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', bootstrap, { once: true });
else bootstrap();

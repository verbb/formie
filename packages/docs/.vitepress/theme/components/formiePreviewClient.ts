import { createFormieClient, setFormieDebugEnabled, type FormModuleManifest } from '../../../../formie-browser/src/index';

type PreviewClientConfig = {
    modules?: FormModuleManifest[];
};

type HeightCallback = (height: number) => void;

function readConfig(previewDocument: Document): PreviewClientConfig {
    const configNode = previewDocument.getElementById('formie-preview-config');

    if (!(configNode instanceof HTMLScriptElement) || !configNode.textContent) {
        return {};
    }

    try {
        return JSON.parse(configNode.textContent) as PreviewClientConfig;
    } catch (error) {
        console.warn('[FormiePreview] Failed to parse preview config.', error);
        return {};
    }
}

function applyModules(previewDocument: Document, modules: FormModuleManifest[] | undefined): void {
    if (!modules?.length) {
        return;
    }

    const serializedModules = JSON.stringify(modules);

    previewDocument.querySelectorAll('[data-formie], [data-formie-form]').forEach((root) => {
        root.setAttribute('data-formie-modules', serializedModules);
    });
}

function measureDocumentHeight(previewDocument: Document): number {
    const body = previewDocument.body;
    const previewHTMLElement = previewDocument.defaultView?.HTMLElement;

    if (!body) {
        return previewDocument.documentElement?.scrollHeight || 0;
    }

    const bodyRect = body.getBoundingClientRect();
    const bodyStyle = previewDocument.defaultView?.getComputedStyle(body);
    const paddingTop = parseFloat(bodyStyle.paddingTop || '0') || 0;
    const paddingBottom = parseFloat(bodyStyle.paddingBottom || '0') || 0;
    const contentBottom = Array.from(body.children).reduce((max, child) => {
        if (!previewHTMLElement || !(child instanceof previewHTMLElement) || child.tagName === 'SCRIPT') {
            return max;
        }

        const rect = child.getBoundingClientRect();
        return Math.max(max, rect.bottom - bodyRect.top);
    }, paddingTop);

    return Math.ceil(contentBottom + paddingBottom);
}

function postHeight(previewWindow: Window, onHeight?: HeightCallback): void {
    const height = measureDocumentHeight(previewWindow.document);

    onHeight?.(height);

    previewWindow.parent?.postMessage({
        type: 'formie-preview:height',
        height,
    }, '*');
}

function observeHeight(previewWindow: Window, onHeight?: HeightCallback): void {
    const previewDocument = previewWindow.document;

    if (typeof previewWindow.ResizeObserver !== 'undefined') {
        const observer = new previewWindow.ResizeObserver(() => {
            postHeight(previewWindow, onHeight);
        });

        observer.observe(previewDocument.documentElement);

        if (previewDocument.body) {
            observer.observe(previewDocument.body);
        }
    }

    ['click', 'input', 'change'].forEach((eventName) => {
        previewDocument.addEventListener(eventName, () => {
            previewWindow.requestAnimationFrame(() => {
                postHeight(previewWindow, onHeight);
            });
        }, true);
    });
}

export async function initFormiePreviewClient(previewWindow: Window, onHeight?: HeightCallback): Promise<void> {
    const previewDocument = previewWindow.document;
    const config = readConfig(previewDocument);

    observeHeight(previewWindow, onHeight);
    previewWindow.addEventListener('load', () => {
        postHeight(previewWindow, onHeight);
    }, { once: true });

    previewWindow.requestAnimationFrame(() => {
        postHeight(previewWindow, onHeight);

        previewWindow.requestAnimationFrame(() => {
            postHeight(previewWindow, onHeight);
        });
    });

    if (config.modules?.length) {
        setFormieDebugEnabled(false);
        applyModules(previewDocument, config.modules);

        const client = createFormieClient();
        await client.scan(previewDocument);
    }

    postHeight(previewWindow, onHeight);
}

import { getDocumentCspNonce } from '#utils/scripts';

const injectedModuleStyles = new Set<string>();

function getStyleNonce(): string | null {
    // Strict CSPs block inline styles without a nonce. Without a csp-nonce meta tag, reuse the
    // nonce given to Formie's own script through `scriptAttributes`. Browsers only expose it
    // through the `nonce` property, never the attribute.
    const startupScript = document.querySelector('script[data-formie-startup]') as HTMLScriptElement | null;

    return getDocumentCspNonce() || startupScript?.nonce || null;
}

export function ensureModuleStyles(moduleId: string, styles: Array<string | null | undefined>): void {
    if (typeof document === 'undefined') {
        return;
    }

    const styleId = `formie-module-style:${moduleId}`;

    if (injectedModuleStyles.has(styleId) || document.querySelector(`style[data-formie-module-style="${moduleId}"]`)) {
        injectedModuleStyles.add(styleId);
        return;
    }

    const cssText = styles
        .filter((style): style is string => typeof style === 'string' && style.trim().length > 0)
        .join('\n');

    if (!cssText) {
        injectedModuleStyles.add(styleId);
        return;
    }

    const style = document.createElement('style');
    const nonce = getStyleNonce();

    if (nonce) {
        style.nonce = nonce;
    }

    style.setAttribute('data-formie-module-style', moduleId);
    style.textContent = cssText;
    document.head.appendChild(style);
    injectedModuleStyles.add(styleId);
}

import { configurePluginKitReact, createCraftHostBridge } from '@verbb/plugin-kit-react/utils';

export const ensureCraftNamespace = (namespacePath) => {
    if (!namespacePath) {
        throw new Error('ensureCraftNamespace() requires a namespacePath.');
    }

    const parts = namespacePath.split('.').map((part) => { return part.trim(); }).filter(Boolean);
    let current = Craft;

    parts.forEach((part) => {
        if (typeof current[part] === typeof undefined) {
            current[part] = {};
        }

        current = current[part];
    });

    return current;
};

export const createShadowMount = (container, {
    styleTexts,
    styleNamespace,
    styleAttr,
    rootAttr,
} = {}) => {
    if (!styleAttr || !rootAttr) {
        throw new Error('createShadowMount() requires styleAttr and rootAttr.');
    }

    if (!Array.isArray(styleTexts)) {
        throw new Error('createShadowMount() requires styleTexts array.');
    }

    if (!container.attachShadow) {
        return { mountNode: container, portalContainer: undefined };
    }

    const shadowRoot = container.shadowRoot ?? container.attachShadow({ mode: 'open' });

    shadowRoot.querySelectorAll(`[${styleAttr}]`).forEach((node) => { return node.remove(); });

    styleTexts.forEach((cssText, index) => {
        if (!cssText) {
            return;
        }

        const style = document.createElement('style');
        style.setAttribute(styleAttr, `${styleNamespace}-${index}`);
        style.textContent = cssText;
        shadowRoot.appendChild(style);
    });

    let mountNode = shadowRoot.querySelector(`[${rootAttr}]`);

    if (!mountNode) {
        mountNode = document.createElement('div');
        mountNode.setAttribute(rootAttr, '');
        shadowRoot.appendChild(mountNode);
    }

    return { mountNode, portalContainer: shadowRoot };
};

export const bootstrapShadowReactApp = ({
    containerSelector,
    pluginHandle,
    styleTexts,
    styleNamespace,
    styleAttr,
    rootAttr,
    portalClassName,
    translationCategory,
    missingContainerMessage = null,
}) => {
    if (!pluginHandle || !/^[a-z0-9]+(?:-[a-z0-9]+)*$/.test(pluginHandle)) {
        throw new Error('bootstrapShadowReactApp() requires a kebab-case pluginHandle.');
    }

    const resolvedStyleNamespace = styleNamespace || pluginHandle;
    const resolvedStyleAttr = styleAttr || `data-${pluginHandle}-shadow-style`;
    const resolvedRootAttr = rootAttr || `data-${pluginHandle}-shadow-root`;
    const resolvedPortalClassName = portalClassName || `${pluginHandle}-ui`;
    const resolvedTranslationCategory = translationCategory || pluginHandle;

    const targetContainer = document.querySelector(containerSelector);

    if (!targetContainer) {
        if (missingContainerMessage) {
            console.error(missingContainerMessage);
        }

        return null;
    }

    const { mountNode, portalContainer } = createShadowMount(targetContainer, {
        styleTexts,
        styleNamespace: resolvedStyleNamespace,
        styleAttr: resolvedStyleAttr,
        rootAttr: resolvedRootAttr,
    });

    configurePluginKitReact({
        portalClassName: resolvedPortalClassName,
        portalContainer,
        shadowRootSelectors: [`[${resolvedRootAttr}]`],
        translationCategory: resolvedTranslationCategory,
        hostBridge: createCraftHostBridge(),
    });

    return { targetContainer, mountNode, portalContainer };
};

export const markContainerReady = (container, readyClassName) => {
    if (!container || !readyClassName) {
        return;
    }

    requestAnimationFrame(() => {
        setTimeout(() => {
            container.classList.add(readyClassName);
        }, 10);
    });
};

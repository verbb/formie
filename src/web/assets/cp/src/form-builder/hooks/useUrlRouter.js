import { useEffect } from 'react';
import { buildBuilderRouteUrl, getBuilderPathname } from '../utils/builderRouteUrl';
import useAppStore from './useAppStore';
import { useFormBuilderApp } from '@form-builder/contexts/FormBuilderAppContext';

// URL patterns
const URL_PATTERNS = {
    // /fields/page2
    PAGE_PATTERN: /^\/fields\/([^\/]+)$/,
    // /integrations/google
    INTEGRATION_PATTERN: /^\/integrations\/([^\/]+)$/,
    // /fields, /behaviour, /notifications, etc.
    TAB_PATTERN: /^\/([^\/]+)$/,
};

// Default values
const DEFAULTS = {
    activeTab: null,
    activePageHandle: null,
    activeIntegrationHandle: null,
};

const getUrl = (url) => {
    return new URL(url, window.location.origin);
};

const normalizePath = (path) => {
    return path.replace(/\/+$/, '');
};

const getRelativePath = (path, basePath) => {
    const normalizedPath = normalizePath(path);
    const normalizedBasePath = normalizePath(basePath);

    if (!normalizedBasePath || normalizedPath === normalizedBasePath) {
        return '';
    }

    if (normalizedPath.startsWith(`${normalizedBasePath}/`)) {
        return normalizedPath.slice(normalizedBasePath.length);
    }

    return path;
};

const getRoutePath = ({ activeTab, activePageHandle, activeIntegrationHandle }) => {
    if (activeTab === 'fields' && activePageHandle) {
        return `/fields/${activePageHandle}`;
    }

    if (activeTab === 'integrations' && activeIntegrationHandle) {
        return `/integrations/${activeIntegrationHandle}`;
    }

    if (activeTab) {
        return `/${activeTab}`;
    }

    return '';
};

// Standalone initialization function that can be called outside of React components
export const initializeRouterState = () => {
    const {
        setActiveTab, setActivePageHandle, setActiveIntegrationHandle, baseUrl,
    } = useAppStore.getState();

    // Parse current URL and set initial state
    const pathParam = Craft.pathParam || 'p';
    const path = getBuilderPathname(getUrl(window.location.href), pathParam);
    const baseUrlPath = getBuilderPathname(getUrl(baseUrl), pathParam);
    const relativePath = getRelativePath(path, baseUrlPath);

    // Set default state
    let activeTab = null;
    let activePageHandle = null;
    let activeIntegrationHandle = null;

    // Parse route state
    if (!relativePath || relativePath === '/') {
        // Root path - use defaults
    } else if (relativePath.match(URL_PATTERNS.PAGE_PATTERN)) {
        // Page pattern: /fields/page2
        const match = relativePath.match(URL_PATTERNS.PAGE_PATTERN);

        activeTab = 'fields';
        activePageHandle = match[1];
    } else if (relativePath.match(URL_PATTERNS.INTEGRATION_PATTERN)) {
        // Integration pattern: /integrations/google
        const match = relativePath.match(URL_PATTERNS.INTEGRATION_PATTERN);

        activeTab = 'integrations';
        activeIntegrationHandle = match[1];
    } else if (relativePath.match(URL_PATTERNS.TAB_PATTERN)) {
        // Tab pattern: /fields, /behaviour, etc.
        const match = relativePath.match(URL_PATTERNS.TAB_PATTERN);

        activeTab = match[1];
    }

    // Default to the fields tab when landing on the root URL
    if (!activeTab) {
        activeTab = 'fields';
    }

    // Set the state directly in the store
    setActiveTab(activeTab);
    setActivePageHandle(activePageHandle);
    setActiveIntegrationHandle(activeIntegrationHandle);
};

function useUrlRouter() {
    const {
        baseUrl,
        activeTab,
        activePageHandle,
        activeIntegrationHandle,
        setActiveTab,
        setActivePageHandle,
        setActiveIntegrationHandle,
    } = useFormBuilderApp();

    // Update URL when state changes
    useEffect(() => {
        const currentState = {
            activeTab: activeTab || DEFAULTS.activeTab,
            activePageHandle: activePageHandle !== null ? activePageHandle : DEFAULTS.activePageHandle,
            activeIntegrationHandle: activeIntegrationHandle || DEFAULTS.activeIntegrationHandle,
        };
        const expectedUrl = buildBuilderRouteUrl(baseUrl, getRoutePath(currentState), window.location.href, Craft.pathParam || 'p');
        const expectedLocation = new URL(expectedUrl);
        const currentPath = window.location.pathname;
        const currentSearch = window.location.search;

        // Only update URL if it doesn't match expected state
        if (currentPath !== expectedLocation.pathname || currentSearch !== expectedLocation.search) {
            // Builder selections share one history entry so Back returns to the previous page.
            window.history.replaceState(currentState, '', expectedUrl);
        }
    }, [activeTab, activePageHandle, activeIntegrationHandle, baseUrl]);


    // Public API
    return {
        // Navigate to a specific tab
        navigateToTab: (tab) => {
            setActiveTab(tab);
        },

        // Navigate to a specific page (within fields tab)
        navigateToPage: (pageHandle) => {
            setActivePageHandle(pageHandle);
        },

        // Navigate to a specific integration
        navigateToIntegration: (integrationHandle) => {
            setActiveIntegrationHandle(integrationHandle);
        },
    };
}

export default useUrlRouter;

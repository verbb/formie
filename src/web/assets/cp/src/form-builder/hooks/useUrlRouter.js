import { useEffect, useRef } from 'react';
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

const applySiteQueryParam = (url) => {
    const siteHandle = new URL(window.location.href).searchParams.get('site');

    url.search = '';

    if (siteHandle) {
        url.searchParams.set('site', siteHandle);
    }

    return url;
};

// Standalone initialization function that can be called outside of React components
export const initializeRouterState = () => {
    const {
        setActiveTab, setActivePageHandle, setActiveIntegrationHandle, baseUrl,
    } = useAppStore.getState();

    // Parse current URL and set initial state
    const path = window.location.pathname;
    const baseUrlPath = getUrl(baseUrl).pathname;
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

    // Since we initialize outside of React, we can assume the router is ready
    const isInitialized = useRef(true);
    const hasSyncedInitialUrlRef = useRef(false);
    const pendingHistoryModeRef = useRef('push');

    // Generate URL from route state
    const generateUrl = (state) => {
        const base = getUrl(baseUrl);
        const basePath = normalizePath(base.pathname);

        base.pathname = `${basePath}${getRoutePath(state)}`;

        return applySiteQueryParam(base).toString();
    };

    // Update URL without triggering navigation
    const updateUrl = (state, options = {}) => {
        const { replace = false } = options;
        const newUrl = generateUrl(state);

        if (replace) {
            window.history.replaceState(state, '', newUrl);
            return;
        }

        window.history.pushState(state, '', newUrl);
    };

    // Update URL when state changes
    useEffect(() => {
        if (!isInitialized.current) { return; }

        const currentState = {
            activeTab: activeTab || DEFAULTS.activeTab,
            activePageHandle: activePageHandle !== null ? activePageHandle : DEFAULTS.activePageHandle,
            activeIntegrationHandle: activeIntegrationHandle || DEFAULTS.activeIntegrationHandle,
        };
        const expectedUrl = generateUrl(currentState);
        const expectedLocation = new URL(expectedUrl);
        const currentPath = window.location.pathname;
        const currentSearch = window.location.search;

        // Only update URL if it doesn't match expected state
        if (currentPath !== expectedLocation.pathname || currentSearch !== expectedLocation.search) {
            const shouldReplace = !hasSyncedInitialUrlRef.current || pendingHistoryModeRef.current === 'replace';
            updateUrl(currentState, { replace: shouldReplace });
        }

        if (!hasSyncedInitialUrlRef.current) {
            hasSyncedInitialUrlRef.current = true;
        }

        pendingHistoryModeRef.current = 'push';
    }, [activeTab, activePageHandle, activeIntegrationHandle, baseUrl]);


    // Public API
    return {
        // Navigate to a specific tab
        navigateToTab: (tab, options = {}) => {
            pendingHistoryModeRef.current = options.replace ? 'replace' : 'push';
            setActiveTab(tab);
        },

        // Navigate to a specific page (within fields tab)
        navigateToPage: (pageHandle, options = {}) => {
            pendingHistoryModeRef.current = options.replace ? 'replace' : 'push';
            setActivePageHandle(pageHandle);
        },

        // Navigate to a specific integration
        navigateToIntegration: (integrationHandle, options = {}) => {
            pendingHistoryModeRef.current = options.replace ? 'replace' : 'push';
            setActiveIntegrationHandle(integrationHandle);
        },
    };
}

export default useUrlRouter;

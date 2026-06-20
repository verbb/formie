import { useCallback, useEffect, useRef } from 'react';
import { cloneDeep } from 'lodash-es';

import useAppStore from '@form-builder/hooks/useAppStore';
import { normalizeFormData } from '@form-builder/hooks/useFormTools';
import { getSiteOverrideForSite, mergeSiteOverridesIntoFormData } from '@form-builder/utils/siteOverrides';
import { resolveSiteFromUrl } from '@form-builder/utils/siteUrl';

const SITE_CRUMB_ID = 'site-crumb';
const SITE_CRUMB_MENU_ID = 'site-crumb-menu';

const updateCrumbLabel = (siteName) => {
    const crumbLink = document.getElementById(SITE_CRUMB_ID);
    const labelSpan = crumbLink?.querySelector(':scope > span:not(.cp-icon)');

    if (labelSpan) {
        labelSpan.textContent = siteName;
    }
};

const updateMenuSelection = (siteId) => {
    const menu = document.getElementById(SITE_CRUMB_MENU_ID);

    if (!menu) {
        return;
    }

    menu.querySelectorAll('[data-formie-site-id]').forEach((item) => {
        const isSelected = Number(item.dataset.formieSiteId) === Number(siteId);
        item.classList.toggle('sel', isSelected);
    });
};

const updateSiteUrl = (siteHandle) => {
    if (!siteHandle) {
        return;
    }

    const url = new URL(window.location.href);
    url.searchParams.set('site', siteHandle);
    window.history.replaceState(window.history.state, '', `${url.pathname}${url.search}${url.hash}`);
};

const buildSiteDisplayData = (siteId) => {
    const {
        canonicalData,
        multiSite,
    } = useAppStore.getState();

    if (!canonicalData || !multiSite?.enabled) {
        return null;
    }

    const overrides = getSiteOverrideForSite(multiSite.overrides, siteId);

    return cloneDeep(
        normalizeFormData(
            mergeSiteOverridesIntoFormData(canonicalData, overrides),
        ),
    );
};

const applySiteDisplayData = (formRef, siteId) => {
    const formStore = formRef.current?.store;

    if (!formStore) {
        return false;
    }

    const displayData = buildSiteDisplayData(siteId);

    if (!displayData) {
        return false;
    }

    formStore.reset(displayData);
    formRef.current?.recaptureUnloadBaseline?.();

    return displayData;
};

function useFormBuilderSiteCrumb(formRef) {
    const multiSite = useAppStore((state) => state.multiSite);
    const activeSiteId = useAppStore((state) => state.activeSiteId);
    const setActiveSiteId = useAppStore((state) => state.setActiveSiteId);
    const setMultiSite = useAppStore((state) => state.setMultiSite);
    const setTitle = useAppStore((state) => state.setTitle);
    const hasSyncedFromUrlRef = useRef(false);
    const hasBootstrappedSiteDataRef = useRef(false);

    const sites = multiSite?.sites || [];

    const switchSite = useCallback((nextSiteId, options = {}) => {
        const {
            force = false,
        } = options;
        const normalizedSiteId = Number(nextSiteId);

        if (!normalizedSiteId || (!force && normalizedSiteId === Number(activeSiteId))) {
            return;
        }

        const {
            multiSite: latestMultiSite,
        } = useAppStore.getState();
        const displayData = applySiteDisplayData(formRef, normalizedSiteId);

        if (!displayData) {
            return;
        }

        const nextSite = sites.find((site) => Number(site.id) === normalizedSiteId);

        setTitle(displayData.title || '');
        setActiveSiteId(normalizedSiteId);
        useAppStore.getState().bumpSiteDisplayRevision();
        setMultiSite({
            ...latestMultiSite,
            activeSiteId: normalizedSiteId,
            layoutReadOnly: false,
        });

        if (nextSite?.name) {
            updateCrumbLabel(nextSite.name);
        }

        if (nextSite?.handle) {
            updateSiteUrl(nextSite.handle);
        }

        updateMenuSelection(normalizedSiteId);
    }, [
        activeSiteId,
        formRef,
        setActiveSiteId,
        setMultiSite,
        setTitle,
        sites,
    ]);

    useEffect(() => {
        if (!multiSite?.enabled || sites.length <= 1) {
            return undefined;
        }

        const menu = document.getElementById(SITE_CRUMB_MENU_ID);

        if (!menu) {
            return undefined;
        }

        const handleMenuClick = (event) => {
            const menuItem = event.target.closest('[data-formie-site-id]');

            if (!menuItem || !menu.contains(menuItem)) {
                return;
            }

            event.preventDefault();
            switchSite(menuItem.dataset.formieSiteId);
        };

        menu.addEventListener('click', handleMenuClick);

        return () => {
            menu.removeEventListener('click', handleMenuClick);
        };
    }, [multiSite?.enabled, sites.length, switchSite]);

    useEffect(() => {
        if (hasBootstrappedSiteDataRef.current || !multiSite?.enabled || sites.length <= 1) {
            return;
        }

        if (!formRef.current?.store) {
            return;
        }

        const {
            canonicalData,
            activeSiteId: currentActiveSiteId,
        } = useAppStore.getState();

        if (!canonicalData || !currentActiveSiteId) {
            return;
        }

        const displayData = applySiteDisplayData(formRef, currentActiveSiteId);

        if (displayData) {
            setTitle(displayData.title || '');
            useAppStore.getState().bumpSiteDisplayRevision();
        }

        hasBootstrappedSiteDataRef.current = true;
    }, [formRef, multiSite, setTitle, sites.length]);

    useEffect(() => {
        if (hasSyncedFromUrlRef.current || !multiSite?.enabled || sites.length <= 1) {
            return;
        }

        if (!formRef.current?.store) {
            return;
        }

        const matchedSite = resolveSiteFromUrl(multiSite);

        if (!matchedSite) {
            hasSyncedFromUrlRef.current = true;
            return;
        }

        const urlSiteId = Number(matchedSite.id);

        if (urlSiteId !== Number(activeSiteId)) {
            switchSite(urlSiteId);
        }

        hasSyncedFromUrlRef.current = true;
    }, [activeSiteId, formRef, multiSite, sites.length, switchSite]);
}

export { useFormBuilderSiteCrumb };

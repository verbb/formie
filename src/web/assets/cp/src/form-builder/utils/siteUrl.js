const getSiteHandleFromUrl = (url = window.location.href) => {
    return new URL(url, window.location.origin).searchParams.get('site');
};

const findSiteByHandle = (sites = [], handle) => {
    if (!handle) {
        return null;
    }

    return sites.find((site) => site.handle === handle) || null;
};

const clearCraftSiteLocalStorage = () => {
    if (typeof Craft !== 'undefined' && typeof Craft.removeLocalStorage === 'function') {
        Craft.removeLocalStorage('BaseElementIndex.siteId');
    }
};

const syncCraftSiteId = (siteId) => {
    const normalizedSiteId = Number(siteId);

    if (!normalizedSiteId || typeof Craft === 'undefined' || typeof Craft.cp?.setSiteId !== 'function') {
        return;
    }

    if (Number(Craft.siteId) === normalizedSiteId) {
        return;
    }

    try {
        Craft.cp.setSiteId(normalizedSiteId);
    } catch (error) {
        // Ignore invalid site IDs.
    }
};

const resolveSiteFromUrl = (multiSite, url = window.location.href) => {
    const sites = multiSite?.sites || [];

    if (!multiSite?.enabled || sites.length <= 1) {
        return null;
    }

    const siteHandle = getSiteHandleFromUrl(url);
    const matchedSite = siteHandle
        ? findSiteByHandle(sites, siteHandle)
        : sites.find((site) => Number(site.id) === Number(multiSite.primarySiteId));

    return matchedSite || null;
};

const bootstrapBuilderSiteFromUrl = (multiSite) => {
    clearCraftSiteLocalStorage();

    const matchedSite = resolveSiteFromUrl(multiSite);

    if (!matchedSite) {
        return null;
    }

    syncCraftSiteId(matchedSite.id);

    return matchedSite;
};

export {
    bootstrapBuilderSiteFromUrl,
    clearCraftSiteLocalStorage,
    findSiteByHandle,
    getSiteHandleFromUrl,
    resolveSiteFromUrl,
    syncCraftSiteId,
};

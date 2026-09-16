// Craft can carry its route in the path or a configured query parameter.
export const getBuilderRoutePath = (url, pathParam = 'p') => {
    return url.searchParams.get(pathParam) ?? url.pathname;
};

// Compare both Craft URL formats as paths, preserving an installation subfolder.
export const getBuilderPathname = (url, pathParam = 'p') => {
    const route = url.searchParams.get(pathParam);

    return route === null ? url.pathname : new URL(route.replace(/^\/+/, ''), url).pathname;
};

export const buildBuilderRouteUrl = (baseUrl, route, currentUrl, pathParam = 'p') => {
    const url = new URL(baseUrl, currentUrl);
    const path = getBuilderRoutePath(url, pathParam).replace(/\/+$/, '') + route;
    const usesQueryRoute = url.searchParams.has(pathParam);
    const site = new URL(currentUrl).searchParams.get('site');
    url.search = '';

    if (usesQueryRoute) {
        url.searchParams.set(pathParam, path);
    } else {
        url.pathname = path;
    }

    if (site) {
        url.searchParams.set('site', site);
    }

    return url.toString();
};

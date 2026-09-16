import { expect, it } from 'vitest';
import { buildBuilderRouteUrl, getBuilderRoutePath } from './builderRouteUrl';

it.each(['p', 'route'])('keeps the Craft %s route reloadable and restores its page', (pathParam) => {
    const base = `https://example.test/index.php?${pathParam}=admin/formie/forms/edit/42&site=default`;
    const result = buildBuilderRouteUrl(base, '/fields/page2', 'https://example.test/index.php?site=french', pathParam);
    const url = new URL(result);
    expect(url.pathname).toBe('/index.php');
    expect(url.searchParams.get(pathParam)).toBe('admin/formie/forms/edit/42/fields/page2');
    expect(url.searchParams.get('site')).toBe('french');
    expect(getBuilderRoutePath(url, pathParam)).toBe('admin/formie/forms/edit/42/fields/page2');
});

it('preserves a path-based installation and the selected site', () => {
    const result = buildBuilderRouteUrl('https://example.test/sub/admin/formie/forms/edit/42', '/behaviour', 'https://example.test/sub/admin/formie/forms/edit/42?site=french');
    expect(result).toBe('https://example.test/sub/admin/formie/forms/edit/42/behaviour?site=french');
    expect(getBuilderRoutePath(new URL(result))).toBe('/sub/admin/formie/forms/edit/42/behaviour');
});

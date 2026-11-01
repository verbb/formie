// Craft can put an action route in either the path or its `p` query parameter.
// Match the route rather than URL encoding or the configured action trigger.
export function matchesAction(url: URL | string, action: string): boolean {
    const parsed = typeof url === 'string' ? new URL(url) : url;
    const route = parsed.searchParams.get('p') ?? parsed.pathname;
    return `/${route}`.endsWith(`/${action}`);
}

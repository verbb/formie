/** Remove an exchanged bearer from browser history without changing unrelated query values. */
export function clearExchangedGrant(token?: string): void {
    if (!token || typeof window === 'undefined') return;
    const url = new URL(window.location.href);
    let changed = false;
    for (const name of ['grantToken', 'resumeToken', 'submissionEditToken']) {
        if (url.searchParams.get(name) === token) {
            url.searchParams.delete(name);
            changed = true;
        }
    }
    if (changed) window.history.replaceState(window.history.state, '', url);
}

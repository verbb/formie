export type RequestProfile = 'same-origin-browser' | 'cross-origin-public' | 'trusted-administrative';
export type BrowserRequestOptions = {
    profile?: Exclude<RequestProfile, 'trusted-administrative'>;
    /** Opaque credential returned by a previous public bootstrap, when resuming. */
    publicSession?: string;
};
const publicSessions = new Map<string, string>();
export async function browserRequest(url: string, init: RequestInit, options: BrowserRequestOptions = {}): Promise<Response> {
    const profile = options.profile ?? 'same-origin-browser';
    const headers = browserRequestHeaders(url, options, init.headers);
    const response = await fetch(url, { ...init, headers, credentials: profile === 'cross-origin-public' ? 'omit' : 'same-origin' });
    const origin = new URL(url, typeof location === 'undefined' ? 'http://localhost' : location.href).origin;
    const token = response.headers.get('X-Formie-Session');
    if (profile === 'cross-origin-public' && token) publicSessions.set(origin, token);
    return response;
}

export function browserRequestHeaders(url: string, options: BrowserRequestOptions = {}, initialHeaders?: HeadersInit): Headers {
    const profile = options.profile ?? 'same-origin-browser';
    if (!['same-origin-browser', 'cross-origin-public'].includes(profile)) {
        throw new Error('Client-rendered forms require a public browser profile. Use administrative API mutations for trusted administration.');
    }
    const headers = new Headers(initialHeaders);
    headers.set('X-Formie-Profile', profile);
    const origin = new URL(url, typeof location === 'undefined' ? 'http://localhost' : location.href).origin;
    if (profile === 'cross-origin-public') {
        const token = options.publicSession ?? publicSessions.get(origin);
        if (token) headers.set('X-Formie-Session', token);
    }
    return headers;
}

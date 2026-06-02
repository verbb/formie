export type RequestJsonOptions = {
    method?: string;
    body?: BodyInit | null;
    headers?: Record<string, string>;
    signal?: AbortSignal;
};

async function request(url: string | URL, options: RequestJsonOptions = {}): Promise<Response> {
    // Do not send `X-Requested-With` here: it is not CORS-safelisted, and Craft GraphQL CORS
    // often omits it from `Access-Control-Allow-Headers` (breaks localhost → ddev, starters, etc.).
    // `Accept` + JSON `Content-Type` are enough for Formie/Craft JSON endpoints.
    const headers: Record<string, string> = {
        Accept: 'application/json',
        ...(options.headers || {}),
    };
    delete headers['X-Requested-With'];
    delete headers['x-requested-with'];

    return fetch(String(url), {
        method: options.method || 'GET',
        body: options.body ?? null,
        signal: options.signal,
        // Avoid sending `Cache-Control`: not CORS-safelisted; use fetch cache mode instead.
        cache: 'no-store',
        headers,
        // `include` + `Access-Control-Allow-Origin: *` is invalid; many Craft GraphQL setups use `*`.
        // `same-origin` keeps cookies for same-host deployments and avoids credentialed cross-origin
        // fetches (e.g. Vite on localhost → ddev HTTPS) so wildcard CORS can succeed.
        credentials: 'same-origin',
    });
}

export async function requestJson<T>(url: string | URL, options: RequestJsonOptions = {}): Promise<T> {
    const response = await request(url, options);

    if (!response.ok) {
        throw new Error(`Request failed (${response.status}) for ${String(url)}`);
    }

    return response.json() as Promise<T>;
}

export async function requestText(url: string | URL, options: RequestJsonOptions = {}): Promise<string> {
    const response = await request(url, options);

    if (!response.ok) {
        throw new Error(`Request failed (${response.status}) for ${String(url)}`);
    }

    return response.text();
}

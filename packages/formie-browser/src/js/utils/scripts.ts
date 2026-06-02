import { waitFor } from '#utils/async';

type GlobalWindow = Window & Record<string, unknown>;

type LoadExternalScriptOptions = {
    id: string;
    src: string;
    async?: boolean;
    defer?: boolean;
};

const scriptLoadCache = new Map<string, Promise<HTMLScriptElement>>();

export async function ensureGlobal<T = unknown>(globalName: string, timeoutMs = 5000): Promise<T> {
    // Third-party SDKs often attach themselves to `window` after their script tag
    // loads, so callers wait on the global rather than assuming load == ready.
    return waitFor(() => {
        const value = (window as unknown as GlobalWindow)[globalName];

        if (typeof value === 'undefined' || value === null) {
            return null;
        }

        return value as T;
    }, {
        timeoutMs,
        intervalMs: 30,
    });
}

export async function loadExternalScript({
    id,
    src,
    async = true,
    defer = true,
}: LoadExternalScriptOptions): Promise<HTMLScriptElement> {
    const existing = document.getElementById(id) as HTMLScriptElement | null;

    if (existing) {
        return existing;
    }

    // Cache by id so multiple forms/providers can safely request the same SDK
    // without racing duplicate script tags into the page.
    if (!scriptLoadCache.has(id)) {
        scriptLoadCache.set(id, new Promise((resolve, reject) => {
            const script = document.createElement('script');
            script.id = id;
            script.src = src;
            script.async = async;
            script.defer = defer;
            script.onload = () => {
                resolve(script);
            };
            script.onerror = () => {
                scriptLoadCache.delete(id);
                reject(new Error(`Failed to load external script: ${src}`));
            };

            document.body.appendChild(script);
        }));
    }

    return scriptLoadCache.get(id) as Promise<HTMLScriptElement>;
}

export async function loadScriptAndEnsureGlobal<T = unknown>(
    globalName: string,
    options: LoadExternalScriptOptions & {
        timeoutMs?: number;
    },
): Promise<T> {
    const existing = (window as unknown as GlobalWindow)[globalName];

    if (typeof existing !== 'undefined' && existing !== null) {
        return existing as T;
    }

    // Some providers expose one stable global no matter how many forms use them,
    // so load once and then gate on that final ready signal.
    await loadExternalScript(options);

    return ensureGlobal<T>(globalName, options.timeoutMs);
}

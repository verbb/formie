import { browserRequest, type BrowserRequestOptions } from './request-profile';
import { stageTransportFiles } from './uploads';
import { assertClientFormBootstrap } from './contract';
import type {
    ClientFormDefinition,
    ClientFormBootstrap,
    ClientFormSession,
    ClientSubmitResult,
    ClientTransport,
} from './types';
import { serializeTransportFieldValues } from './schema';
import { clearExchangedGrant } from './grants';

export type RestClientTransportOptions = BrowserRequestOptions & {
    /**
     * Craft web root used to build action URLs.
     * Absolute examples: `https://example.test/` or `https://example.test/craft/`.
     * Relative examples: `/` or `/craft`.
     * Must include any subdirectory install path; root-relative action paths are appended.
     */
    endpoint: string;
    formHandle: string;
    siteId?: number;
    credentials?: RequestCredentials;
    grantToken?: string;
    grantPurpose?: 'continue-incomplete' | 'revise-complete';
    draftContext?: string;
    query?: Record<string, string | string[]>;
};

/**
 * Join an install/web base with a root-relative Craft action path.
 * Absolute bases keep their pathname (subdirectory installs); absolute action paths are not treated as origin-only.
 */
export function buildActionUrl(baseUrl: string, path: string): string {
    if (path.startsWith('http://') || path.startsWith('https://')) {
        return path;
    }

    const normalizedPath = path.startsWith('/') ? path : `/${path}`;

    if (baseUrl.startsWith('http://') || baseUrl.startsWith('https://')) {
        const base = new URL(baseUrl);
        const basePath = base.pathname.replace(/\/+$/, '');
        base.pathname = `${basePath}${normalizedPath}`;
        base.search = '';
        base.hash = '';

        return base.toString();
    }

    const normalizedBaseUrl = baseUrl.trim();

    if (!normalizedBaseUrl || normalizedBaseUrl === '/') {
        return normalizedPath;
    }

    return `${normalizedBaseUrl.replace(/\/+$/, '')}${normalizedPath}`;
}

async function requestJson<T>(url: string, init: RequestInit, options: BrowserRequestOptions): Promise<T> {
    const response = await browserRequest(url, init, options);

    const payload = await response.json();
    if (!response.ok && !(typeof payload.outcome === 'string' && [403, 409, 422, 429].includes(response.status))) {
        throw new Error(`Request failed with status ${response.status}.`);
    }

    return payload as T;
}

function appendCsrfToken(body: Record<string, unknown>, session?: ClientFormSession | null): void {
    const csrf = session?.tokens?.csrf;

    if (!csrf?.name || !csrf.value) {
        return;
    }

    body[csrf.name] = csrf.value;
}

export async function loadClientFormBootstrap(options: RestClientTransportOptions): Promise<ClientFormBootstrap> {
    const url = buildActionUrl(options.endpoint, '/actions/formie/client/forms/load');
    const body = JSON.stringify({
        handle: options.formHandle,
        siteId: options.siteId,
        grantToken: options.grantToken,
        grantPurpose: options.grantPurpose,
        draftContext: options.draftContext,
        query: options.query,
    });

    const envelope = await requestJson<ClientFormBootstrap>(url, {
        method: 'POST',
        credentials: options.credentials ?? 'same-origin',
        headers: {
            'Content-Type': 'application/json',
        },
        body,
    }, options);
    assertClientFormBootstrap(envelope);
    clearExchangedGrant(options.grantToken);
    return envelope;
}

export function createRestClientTransport(options: RestClientTransportOptions): ClientTransport {
    return {
        browserRequestOptions: { profile: options.profile ?? 'same-origin-browser', publicSession: options.publicSession },
        async submit({ definition, session, values, action, browserData }): Promise<ClientSubmitResult> {
            const url = buildActionUrl(options.endpoint, '/actions/formie/client/submissions/submit');
            const serializedValues = await serializeTransportFieldValues(definition, await stageTransportFiles(definition, session, values, options));
            const body: Record<string, unknown> = {
                handle: options.formHandle,
                siteId: options.siteId,
                action,
                browserData,
                session,
                values: serializedValues,
            };

            appendCsrfToken(body, session);

            return requestJson<ClientSubmitResult>(url, {
                method: 'POST',
                credentials: options.credentials ?? 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(body),
            }, options);
        },
        async refreshSession({ session }): Promise<ClientFormSession> {
            const url = buildActionUrl(options.endpoint, '/actions/formie/client/sessions/refresh');
            const body: Record<string, unknown> = {
                handle: options.formHandle,
                siteId: options.siteId,
                session,
            };

            appendCsrfToken(body, session);

            return requestJson<ClientFormSession>(url, {
                method: 'POST',
                credentials: options.credentials ?? 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(body),
            }, options);
        },
        async setPage({ definition, session, values, currentPageId, targetPageId }): Promise<ClientSubmitResult> {
            const url = buildActionUrl(options.endpoint, '/actions/formie/client/forms/page');
            const serializedValues = await serializeTransportFieldValues(definition, await stageTransportFiles(definition, session, values, options));
            const body: Record<string, unknown> = {
                handle: options.formHandle,
                siteId: options.siteId,
                currentPageId,
                targetPageId,
                session,
                values: serializedValues,
            };

            appendCsrfToken(body, session);

            return requestJson<ClientSubmitResult>(url, {
                method: 'POST',
                credentials: options.credentials ?? 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(body),
            }, options);
        },
    };
}

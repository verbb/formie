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

export type GraphqlClientTransportOptions = BrowserRequestOptions & {
    endpoint: string;
    formHandle: string;
    siteId?: number;
    credentials?: RequestCredentials;
    grantToken?: string;
    grantPurpose?: 'continue-incomplete' | 'revise-complete';
    draftContext?: string;
    query?: Record<string, string | string[]>;
};

type GraphqlResponse<T> = {
    data?: T;
    errors?: Array<{ message?: string }>;
};

const CLIENT_SESSION_SELECTION = `
    id
    version
    currentPageId
    tokens
    continuation
`;

const CLIENT_SUBMIT_RESULT_SELECTION = `
    success
    outcome
    version
    submissionUid
    resumeToken
    resumeUrl
    resumeTokenExpiresAt
    currentPageId
    nextPageId
    previousPageId
    isFinalPage
    errors
    messages
    clientEvents
    payment
    session {
        ${CLIENT_SESSION_SELECTION}
    }
    quizResult
    completion
    redirect
`;

function buildGraphqlUrl(endpoint: string): string {
    if (endpoint.startsWith('http://') || endpoint.startsWith('https://')) {
        return endpoint;
    }

    const normalizedEndpoint = endpoint.trim();

    if (!normalizedEndpoint || normalizedEndpoint === '/') {
        return '/api';
    }

    return normalizedEndpoint;
}

async function requestGraphql<T>(options: GraphqlClientTransportOptions, query: string, variables: Record<string, unknown>): Promise<T> {
    const response = await browserRequest(buildGraphqlUrl(options.endpoint), {
        method: 'POST',
        // Default `same-origin`: credentialed cross-origin + `Allow-Origin: *` is invalid in browsers.
        credentials: options.credentials ?? 'same-origin',
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
        },
        body: JSON.stringify({
            query,
            variables,
        }),
    }, options);

    if (!response.ok) {
        throw new Error(`Request failed with status ${response.status}.`);
    }

    const payload = await response.json() as GraphqlResponse<T>;

    if (payload.errors?.length) {
        throw new Error(payload.errors[0]?.message || 'GraphQL returned an error.');
    }

    if (!payload.data) {
        throw new Error('GraphQL returned no data.');
    }

    return payload.data;
}

export async function loadGraphqlClientFormBootstrap(options: GraphqlClientTransportOptions): Promise<ClientFormBootstrap> {
    const data = await requestGraphql<{
        formieClientForm?: ClientFormBootstrap | null;
    }>(
        options,
        `
            query ClientForm($handle: String!, $siteId: Int, $grantToken: String, $grantPurpose: String, $draftContext: String, $query: Json) {
                formieClientForm(handle: $handle, siteId: $siteId, grantToken: $grantToken, grantPurpose: $grantPurpose, draftContext: $draftContext, query: $query) {
                    contractVersion
                    definition
                    session {
                        ${CLIENT_SESSION_SELECTION}
                    }
                }
            }
        `,
        {
            handle: options.formHandle,
            siteId: options.siteId,
            grantToken: options.grantToken,
            grantPurpose: options.grantPurpose,
            draftContext: options.draftContext,
            query: options.query,
        },
    );

    if (!data.formieClientForm) {
        throw new Error('No client form definition was returned.');
    }

    assertClientFormBootstrap(data.formieClientForm);
    clearExchangedGrant(options.grantToken);
    return data.formieClientForm;
}

export function createGraphqlClientTransport(options: GraphqlClientTransportOptions): ClientTransport {
    return {
        browserRequestOptions: { profile: options.profile ?? 'same-origin-browser', publicSession: options.publicSession },
        async submit({ definition, session, values, action, browserData }): Promise<ClientSubmitResult> {
            const serializedValues = await serializeTransportFieldValues(definition, await stageTransportFiles(definition, session, values, options));

            const data = await requestGraphql<{
                submitFormieClientForm?: ClientSubmitResult | null;
            }>(
                options,
                `
                    mutation SubmitFormieClientForm(
                        $input: FormieClientSubmitInput!
                    ) {
                        submitFormieClientForm(input: $input) {
                            ${CLIENT_SUBMIT_RESULT_SELECTION}
                        }
                    }
                `,
                {
                    input: {
                        handle: options.formHandle,
                        siteId: options.siteId,
                        action,
                browserData,
                        session,
                        values: serializedValues,
                    },
                },
            );

            if (!data.submitFormieClientForm) {
                throw new Error('No client submit result was returned.');
            }

            return data.submitFormieClientForm;
        },
        async refreshSession({ session }): Promise<ClientFormSession> {
            const data = await requestGraphql<{
                refreshFormieClientSession?: ClientFormSession | null;
            }>(
                options,
                `
                    mutation RefreshFormieClientSession(
                        $input: FormieClientSessionRefreshInput!
                    ) {
                        refreshFormieClientSession(input: $input) {
                            ${CLIENT_SESSION_SELECTION}
                        }
                    }
                `,
                {
                    input: {
                        handle: options.formHandle,
                        siteId: options.siteId,
                        session,
                    },
                },
            );

            if (!data.refreshFormieClientSession) {
                throw new Error('No client session was returned.');
            }

            return data.refreshFormieClientSession;
        },
        async setPage({ definition, session, values, currentPageId, targetPageId }): Promise<ClientSubmitResult> {
            const serializedValues = await serializeTransportFieldValues(definition, await stageTransportFiles(definition, session, values, options));

            const data = await requestGraphql<{
                setFormieClientPage?: ClientSubmitResult | null;
            }>(
                options,
                `
                    mutation SetFormieClientPage(
                        $input: FormieClientSetPageInput!
                    ) {
                        setFormieClientPage(input: $input) {
                            success outcome httpStatus errors messages currentPageId nextPageId version session { ${CLIENT_SESSION_SELECTION} }
                        }
                    }
                `,
                {
                    input: {
                        handle: options.formHandle,
                        siteId: options.siteId,
                        currentPageId,
                        targetPageId,
                        session,
                        values: serializedValues,
                    },
                },
            );

            if (!data.setFormieClientPage) {
                throw new Error('No client page session was returned.');
            }

            return data.setFormieClientPage;
        },
    };
}

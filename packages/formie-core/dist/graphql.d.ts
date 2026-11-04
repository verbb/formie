import { type BrowserRequestOptions } from './request-profile.js';
import type { ClientFormBootstrap, ClientTransport } from './types.js';
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
export declare function loadGraphqlClientFormBootstrap(options: GraphqlClientTransportOptions): Promise<ClientFormBootstrap>;
export declare function createGraphqlClientTransport(options: GraphqlClientTransportOptions): ClientTransport;
//# sourceMappingURL=graphql.d.ts.map
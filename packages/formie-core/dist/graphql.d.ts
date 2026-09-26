import { type BrowserRequestOptions } from './request-profile';
import type { ClientFormBootstrap, ClientTransport } from './types';
export type GraphqlClientTransportOptions = BrowserRequestOptions & {
    endpoint: string;
    formHandle: string;
    siteId?: number;
    credentials?: RequestCredentials;
    grantToken?: string;
    grantPurpose?: 'continue-incomplete' | 'revise-complete';
    draftContext?: string;
};
export declare function loadGraphqlClientFormBootstrap(options: GraphqlClientTransportOptions): Promise<ClientFormBootstrap>;
export declare function createGraphqlClientTransport(options: GraphqlClientTransportOptions): ClientTransport;
//# sourceMappingURL=graphql.d.ts.map
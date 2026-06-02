import type { FrontendFormEnvelope, FrontendTransport } from './types';
export type GraphqlFrontendTransportOptions = {
    endpoint: string;
    formHandle: string;
    siteId?: number;
    credentials?: RequestCredentials;
};
export declare function loadGraphqlFrontendEnvelope(options: GraphqlFrontendTransportOptions): Promise<FrontendFormEnvelope>;
export declare function createGraphqlFrontendTransport(options: GraphqlFrontendTransportOptions): FrontendTransport;
//# sourceMappingURL=graphql.d.ts.map
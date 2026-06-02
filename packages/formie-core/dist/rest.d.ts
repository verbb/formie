import type { FrontendFormEnvelope, FrontendTransport } from './types';
export type RestFrontendTransportOptions = {
    endpoint: string;
    formHandle: string;
    siteId?: number;
    credentials?: RequestCredentials;
};
export declare function loadFrontendEnvelope(options: RestFrontendTransportOptions): Promise<FrontendFormEnvelope>;
export declare function createRestFrontendTransport(options: RestFrontendTransportOptions): FrontendTransport;
//# sourceMappingURL=rest.d.ts.map
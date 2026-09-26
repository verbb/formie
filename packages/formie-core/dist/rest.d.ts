import { type BrowserRequestOptions } from './request-profile';
import type { ClientFormBootstrap, ClientTransport } from './types';
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
};
/**
 * Join an install/web base with a root-relative Craft action path.
 * Absolute bases keep their pathname (subdirectory installs); absolute action paths are not treated as origin-only.
 */
export declare function buildActionUrl(baseUrl: string, path: string): string;
export declare function loadClientFormBootstrap(options: RestClientTransportOptions): Promise<ClientFormBootstrap>;
export declare function createRestClientTransport(options: RestClientTransportOptions): ClientTransport;
//# sourceMappingURL=rest.d.ts.map
export type RequestProfile = 'same-origin-browser' | 'cross-origin-public' | 'trusted-administrative';
export type BrowserRequestOptions = {
    profile?: Exclude<RequestProfile, 'trusted-administrative'>;
    /** Opaque credential returned by a previous public bootstrap, when resuming. */
    publicSession?: string;
};
export declare function browserRequest(url: string, init: RequestInit, options?: BrowserRequestOptions): Promise<Response>;
export declare function browserRequestHeaders(url: string, options?: BrowserRequestOptions, initialHeaders?: HeadersInit): Headers;
//# sourceMappingURL=request-profile.d.ts.map
import type { FrontendFormEnvelope, FrontendFormInstance, FrontendTransport } from './types';
type CreateFrontendFormInstanceOptions = {
    envelope: FrontendFormEnvelope;
    transport: FrontendTransport;
};
export declare function createFrontendFormInstance({ envelope, transport }: CreateFrontendFormInstanceOptions): FrontendFormInstance;
export {};
//# sourceMappingURL=form-instance.d.ts.map
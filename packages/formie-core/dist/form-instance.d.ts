import type { ClientFormBootstrap, ClientFormInstance, ClientTransport } from './types';
type CreateClientFormInstanceOptions = {
    envelope: ClientFormBootstrap;
    transport: ClientTransport;
};
export declare function createClientFormInstance({ envelope, transport }: CreateClientFormInstanceOptions): ClientFormInstance;
export {};
//# sourceMappingURL=form-instance.d.ts.map
import type { ClientFormDefinition, ClientFormSession } from './types';
export type ClientErrorAriaLive = 'polite' | 'assertive' | 'off';
export declare function getClientErrorAriaLive(definition: ClientFormDefinition): ClientErrorAriaLive;
export declare function getClientFieldErrorId(session: ClientFormSession, errorKey: string): string;
//# sourceMappingURL=accessibility.d.ts.map
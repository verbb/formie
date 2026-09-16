import type { FrontendFormDefinition, FrontendFormSession } from './types';
export type FrontendErrorAriaLive = 'polite' | 'assertive' | 'off';
export declare function getFrontendErrorAriaLive(definition: FrontendFormDefinition): FrontendErrorAriaLive;
export declare function getFrontendFieldErrorId(session: FrontendFormSession, errorKey: string): string;
//# sourceMappingURL=accessibility.d.ts.map
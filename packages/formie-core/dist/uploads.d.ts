import { type BrowserRequestOptions } from './request-profile';
import type { ClientFormDefinition, ClientFormSession } from './types';
/** Files are staged before submission; final submits carry scoped attachment capabilities. */
export declare function stageTransportFiles(definition: ClientFormDefinition, session: ClientFormSession, values: Record<string, unknown>, options: BrowserRequestOptions): Promise<Record<string, unknown>>;
//# sourceMappingURL=uploads.d.ts.map
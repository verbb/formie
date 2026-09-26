/** Shared grammar and explicit browser projection evaluation. */
export interface ReferenceExpression {
    raw: string;
    target: string;
    identifier: string;
    selector: string;
    default: string;
    transformerId: string;
    transformerParams: Record<string, string>;
    version: number;
    isValid: boolean;
    diagnostic?: string;
}
export declare function parseReference(raw: string): ReferenceExpression;
export declare function serializeReference(expression: ReferenceExpression): string;
export interface ReferenceDefinition {
    id: string;
    availability: {
        server: boolean;
        browser: boolean;
    };
    selectors?: string[];
    transforms?: string[];
}
export interface ReferenceContext {
    definitions: Record<string, ReferenceDefinition>;
    values: Record<string, unknown>;
    transforms?: Record<string, {
        browser: boolean;
        parameters: string[];
        accepts: (value: unknown) => boolean;
        acceptsOutput: (value: unknown) => boolean;
        resolve: (value: unknown, parameters: Record<string, string>) => unknown;
    }>;
}
export interface ResolvedReference {
    expression: ReferenceExpression;
    value?: unknown;
    diagnostic?: 'invalidExpression' | 'missingField' | 'unknownSource' | 'forbiddenSource' | 'invalidSelector' | 'unknownTransform' | 'invalidType' | 'invalidRowScope';
}
/** Browser evaluation accepts only explicitly supplied browser projections and definitions. */
export declare function resolveReference(raw: string, context: ReferenceContext): ResolvedReference;
//# sourceMappingURL=references.d.ts.map
import type { ConditionEvaluation } from './conditions';
import type { ClientFieldDefinition, ClientFormState } from './types';
type Occurrence = {
    field: ClientFieldDefinition;
    key: string;
    path: string[];
    parent?: string;
};
export declare function evaluateClientCondition(condition: ClientFieldDefinition['condition'], state: ClientFormState, target?: Occurrence): ConditionEvaluation;
export declare function clientActionAllowed(state: ClientFormState): boolean;
export declare function deriveConditionState(state: ClientFormState): ClientFormState;
export {};
//# sourceMappingURL=condition-state.d.ts.map
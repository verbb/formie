import type { ConditionSource } from '#modules/fields/conditions/types';
export declare const SUBMISSION_CONTEXT_ATTR = "data-formie-submission";
export declare const SUBMISSION_CONTEXT_CHANGE_EVENT = "formie:submission-context-change";
/**
 * Find the nearest form/host that carries the submission condition snapshot.
 * Prefer ancestors of `from` (the conditioned node), then fall back to `root`.
 */
export declare function findSubmissionContextHost(root: Element, from?: Element): Element | null;
export declare function readSubmissionContext(root: Element, from?: Element): Record<string, string>;
/**
 * Resolve `{submission:status}` (etc.) from the emitted snapshot.
 * Handle is the property key from PHP (`status`, `title`, `formName`, …).
 */
export declare function readSubmissionConditionValues(root: Element, source: ConditionSource, from?: Element): string[];
//# sourceMappingURL=submission-context.d.ts.map
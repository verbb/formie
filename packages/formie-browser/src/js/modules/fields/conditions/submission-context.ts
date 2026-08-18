import { applyConditionSource } from '#modules/fields/conditions/transforms';
import type { ConditionSource } from '#modules/fields/conditions/types';

export const SUBMISSION_CONTEXT_ATTR = 'data-formie-submission';
export const SUBMISSION_CONTEXT_CHANGE_EVENT = 'formie:submission-context-change';

function isRecord(value: unknown): value is Record<string, unknown> {
    return Boolean(value) && typeof value === 'object' && !Array.isArray(value);
}

/**
 * Find the nearest form/host that carries the submission condition snapshot.
 * Prefer ancestors of `from` (the conditioned node), then fall back to `root`.
 */
export function findSubmissionContextHost(root: Element, from: Element = root): Element | null {
    const closest = from.closest(`[${SUBMISSION_CONTEXT_ATTR}]`);

    if (closest) {
        return closest;
    }

    if (root instanceof Element && root.hasAttribute(SUBMISSION_CONTEXT_ATTR)) {
        return root;
    }

    return root.querySelector(`[${SUBMISSION_CONTEXT_ATTR}]`);
}

export function readSubmissionContext(root: Element, from: Element = root): Record<string, string> {
    const host = findSubmissionContextHost(root, from);
    const raw = host?.getAttribute(SUBMISSION_CONTEXT_ATTR);

    if (!raw) {
        return {};
    }

    try {
        const parsed = JSON.parse(raw) as unknown;

        if (!isRecord(parsed)) {
            return {};
        }

        return Object.fromEntries(Object.entries(parsed).map(([key, value]) => {
            return [key, value == null ? '' : String(value)];
        }));
    } catch (error) {
        console.error('[formie] Invalid submission context JSON.', error);
        return {};
    }
}

/**
 * Resolve `{submission:status}` (etc.) from the emitted snapshot.
 * Handle is the property key from PHP (`status`, `title`, `formName`, …).
 */
export function readSubmissionConditionValues(
    root: Element,
    source: ConditionSource,
    from: Element = root,
): string[] {
    const context = readSubmissionContext(root, from);
    const key = String(source.handle || '').trim();
    let value = '';

    if (key !== '') {
        if (Object.prototype.hasOwnProperty.call(context, key)) {
            value = context[key] ?? '';
        } else {
            // Variables map uses camelCase (`submissionStatus`); tolerate that alias.
            const aliased = `submission${key.charAt(0).toUpperCase()}${key.slice(1)}`;
            value = context[aliased] ?? '';
        }
    }

    if (value === '' && source.defaultValue) {
        value = source.defaultValue;
    }

    return applyConditionSource([value], source);
}

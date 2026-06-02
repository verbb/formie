import { escapeSelectorValue } from '#modules/fields/shared';
import type { ConditionDefinition, ConditionInput, ConditionSource } from '#modules/fields/conditions/types';
import { fieldKeyToInputName } from '#utils/field-references';

export const CONDITION_INPUT_SELECTOR = 'input, select, textarea';

const ROW_SCOPE_SELECTOR = '[data-formie-repeater-item], [data-formie-table-row]';

function isConditionInput(element: Element): element is ConditionInput {
    return element instanceof HTMLInputElement || element instanceof HTMLSelectElement || element instanceof HTMLTextAreaElement;
}

function getNodeRowToken(node: Element): string | null {
    const nodeInput = node.querySelector(CONDITION_INPUT_SELECTOR);

    if (!nodeInput) {
        return null;
    }

    const name = nodeInput.getAttribute('name') || '';
    const tokens = Array.from(name.matchAll(/\[(\d+)\]/g));

    if (!tokens.length) {
        return null;
    }

    return tokens[tokens.length - 1]?.[1] || null;
}

function getRowScope(node: Element): Element | null {
    return node.closest(ROW_SCOPE_SELECTOR);
}

function getFieldInputs(fieldNode: Element): ConditionInput[] {
    return Array.from(fieldNode.querySelectorAll(CONDITION_INPUT_SELECTOR)).filter((element): element is ConditionInput => {
        return isConditionInput(element);
    });
}

function getInputNameTokens(input: ConditionInput): string[] {
    const name = input.getAttribute('name') || '';
    return Array.from(name.matchAll(/\[([^\]]+)\]/g)).map((match) => {
        return match[1] || '';
    }).filter(Boolean);
}

function matchesSelectorPath(input: ConditionInput, selector: string): boolean {
    if (!selector) {
        return true;
    }

    const selectorTokens = selector.split(/[.:]/).filter(Boolean);

    if (!selectorTokens.length) {
        return true;
    }

    const inputTokens = getInputNameTokens(input);

    if (inputTokens.length < selectorTokens.length) {
        return false;
    }

    return selectorTokens.every((token, index) => {
        return inputTokens[inputTokens.length - selectorTokens.length + index] === token;
    });
}

function filterInputsBySelector(inputs: ConditionInput[], selector: string): ConditionInput[] {
    if (!selector) {
        return inputs;
    }

    const matchedInputs = inputs.filter((input) => {
        return matchesSelectorPath(input, selector);
    });

    return matchedInputs.length ? matchedInputs : inputs;
}

function preferSameRow<TElement extends Element>(targetNode: Element, candidates: TElement[]): TElement[] {
    const targetRow = getRowScope(targetNode);

    if (!targetRow) {
        return candidates;
    }

    const sameRowCandidates = candidates.filter((candidate) => {
        return getRowScope(candidate) === targetRow;
    });

    return sameRowCandidates.length ? sameRowCandidates : candidates;
}

export function resolveConditionSource(condition: ConditionDefinition): ConditionSource | null {
    if (condition.source?.target === 'field' && condition.source.handle) {
        return condition.source;
    }

    return null;
}

export function queryConditionInputs(
    root: Element,
    targetNode: Element,
    condition: ConditionDefinition,
): ConditionInput[] {
    const source = resolveConditionSource(condition);

    if (!source || source.target !== 'field' || !source.handle) {
        return [];
    }

    const escapedFieldHandle = escapeSelectorValue(source.handle);
    const fieldMatches = Array.from(root.querySelectorAll(`[data-formie-field-handle="${escapedFieldHandle}"]`));

    if (fieldMatches.length) {
        return preferSameRow(targetNode, fieldMatches).flatMap((fieldNode) => {
            return filterInputsBySelector(getFieldInputs(fieldNode), source.selector);
        });
    }

    const sourceInputName = fieldKeyToInputName(source.handle);
    const exactName = escapeSelectorValue(sourceInputName);
    const direct = Array.from(root.querySelectorAll(`[name="${exactName}"]`)).filter((element): element is ConditionInput => {
        return isConditionInput(element);
    });
    const multi = Array.from(root.querySelectorAll(`[name="${exactName}[]"]`)).filter((element): element is ConditionInput => {
        return isConditionInput(element);
    });

    if (direct.length || multi.length) {
        return preferSameRow(targetNode, [...direct, ...multi]);
    }

    if (!source.handle.includes('__ROW__')) {
        return [];
    }

    const rowToken = getNodeRowToken(targetNode);

    if (rowToken) {
        const rowFieldName = fieldKeyToInputName(source.handle.replace(/__ROW__/g, rowToken));
        const escapedRowFieldName = escapeSelectorValue(rowFieldName);
        const rowDirect = Array.from(root.querySelectorAll(`[name="${escapedRowFieldName}"]`)).filter((element): element is ConditionInput => {
            return isConditionInput(element);
        });
        const rowMulti = Array.from(root.querySelectorAll(`[name="${escapedRowFieldName}[]"]`)).filter((element): element is ConditionInput => {
            return isConditionInput(element);
        });

        if (rowDirect.length || rowMulti.length) {
            return [...rowDirect, ...rowMulti];
        }
    }

    const regexString = fieldKeyToInputName(source.handle)
        .replace(/[.*+?^${}()|[\]\\]/g, '\\$&')
        .replace(/__ROW__/g, '\\d+');
    const regex = new RegExp(regexString);

    return Array.from(root.querySelectorAll('[name]')).filter((element): element is ConditionInput => {
        return isConditionInput(element) && regex.test(element.getAttribute('name') || '');
    });
}

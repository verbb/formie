import {
    coerceCalculationVariables,
    evaluateCalculationExpression,
    getCalculationFormula,
    getCalculationVariableEntries,
    readCalculationVariableValue,
    type CalculationOptions,
    type CalculationVariable,
    type CalculationVariableEntry,
} from '@verbb/formie-core';

import type { BrowserModuleDefinition } from '#contracts/modules';
import { dispatchFieldEvent, getModuleFieldContainers } from '#modules/fields/shared';
import { buildFieldValueRegistry, fieldKeyToInputName, inputNameToFieldKey, normalizeFieldKey, resolveFieldReferenceLive } from '#utils/field-references';
import { getRowScopedWatchNames, resolveRowScopedFieldReference, type RowScopeParams } from '#utils/field-references.row-scope';
import { createDebug } from '#utils/debug';

const INPUT_SELECTOR = 'input[data-formie-calculation-input]';

// Conditions clear a hidden field's answer on the server, so a calculation doesn't read it either.
const UNAVAILABLE_SOURCE_SELECTOR = '[data-formie-conditionally-hidden], [data-formie-conditionally-disabled], [data-formie-conditions-disabled]';

function getRowScopeParams(variable: CalculationVariable): RowScopeParams {
    const scoped = variable as CalculationVariable & RowScopeParams;

    return {
        scope: scoped.scope,
        index: scoped.index,
        rows: scoped.rows,
        fieldKind: scoped.fieldKind,
    };
}
const MODULE_ID = 'calculations';

type RowContext = { handle: string; row: string };

// A Calculations field in a Repeater row is named like `fields[items][2][total]`.
function getRowContext(input: HTMLInputElement): RowContext | null {
    const parts = inputNameToFieldKey(input.name).split('.').filter(Boolean);

    return parts.length >= 3 ? { handle: parts[0], row: parts[1] } : null;
}

// A source from the calculation's own Repeater, such as `items.qty`, reads the calculation's row (`items.2.qty`).
// Row scopes such as `scope:all` still read across rows.
function sourceKeyForRow(sourceKey: string, row: RowContext | null): string {
    const parts = normalizeFieldKey(sourceKey).split('.').filter(Boolean);

    if (!row || parts.length < 2 || parts[0] !== row.handle || /^\d+$/.test(parts[1] || '')) {
        return sourceKey;
    }

    return [parts[0], row.row, ...parts.slice(1)].join('.');
}
const debug = createDebug('fields', 'calculations');

function resolveVariables(
    root: Element,
    variableEntries: CalculationVariableEntry[],
    options: CalculationOptions,
    row: RowContext | null = null,
): Record<string, unknown> {
    const registry = buildFieldValueRegistry(root);
    const variables: Record<string, unknown> = {};

    registry.forEach((entry, key) => {
        entry.inputs = entry.inputs.filter((input) => !input.closest(UNAVAILABLE_SOURCE_SELECTOR));

        if (!entry.inputs.length) {
            registry.delete(key);
        }
    });

    variableEntries.forEach(([variableKey, variable]) => {
        const scopeParams = getRowScopeParams(variable);
        const scope = String(scopeParams.scope || '').trim();

        if (scope) {
            const resolved = resolveRowScopedFieldReference(variable.sourceKey || '', scopeParams, registry);
            variables[variableKey] = readCalculationVariableValue(variable, resolved.value);
            return;
        }

        const resolved = resolveFieldReferenceLive(sourceKeyForRow(variable.sourceKey || '', row), registry);
        variables[variableKey] = readCalculationVariableValue(variable, resolved.value);
    });

    return coerceCalculationVariables(variables, options.formatting);
}

function getWatchNames(root: Element, variableEntries: CalculationVariableEntry[], row: RowContext | null = null): Set<string> {
    const registry = buildFieldValueRegistry(root);
    const watchNames = new Set<string>();

    variableEntries.forEach(([, variable]) => {
        const scopeParams = getRowScopeParams(variable);
        const scope = String(scopeParams.scope || '').trim();

        if (scope) {
            getRowScopedWatchNames(variable.sourceKey || '', scopeParams, registry).forEach((name) => {
                watchNames.add(name);
            });

            return;
        }

        const key = normalizeFieldKey(sourceKeyForRow(variable.sourceKey || '', row));
        const entry = registry.get(key);

        if (entry?.names?.length) {
            entry.names.forEach((name) => {
                watchNames.add(name);
            });

            return;
        }

        const fallback = fieldKeyToInputName(key);
        if (fallback) {
            watchNames.add(fallback);
            watchNames.add(`${fallback}[]`);
        }
    });

    return watchNames;
}

type CalculationsBinding = { destroy: () => void; reevaluate: () => void };

function bindCalculationsField(root: Element, field: HTMLElement, input: HTMLInputElement, options: CalculationOptions): CalculationsBinding {
    const formula = getCalculationFormula(options);
    const variableEntries = getCalculationVariableEntries(options);
    const row = getRowContext(input);
    const sourceBindings = new Map<EventTarget, Map<string, EventListener>>();
    let observer: MutationObserver | null = null;
    let destroyed = false;
    let evaluateQueued = false;
    let rebindQueued = false;

    const cleanupBindings = () => {
        sourceBindings.forEach((listeners, target: EventTarget) => {
            listeners.forEach((listener, eventName) => {
                (target as Element).removeEventListener(eventName, listener);
            });
        });

        sourceBindings.clear();
    };

    const dispatchCalculatedValueChanged = (valueChanged: boolean) => {
        if (!valueChanged || destroyed) {
            return;
        }

        queueMicrotask(() => {
            if (destroyed) {
                return;
            }

            input.dispatchEvent(new Event('input', { bubbles: true }));
            input.dispatchEvent(new Event('change', { bubbles: true }));
        });
    };

    const evaluate = (isInit = false) => {
        const variables = resolveVariables(root, variableEntries, options, row);
        debug.log('Evaluate requested.', {
            fieldHandle: field.getAttribute('data-formie-field-handle') || null,
            isInit,
        });
        const beforeDetail = {
            calculations: input,
            init: isInit,
            formula,
            variables,
        };

        dispatchFieldEvent(field, MODULE_ID, 'before-evaluate', beforeDetail);

        if (!beforeDetail.formula) {
            const valueChanged = input.value !== '';
            input.value = '';
            dispatchCalculatedValueChanged(valueChanged);
            return;
        }

        try {
            const result = evaluateCalculationExpression(beforeDetail.formula, beforeDetail.variables, options);

            const afterDetail = {
                calculations: input,
                init: isInit,
                formula: beforeDetail.formula,
                variables: beforeDetail.variables,
                result,
            };

            dispatchFieldEvent(field, MODULE_ID, 'after-evaluate', afterDetail);
            const nextValue = typeof afterDetail.result === 'string' || typeof afterDetail.result === 'number'
                ? String(afterDetail.result)
                : '';
            const valueChanged = input.value !== nextValue;
            input.value = nextValue;
            debug.log('Evaluate complete.', {
                fieldHandle: field.getAttribute('data-formie-field-handle') || null,
                valueChanged,
                nextValue,
            });
            dispatchCalculatedValueChanged(valueChanged);
        } catch (error) {
            const valueChanged = input.value !== '';
            console.error('[formie] Failed to evaluate calculation.', error);
            debug.warn('Evaluate failed.', {
                fieldHandle: field.getAttribute('data-formie-field-handle') || null,
                error: error instanceof Error ? error.message : error,
            });
            input.value = '';
            dispatchCalculatedValueChanged(valueChanged);
        }
    };

    const scheduleEvaluate = (isInit = false) => {
        if (evaluateQueued || destroyed) {
            return;
        }

        evaluateQueued = true;

        queueMicrotask(() => {
            evaluateQueued = false;
            evaluate(isInit);
        });
    };

    const bindSources = () => {
        cleanupBindings();
        const watchNames = getWatchNames(root, variableEntries, row);
        debug.log('Binding variable watchers.', {
            fieldHandle: field.getAttribute('data-formie-field-handle') || null,
            watchCount: watchNames.size,
        });

        if (!watchNames.size) {
            return;
        }

        const listener: EventListener = (event) => {
            const target = event.target as HTMLInputElement | HTMLTextAreaElement | HTMLSelectElement | null;
            const name = target?.name || '';

            if (!name || !watchNames.has(name)) {
                return;
            }

            debug.log('Source change detected.', {
                fieldHandle: field.getAttribute('data-formie-field-handle') || null,
                sourceName: name,
                eventType: event.type,
            });
            scheduleEvaluate(false);
        };

        ['input', 'change'].forEach((eventName) => {
            root.addEventListener(eventName, listener);
            const listeners = sourceBindings.get(root) || new Map<string, EventListener>();
            listeners.set(eventName, listener);
            sourceBindings.set(root, listeners);
        });
    };

    const scheduleRebind = () => {
        if (rebindQueued || destroyed) {
            return;
        }

        rebindQueued = true;

        queueMicrotask(() => {
            rebindQueued = false;
            bindSources();
            scheduleEvaluate(false);
        });
    };

    bindSources();
    observer = new MutationObserver(() => {
        scheduleRebind();
    });
    observer.observe(root, {
        childList: true,
        subtree: true,
    });
    evaluate(true);

    return {
        destroy: () => {
            destroyed = true;
            observer?.disconnect();
            cleanupBindings();
        },
        reevaluate: () => scheduleEvaluate(false),
    };
}

export const calculationsModule: BrowserModuleDefinition = {
    moduleId: `formie:${MODULE_ID}`,
    version: 2,
    surfaces: ['server-rendered', 'client-rendered', 'cp-edit'],
    kind: 'field',
    match: (ctx) => {
        return !!ctx.target.querySelector(INPUT_SELECTOR);
    },
    setup: async(ctx) => {
        const options = (ctx.options || {}) as CalculationOptions;
        const fields = getModuleFieldContainers(ctx);
        debug.log('Module setup.', {
            fieldCount: fields.length,
            formatting: options.formatting || null,
        });
        const bindings = fields.flatMap((field) => {
            const input = field.querySelector(INPUT_SELECTOR);

            return input instanceof HTMLInputElement ? [bindCalculationsField(ctx.root, field, input, options)] : [];
        });

        // Showing or hiding a source changes what a calculation reads without an input event.
        const offConditions = ctx.on('formie:conditions:evaluated', () => {
            bindings.forEach((binding) => binding.reevaluate());
        });

        await ctx.emit('formie:module:calculations:init', {
            count: bindings.length,
        });

        return {
            destroy: () => {
                debug.log('Module destroy.', {
                    fieldCount: bindings.length,
                });
                offConditions();
                bindings.forEach((binding) => {
                    binding.destroy();
                });

                void ctx.emit('formie:module:calculations:destroy', {});
            },
        };
    },
};

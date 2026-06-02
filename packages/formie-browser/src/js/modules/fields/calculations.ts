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

import type { FormieModuleDefinition } from '#contracts/modules';
import { dispatchFieldEvent, getModuleFieldContainers } from '#modules/fields/shared';
import { buildFieldValueRegistry, fieldKeyToInputName, normalizeFieldKey, resolveFieldReferenceLive } from '#utils/field-references';
import { createDebug } from '#utils/debug';

const INPUT_SELECTOR = 'input[data-formie-calculation-input]';
const MODULE_ID = 'calculations';
const debug = createDebug('fields', 'calculations');

function resolveVariables(
    root: Element,
    variableEntries: CalculationVariableEntry[],
    options: CalculationOptions,
): Record<string, unknown> {
    const registry = buildFieldValueRegistry(root);
    const variables: Record<string, unknown> = {};

    variableEntries.forEach(([variableKey, variable]) => {
        const resolved = resolveFieldReferenceLive(variable.sourceKey || '', registry);
        variables[variableKey] = readCalculationVariableValue(variable, resolved.value);
    });

    return coerceCalculationVariables(variables, options.formatting);
}

function getWatchNames(root: Element, variableEntries: CalculationVariableEntry[]): Set<string> {
    const registry = buildFieldValueRegistry(root);
    const watchNames = new Set<string>();

    variableEntries.forEach(([, variable]) => {
        const key = normalizeFieldKey(variable.sourceKey || '');
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

function bindCalculationsField(root: Element, field: HTMLElement, input: HTMLInputElement, options: CalculationOptions): () => void {
    const formula = getCalculationFormula(options);
    const variableEntries = getCalculationVariableEntries(options);
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
        const variables = resolveVariables(root, variableEntries, options);
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
        const watchNames = getWatchNames(root, variableEntries);
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

    return () => {
        destroyed = true;
        observer?.disconnect();
        cleanupBindings();
    };
}

export const calculationsModule: FormieModuleDefinition = {
    id: MODULE_ID,
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
        const cleanups = fields.map((field) => {
            const input = field.querySelector(INPUT_SELECTOR);
            if (!(input instanceof HTMLInputElement)) {
                return () => {};
            }

            return bindCalculationsField(ctx.root, field, input, options);
        });

        await ctx.emit('formie:module:calculations:init', {
            count: cleanups.length,
        });

        return {
            destroy: () => {
                debug.log('Module destroy.', {
                    fieldCount: cleanups.length,
                });
                cleanups.forEach((cleanup) => {
                    cleanup();
                });

                void ctx.emit('formie:module:calculations:destroy', {});
            },
        };
    },
};

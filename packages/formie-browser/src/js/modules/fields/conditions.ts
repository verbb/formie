import type { BrowserModuleDefinition } from '#contracts/modules';
import { CONDITION_SELECTOR, getConditionNodes, parseConditionSettings } from '#modules/fields/conditions/config';
import { applyConditionVisibility } from '#modules/fields/conditions/effects';
import { evaluateConditionSettings } from '#modules/fields/conditions/evaluator';
import { queryConditionInputs } from '#modules/fields/conditions/references';
import {
    SUBMISSION_CONTEXT_ATTR,
    SUBMISSION_CONTEXT_CHANGE_EVENT,
} from '#modules/fields/conditions/submission-context';
import type { ConditionEntry, ConditionInput } from '#modules/fields/conditions/types';
import { getConditionInputEventNames } from '#modules/fields/conditions/values';
import { createDebug } from '#utils/debug';

const debug = createDebug('conditions');

function uniqueConditionInputs(inputs: ConditionInput[]): ConditionInput[] {
    const seenInputs = new Set<ConditionInput>();

    return inputs.filter((input) => {
        if (seenInputs.has(input)) {
            return false;
        }

        seenInputs.add(input);
        return true;
    });
}

export const conditionsModule: BrowserModuleDefinition = {
    moduleId: 'formie:conditions',
    version: 1,
    surfaces: ['server-rendered', 'client-rendered', 'cp-edit'],
    kind: 'field',
    match: (ctx) => {
        return ctx.target instanceof HTMLElement && (
            ctx.target.matches(CONDITION_SELECTOR) ||
            !!ctx.target.querySelector(CONDITION_SELECTOR)
        );
    },
    setup: async(ctx) => {
        const scopeRoot = ctx.target instanceof HTMLElement ? ctx.target : ctx.root;

        if (!getConditionNodes(scopeRoot).length) {
            debug.log('No condition nodes in scope.');
            return;
        }

        const sourceUnbinds: Array<() => void> = [];
        let entries: ConditionEntry[] = [];
        let cycles = new Set<Element>();
        let evaluationQueued = false;
        let rebuildQueued = false;

        const cleanupSourceBindings = (): void => {
            sourceUnbinds.forEach((unbind) => {
                unbind();
            });
            sourceUnbinds.length = 0;
        };

        const buildEntries = (): ConditionEntry[] => {
            return getConditionNodes(scopeRoot).flatMap((node) => {
                const settings = parseConditionSettings(node);

                if (!settings) {
                    return [];
                }

                const sourceInputs = uniqueConditionInputs(settings.conditions.flatMap((condition) => {
                    return queryConditionInputs(scopeRoot, node, condition);
                }));

                return [{
                    node,
                    settings,
                    sourceInputs,
                }];
            });
        };

        const orderEntries = (items: ConditionEntry[]): ConditionEntry[] => {
            const byNode = new Map(items.map((entry) => [entry.node, entry]));
            const visited = new Set<Element>();
            const visiting: Element[] = [];
            const ordered: ConditionEntry[] = [];
            cycles = new Set();
            const visit = (entry: ConditionEntry): void => {
                if (visited.has(entry.node)) return;
                const index = visiting.indexOf(entry.node);
                if (index >= 0) { visiting.slice(index).forEach((node) => cycles.add(node)); return; }
                visiting.push(entry.node);
                const ancestors = (node: Element | null) => {
                    for (let current = node?.closest(CONDITION_SELECTOR); current; current = current.parentElement?.closest(CONDITION_SELECTOR)) {
                        const dependency = byNode.get(current);
                        if (dependency) visit(dependency);
                    }
                };
                ancestors(entry.node.parentElement);
                entry.sourceInputs.forEach((input) => ancestors(input));
                visiting.pop();
                visited.add(entry.node);
                ordered.push(entry);
            };
            items.forEach(visit);
            if (cycles.size) debug.warn('Condition dependency cycle.', { count: cycles.size });
            return ordered;
        };

        const cpDisplayMode = ctx.options?.cpDisplayMode === 'muted' ? 'muted' : 'hide';
        let isApplyingConditions = false;

        const runEvaluationPass = (): boolean => {
            let hasStateChanges = false;

            entries.forEach((entry) => {
                const result = cycles.has(entry.node) ? { finalResult: false, shouldHide: ['show', 'enable'].includes(entry.settings.showRule), diagnostics: [{ code: 'dependencyCycle' }] } : evaluateConditionSettings(entry.settings, (condition) => {
                    return queryConditionInputs(scopeRoot, entry.node, condition);
                }, {
                    root: scopeRoot,
                    from: entry.node,
                });

                const displayMode = entry.node.hasAttribute('data-formie-page')
                    ? 'hide'
                    : cpDisplayMode;

                const stateChanged = applyConditionVisibility(
                    entry.node,
                    result.shouldHide,
                    entry.settings.clearOnHide,
                    { displayMode, disabledOnly: ['enable', 'disable'].includes(entry.settings.showRule) },
                );
                hasStateChanges = hasStateChanges || stateChanged;
                debug.log('Condition evaluated.', {
                    shouldHide: result.shouldHide,
                    finalResult: result.finalResult,
                    stateChanged,
                });

                void ctx.emit('formie:conditions:evaluated', {
                    node: entry.node,
                    shouldHide: result.shouldHide,
                    finalResult: result.finalResult,
                    clearOnHide: entry.settings.clearOnHide,
                    diagnostics: cycles.has(entry.node) ? [{ code: 'dependencyCycle' }] : [],
                });
            });

            return hasStateChanges;
        };

        const evaluateAll = (): void => {
            if (isApplyingConditions) {
                return;
            }

            isApplyingConditions = true;

            try {
                runEvaluationPass();
            } finally {
                isApplyingConditions = false;
            }
        };

        const scheduleEvaluateAll = (): void => {
            if (isApplyingConditions) {
                return;
            }

            if (evaluationQueued) {
                return;
            }

            evaluationQueued = true;

            requestAnimationFrame(() => {
                evaluationQueued = false;

                if (isApplyingConditions) {
                    return;
                }

                evaluateAll();
            });
        };

        const bindSourceInputs = (): void => {
            uniqueConditionInputs(entries.flatMap((entry) => {
                return entry.sourceInputs;
            })).forEach((input) => {
                const handler = () => {
                    scheduleEvaluateAll();
                };

                getConditionInputEventNames(input).forEach((eventName) => {
                    input.addEventListener(eventName, handler);
                });

                sourceUnbinds.push(() => {
                    getConditionInputEventNames(input).forEach((eventName) => {
                        input.removeEventListener(eventName, handler);
                    });
                });
            });

            if (ctx.form) {
                const resetHandler = () => {
                    window.setTimeout(() => {
                        scheduleEvaluateAll();
                    }, 0);
                };

                ctx.form.addEventListener('reset', resetHandler);
                sourceUnbinds.push(() => {
                    ctx.form?.removeEventListener('reset', resetHandler);
                });

                // CP status menu (etc.) updates `data-formie-submission` and fires this event.
                const submissionContextHandler = () => {
                    scheduleEvaluateAll();
                };

                ctx.form.addEventListener(SUBMISSION_CONTEXT_CHANGE_EVENT, submissionContextHandler);
                sourceUnbinds.push(() => {
                    ctx.form?.removeEventListener(SUBMISSION_CONTEXT_CHANGE_EVENT, submissionContextHandler);
                });
            }
        };

        const rebuild = (): void => {
            cleanupSourceBindings();
            entries = orderEntries(buildEntries());
            bindSourceInputs();
            debug.log('Rebuilt condition graph.', {
                entryCount: entries.length,
            });
            scheduleEvaluateAll();
        };

        const scheduleRebuild = (): void => {
            if (isApplyingConditions) {
                return;
            }

            if (rebuildQueued) {
                return;
            }

            rebuildQueued = true;

            requestAnimationFrame(() => {
                rebuildQueued = false;

                if (isApplyingConditions) {
                    return;
                }

                rebuild();
            });
        };

        const observer = new MutationObserver((mutations) => {
            if (isApplyingConditions) {
                return;
            }

            const shouldRebuild = mutations.some((mutation) => {
                return mutation.type === 'childList' && (mutation.addedNodes.length > 0 || mutation.removedNodes.length > 0);
            });
            const shouldEvaluate = mutations.some((mutation) => {
                return mutation.type === 'attributes';
            });

            if (shouldRebuild) {
                scheduleRebuild();
            } else if (shouldEvaluate) {
                scheduleEvaluateAll();
            }
        });

        observer.observe(scopeRoot, {
            childList: true,
            subtree: true,
            attributes: true,
            attributeFilter: [
                'hidden',
                'aria-hidden',
                'data-formie-conditionally-hidden',
                'data-formie-page-hidden',
                'data-formie-row-hidden',
                SUBMISSION_CONTEXT_ATTR,
            ],
        });

        rebuild();

        await ctx.emit('formie:module:conditions:init', {
            count: entries.length,
        });
        debug.log('Module setup complete.', { entryCount: entries.length });

        return {
            destroy: () => {
                cleanupSourceBindings();
                observer.disconnect();
                debug.log('Module destroy.');
                void ctx.emit('formie:module:conditions:destroy', {});
            },
        };
    },
};

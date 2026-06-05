import type { FormieModuleDefinition } from '#contracts/modules';
import { CONDITION_SELECTOR, getConditionNodes, parseConditionSettings } from '#modules/fields/conditions/config';
import { applyConditionVisibility } from '#modules/fields/conditions/effects';
import { evaluateConditionSettings } from '#modules/fields/conditions/evaluator';
import { queryConditionInputs } from '#modules/fields/conditions/references';
import type { ConditionEntry, ConditionInput } from '#modules/fields/conditions/types';
import { getConditionInputEventNames } from '#modules/fields/conditions/values';
import { createDebug } from '#utils/debug';

const MAX_EVALUATION_PASSES = 4;
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

export const conditionsModule: FormieModuleDefinition = {
    id: 'conditions',
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

                if (!settings || !settings.conditions.length) {
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

        const cpDisplayMode = ctx.options?.cpDisplayMode === 'muted' ? 'muted' : 'hide';
        let isApplyingConditions = false;

        const runEvaluationPass = (): boolean => {
            let hasStateChanges = false;

            entries.forEach((entry) => {
                const result = evaluateConditionSettings(entry.settings, (condition) => {
                    return queryConditionInputs(scopeRoot, entry.node, condition);
                });

                const displayMode = entry.node.hasAttribute('data-formie-page')
                    ? 'hide'
                    : cpDisplayMode;

                const stateChanged = applyConditionVisibility(
                    entry.node,
                    result.shouldHide,
                    entry.settings.clearOnHide,
                    { displayMode },
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
                for (let pass = 0; pass < MAX_EVALUATION_PASSES; pass += 1) {
                    if (!runEvaluationPass()) {
                        break;
                    }

                    if (pass === MAX_EVALUATION_PASSES - 1) {
                        debug.warn('Reached max evaluation passes.', { maxPasses: MAX_EVALUATION_PASSES });
                    }
                }
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
            }
        };

        const rebuild = (): void => {
            cleanupSourceBindings();
            entries = buildEntries();
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

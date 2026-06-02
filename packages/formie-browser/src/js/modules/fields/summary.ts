import summaryCss from '#theme/fields/_summary.css?inline';

import type { FormieModuleDefinition } from '#contracts/modules';
import { dispatchFieldEvent, getModuleFieldContainers } from '#modules/fields/shared';
import { ensureModuleStyles } from '#modules/styles';
import { toggleThemeClasses } from '#theme/theme-classes';
import { debounce } from '#utils/async';
import { createDebug } from '#utils/debug';
import { requestText } from '#utils/http';

const BLOCKS_SELECTOR = '[data-formie-summary-blocks]';
const CONTAINER_SELECTOR = '[data-formie-summary-container]';
const SUMMARY_ACTION = 'formie/fields/get-summary-html';
const MODULE_ID = 'summary';
const debug = createDebug('fields', 'summary');

ensureModuleStyles(MODULE_ID, [summaryCss]);

type SummaryRequestState = {
    accessToken: string | null;
};

function getSummaryRequestUrl(): string {
    const url = new URL(window.location.href);
    url.hash = '';

    return url.toString();
}

function getSummaryRequestState(field: HTMLElement): SummaryRequestState {
    const summaryTokenInput = field.querySelector('[data-formie-summary-token]') as HTMLInputElement | null;
    const accessToken = summaryTokenInput?.value?.trim() || null;

    return {
        accessToken,
    };
}

async function requestSummaryHtml(form: HTMLFormElement, state: SummaryRequestState, signal?: AbortSignal): Promise<string> {
    if (!state.accessToken) {
        throw new Error('Summary field requires an access token.');
    }

    // Summary output is server-rendered from the current submission state rather
    // than rebuilt in JS, so it stays aligned with backend formatting rules.
    const formData = new FormData(form);
    formData.set('action', SUMMARY_ACTION);
    formData.set('accessToken', state.accessToken);

    return requestText(getSummaryRequestUrl(), {
        method: 'POST',
        body: formData,
        signal,
        headers: {
            Accept: 'text/html',
        },
    });
}

function initSummaryField(field: HTMLElement, root: Element): () => void {
    const form = field.closest('form');

    if (!(form instanceof HTMLFormElement)) {
        debug.warn('Missing form ancestor; skipping field.');
        return () => {};
    }

    let hasFetched = false;
    let isDirty = true;
    let isVisible = false;
    let dirtyVersion = 0;
    let requestVersion = 0;
    let activeRequest: AbortController | null = null;

    const getBlocks = (): HTMLElement | null => {
        const blocks = field.querySelector(BLOCKS_SELECTOR);
        return blocks instanceof HTMLElement ? blocks : null;
    };

    const getContainer = (): HTMLElement | null => {
        const container = field.querySelector(CONTAINER_SELECTOR);
        return container instanceof HTMLElement ? container : null;
    };

    const setLoadingState = (isLoading: boolean): void => {
        const blocks = getBlocks();

        if (!blocks) {
            return;
        }

        if (isLoading) {
            blocks.setAttribute('data-formie-loading', 'true');
            blocks.setAttribute('aria-busy', 'true');
            toggleThemeClasses(blocks, form, 'loading', true);
            return;
        }

        blocks.removeAttribute('data-formie-loading');
        blocks.removeAttribute('aria-busy');
        toggleThemeClasses(blocks, form, 'loading', false);
    };

    const initialState = getSummaryRequestState(field);
    setLoadingState(!!initialState.accessToken);

    const queueFetch = (): void => {
        if (!isVisible || (hasFetched && !isDirty)) {
            return;
        }

        debug.log('Queueing fetch.');
        void fetchSummary();
    };

    const fetchSummary = debounce(async() => {
        const state = getSummaryRequestState(field);

        if (!getBlocks() || !state.accessToken) {
            debug.warn('Missing state for fetch.', state);
            setLoadingState(false);
            return;
        }

        requestVersion += 1;
        const currentRequestVersion = requestVersion;
        const requestDirtyVersion = dirtyVersion;
        activeRequest?.abort();
        activeRequest = new AbortController();
        setLoadingState(true);

        try {
            const html = await requestSummaryHtml(form, state, activeRequest.signal);

            if (currentRequestVersion !== requestVersion) {
                return;
            }

            const container = getContainer();
            const nextMarkup = document.createElement('template');
            nextMarkup.innerHTML = html.trim();
            const nextContainer = nextMarkup.content.querySelector(CONTAINER_SELECTOR);

            if (container && nextContainer instanceof HTMLElement) {
                container.replaceWith(nextContainer);
            } else if (container) {
                container.innerHTML = html;
            }

            hasFetched = true;
            isDirty = dirtyVersion !== requestDirtyVersion;
            debug.log('Fetch complete.', {
                isDirty,
                dirtyVersion,
                requestVersion: currentRequestVersion,
            });
            dispatchFieldEvent(field, MODULE_ID, 'fetch-summary', {
                summary: field,
                html,
            });
        } catch (error) {
            if (error instanceof DOMException && error.name === 'AbortError') {
                debug.log('Fetch aborted.');
                return;
            }

            console.error('[formie] Failed to load summary field HTML.', error);
        } finally {
            if (currentRequestVersion === requestVersion) {
                setLoadingState(false);
                activeRequest = null;

                if (isDirty) {
                    queueFetch();
                }
            }
        }
    }, 300);

    const markDirty = (event?: Event): void => {
        const target = event?.target;

        if (target instanceof Node && field.contains(target)) {
            return;
        }

        isDirty = true;
        dirtyVersion += 1;
        debug.log('Marked dirty.', { dirtyVersion });
    };

    const handleFieldMutation = (event: Event): void => {
        markDirty(event);
        queueFetch();
    };

    const handleSubmitResult = (): void => {
        isDirty = true;
        debug.log('Submit result received; refreshing.');
        queueFetch();
    };

    const handlePageNavigate = (): void => {
        isDirty = true;
        debug.log('Page navigation received; refreshing.');
        queueFetch();
    };

    // Defer the summary fetch until the field is near view. This avoids extra
    // requests for hidden pages or summaries the user never reaches.
    const observer = new IntersectionObserver((entries) => {
        isVisible = !!entries[0]?.isIntersecting;

        if (!isVisible) {
            return;
        }

        debug.log('Field became visible.');
        dispatchFieldEvent(field, MODULE_ID, 'field-visible', {
            summary: field,
        });
        queueFetch();
    }, {
        root: form,
        rootMargin: '50px',
    });

    observer.observe(field);
    form.addEventListener('input', handleFieldMutation);
    form.addEventListener('change', handleFieldMutation);
    root.addEventListener('formie:page:navigate:after', handlePageNavigate as EventListener);
    root.addEventListener('formie:submit:result', handleSubmitResult as EventListener);

    return () => {
        activeRequest?.abort();
        observer.disconnect();
        form.removeEventListener('input', handleFieldMutation);
        form.removeEventListener('change', handleFieldMutation);
        root.removeEventListener('formie:page:navigate:after', handlePageNavigate as EventListener);
        root.removeEventListener('formie:submit:result', handleSubmitResult as EventListener);
        debug.log('Field destroyed.');
    };
}

export const summaryModule: FormieModuleDefinition = {
    id: MODULE_ID,
    kind: 'field',
    match: (ctx) => {
        return !!ctx.target.querySelector(BLOCKS_SELECTOR);
    },
    setup: async(ctx) => {
        const cleanups = getModuleFieldContainers(ctx).map((field) => {
            return initSummaryField(field, ctx.root);
        });
        debug.log('Module setup.', { fieldCount: cleanups.length });

        await ctx.emit('formie:module:summary:init', {
            count: cleanups.length,
        });

        return {
            destroy: () => {
                cleanups.forEach((cleanup) => {
                    cleanup();
                });

                debug.log('Module destroy.', { fieldCount: cleanups.length });
                void ctx.emit('formie:module:summary:destroy', {});
            },
        };
    },
};

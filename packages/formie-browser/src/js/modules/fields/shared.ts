import type { ModuleSetupContext } from '#contracts/modules';
import { getFieldModuleEventName } from '#utils/event-names';
import type { ValidationContext } from '#validation/types';

type ElementCleanup = void | (() => void);
type ValidatorApi = {
    addValidator: (name: string, validatorFunction: (ctx: ValidationContext) => boolean, errorMessage?: (ctx: ValidationContext) => string) => void;
    removeValidator: (name: string) => void;
};

type FormWithValidationApi = HTMLFormElement & {
    formieValidation?: ValidatorApi;
};

const fallbackCssEscape = (value: string): string => {
    return value.replace(/["\\]/g, '\\$&');
};
const validatorRegistrations = new WeakMap<HTMLFormElement, Map<string, number>>();

export function escapeSelectorValue(value: string): string {
    if (typeof window.CSS?.escape === 'function') {
        return window.CSS.escape(value);
    }

    return fallbackCssEscape(value);
}

export function getFieldContainers(root: Element, fieldHandle?: string): HTMLElement[] {
    if (!fieldHandle) {
        return Array.from(root.querySelectorAll('[data-formie-field-handle]')).filter((element): element is HTMLElement => {
            return element instanceof HTMLElement;
        });
    }

    return Array.from(root.querySelectorAll(`[data-formie-field-handle="${escapeSelectorValue(fieldHandle)}"]`)).filter((element): element is HTMLElement => {
        return element instanceof HTMLElement;
    });
}

export function getModuleFieldContainers(ctx: ModuleSetupContext): HTMLElement[] {
    // Target-aware manifests may already hand a module one concrete field node.
    // Fall back to scanning descendants only when the target is a broader surface.
    if (ctx.target instanceof HTMLElement && ctx.target.hasAttribute('data-formie-field-handle')) {
        return [ctx.target];
    }

    if (ctx.target instanceof HTMLElement) {
        return Array.from(ctx.target.querySelectorAll('[data-formie-field-handle]')).filter((element): element is HTMLElement => {
            return element instanceof HTMLElement;
        });
    }

    return [];
}

export function getModuleFieldTarget(ctx: ModuleSetupContext): HTMLElement | null {
    return getModuleFieldContainers(ctx)[0] || null;
}

export function getFormValidator(form: HTMLFormElement | null): ValidatorApi | null {
    if (!form) {
        return null;
    }

    const formWithValidationApi = form as FormWithValidationApi;
    return formWithValidationApi.formieValidation || null;
}

export function retainFormValidators(
    form: HTMLFormElement | null,
    key: string,
    register: (validator: ValidatorApi) => void,
): void {
    if (!form) {
        return;
    }

    const formRegistrations = validatorRegistrations.get(form) || new Map<string, number>();
    const currentCount = formRegistrations.get(key) || 0;

    if (currentCount === 0) {
        const validator = getFormValidator(form);
        if (validator) {
            register(validator);
        }
    }

    formRegistrations.set(key, currentCount + 1);
    validatorRegistrations.set(form, formRegistrations);
}

export function releaseFormValidators(form: HTMLFormElement | null, key: string, validatorNames: readonly string[]): void {
    if (!form) {
        return;
    }

    const formRegistrations = validatorRegistrations.get(form);
    const currentCount = formRegistrations?.get(key) || 0;

    if (currentCount <= 1) {
        const validator = getFormValidator(form);
        validatorNames.forEach((validatorName) => {
            validator?.removeValidator(validatorName);
        });

        formRegistrations?.delete(key);

        if (!formRegistrations || formRegistrations.size === 0) {
            validatorRegistrations.delete(form);
            return;
        }

        validatorRegistrations.set(form, formRegistrations);
        return;
    }

    formRegistrations?.set(key, currentCount - 1);
}

export function observeMatchingElements<TElement extends Element>(
    root: Element,
    selector: string,
    isMatch: (element: Element) => element is TElement,
    init: (element: TElement) => ElementCleanup,
): () => void {
    const cleanups = new Map<TElement, () => void>();

    const register = (element: Element): void => {
        if (!isMatch(element) || cleanups.has(element)) {
            return;
        }

        const cleanup = init(element);
        cleanups.set(element, cleanup || (() => {}));
    };

    const scan = (node: ParentNode): void => {
        if (node instanceof Element && node.matches(selector)) {
            register(node);
        }

        node.querySelectorAll(selector).forEach((element) => {
            register(element);
        });
    };

    const prune = (): void => {
        cleanups.forEach((cleanup, element) => {
            if (root.contains(element)) {
                return;
            }

            // Dynamic row/page removal should run per-element teardown immediately
            // so nested modules do not retain stale listeners or observer state.
            cleanup();
            cleanups.delete(element);
        });
    };

    scan(root);

    const observer = new MutationObserver((mutations) => {
        mutations.forEach((mutation) => {
            mutation.addedNodes.forEach((node) => {
                if (node instanceof Element) {
                    scan(node);
                }
            });
        });

        prune();
    });

    observer.observe(root, {
        childList: true,
        subtree: true,
    });

    return () => {
        observer.disconnect();

        cleanups.forEach((cleanup) => {
            cleanup();
        });

        cleanups.clear();
    };
}

function getOwnerDocument(root: Element): Document {
    return root.ownerDocument || document;
}

export function getTemplateSource(
    root: Element,
    templateId: string | null | undefined,
): HTMLTemplateElement | HTMLScriptElement | HTMLElement | null {
    const doc = getOwnerDocument(root);

    if (templateId) {
        const explicitCandidates: Array<Element | null> = [
            root.querySelector(`template[data-formie-template-id="${escapeSelectorValue(templateId)}"]`),
            root.querySelector(`script[data-formie-template-id="${escapeSelectorValue(templateId)}"]`),
            doc.querySelector(`template[data-formie-template-id="${escapeSelectorValue(templateId)}"]`),
            doc.querySelector(`script[data-formie-template-id="${escapeSelectorValue(templateId)}"]`),
            doc.getElementById(templateId),
        ];

        for (const candidate of explicitCandidates) {
            if (candidate instanceof HTMLTemplateElement || candidate instanceof HTMLScriptElement) {
                return candidate;
            }
        }
    }

    return null;
}

export function getTemplateSourceHtml(source: HTMLTemplateElement | HTMLScriptElement | HTMLElement): string {
    if (source instanceof HTMLTemplateElement) {
        return source.innerHTML;
    }

    if (source instanceof HTMLScriptElement) {
        return source.textContent || '';
    }

    return source.innerHTML;
}

export function dispatchFieldEvent(target: Element, moduleId: string, name: string, detail: Record<string, unknown>): void {
    const eventName = getFieldModuleEventName(moduleId, name);

    target.dispatchEvent(new CustomEvent(eventName, {
        bubbles: true,
        detail,
    }));
}

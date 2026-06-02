import type { ResolvedLegacyCompatibilityOptions } from '#compatibility/event-map';
import type { FormieValidator } from '#validation/validator';

type ValidatorReadyDetail = {
    validator: FormieValidator;
    addValidator: FormieValidator['addValidator'];
    removeValidator: FormieValidator['removeValidator'];
};

type BindLegacyValidatorCompatibilityOptions = {
    target: Element;
    form: HTMLFormElement;
    validatorDetail: ValidatorReadyDetail | null;
    options: ResolvedLegacyCompatibilityOptions;
    unbinds: Array<() => void>;
};

function dispatchLegacyValidatorEvent(target: Document | Element, legacyEvent: string, detail: unknown): void {
    target.dispatchEvent(new CustomEvent(legacyEvent, {
        bubbles: true,
        detail,
    }));
}

function matchesValidator(detail: unknown, validator: FormieValidator): detail is { validator: FormieValidator } {
    return !!detail && typeof detail === 'object' && (detail as { validator?: FormieValidator }).validator === validator;
}

export function bindLegacyValidatorCompatibility({
    target,
    form,
    validatorDetail,
    options,
    unbinds,
}: BindLegacyValidatorCompatibilityOptions): void {
    if (!options.legacyValidatorEvents || !validatorDetail) {
        return;
    }

    const { validator, addValidator, removeValidator } = validatorDetail;
    const baseDetail = {
        ...validatorDetail,
        form,
        target,
    };

    dispatchLegacyValidatorEvent(document, 'formieValidatorInitialized', baseDetail);

    const destroyHandler = (event: Event) => {
        if (!(event instanceof CustomEvent) || !matchesValidator(event.detail, validator)) {
            return;
        }

        dispatchLegacyValidatorEvent(document, 'formieValidatorDestroyed', {
            ...baseDetail,
            ...event.detail,
        });
    };

    const showErrorHandler = (event: Event) => {
        if (!(event instanceof CustomEvent) || !matchesValidator(event.detail, validator) || !(event.target instanceof Element)) {
            return;
        }

        if (!form.contains(event.target)) {
            return;
        }

        dispatchLegacyValidatorEvent(event.target, 'formieValidatorShowError', {
            ...event.detail,
            addValidator,
            removeValidator,
            form,
            target,
        });
    };

    const clearErrorHandler = (event: Event) => {
        if (!(event instanceof CustomEvent) || !matchesValidator(event.detail, validator) || !(event.target instanceof Element)) {
            return;
        }

        if (!form.contains(event.target)) {
            return;
        }

        dispatchLegacyValidatorEvent(event.target, 'formieValidatorClearError', {
            ...event.detail,
            addValidator,
            removeValidator,
            form,
            target,
        });
    };

    document.addEventListener('formie:validator:destroy', destroyHandler as EventListener);
    document.addEventListener('formie:validator:show-error', showErrorHandler as EventListener);
    document.addEventListener('formie:validator:clear-error', clearErrorHandler as EventListener);

    unbinds.push(() => {
        document.removeEventListener('formie:validator:destroy', destroyHandler as EventListener);
        document.removeEventListener('formie:validator:show-error', showErrorHandler as EventListener);
        document.removeEventListener('formie:validator:clear-error', clearErrorHandler as EventListener);
    });
}

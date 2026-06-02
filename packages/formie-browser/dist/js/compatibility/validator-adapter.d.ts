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
export declare function bindLegacyValidatorCompatibility({ target, form, validatorDetail, options, unbinds, }: BindLegacyValidatorCompatibilityOptions): void;
export {};
//# sourceMappingURL=validator-adapter.d.ts.map
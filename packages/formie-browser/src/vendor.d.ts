declare module 'friendly-challenge' {
    export class WidgetInstance {
        constructor(container: Element, options?: Record<string, unknown>);
        start(): Promise<void>;
        destroy(): void;
    }
}

declare module '@friendlycaptcha/sdk' {
    export type WidgetHandle = EventTarget & {
        start(): void;
        destroy(): void;
    };

    export class FriendlyCaptchaSDK {
        createWidget(options: Record<string, unknown>): WidgetHandle;
    }
}

declare module '@placekit/autocomplete-js' {
    type PlacekitAutocomplete = (
        apiKey: string,
        options?: Record<string, unknown>,
    ) => {
        on(event: string, callback: (value: unknown, item: unknown) => void): void;
    };

    const placekitAutocomplete: PlacekitAutocomplete;

    export default placekitAutocomplete;
}

declare module 'expression-language' {
    export default class ExpressionLanguage {
        evaluate(expression: string, values?: Record<string, unknown>): unknown;
    }
}

declare module 'flatpickr' {
    type FlatpickrInstance = {
        destroy(): void;
    };

    type Flatpickr = (
        element: Element,
        options?: Record<string, unknown>,
    ) => FlatpickrInstance;

    const flatpickr: Flatpickr;

    export default flatpickr;
}

declare module 'flatpickr/dist/l10n/index.js' {
    const locales: Record<string, unknown>;
    export = locales;
}

declare module 'intl-tel-input' {
    type IntlTelInputInstance = {
        setCountry(country: string): void;
        isValidNumber(): boolean;
        getSelectedCountryData(): { iso2?: string } | null;
        getValidationError(): number;
        destroy(): void;
    };

    type IntlTelInput = (
        input: HTMLInputElement,
        options?: Record<string, unknown>,
    ) => IntlTelInputInstance;

    const intlTelInput: IntlTelInput;

    export default intlTelInput;
}

declare module 'intl-tel-input/utils' {
    const utils: Record<string, unknown>;
    export default utils;
}

declare module 'pell' {
    export function exec(command: string, value?: string): void;
    export function init(options: Record<string, unknown>): { content: HTMLElement };
}

declare module 'signature_pad' {
    export default class SignaturePad {
        constructor(canvas: HTMLCanvasElement, options?: Record<string, unknown>);
        addEventListener(eventName: string, listener: EventListener): void;
        clear(): void;
        off(): void;
        on(): void;
        removeEventListener(eventName: string, listener: EventListener): void;
        toDataURL(type?: string, encoderOptions?: number): string;
        fromDataURL(dataUrl: string, options?: Record<string, unknown>): Promise<void>;
        isEmpty(): boolean;
    }
}

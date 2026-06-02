/// <reference types="vite/client" />

declare namespace google {
    namespace maps {
        namespace places {
            class PlaceAutocompleteElement extends HTMLElement {
                constructor(options?: Record<string, unknown>);
                addEventListener(type: string, listener: EventListenerOrEventListenerObject): void;
            }
        }
    }
}

declare module '*.svg?raw' {
    const content: string;
    export default content;
}

declare module '#icons/*.svg?raw' {
    const content: string;
    export default content;
}

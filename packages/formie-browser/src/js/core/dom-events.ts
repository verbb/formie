import { toDomEventName } from '#utils/event-names';

export function dispatchFormieDomEvent(target: Element, eventName: string, detail: unknown): void {
    target.dispatchEvent(new CustomEvent(toDomEventName(eventName), {
        bubbles: true,
        detail,
    }));
}

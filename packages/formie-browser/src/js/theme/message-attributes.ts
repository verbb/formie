type MessageKey = 'messageError' | 'messageSuccess';

export function applyMessageAttributes(
    target: Element,
    form: HTMLFormElement,
    key: MessageKey,
    remove = false,
): void {
    const raw = form.getAttribute('data-formie-message-attributes');

    if (!raw) {
        return;
    }

    let messages: unknown;

    try {
        messages = JSON.parse(raw);
    } catch {
        return;
    }

    if (!messages || typeof messages !== 'object' || Array.isArray(messages)) {
        return;
    }

    const attributes: unknown = (messages as Record<string, unknown>)[key];

    if (
        !attributes ||
        typeof attributes !== 'object' ||
        Array.isArray(attributes)
    ) {
        return;
    }

    // These values are resolved by PHP using the same theme rules as server output.
    // Classes and structural/live-region attributes remain owned by the caller.
    Object.entries(attributes).forEach(([name, value]) => {
        if (
            name === 'class' ||
            !/^[^\s"'<>/=]+$/.test(name) ||
            (typeof value !== 'string' && value !== true)
        ) {
            return;
        }

        if (remove) {
            target.removeAttribute(name);
        } else {
            target.setAttribute(name, value === true ? '' : value);
        }
    });
}

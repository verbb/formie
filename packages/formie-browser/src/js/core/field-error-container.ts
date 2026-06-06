export type FieldErrorPosition = 'above' | 'below';

export function getFieldErrorPosition(fieldNode: Element): FieldErrorPosition {
    const layout = fieldNode.querySelector('[data-formie-field-layout]');
    const position = layout?.getAttribute('data-formie-error-position')?.trim();

    return position === 'above' ? 'above' : 'below';
}

export function ensureFieldErrorContainer(
    fieldNode: Element,
    applyTheme?: (container: HTMLElement) => void,
): HTMLElement {
    const existing = fieldNode.querySelector('[data-formie-field-errors]') as HTMLElement | null;

    if (existing) {
        return existing;
    }

    const content = fieldNode.querySelector('[data-formie-field-content]') as HTMLElement | null;
    const control = fieldNode.querySelector('[data-formie-field-control]') as HTMLElement | null;
    const position = getFieldErrorPosition(fieldNode);
    const container = document.createElement('div');

    container.setAttribute('data-formie-field-errors', 'true');
    applyTheme?.(container);

    if (content && control) {
        if (position === 'above') {
            content.insertBefore(container, control);
        } else {
            content.appendChild(container);
        }
    } else {
        fieldNode.appendChild(container);
    }

    return container;
}

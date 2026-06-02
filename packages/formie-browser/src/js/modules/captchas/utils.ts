import { sleep } from '#utils/async';

export function getScriptAttributes(loadingMethod?: string): { async: boolean; defer: boolean } {
    const normalized = String(loadingMethod || 'asyncDefer').toLowerCase();

    return {
        async: normalized.includes('async'),
        defer: normalized.includes('defer'),
    };
}

export function getInputValue(root: ParentNode, name: string): string {
    const inputs = Array.from(root.querySelectorAll(`input[name="${name}"], textarea[name="${name}"]`)) as Array<HTMLInputElement | HTMLTextAreaElement>;

    for (const input of inputs) {
        const value = String(input.value || '').trim();

        if (value !== '') {
            return value;
        }
    }

    return '';
}

export function hasCaptchaValue(root: ParentNode, names: string[]): boolean {
    return names.some((name) => {
        return getInputValue(root, name) !== '';
    });
}

export function clearCaptchaValues(root: ParentNode, names: string[]): void {
    names.forEach((name) => {
        const inputs = Array.from(root.querySelectorAll(`input[name="${name}"], textarea[name="${name}"]`)) as Array<HTMLInputElement | HTMLTextAreaElement>;

        inputs.forEach((input) => {
            input.value = '';
        });
    });
}

export function ensureCaptchaValueInput(
    root: ParentNode,
    name: string,
    {
        value = '',
        container,
    }: {
        value?: string;
        container?: HTMLElement | null;
    } = {},
): HTMLInputElement {
    let input = root.querySelector(`input[name="${name}"]`) as HTMLInputElement | null;

    if (!input) {
        input = document.createElement('input');
        input.type = 'hidden';
        input.name = name;

        const target = container || (root instanceof HTMLElement ? root : null);
        target?.appendChild(input);
    }

    input.value = value;

    return input;
}

export async function waitForCaptchaValue(root: ParentNode, names: string[], waitForValueMs: number): Promise<boolean> {
    if (hasCaptchaValue(root, names)) {
        return true;
    }

    const deadline = Date.now() + Math.max(waitForValueMs, 0);

    while (Date.now() < deadline) {
        await sleep(120);

        if (hasCaptchaValue(root, names)) {
            return true;
        }
    }

    return false;
}

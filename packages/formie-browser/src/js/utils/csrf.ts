export type FormCsrfToken = {
    name: string;
    value: string;
};

type CraftGlobal = {
    csrfTokenName?: string;
    csrfTokenValue?: string;
};

const DEFAULT_CRAFT_CSRF_TOKEN_NAME = 'CRAFT_CSRF_TOKEN';
const FORMIE_CSRF_PARAM_ATTR = 'data-formie-csrf-param';
const FORMIE_CSRF_INPUT_ATTR = 'data-formie-csrf';

function getCraftCsrfTokenName(): string | null {
    const craft = (globalThis as { Craft?: CraftGlobal }).Craft;
    const name = craft?.csrfTokenName;

    return typeof name === 'string' && name.trim() ? name.trim() : null;
}

function escapeAttributeSelectorValue(value: string): string {
    if (typeof CSS !== 'undefined' && typeof CSS.escape === 'function') {
        return CSS.escape(value);
    }

    // Attribute-selector fallback for environments without CSS.escape.
    return value.replace(/\\/g, '\\\\').replace(/"/g, '\\"');
}

function queryNamedInput(root: ParentNode, name: string): HTMLInputElement | null {
    const input = root.querySelector(`input[name="${escapeAttributeSelectorValue(name)}"]`);

    return input instanceof HTMLInputElement ? input : null;
}

/**
 * Resolve the Craft CSRF hidden input for a Formie form.
 *
 * Prefer Formie markers / declared param names so custom `csrfTokenName`
 * values in `config/general.php` keep working. Fall back to Craft's CP
 * global and the default token name for older or custom templates.
 */
export function getFormCsrfInput(form: ParentNode | null | undefined): HTMLInputElement | null {
    if (!form) {
        return null;
    }

    const marked = form.querySelector(`input[${FORMIE_CSRF_INPUT_ATTR}]`);

    if (marked instanceof HTMLInputElement && marked.name.trim()) {
        return marked;
    }

    if (form instanceof Element) {
        const declaredParam = form.getAttribute(FORMIE_CSRF_PARAM_ATTR)?.trim();

        if (declaredParam) {
            const declaredInput = queryNamedInput(form, declaredParam);

            if (declaredInput) {
                return declaredInput;
            }
        }
    }

    const craftName = getCraftCsrfTokenName();

    if (craftName) {
        const craftInput = queryNamedInput(form, craftName);

        if (craftInput) {
            return craftInput;
        }
    }

    return queryNamedInput(form, DEFAULT_CRAFT_CSRF_TOKEN_NAME);
}

export function getFormCsrfToken(form: ParentNode | null | undefined): FormCsrfToken | null {
    const input = getFormCsrfInput(form);
    const name = input?.name?.trim() || '';
    const value = input?.value?.trim() || '';

    if (!name || !value) {
        return null;
    }

    return { name, value };
}

export function appendFormCsrfToFormData(body: FormData, form: ParentNode | null | undefined): void {
    const csrf = getFormCsrfToken(form);

    if (csrf) {
        body.append(csrf.name, csrf.value);
    }
}

export function applyFormCsrfToRecord(target: Record<string, string>, form: ParentNode | null | undefined): void {
    const csrf = getFormCsrfToken(form);

    if (csrf) {
        target[csrf.name] = csrf.value;
    }
}

export function isFormCsrfFieldName(name: string, form?: ParentNode | null): boolean {
    const normalizedName = name.endsWith('[]') ? name.slice(0, -2) : name;

    if (!normalizedName) {
        return false;
    }

    if (normalizedName === DEFAULT_CRAFT_CSRF_TOKEN_NAME) {
        return true;
    }

    const craftName = getCraftCsrfTokenName();

    if (craftName && normalizedName === craftName) {
        return true;
    }

    if (form instanceof Element) {
        const declaredParam = form.getAttribute(FORMIE_CSRF_PARAM_ATTR)?.trim();

        if (declaredParam && normalizedName === declaredParam) {
            return true;
        }
    }

    const csrf = getFormCsrfToken(form);

    return !!csrf && normalizedName === csrf.name;
}

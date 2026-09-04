/**
 * Field-error ARIA helpers.
 *
 * Pair `aria-errormessage` with `aria-describedby` pointing at the same error
 * message id. `aria-errormessage` alone is inconsistently announced (notably
 * iOS Safari + VoiceOver); Adrian Roselli's cross-AT testing found the pairing
 * more reliable without clobbering instruction ids already on describedby.
 */

export function appendDescribedBy(input: HTMLElement, describedById: string): void {
    const current = (input.getAttribute('aria-describedby') || '').trim();
    const items = current ? current.split(/\s+/) : [];

    if (!items.includes(describedById)) {
        items.push(describedById);
    }

    input.setAttribute('aria-describedby', items.join(' ').trim());
}

export function removeDescribedBy(input: HTMLElement, describedById: string): void {
    const current = (input.getAttribute('aria-describedby') || '').trim();

    if (!current) {
        return;
    }

    const filtered = current.split(/\s+/).filter((item) => {
        return item !== describedById;
    });

    if (filtered.length) {
        input.setAttribute('aria-describedby', filtered.join(' '));
        return;
    }

    input.removeAttribute('aria-describedby');
}

/**
 * Drop describedby ids whose targets no longer exist (Formie removes error
 * message nodes from the DOM when clearing). Keeps instruction/other ids.
 */
export function pruneMissingDescribedBy(input: HTMLElement, doc: Document = document): void {
    const current = (input.getAttribute('aria-describedby') || '').trim();

    if (!current) {
        return;
    }

    const remaining = current.split(/\s+/).filter((id) => {
        return !!id && !!doc.getElementById(id);
    });

    if (remaining.length) {
        input.setAttribute('aria-describedby', remaining.join(' '));
        return;
    }

    input.removeAttribute('aria-describedby');
}

export function setErrorMessageReference(input: HTMLElement, errorMessageId: string): void {
    input.setAttribute('aria-errormessage', errorMessageId);
    // Same id in describedby so AT that ignore aria-errormessage still hear it.
    appendDescribedBy(input, errorMessageId);
}

export function clearErrorMessageReference(input: HTMLElement, errorMessageId: string): void {
    if (input.getAttribute('aria-errormessage') === errorMessageId) {
        input.removeAttribute('aria-errormessage');
    }

    removeDescribedBy(input, errorMessageId);
}

/**
 * Clear errormessage refs and prune any describedby ids that no longer resolve
 * (covers message nodes removed from the DOM and legacy container ids).
 */
export function clearFieldErrorAria(input: HTMLElement, errorMessageIds: string[] = []): void {
    errorMessageIds.forEach((errorMessageId) => {
        if (input.getAttribute('aria-errormessage') === errorMessageId) {
            input.removeAttribute('aria-errormessage');
        }
    });

    if (!errorMessageIds.length && input.hasAttribute('aria-errormessage')) {
        input.removeAttribute('aria-errormessage');
    }

    pruneMissingDescribedBy(input);
}

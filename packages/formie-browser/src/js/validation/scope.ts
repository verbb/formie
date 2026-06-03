export function getFormPages(form: HTMLFormElement): HTMLElement[] {
    return Array.from(form.querySelectorAll('[data-formie-page]')) as HTMLElement[];
}

export function getValidationScope(form: HTMLFormElement): { scope: Element; final: boolean } {
    const pages = getFormPages(form);

    if (!pages.length) {
        return {
            scope: form,
            final: true,
        };
    }

    // Multi-page submits validate the visible page until the user reaches the
    // final step, where hidden earlier pages are included again.
    const currentPage = pages.find((page) => {
        return !page.hasAttribute('data-formie-page-hidden');
    }) || pages[pages.length - 1];

    return {
        scope: currentPage,
        final: currentPage === pages[pages.length - 1],
    };
}

import { toggleThemeClasses } from '#theme/theme-classes';

function setTabErrorState(tab: HTMLElement, hasError: boolean): void {
    toggleThemeClasses(tab, tab, 'tabError', hasError);

    if (hasError) {
        tab.setAttribute('data-formie-tab-error', 'true');
        return;
    }

    tab.removeAttribute('data-formie-tab-error');
}

export function syncPageTabErrors(form: HTMLFormElement): void {
    const pageIdsWithErrors = new Set<string>();

    // Page tabs derive their error state from rendered field markup so they stay
    // in sync regardless of whether errors came from client validation or submit.
    form.querySelectorAll('[data-formie-page]').forEach((pageNode) => {
        const page = pageNode as HTMLElement;
        const pageId = page.getAttribute('data-formie-page-id');

        if (!pageId) {
            return;
        }

        if (page.querySelector('[data-formie-field-has-error]')) {
            pageIdsWithErrors.add(pageId);
        }
    });

    form.querySelectorAll('[data-formie-tab]').forEach((tabNode) => {
        const tab = tabNode as HTMLElement;
        const pageId = tab.getAttribute('data-formie-page-id');
        setTabErrorState(tab, !!pageId && pageIdsWithErrors.has(pageId));
    });
}

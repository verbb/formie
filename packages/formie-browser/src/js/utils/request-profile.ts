import type { BrowserRequestOptions } from '@verbb/formie-core';

const formRequests = new WeakMap<HTMLFormElement, BrowserRequestOptions>();
export function setFormBrowserRequestOptions(form: HTMLFormElement, options: BrowserRequestOptions): void {
    formRequests.set(form, { ...options });
    form.dataset.formieRequestProfile = options.profile ?? 'same-origin-browser';
}
export function getFormBrowserRequestOptions(form: HTMLFormElement | null | undefined): BrowserRequestOptions {
    return form && formRequests.get(form) || { profile: form?.dataset.formieRequestProfile as BrowserRequestOptions['profile'] };
}

import type { BrowserModuleDefinition } from '#contracts/modules';

// The Question captcha asks the visitor a question set in Spam Protection. Server-rendered forms render it in the form
// template. Client-rendered forms have no template, so this module adds the question, its answer input and the hidden
// value the server reads, `formieCaptchaQuestion`, holding the question's index and the answer as JSON.
const PARAM = 'formieCaptchaQuestion';

type QuestionOptions = {
    question?: string;
    index?: number;
    requiredMessage?: string;
};

let questionCount = 0;

export const questionCaptchaModule: BrowserModuleDefinition = {
    moduleId: 'formie:question-captcha',
    version: 2,
    surfaces: ['client-rendered'],
    kind: 'captcha',
    match: () => true,
    setup: async(ctx) => {
        const options = (ctx.options || {}) as QuestionOptions;
        const host = ctx.form ?? ctx.root;
        const id = `formie-question-captcha-${++questionCount}`;
        const wrapper = document.createElement('div');
        wrapper.className = 'formie-question-captcha';
        wrapper.setAttribute('data-formie-question-captcha', '');

        const label = document.createElement('label');
        label.htmlFor = `${id}-answer`;
        label.textContent = options.question || '';

        const input = document.createElement('input');
        input.type = 'text';
        input.id = `${id}-answer`;
        input.required = true;
        input.autocomplete = 'off';
        input.setAttribute('aria-required', 'true');
        input.setAttribute('data-formie-question-input', '');

        const error = document.createElement('p');
        error.id = `${id}-error`;
        error.className = 'formie-question-captcha-error';
        error.setAttribute('role', 'alert');
        error.hidden = true;

        const value = document.createElement('input');
        value.type = 'hidden';
        value.name = PARAM;

        const sync = () => {
            value.value = JSON.stringify({ index: options.index ?? 0, answer: input.value });
        };
        const showError = (message: string | null) => {
            error.textContent = message || '';
            error.hidden = !message;
            input.toggleAttribute('aria-invalid', !!message);

            if (message) {
                input.setAttribute('aria-describedby', error.id);
            } else {
                input.removeAttribute('aria-describedby');
            }
        };
        const onInput = () => {
            sync();
            showError(null);
        };

        sync();
        input.addEventListener('input', onInput);
        wrapper.append(label, input, error, value);
        host.append(wrapper);

        return {
            beforeSubmit: (stageCtx) => {
                if (stageCtx.action !== 'submit' || input.value.trim() !== '') {
                    return;
                }

                const message = options.requiredMessage || 'Please answer the security question.';
                showError(message);
                input.focus();
                stageCtx.abort(message);
            },
            afterSubmit: (_stageCtx, result) => {
                // A wrong answer comes back as an error on the captcha's value, which the visitor can only fix here.
                const errors = (result.errors as { fields?: Record<string, string[]> } | undefined)?.fields?.[PARAM];
                showError(Array.isArray(errors) && errors.length > 0 ? errors[0] : null);
            },
            destroy: () => {
                input.removeEventListener('input', onInput);
                wrapper.remove();
            },
        };
    },
};

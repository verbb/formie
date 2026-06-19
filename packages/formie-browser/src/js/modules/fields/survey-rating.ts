import surveyPresentationsCss from '#theme-css/fields/_survey-presentations.css?inline';

import type { FormieModuleDefinition } from '#contracts/modules';
import { dispatchFieldEvent } from '#modules/fields/shared';
import { ensureModuleStyles } from '#modules/styles';
import { createDebug } from '#utils/debug';

const FIELD_SELECTOR = '[data-formie-survey-rating]';
const STARS_SELECTOR = '[data-formie-survey-rating-stars]';
const OPTION_SELECTOR = '[data-formie-rating-option]';
const INPUT_SELECTOR = 'input[data-formie-rating-input]';
const PRESSED_WHILE_CHECKED = 'data-formie-rating-pressed-while-checked';
const MODULE_ID = 'survey-rating';
const debug = createDebug('fields', 'survey-rating');

ensureModuleStyles(MODULE_ID, [surveyPresentationsCss]);

function getRatingInputs(stars: HTMLElement): HTMLInputElement[] {
    return Array.from(stars.querySelectorAll(INPUT_SELECTOR)).filter((input): input is HTMLInputElement => {
        return input instanceof HTMLInputElement;
    });
}

function updateRatingValue(stars: HTMLElement): void {
    const selectedIndex = getRatingInputs(stars).findIndex((input) => {
        return input.checked;
    });

    if (selectedIndex >= 0) {
        stars.setAttribute('data-formie-rating-value', String(selectedIndex + 1));
        return;
    }

    stars.removeAttribute('data-formie-rating-value');
}

function bindRatingField(field: HTMLElement): () => void {
    const stars = field.querySelector(STARS_SELECTOR);

    if (!(stars instanceof HTMLElement)) {
        debug.warn('Missing rating stars container; skipping field.');
        return () => {};
    }

    const cleanups: Array<() => void> = [];

    const clearHover = () => {
        stars.removeAttribute('data-formie-rating-hover');
    };

    const setHoverFromOption = (option: HTMLElement) => {
        const options = Array.from(stars.querySelectorAll(OPTION_SELECTOR));
        const index = options.indexOf(option);

        if (index >= 0) {
            stars.setAttribute('data-formie-rating-hover', String(index + 1));
        }
    };

    stars.querySelectorAll(OPTION_SELECTOR).forEach((option) => {
        if (!(option instanceof HTMLElement)) {
            return;
        }

        const input = option.querySelector(INPUT_SELECTOR);

        const onPointerDown = () => {
            if (!(input instanceof HTMLInputElement)) {
                return;
            }

            if (input.checked) {
                input.setAttribute(PRESSED_WHILE_CHECKED, 'true');
                return;
            }

            input.removeAttribute(PRESSED_WHILE_CHECKED);
        };

        const onMouseEnter = () => {
            setHoverFromOption(option);
        };

        option.addEventListener('pointerdown', onPointerDown);
        option.addEventListener('mouseenter', onMouseEnter);
        cleanups.push(() => {
            option.removeEventListener('pointerdown', onPointerDown);
            option.removeEventListener('mouseenter', onMouseEnter);
        });
    });

    stars.addEventListener('mouseleave', clearHover);
    cleanups.push(() => {
        stars.removeEventListener('mouseleave', clearHover);
    });

    getRatingInputs(stars).forEach((input) => {
        const onClick = (event: MouseEvent) => {
            if (input.getAttribute(PRESSED_WHILE_CHECKED) !== 'true') {
                updateRatingValue(stars);
                dispatchFieldEvent(field, MODULE_ID, 'change', {
                    ratingField: field,
                    value: input.checked ? input.value : '',
                });
                return;
            }

            event.preventDefault();
            getRatingInputs(stars).forEach((candidate) => {
                candidate.checked = false;
                candidate.removeAttribute(PRESSED_WHILE_CHECKED);
            });
            updateRatingValue(stars);
            dispatchFieldEvent(field, MODULE_ID, 'change', {
                ratingField: field,
                value: '',
            });
        };

        input.addEventListener('click', onClick);
        cleanups.push(() => {
            input.removeEventListener('click', onClick);
            input.removeAttribute(PRESSED_WHILE_CHECKED);
        });
    });

    updateRatingValue(stars);

    return () => {
        cleanups.forEach((cleanup) => {
            cleanup();
        });
        clearHover();
        stars.removeAttribute('data-formie-rating-value');
    };
}

export const surveyRatingModule: FormieModuleDefinition = {
    id: MODULE_ID,
    kind: 'field',
    match: (ctx) => {
        return ctx.target instanceof HTMLElement && (
            ctx.target.matches(FIELD_SELECTOR) ||
            !!ctx.target.querySelector(FIELD_SELECTOR)
        );
    },
    setup: async(ctx) => {
        if (!(ctx.target instanceof HTMLElement)) {
            return;
        }

        const fields = ctx.target.matches(FIELD_SELECTOR)
            ? [ctx.target]
            : Array.from(ctx.target.querySelectorAll(FIELD_SELECTOR)).filter((field): field is HTMLElement => {
                return field instanceof HTMLElement;
            });

        debug.log('Module setup.', {
            fieldCount: fields.length,
        });

        const destroyBindings = fields.map((field) => {
            return bindRatingField(field);
        });

        await ctx.emit('formie:module:survey-rating:init', {
            count: fields.length,
        });

        return {
            destroy: () => {
                destroyBindings.forEach((destroyBinding) => {
                    destroyBinding();
                });
                debug.log('Module destroy.', {
                    fieldCount: fields.length,
                });
                void ctx.emit('formie:module:survey-rating:destroy', {});
            },
        };
    },
};

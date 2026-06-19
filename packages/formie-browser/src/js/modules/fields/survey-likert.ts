import surveyPresentationsCss from '#theme-css/fields/_survey-presentations.css?inline';

import type { FormieModuleDefinition } from '#contracts/modules';
import { ensureModuleStyles } from '#modules/styles';
import { createDebug } from '#utils/debug';

const FIELD_SELECTOR = '[data-formie-likert-field-layout]';
const MODULE_ID = 'survey-likert';
const debug = createDebug('fields', 'survey-likert');

ensureModuleStyles(MODULE_ID, [surveyPresentationsCss]);

export const surveyLikertModule: FormieModuleDefinition = {
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

        await ctx.emit('formie:module:survey-likert:init', {
            count: fields.length,
        });

        return {
            destroy: () => {
                debug.log('Module destroy.', {
                    fieldCount: fields.length,
                });
                void ctx.emit('formie:module:survey-likert:destroy', {});
            },
        };
    },
};

import flatpickr from 'flatpickr';
import * as flatpickrLocales from 'flatpickr/dist/l10n/index.js';
import flatpickrCss from 'flatpickr/dist/flatpickr.css?inline';

import type { FormieModuleDefinition } from '#contracts/modules';
import { dispatchFieldEvent, getModuleFieldContainers } from '#modules/fields/shared';
import { ensureModuleStyles } from '#modules/styles';
import { createDebug } from '#utils/debug';

const INPUT_SELECTOR = 'input[data-formie-date-datepicker-input]';
const RANGE_START_SELECTOR = 'input[data-formie-date-range-start-input]';
const RANGE_END_SELECTOR = 'input[data-formie-date-range-end-input]';
const MODULE_ID = 'date-picker';
const debug = createDebug('fields', 'date-picker');

ensureModuleStyles(MODULE_ID, [flatpickrCss]);

type DatePickerOptions = {
    datePickerOptions?: Array<{ label?: string; value?: unknown }>;
    dateFormat?: string;
    timeFormat?: string;
    getIsDate?: boolean;
    getIsTime?: boolean;
    getIsDateTime?: boolean;
    collectMode?: string;
    locale?: string;
    minDate?: string | null;
    maxDate?: string | null;
    availableDaysOfWeek?: string[] | number[] | '*';
};

type FlatpickrInstanceLike = {
    destroy: () => void;
};

type FlatpickrInput = HTMLInputElement & {
    _formieFlatpickr?: FlatpickrInstanceLike;
};

type FlatpickrChangeInstance = {
    input: HTMLInputElement;
    altInput?: HTMLInputElement;
};

function attributesPlugin() {
    return (instance: { input: HTMLInputElement; altInput?: HTMLInputElement | undefined; loadedPlugins: string[] }) => {
        return {
            onReady: () => {
                if (!instance.altInput) {
                    return;
                }

                // Flatpickr's alt input becomes the visible control, so copy the
                // original accessibility/data attributes onto it before removing
                // them from the hidden transport input.
                const excludedAttributes = new Set(['type', 'name', 'value']);
                instance.input.getAttributeNames().forEach((attribute) => {
                    if (excludedAttributes.has(attribute)) {
                        return;
                    }

                    const value = instance.input.getAttribute(attribute);
                    if (value !== null) {
                        instance.altInput?.setAttribute(attribute, value);
                    }

                    instance.input.removeAttribute(attribute);
                });

                instance.loadedPlugins.push('formie-attributes');
            },
        };
    };
}

function normalizeOffsetDate(input: string | null | undefined, type: 'min' | 'max'): Date | null {
    if (!input) {
        return null;
    }

    if (!Number.isNaN(Date.parse(input))) {
        return new Date(input);
    }

    // Form config may express relative min/max bounds like "+2 weeks", which are
    // resolved at runtime against the visitor's current date.
    const match = input.trim().match(/^([+-]?\d+)\s*(day|days|week|weeks|month|months|year|years)$/i);
    if (!match) {
        return null;
    }

    const amount = parseInt(match[1] || '0', 10);
    const unit = (match[2] || '').toLowerCase();
    const date = new Date();

    switch (unit) {
        case 'day':
        case 'days':
            date.setDate(date.getDate() + amount);
            break;
        case 'week':
        case 'weeks':
            date.setDate(date.getDate() + (amount * 7));
            break;
        case 'month':
        case 'months':
            date.setMonth(date.getMonth() + amount);
            break;
        case 'year':
        case 'years':
            date.setFullYear(date.getFullYear() + amount);
            break;
        default:
            return null;
    }

    if (type === 'min') {
        date.setHours(0, 0, 0, 0);
    } else {
        date.setHours(23, 59, 59, 999);
    }

    return date;
}

function prepareFormat(options: DatePickerOptions): string {
    const format = options.getIsDate
        ? (options.dateFormat || '')
        : options.getIsTime
            ? (options.timeFormat || '')
            : `${options.dateFormat || ''} ${options.timeFormat || ''}`.trim();

    // Translate Formie/PHP-ish format tokens into the subset flatpickr expects.
    return format
        .replaceAll('A', 'K')
        .replaceAll('a', 'K')
        .replaceAll('s', 'S')
        .replaceAll('g', 'h')
        .replaceAll('h', 'G');
}

function getLocale(locale: string | undefined): unknown {
    if (!locale || locale === 'en') {
        return 'en';
    }

    const localeMap = flatpickrLocales as Record<string, unknown>;
    return localeMap[locale] ?? localeMap.default ?? 'en';
}

function getDisabledWeekdayHandler(availableDaysOfWeek: DatePickerOptions['availableDaysOfWeek']) {
    if (!availableDaysOfWeek || availableDaysOfWeek === '*') {
        return undefined;
    }

    const allowedDays = availableDaysOfWeek.map((value) => {
        return Number(value);
    });

    return (date: Date) => {
        return !allowedDays.includes(date.getDay());
    };
}

function getCustomOptions(options: DatePickerOptions): Record<string, unknown> {
    const result: Record<string, unknown> = {};

    (options.datePickerOptions || []).forEach((entry) => {
        if (!entry.label) {
            return;
        }

        result[entry.label] = entry.value;
    });

    return result;
}

function pad(value: number): string {
    return String(value).padStart(2, '0');
}

function formatSubmissionDate(date: Date, options: DatePickerOptions): string {
    const year = date.getFullYear();
    const month = pad(date.getMonth() + 1);
    const day = pad(date.getDate());

    if (!options.getIsTime && !options.getIsDateTime) {
        return `${year}-${month}-${day}`;
    }

    const hours = pad(date.getHours());
    const minutes = pad(date.getMinutes());
    const seconds = pad(date.getSeconds());

    return `${year}-${month}-${day} ${hours}:${minutes}:${seconds}`;
}

function parseSubmissionDate(value: string): Date | null {
    const parsed = new Date(value);

    if (Number.isNaN(parsed.getTime())) {
        return null;
    }

    return parsed;
}

function syncRangeHiddenInputs(
    selectedDates: Date[],
    startInput: HTMLInputElement | null,
    endInput: HTMLInputElement | null,
    options: DatePickerOptions,
): void {
    if (startInput) {
        startInput.value = selectedDates[0] ? formatSubmissionDate(selectedDates[0], options) : '';
        startInput.dispatchEvent(new Event('input', { bubbles: true }));
    }

    if (endInput) {
        endInput.value = selectedDates[1] ? formatSubmissionDate(selectedDates[1], options) : '';
        endInput.dispatchEvent(new Event('input', { bubbles: true }));
    }
}

function initDatePicker(input: FlatpickrInput, options: DatePickerOptions): () => void {
    input._formieFlatpickr?.destroy();

    const fieldContainer = input.closest('[data-formie-field-handle]');
    const isRange = options.collectMode === 'range' || input.hasAttribute('data-formie-date-range-input');
    const startInput = fieldContainer?.querySelector(RANGE_START_SELECTOR);
    const endInput = fieldContainer?.querySelector(RANGE_END_SELECTOR);

    const defaultOptions: Record<string, unknown> = {
        disableMobile: true,
        allowInput: true,
        altInput: true,
        altFormat: prepareFormat(options),
        dateFormat: 'Y-m-d H:i:S',
        hourIncrement: 1,
        minuteIncrement: 1,
        minDate: normalizeOffsetDate(options.minDate, 'min'),
        maxDate: normalizeOffsetDate(options.maxDate, 'max'),
        plugins: [attributesPlugin()],
        locale: getLocale(options.locale),
        onChange: (selectedDates: Date[], _dateStr: string, instance: FlatpickrChangeInstance) => {
            if (isRange) {
                syncRangeHiddenInputs(
                    selectedDates,
                    startInput instanceof HTMLInputElement ? startInput : null,
                    endInput instanceof HTMLInputElement ? endInput : null,
                    options,
                );
            }

            // Mirror changes back through bubbling input events so validation,
            // conditions, and calculations respond to picker-driven updates.
            instance.input.dispatchEvent(new Event('input', { bubbles: true }));
            instance.altInput?.dispatchEvent(new Event('input', { bubbles: true }));
        },
    };

    const disableWeekdays = getDisabledWeekdayHandler(options.availableDaysOfWeek);
    if (disableWeekdays) {
        defaultOptions.disable = [disableWeekdays];
    }

    if (options.getIsTime || options.getIsDateTime) {
        defaultOptions.enableTime = true;
    }

    if (options.getIsTime) {
        defaultOptions.noCalendar = true;
    }

    if (isRange) {
        defaultOptions.mode = 'range';

        const startDate = startInput instanceof HTMLInputElement && startInput.value
            ? parseSubmissionDate(startInput.value)
            : null;
        const endDate = endInput instanceof HTMLInputElement && endInput.value
            ? parseSubmissionDate(endInput.value)
            : null;

        if (startDate) {
            defaultOptions.defaultDate = endDate ? [startDate, endDate] : [startDate];
        }
    }

    const mergedOptions = {
        ...defaultOptions,
        ...getCustomOptions(options),
    };

    if (isRange) {
        mergedOptions.mode = 'range';
    }

    dispatchFieldEvent(input, MODULE_ID, 'before-init', {
        datepicker: input,
        options: mergedOptions,
    });

    const instance = flatpickr(input, mergedOptions) as FlatpickrInstanceLike;
    input._formieFlatpickr = instance;
    debug.log('Initialized.', {
        inputName: input.name,
        isRange,
    });

    dispatchFieldEvent(input, MODULE_ID, 'after-init', {
        datepicker: instance,
        options: mergedOptions,
    });

    return () => {
        instance.destroy();
        delete input._formieFlatpickr;
        debug.log('Destroyed.', {
            inputName: input.name,
        });
    };
}

export const datePickerModule: FormieModuleDefinition = {
    id: MODULE_ID,
    kind: 'field',
    match: (ctx) => {
        return !!ctx.target.querySelector(INPUT_SELECTOR);
    },
    setup: async (ctx) => {
        const options = (ctx.options || {}) as DatePickerOptions;
        const fields = getModuleFieldContainers(ctx);
        const cleanups = fields.map((field) => {
            const input = field.querySelector(INPUT_SELECTOR);
            if (!(input instanceof HTMLInputElement)) {
                debug.warn('Field missing date input; skipping.');
                return () => { };
            }

            return initDatePicker(input as FlatpickrInput, options);
        });
        debug.log('Module setup.', { fieldCount: fields.length });

        await ctx.emit('formie:module:date-picker:init', {
            count: cleanups.length,
        });

        return {
            destroy: () => {
                cleanups.forEach((cleanup) => {
                    cleanup();
                });

                debug.log('Module destroy.', { fieldCount: fields.length });
                void ctx.emit('formie:module:date-picker:destroy', {});
            },
        };
    },
};

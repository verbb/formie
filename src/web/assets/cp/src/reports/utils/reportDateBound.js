export const DEFAULT_DATE_BOUND = {
    option: '',
    date: null,
    offset: 'add',
    offsetNumber: 0,
    offsetType: 'days',
};

export const DATE_BOUND_OPTIONS = [
    { label: Craft.t('formie', 'None'), value: '' },
    { label: Craft.t('formie', 'Today’s Date/Time'), value: 'today' },
    { label: Craft.t('formie', 'Specific Date/Time'), value: 'date' },
];

export const DATE_BOUND_OFFSET_OPTIONS = [
    { label: Craft.t('formie', 'Add'), value: 'add' },
    { label: Craft.t('formie', 'Subtract'), value: 'subtract' },
];

export const DATE_BOUND_OFFSET_TYPE_OPTIONS = [
    { label: Craft.t('formie', 'Days'), value: 'days' },
    { label: Craft.t('formie', 'Weeks'), value: 'weeks' },
    { label: Craft.t('formie', 'Months'), value: 'months' },
    { label: Craft.t('formie', 'Years'), value: 'years' },
];

export const normalizeDateBound = (bound = {}, boundary = 'start') => {
    const defaults = DEFAULT_DATE_BOUND;
    const normalized = {
        ...defaults,
        ...(bound || {}),
    };

    if (!['', 'today', 'date'].includes(normalized.option)) {
        normalized.option = '';
    }

    if (normalized.option !== 'date') {
        normalized.date = null;
    }

    normalized.offset = normalized.offset === 'subtract' ? 'subtract' : 'add';
    normalized.offsetNumber = Math.max(0, Number.parseInt(String(normalized.offsetNumber ?? 0), 10) || 0);

    if (!['days', 'weeks', 'months', 'years'].includes(normalized.offsetType)) {
        normalized.offsetType = 'days';
    }

    return normalized;
};

/** @typedef {'first' | 'last' | 'all' | 'count' | 'index' | 'custom'} RepeaterRowPreset */

import { parseVariableTokenMetadata } from '@form-builder/fields/utils/variablePicker';

export const REPEATER_ROW_PRESET_VALUES = /** @type {const} */ ([
    'first',
    'last',
    'all',
    'count',
    'index',
    'custom',
]);

/**
 * @param {(key: string, params?: Record<string, string>) => string} t
 */
export const getRepeaterRowPresetOptions = (t) => {
    return [
        { value: 'first', label: t('First row') },
        { value: 'last', label: t('Last row') },
        { value: 'all', label: t('All rows') },
        { value: 'count', label: t('Row count') },
        { value: 'index', label: t('Specific row') },
        { value: 'custom', label: t('Custom…') },
    ];
};

export const isRepeaterSubFieldOption = (option) => {
    return Boolean(option?.repeaterSubField || option?.tableColumnSubField);
};

export const isTableColumnSubFieldOption = (option) => {
    return Boolean(option?.tableColumnSubField);
};

export const isRepeaterScopedFieldToken = (tokenValue = '') => {
    const meta = parseVariableTokenMetadata(String(tokenValue || ''));
    const params = meta.referenceParams || {};
    const scope = String(params.scope || '').trim();
    const rowsExpression = String(params.rows || '').trim();

    if (scope === 'rows' || rowsExpression) {
        return true;
    }

    if (scope === 'index' || params.index != null) {
        return true;
    }

    if (REPEATER_ROW_PRESET_VALUES.includes(scope)) {
        return true;
    }

    const tokenWithoutDefault = String(meta.tokenWithoutDefault || '');
    return /^\{field:[^:}]+:\d+:.+\}$/.test(tokenWithoutDefault);
};

export const shouldShowRepeaterRowTargeting = (tokenValue = '', variableOption = null) => {
    return isRepeaterSubFieldOption(variableOption) || isRepeaterScopedFieldToken(tokenValue);
};

export const deriveRepeaterSubFieldLabelFromToken = (tokenValue = '') => {
    const baseToken = getRepeaterBaseToken(tokenValue);
    const match = String(baseToken || '').match(/^\{field:([^}]+)\}$/);

    if (!match) {
        return '';
    }

    const segments = String(match[1] || '').split(':').filter(Boolean);
    const handleSegment = segments.length >= 2 ? segments[segments.length - 1] : segments[0] ?? '';
    const handle = String(handleSegment).split(';')[0]?.trim() ?? '';

    return handle
        .replace(/([a-z0-9])([A-Z])/g, '$1 $2')
        .replace(/[-_]+/g, ' ')
        .trim()
        .replace(/\b\w/g, (char) => char.toUpperCase());
};

export const isUsableRepeaterBaseLabel = (label = '') => {
    const normalized = String(label || '').trim();

    if (!normalized || normalized === 'Field') {
        return false;
    }

    return !/[;=]/.test(normalized);
};

export const normalizeRepeaterBaseLabel = (label = '', tokenValue = '') => {
    let normalized = String(label || '')
        .replace(/\s*\([^)]+\)\s*$/, '')
        .replace(/;\s*(?:scope|index|rows)=[^;]*/gi, '')
        .trim();

    if (!isUsableRepeaterBaseLabel(normalized)) {
        normalized = deriveRepeaterSubFieldLabelFromToken(tokenValue) || normalized;
    }

    return normalized;
};

export const createSyntheticRepeaterSubFieldOption = (tokenValue = '', fallbackLabel = '') => {
    const strippedFallback = normalizeRepeaterBaseLabel(fallbackLabel, tokenValue);
    const derivedLabel = deriveRepeaterSubFieldLabelFromToken(tokenValue);
    const baseLabel = (isUsableRepeaterBaseLabel(strippedFallback) && strippedFallback)
        ? strippedFallback
        : (derivedLabel || strippedFallback || 'Field');

    return {
        repeaterSubField: true,
        repeaterBaseLabel: baseLabel,
        label: baseLabel,
        value: getRepeaterBaseToken(tokenValue),
    };
};

export const createSyntheticTableColumnSubFieldOption = (tokenValue = '', fallbackLabel = '') => {
    const strippedFallback = normalizeRepeaterBaseLabel(fallbackLabel, tokenValue);
    const derivedLabel = deriveRepeaterSubFieldLabelFromToken(tokenValue);
    const baseLabel = (isUsableRepeaterBaseLabel(strippedFallback) && strippedFallback)
        ? strippedFallback
        : (derivedLabel || strippedFallback || 'Field');

    return {
        tableColumnSubField: true,
        repeaterBaseLabel: baseLabel,
        label: baseLabel,
        value: getRepeaterBaseToken(tokenValue),
    };
};

export const getRepeaterBaseToken = (tokenValue = '') => {
    const meta = parseVariableTokenMetadata(String(tokenValue || ''));
    const match = String(meta.tokenWithoutDefault || '').match(/^\{field:([^}]+)\}$/);

    if (match) {
        const body = match[1];
        const baseBody = String(body).split(';')[0] ?? '';

        return baseBody ? `{field:${baseBody}}` : '';
    }

    return '';
};

export const parseRepeaterRowTargeting = (tokenValue = '') => {
    const meta = parseVariableTokenMetadata(String(tokenValue || ''));
    const params = meta.referenceParams || {};
    const scope = String(params.scope || '').trim();
    const rowsExpression = String(params.rows || '').trim();

    if (scope === 'rows' || rowsExpression) {
        return {
            preset: 'custom',
            index: '',
            rowsExpression,
        };
    }

    if (scope === 'index') {
        const indexValue = Number.parseInt(String(params.index ?? ''), 10);

        return {
            preset: 'index',
            index: Number.isFinite(indexValue) ? String(indexValue + 1) : '1',
            rowsExpression: '',
        };
    }

    if (REPEATER_ROW_PRESET_VALUES.includes(scope)) {
        return {
            preset: scope,
            index: '',
            rowsExpression: '',
        };
    }

    return {
        preset: 'first',
        index: '',
        rowsExpression: '',
    };
};

export const buildRepeaterReferenceToken = (baseToken, {
    preset = 'first',
    index = '',
    rowsExpression = '',
} = {}) => {
    const match = String(baseToken || '').match(/^\{field:([^}]+)\}$/);

    if (!match) {
        return String(baseToken || '');
    }

    const fieldBody = match[1];
    const referenceParams = {};

    if (preset === 'custom') {
        referenceParams.scope = 'rows';
        referenceParams.rows = String(rowsExpression || '').trim();
    } else if (preset === 'index') {
        const rowNumber = Number.parseInt(String(index || ''), 10);
        referenceParams.scope = 'index';
        referenceParams.index = Number.isFinite(rowNumber) ? String(Math.max(0, rowNumber - 1)) : '0';
    } else {
        referenceParams.scope = preset;
    }

    const segments = [fieldBody];

    Object.entries(referenceParams).forEach(([key, value]) => {
        if (value == null || String(value).trim() === '') {
            return;
        }

        segments.push(`${key}=${encodeURIComponent(String(value))}`);
    });

    return `{field:${segments.join(';')}}`;
};

export const applyRepeaterRowTargetingToToken = (tokenValue, targeting) => {
    const baseToken = getRepeaterBaseToken(tokenValue) || String(tokenValue || '');

    return buildRepeaterReferenceToken(baseToken, targeting);
};

export const formatRepeaterRowTargetingLabel = (targeting, t) => {
    const preset = String(targeting?.preset || 'first');

    if (preset === 'custom') {
        const rowsExpression = String(targeting?.rowsExpression || '').trim();
        return rowsExpression ? t('Rows {rows}', { rows: rowsExpression }) : t('Custom rows');
    }

    if (preset === 'index') {
        const rowNumber = String(targeting?.index || '1').trim() || '1';
        return t('Row {row}', { row: rowNumber });
    }

    const presetLabels = {
        first: t('First row'),
        last: t('Last row'),
        all: t('All rows'),
        count: t('Row count'),
    };

    return presetLabels[preset] || presetLabels.first;
};

export const buildRepeaterVariableLabel = (baseLabel, targeting, t) => {
    const trimmedBase = String(baseLabel || '').trim();

    if (!trimmedBase) {
        return formatRepeaterRowTargetingLabel(targeting, t);
    }

    return `${trimmedBase} (${formatRepeaterRowTargetingLabel(targeting, t)})`;
};

export const resolveRepeaterVariableDisplayLabel = (tokenValue, option, t) => {
    const preferredBase = option?.repeaterBaseLabel || option?.label || '';
    let baseLabel = normalizeRepeaterBaseLabel(preferredBase, tokenValue);

    if (!baseLabel || baseLabel === 'Field') {
        baseLabel = deriveRepeaterSubFieldLabelFromToken(tokenValue) || baseLabel;
    }

    const targeting = parseRepeaterRowTargeting(tokenValue);

    return buildRepeaterVariableLabel(baseLabel, targeting, t);
};


// ==========================================================================

// Formie Plugin for Craft CMS
// Author: Verbb - https://verbb.io/

// ==========================================================================

// CSS needs to be imported here as it's treated as a module
import '../scss/formie-submissions.scss';

import { hydrateFormieModules } from '@verbb/formie-browser';
import { getTextLimitMetrics } from '@verbb/formie-core';

import './includes/submission-index';
import './includes/submission-unmark-spam';

if (typeof Craft.Formie === typeof undefined) {
    Craft.Formie = {};
}

const MODULE_ROOT_SELECTOR = '[data-fui-form]';
const TEXT_LIMIT_INPUT_SELECTOR = 'input[data-formie-single-line-text-input], textarea[data-formie-multi-line-text-input]';
const cpModuleHydrators = new WeakMap();
let cpTextLimitDelegated = false;
let cpMutedDelegated = false;

function parseConfigAttribute(target) {
    const rawConfig = target.getAttribute('data-fui-form');

    if (!rawConfig) {
        return null;
    }

    try {
        return JSON.parse(rawConfig);
    } catch (error) {
        console.error('[formie] Failed to parse CP submission form config.', error);
        return null;
    }
}

async function initSubmissionModules(root = document) {
    const targets = [];

    if (root instanceof Element && root.matches(MODULE_ROOT_SELECTOR)) {
        targets.push(root);
    }

    root.querySelectorAll(MODULE_ROOT_SELECTOR).forEach((target) => {
        targets.push(target);
    });

    await Promise.all(targets.map(async(target) => {
        if (!(target instanceof Element) || cpModuleHydrators.has(target)) {
            return;
        }

        const config = parseConfigAttribute(target);
        const modules = Array.isArray(config?.modules) ? config.modules : [];

        if (modules.length === 0) {
            return;
        }

        const hydrator = await hydrateFormieModules({
            root: target,
            form: target.closest('form'),
            modules,
            mode: 'server-rendered',
        });

        cpModuleHydrators.set(target, hydrator);
        target.setAttribute('data-formie-modules-ready', 'true');
    }));
}

function getCpTextLimitTarget(input) {
    const field = input.closest('[data-formie-field-handle]');

    if (!(field instanceof HTMLElement)) {
        return null;
    }

    const existingTarget = field.querySelector('[data-formie-limit-text]');
    return existingTarget instanceof HTMLElement ? existingTarget : null;
}

function getCpTextLimitCounterState(input, remaining, unit) {
    const isEmpty = unit === 'character' ? input.value === '' : input.value.trim() === '';

    if (isEmpty) {
        return 'allowed';
    }

    if (remaining < 0) {
        return 'over';
    }

    return 'left';
}

function getCpTextLimitMessageKey(unit, state) {
    if (unit === 'character') {
        if (state === 'allowed') {
            return '{count, plural, one{character allowed} other{characters allowed}}';
        }

        if (state === 'over') {
            return '{count, plural, one{character over limit} other{characters over limit}}';
        }

        return '{count, plural, one{character left} other{characters left}}';
    }

    if (state === 'allowed') {
        return '{count, plural, one{word allowed} other{words allowed}}';
    }

    if (state === 'over') {
        return '{count, plural, one{word over limit} other{words over limit}}';
    }

    return '{count, plural, one{word left} other{words left}}';
}

function renderCpTextLimitCounter(target, input, remaining, limit, unit) {
    const state = getCpTextLimitCounterState(input, remaining, unit);
    const displayCount = state === 'allowed' ? limit : Math.abs(remaining);
    const numberClass = state === 'over' ? 'fui-limit-number fui-limit-number-error' : 'fui-limit-number';
    const messageKey = getCpTextLimitMessageKey(unit, state);

    target.innerHTML = `<span class="${numberClass}">${displayCount}</span> ${Craft.t('formie', messageKey, { count: displayCount })}`;
}

function updateCpTextLimit(input) {
    const target = getCpTextLimitTarget(input);

    if (!target) {
        return;
    }

    const maxChars = parseInt(input.getAttribute('data-formie-max-chars') || '', 10) || 0;
    const maxWords = parseInt(input.getAttribute('data-formie-max-words') || '', 10) || 0;
    const metrics = getTextLimitMetrics(input.value || '');

    if (maxChars > 0) {
        const remaining = maxChars - metrics.graphemeCount;
        renderCpTextLimitCounter(target, input, remaining, maxChars, 'character');
        return;
    }

    if (maxWords > 0) {
        const remaining = maxWords - metrics.wordCount;
        renderCpTextLimitCounter(target, input, remaining, maxWords, 'word');
    }
}

function ensureCpMutedHeadingLabels(heading) {
    if (!(heading instanceof HTMLElement) || heading.dataset.fuiCpMutedBound === 'true') {
        return;
    }

    heading.dataset.fuiCpMutedBound = 'true';
    heading.dataset.fuiCpMutedLabel = Craft.t('formie', 'Hidden by conditions. Click to expand.');
    heading.dataset.fuiCpMutedExpandedLabel = Craft.t('formie', 'Hidden by conditions. Click to collapse.');
}

function initCpMutedConditionalFields(root = document) {
    if (!root || typeof root.querySelectorAll !== 'function') {
        return;
    }

    root.querySelectorAll('.fui-cp-muted-conditional-field > .heading').forEach((heading) => {
        ensureCpMutedHeadingLabels(heading);
    });
}

function initSubmissionTextLimits(root = document) {
    if (!root || typeof root.querySelectorAll !== 'function') {
        return;
    }

    root.querySelectorAll(TEXT_LIMIT_INPUT_SELECTOR).forEach((input) => {
        if (input instanceof HTMLInputElement || input instanceof HTMLTextAreaElement) {
            updateCpTextLimit(input);
        }
    });
}

const bootstrap = () => {
    if (!cpTextLimitDelegated) {
        const delegatedHandler = (event) => {
            const input = event.target;

            if (!(input instanceof HTMLInputElement || input instanceof HTMLTextAreaElement)) {
                return;
            }

            if (!input.matches(TEXT_LIMIT_INPUT_SELECTOR)) {
                return;
            }

            updateCpTextLimit(input);
        };

        document.addEventListener('input', delegatedHandler);
        document.addEventListener('change', delegatedHandler);
        cpTextLimitDelegated = true;
    }

    if (!cpMutedDelegated) {
        document.addEventListener('click', (event) => {
            const target = event.target;

            if (!(target instanceof Element)) {
                return;
            }

            const heading = target.closest('.fui-cp-muted-conditional-field > .heading');

            if (!(heading instanceof HTMLElement)) {
                return;
            }

            ensureCpMutedHeadingLabels(heading);

            const field = heading.closest('.fui-cp-muted-conditional-field');

            if (field instanceof HTMLElement) {
                field.classList.toggle('fui-cp-muted-conditional-field--expanded');
            }
        });

        cpMutedDelegated = true;
    }

    initCpMutedConditionalFields(document);
    initSubmissionTextLimits(document);
    document.addEventListener('formie:field:repeater:init-row', (event) => {
        const row = event instanceof CustomEvent ? event.detail?.row : null;
        if (row instanceof Element) {
            initSubmissionTextLimits(row);
            initCpMutedConditionalFields(row);
        }
    });

    void initSubmissionModules(document).then(() => {
        initCpMutedConditionalFields(document);
    });
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bootstrap, { once: true });
} else {
    bootstrap();
}

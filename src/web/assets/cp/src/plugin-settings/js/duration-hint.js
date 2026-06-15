const UNIT_DEFS = [
    { singular: 'day', plural: 'days', seconds: 86400 },
    { singular: 'hour', plural: 'hours', seconds: 3600 },
    { singular: 'minute', plural: 'minutes', seconds: 60 },
    { singular: 'second', plural: 'seconds', seconds: 1 },
];

export function formatDuration(seconds) {
    const total = Math.max(0, Math.floor(Number(seconds) || 0));
    const parts = [];
    let remaining = total;

    for (const unit of UNIT_DEFS) {
        const count = Math.floor(remaining / unit.seconds);

        if (count > 0) {
            remaining %= unit.seconds;
            const label = Craft.t('formie', count === 1 ? unit.singular : unit.plural);
            parts.push(`${count} ${label}`);
        }
    }

    if (!parts.length) {
        parts.push(`0 ${Craft.t('formie', 'seconds')}`);
    }

    return Craft.t('formie', 'Equals {duration}.', {
        duration: parts.join(', '),
    });
}

function updateHint(input, hint) {
    const value = String(input.value ?? '').trim();

    if (value === '' || Number.isNaN(Number(value))) {
        hint.textContent = '';
        hint.classList.add('hidden');
        return;
    }

    hint.textContent = formatDuration(value);
    hint.classList.remove('hidden');
}

function bindDurationHint(input) {
    const field = input.closest('.field');

    if (!field || field.dataset.fuiDurationHintBound === 'true') {
        return;
    }

    field.dataset.fuiDurationHintBound = 'true';

    let hint = field.querySelector('[data-fui-duration-hint-output]');

    if (!hint) {
        hint = document.createElement('p');
        hint.className = 'fui-duration-hint';
        hint.dataset.fuiDurationHintOutput = '';
        hint.setAttribute('aria-live', 'polite');
        field.appendChild(hint);
    }

    const refresh = () => updateHint(input, hint);

    input.addEventListener('input', refresh);
    input.addEventListener('change', refresh);
    refresh();
}

export function initDurationHints(root = document) {
    root.querySelectorAll('[data-fui-duration-hint]').forEach(bindDurationHint);
}

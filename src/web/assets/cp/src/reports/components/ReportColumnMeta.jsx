import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faMinus, faPlus } from '@fortawesome/pro-solid-svg-icons';

import { Button } from '@verbb/plugin-kit-react/components';
import { cn } from '@verbb/plugin-kit-react/utils';

export const isFieldColumn = (column) => (column?.type || 'attribute') === 'field';

export function ReportColumnTypeBadge({ column, className }) {
    const isField = isFieldColumn(column);

    return (
        <span
            className={cn(
                'inline-flex h-6 w-6 shrink-0 items-center justify-center rounded text-[11px] font-semibold leading-none',
                isField
                    ? 'bg-emerald-100 text-emerald-800 ring-1 ring-emerald-200/80'
                    : 'bg-sky-100 text-sky-800 ring-1 ring-sky-200/80',
                className,
            )}
            title={isField ? Craft.t('formie', 'Field') : Craft.t('formie', 'Attribute')}
        >
            {isField ? 'F' : 'A'}
        </span>
    );
}

export function ReportColumnFormLabel({ formTitle, className }) {
    if (!formTitle) {
        return null;
    }

    return (
        <span
            className={cn(
                'inline-flex max-w-full items-center truncate rounded-md bg-gray-100 px-1.5 py-0.5 text-[11px] font-medium text-gray-600',
                className,
            )}
        >
            {formTitle}
        </span>
    );
}

export function ReportColumnToggleButton({
    enabled,
    disabled = false,
    addLabel,
    removeLabel,
    onClick,
}) {
    return (
        <Button
            type="button"
            size="none"
            variant={enabled ? 'secondary' : 'default'}
            className="h-7 w-7 shrink-0 justify-center p-0"
            disabled={disabled}
            aria-label={enabled ? removeLabel : addLabel}
            onClick={onClick}
        >
            <FontAwesomeIcon
                icon={enabled ? faMinus : faPlus}
                className="size-3"
            />
        </Button>
    );
}

export const getEnabledColumnRowClassName = (column, { isDragSource = false, variant = 'row' } = {}) => {
    return cn(
        'flex items-center gap-3 border-gray-100 bg-white',
        variant === 'row' && 'border-b px-2 py-1.5',
        variant === 'ghost' && 'rounded border border-gray-200 px-3 py-1.5 shadow-lg',
        isDragSource && 'opacity-40',
    );
};

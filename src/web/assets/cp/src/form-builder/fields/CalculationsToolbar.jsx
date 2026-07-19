import { Button, Dialog } from '@verbb/plugin-kit-react/components';
import { useTranslation } from '@verbb/plugin-kit-react/hooks';

import { VariableDropdown } from '@form-builder/fields/variable-picker';

const OPERATOR_GROUPS = [
    {
        title: 'Arithmetic',
        items: [
            ['+', 'Addition'],
            ['-', 'Subtraction'],
            ['*', 'Multiplication'],
            ['/', 'Division'],
            ['%', 'Modulus'],
            ['**', 'Power'],
        ],
    },
    {
        title: 'Bitwise',
        items: [
            ['&', 'AND'],
            ['|', 'OR'],
            ['^', 'XOR'],
        ],
    },
    {
        title: 'Logical',
        items: [
            ['!, not', 'Not'],
            ['&&, and', 'And'],
            ['||, or', 'Or'],
        ],
    },
    {
        title: 'Comparison',
        items: [
            ['==', 'Equal'],
            ['===', 'Identical'],
            ['!=', 'Not equal'],
            ['!==', 'Not identical'],
            ['<, >, <=, >=', 'Relational'],
            ['matches', 'Regex match'],
        ],
    },
    {
        title: 'Ternary',
        items: [['a ? b : c', 'Conditional']],
    },
    {
        title: 'Array',
        items: [
            ['in', 'Contains'],
            ['not in', 'Does not contain'],
        ],
    },
    {
        title: 'Numeric',
        items: [['..', 'Range']],
    },
    {
        title: 'String',
        items: [['~', 'Concatenation']],
    },
];

/**
 * Custom TipTap toolbar chrome for `$field: 'calculations'`.
 * Must be the *only* light-DOM under `slot="toolbar"` — a sibling `pk-dialog`
 * (inline-block) sat under the bar and inflated the toolbar (~67px vs ~48px).
 */
export function CalculationsToolbar({
    editor,
    variableCategories,
    variableCategoryLabels,
    variableCategoryOrder,
    onGuideOpenChange,
    validating,
    onValidate,
}) {
    const t = useTranslation();

    return (
        // v1 CalculationsToolbar: p-[8px]; ml-auto pins actions flush right.
        <div className="flex items-center gap-2 relative z-10 p-[8px] border-b border-[rgba(96,125,159,0.4)] bg-white shadow-[0_2px_3px_rgba(49,49,93,.07)]">
            <VariableDropdown
                editor={editor}
                variableCategories={variableCategories}
                variableCategoryLabels={variableCategoryLabels}
                variableCategoryOrder={variableCategoryOrder}
                title={t('Insert Variable')}
                buttonLabel={t('Insert Field')}
                buttonVariant="outline"
                buttonSize="sm"
                buttonIconSize="0.75rem"
                buttonClassName="h-auto"
                triggerMode="toolbar"
            />

            <div className="ml-auto flex items-center gap-1.5">
                <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    onClick={() => { onGuideOpenChange(true); }}
                >
                    {t('Syntax Guide')}
                </Button>

                <Button
                    type="button"
                    variant="secondary"
                    size="sm"
                    loading={validating}
                    onClick={onValidate}
                >
                    {t('Test Formula')}
                </Button>
            </div>
        </div>
    );
}

/** Syntax Guide modal — render *outside* `slot="toolbar"` / `pk-tiptap-editor`. */
export function CalculationsSyntaxGuideDialog({ open, onOpenChange }) {
    const t = useTranslation();

    return (
        <Dialog
            open={open}
            label={t('Syntax Guide')}
            description={t('Use field variables and expression syntax in formulas.')}
            className="formie-calculations-syntax-guide-dialog"
            onPkOpenChange={(event) => {
                onOpenChange(Boolean(event.detail?.open ?? event.target?.open));
            }}
        >
            {/* v1 DialogContent: max-w-4xl + grid gap-4 p-4 (body padding from pk-dialog). */}
            <div className="grid gap-4 text-sm md:grid-cols-2 xl:grid-cols-4">
                {OPERATOR_GROUPS.map((group) => (
                    <div key={group.title}>
                        <p className="mb-2 font-semibold text-[color:var(--pk-color-gray-700)]">
                            {t(group.title)}
                        </p>
                        <ul className="space-y-1 text-xs text-[color:var(--pk-color-gray-600)]">
                            {group.items.map(([token, label]) => (
                                <li key={`${group.title}-${token}`} className="flex items-center gap-2">
                                    <code className="rounded bg-[color:var(--pk-color-gray-200)] px-1.5 py-0.5 font-mono text-[11px]">
                                        {token}
                                    </code>
                                    <span>{t(label)}</span>
                                </li>
                            ))}
                        </ul>
                    </div>
                ))}
            </div>
        </Dialog>
    );
}

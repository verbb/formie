import { useId, useState } from 'react';
import { useEditorState } from '@tiptap/react';
import { Button, Icon, Popover, Tooltip } from '@verbb/plugin-kit-react/components';
import { cn } from '@verbb/plugin-kit-react/utils';
import { useTranslation } from '@verbb/plugin-kit-react/hooks';

import { buildVariableTagAttrs } from '@verbb/plugin-kit-tiptap-core';
import { VariableCommandList } from './VariableCommandList.jsx';
import { useVariablePicker } from './useVariablePicker.js';

export function VariableDropdown({
    editor,
    variableCategories,
    variableCategoryLabels,
    variableCategoryOrder,
    title,
    buttonLabel,
    buttonVariant,
    buttonSize,
    buttonClassName,
    buttonIconSize,
    open,
    onOpenChange,
    triggerMode = 'toolbar',
}) {
    const t = useTranslation();
    const triggerId = useId();
    const [uncontrolledOpen, setUncontrolledOpen] = useState(false);

    const isControlledOpen = typeof open === 'boolean';
    const isOpen = isControlledOpen ? open : uncontrolledOpen;

    const picker = useVariablePicker({
        variableCategories,
        variableCategoryLabels,
        variableCategoryOrder,
        isOpen,
        onApply: (baseVariable, variable) => {
            const attrs = buildVariableTagAttrs(baseVariable, variable);
            const chain = editor?.chain();
            chain?.focus()?.setVariableTag(attrs)?.run();
            if (!isControlledOpen) {
                setUncontrolledOpen(false);
            }
            onOpenChange?.(false);
            picker.reset();
        },
    });

    const isVariableActive = useEditorState({
        editor: editor,
        selector: ({ editor: ed }) => {
            return (ed?.isFocused && ed?.isActive('variableTag')) ?? false;
        },
    });

    const handleOpenChange = (nextOpen) => {
        if (!nextOpen) {
            picker.reset();
        }
        if (!isControlledOpen) {
            setUncontrolledOpen(nextOpen);
        }
        onOpenChange?.(nextOpen);
    };

    const hasAnyVariables = Object.values(variableCategories ?? {}).some(
        items => {
            return Array.isArray(items) && items.length > 0;
        },
    );

    if (!hasAnyVariables) {
        return null;
    }

    const tip = buttonLabel || title;
    const isInputTrigger = triggerMode === 'input';
    // Fill the TipTap host height (compact mapping = 30px, stock ≈ 32px) — fixed
    // h-[32px] overflowed shorter --pk-tiptap-input-height fields.
    const inputRailBoxClass = 'block h-full w-[37px]';

    // Toolbar: match pk-tiptap `.toolbar-btn` (2rem square, radius-md, 1rem glyph).
    // Use transparent (not none — none forces border-radius: 0). Hover/active paint
    // lives on ::part(base), never the host (host bg reads as a sharp rectangle).
    // Tooltip is a sibling via `for` (same as stock toolbar), not the popover trigger.
    const triggerButton = (
        <Button
            id={triggerId}
            slot="trigger"
            variant={buttonVariant ?? (isInputTrigger ? 'none' : 'transparent')}
            size={isInputTrigger ? 'none' : (buttonSize ?? 'sm')}
            aria-label={tip || undefined}
            style={isInputTrigger ? {
                '--pk-btn-icon-size': '1.08em',
            } : {
                '--pk-btn-height': '2rem',
                // Padding must be the token (on ::part(base)) — Tailwind px-* on the host
                // paints as outer green padding around the button chrome.
                ...(buttonLabel ? { '--pk-btn-padding-inline': '8px' } : {}),
                '--pk-btn-icon-size': buttonIconSize ?? '1rem',
                '--pk-btn-radius': 'var(--pk-radius-md)',
            }}
            className={cn(
                triggerMode === 'toolbar' && [
                    'text-[#1c2e36] [&::part(base)]:text-[#1c2e36]',
                    isVariableActive && '[&::part(base)]:bg-[rgb(226_232_240)]',
                ],
                isInputTrigger && [
                    'h-full w-full',
                    'border-l border-[#d7dfe7]',
                    'rounded-[3px] rounded-l-none',
                    'text-[#1c2e36]',
                    'bg-white hover:bg-slate-50',
                    '[&::part(base)]:h-full [&::part(base)]:min-h-full [&::part(base)]:w-full',
                    '[&::part(base)]:rounded-[3px] [&::part(base)]:rounded-l-none',
                    inputRailBoxClass,
                ],
                buttonClassName ?? '',
            )}
        >
            <Icon slot="start" icon="plus-circle" />
            {buttonLabel && <span>{buttonLabel}</span>}
        </Button>
    );

    const popover = (
        <Popover
            open={isOpen}
            flush
            placement={isInputTrigger ? 'bottom-end' : 'bottom-start'}
            sideOffset={6}
            className={isInputTrigger ? inputRailBoxClass : undefined}
            onPkOpenChange={(event) => {
                handleOpenChange(Boolean(
                    event.detail?.open
                    ?? event.target?.open,
                ));
            }}
        >
            {triggerButton}
            <div className="min-w-[260px] max-w-[360px] overflow-hidden flex flex-col">
                <VariableCommandList
                    search={picker.search}
                    onSearchChange={picker.setSearch}
                    groups={picker.groups}
                    options={picker.options}
                    onSelect={picker.handleSelect}
                    placeholder={t('Search variables')}
                    showSearch
                    shouldFilter={false}
                    onBack={picker.page ? picker.handleBack : undefined}
                    isChildMode={!!picker.page}
                    selectFirstItem={isInputTrigger}
                    autoFocusSearchInput
                    open={isOpen}
                />
            </div>
        </Popover>
    );

    // Absolute end-cap — height follows the TipTap host (inset-y), width matches v1 (37px).
    if (isInputTrigger) {
        return (
            <div className="absolute inset-y-[1px] right-[1px] z-10 w-[37px]">
                {popover}
            </div>
        );
    }

    // Single toolbar-item root so `slot="toolbar-end"` participates in kit flex/gap.
    // Tooltip uses display:contents (same as stock `.toolbar-item pk-tooltip`) so it
    // does not steal a flex slot — shadow CSS cannot style light-DOM descendants.
    return (
        <div className="inline-flex items-center">
            {tip ? (
                <Tooltip
                    for={triggerId}
                    content={tip}
                    placement="top"
                    className="contents"
                />
            ) : null}
            {popover}
        </div>
    );
}

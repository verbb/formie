import { memo, useMemo, useState } from 'react';

import { Icon, Input } from '@verbb/plugin-kit-react/components';
import { cn } from '@verbb/plugin-kit-react/utils';

import { columnKey } from '@reports/components/ReportColumnsEditor';
import { ReportColumnToggleButton } from '@reports/components/ReportColumnMeta';
import { getDuplicateFormTitles } from '@reports/utils/reportColumnFormContext';

const SIDEBAR_HEIGHT_CLASS = 'h-[min(65vh,600px)]';

const columnMatchesSearch = (column, query, group) => {
    if (!query) {
        return true;
    }

    const label = String(column.label || column.handle || '').toLowerCase();
    const handle = String(column.handle || '').toLowerCase();
    const formTitle = String(group?.formTitle || '').toLowerCase();
    const formHandle = String(group?.formHandle || '').toLowerCase();

    return label.includes(query)
        || handle.includes(query)
        || formTitle.includes(query)
        || formHandle.includes(query);
};

const getEnabledColumnKeys = (columns) => {
    return new Set(
        (columns || [])
            .filter((column) => column?.enabled)
            .map((column) => columnKey(column)),
    );
};

export const ReportAvailableColumnsSidebar = memo(function ReportAvailableColumnsSidebar({
    groups = [],
    enabledColumns = [],
    disabled = false,
    onToggleColumn,
    onToggleFormColumns,
}) {
    const [searchQuery, setSearchQuery] = useState('');
    const [expandedFormIds, setExpandedFormIds] = useState(() => new Set());

    const normalizedSearch = searchQuery.trim().toLowerCase();
    const enabledKeys = useMemo(() => getEnabledColumnKeys(enabledColumns), [enabledColumns]);
    const duplicateFormTitles = useMemo(() => getDuplicateFormTitles(groups), [groups]);

    const filteredGroups = useMemo(() => {
        return (groups || [])
            .map((group) => {
                const visibleColumns = (group.columns || []).filter((column) => {
                    return columnMatchesSearch(column, normalizedSearch, group);
                });

                return {
                    ...group,
                    columns: visibleColumns,
                };
            })
            .filter((group) => group.columns.length > 0);
    }, [groups, normalizedSearch]);

    const toggleFormExpanded = (formId) => {
        setExpandedFormIds((current) => {
            const next = new Set(current);

            if (next.has(formId)) {
                next.delete(formId);
            } else {
                next.add(formId);
            }

            return next;
        });
    };

    if (!groups?.length) {
        return null;
    }

    return (
        <aside className={cn(
            'flex min-h-0 flex-col rounded-lg border border-gray-200 bg-gray-50',
            SIDEBAR_HEIGHT_CLASS,
        )}
        >
            <div className="shrink-0 border-b border-gray-200 px-4 py-3">
                <div className="text-sm font-medium text-gray-700">
                    {Craft.t('formie', 'Browse by Form')}
                </div>
                <p className="m-0 mt-1 text-xs text-gray-500">
                    {Craft.t('formie', 'Expand a form or search to browse its fields.')}
                </p>
                <Input
                    className="mt-3"
                    placeholder={Craft.t('formie', 'Search forms or fields…')}
                    value={searchQuery}
                    onChange={(event) => { setSearchQuery(event.target.value); }}
                />
            </div>

            <div className="min-h-0 flex-1 overflow-y-auto overscroll-contain p-2">
                {filteredGroups.length ? filteredGroups.map((group) => {
                    const showFields = expandedFormIds.has(group.formId) || normalizedSearch.length > 0;
                    const enabledCount = group.columns.filter((column) => enabledKeys.has(columnKey(column))).length;
                    const allEnabled = enabledCount === group.columns.length && group.columns.length > 0;
                    const noneEnabled = enabledCount === 0;
                    const groupTitle = group.formTitle || group.formHandle;
                    const showFormHandle = duplicateFormTitles.has(groupTitle) && group.formHandle;

                    return (
                        <div key={group.formId} className="mb-2 rounded-md border border-gray-200 bg-white">
                            <div className={cn('flex items-center gap-1 px-2 py-2', showFields && 'border-b border-gray-100')}>
                                <button
                                    type="button"
                                    className="flex h-7 w-7 shrink-0 items-center justify-center rounded text-gray-500 hover:bg-gray-100 hover:text-gray-700"
                                    aria-expanded={showFields}
                                    aria-label={Craft.t('formie', 'Toggle {form} fields', {
                                        form: group.formTitle || group.formHandle,
                                    })}
                                    onClick={() => { toggleFormExpanded(group.formId); }}
                                >
                                    <Icon
                                        icon={showFields ? 'chevron-down' : 'chevron-right'}
                                        className="size-3"
                                    />
                                </button>

                                <div className="min-w-0 flex-1">
                                    <div className="truncate text-sm font-semibold text-gray-900">
                                        {groupTitle}
                                    </div>
                                    {showFormHandle ? (
                                        <div className="truncate text-[11px] text-gray-500 code">
                                            {group.formHandle}
                                        </div>
                                    ) : group.groupName ? (
                                        <div className="truncate text-[11px] font-medium uppercase tracking-wide text-gray-400">
                                            {group.groupName}
                                        </div>
                                    ) : null}
                                </div>

                                <span className="shrink-0 px-1 text-xs tabular-nums text-gray-500">
                                    {enabledCount}/{group.columns.length}
                                </span>

                                <ReportColumnToggleButton
                                    enabled={false}
                                    disabled={disabled || allEnabled}
                                    addLabel={Craft.t('formie', 'Add all fields from {form}', {
                                        form: group.formTitle || group.formHandle,
                                    })}
                                    removeLabel={Craft.t('formie', 'Add all fields from {form}', {
                                        form: group.formTitle || group.formHandle,
                                    })}
                                    onClick={() => { onToggleFormColumns?.(group, true); }}
                                />
                                <ReportColumnToggleButton
                                    enabled
                                    disabled={disabled || noneEnabled}
                                    addLabel={Craft.t('formie', 'Remove all fields from {form}', {
                                        form: group.formTitle || group.formHandle,
                                    })}
                                    removeLabel={Craft.t('formie', 'Remove all fields from {form}', {
                                        form: group.formTitle || group.formHandle,
                                    })}
                                    onClick={() => { onToggleFormColumns?.(group, false); }}
                                />
                            </div>

                            {showFields ? (
                                <div className="px-1 py-1">
                                    {group.columns.map((column) => {
                                        const key = columnKey(column);
                                        const isEnabled = enabledKeys.has(key);

                                        return (
                                            <div
                                                key={`${group.formId}:${column.handle}`}
                                                className={cn(
                                                    'flex items-center gap-2 rounded px-2 py-1.5',
                                                    isEnabled && 'bg-slate-100',
                                                )}
                                            >
                                                <div className="min-w-0 flex-1">
                                                    <div className="truncate text-sm text-gray-800">
                                                        {column.label || column.handle}
                                                    </div>
                                                    <div className="truncate text-xs text-gray-500 code">
                                                        {column.handle}
                                                    </div>
                                                </div>
                                                <ReportColumnToggleButton
                                                    enabled={isEnabled}
                                                    disabled={disabled}
                                                    addLabel={Craft.t('formie', 'Add {name}', {
                                                        name: column.label || column.handle,
                                                    })}
                                                    removeLabel={Craft.t('formie', 'Remove {name}', {
                                                        name: column.label || column.handle,
                                                    })}
                                                    onClick={() => { onToggleColumn({ ...column, formId: group.formId }, !isEnabled); }}
                                                />
                                            </div>
                                        );
                                    })}
                                </div>
                            ) : null}
                        </div>
                    );
                }) : (
                    <p className="m-0 px-2 py-3 text-sm text-gray-500">
                        {Craft.t('formie', 'No matching fields found.')}
                    </p>
                )}
            </div>
        </aside>
    );
});

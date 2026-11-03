import { useMemo, useState } from 'react';

import { Button, Icon, Spinner } from '@verbb/plugin-kit-react/components';

import { ReportViewerColumnList } from '@reports/components/ReportViewerColumnList';

import { applySectionColumns, groupExportColumns, setColumnsEnabled } from './utils/submissionExportState.js';

const getSectionLabel = (section) => {
    if (section.key === 'attributes') {
        return Craft.t('formie', 'Submission Details');
    }

    return section.label || Craft.t('formie', 'Other Fields');
};

const countLabel = (columns) => {
    const enabled = columns.filter((column) => {return column.enabled}).length;

    if (enabled !== columns.length) {
        return Craft.t('formie', '{enabled, number} of {count, number} {count, plural, =1{column} other{columns}}', { enabled, count: columns.length });
    }

    return columns.length === 1
        ? Craft.t('formie', '1 column')
        : Craft.t('formie', 'All {count, number} columns', { count: columns.length });
};

function ColumnSection({ section, columns, open, onToggle, onChange }) {
    const label = getSectionLabel(section);

    return (
        <section className="border-t border-gray-200 first:border-t-0">
            <div className="flex items-center gap-2 px-2 py-1.5">
                <button
                    type="button"
                    className="flex min-w-0 flex-1 cursor-pointer items-center gap-2 border-0 bg-transparent p-0 text-left text-sm"
                    aria-expanded={open}
                    onClick={onToggle}
                >
                    <Icon icon={open ? 'chevron-down' : 'chevron-right'} className="size-3 shrink-0 text-gray-500" />
                    <span className="truncate font-medium text-gray-900">{label}</span>
                    <span className="shrink-0 text-xs text-gray-500">{countLabel(section.columns)}</span>
                </button>

                <Button
                    type="button"
                    size="sm"
                    aria-label={Craft.t('formie', 'Select all columns for {name}', { name: label })}
                    onClick={() => { onChange(setColumnsEnabled(columns, section.columns, true)); }}
                >
                    {Craft.t('formie', 'All')}
                </Button>
                <Button
                    type="button"
                    size="sm"
                    aria-label={Craft.t('formie', 'Select no columns for {name}', { name: label })}
                    onClick={() => { onChange(setColumnsEnabled(columns, section.columns, false)); }}
                >
                    {Craft.t('formie', 'None')}
                </Button>
            </div>

            {/* Only open sections render rows, as All Forms can mean hundreds of sortable columns. */}
            {open ? (
                <div className="px-2 pb-2">
                    <ReportViewerColumnList
                        columns={section.columns}
                        scrollable={false}
                        onChange={(nextColumns) => {
                            onChange(applySectionColumns(columns, section.columns, nextColumns));
                        }}
                    />
                </div>
            ) : null}
        </section>
    );
}

export function SubmissionExportColumns({ columns, groups, loading, onChange, onReset }) {
    const [expanded, setExpanded] = useState(false);
    const [toggledSections, setToggledSections] = useState(() => {return new Set()});
    const sections = useMemo(() => {return groupExportColumns(columns, groups)}, [columns, groups]);

    // A single form is short enough to show in full. Several start collapsed.
    const defaultOpen = sections.filter((section) => {return section.key !== 'attributes'}).length <= 1;

    const toggleSection = (key) => {
        setToggledSections((current) => {
            const next = new Set(current);

            if (next.has(key)) {
                next.delete(key);
            } else {
                next.add(key);
            }

            return next;
        });
    };

    if (loading && !columns.length) {
        return <Spinner />;
    }

    return (
        <>
            <div className="flex flex-wrap items-center gap-2">
                <Button
                    type="button"
                    disabled={!columns.length}
                    aria-expanded={expanded}
                    onClick={() => { setExpanded((current) => {return !current}); }}
                >
                    {countLabel(columns)}
                    <Icon slot="end" icon={expanded ? 'chevron-up' : 'chevron-down'} className="size-3" />
                </Button>

                {expanded ? (
                    <>
                        <Button
                            type="button"
                            aria-label={Craft.t('formie', 'Select all columns')}
                            onClick={() => { onChange(setColumnsEnabled(columns, columns, true)); }}
                        >
                            {Craft.t('formie', 'All')}
                        </Button>
                        <Button
                            type="button"
                            aria-label={Craft.t('formie', 'Select no columns')}
                            onClick={() => { onChange(setColumnsEnabled(columns, columns, false)); }}
                        >
                            {Craft.t('formie', 'None')}
                        </Button>
                        <Button type="button" onClick={onReset}>
                            {Craft.t('app', 'Reset')}
                        </Button>
                    </>
                ) : null}
            </div>

            {expanded ? (
                <div className="mt-2 max-h-96 overflow-y-auto overscroll-contain rounded-md border border-gray-200">
                    {sections.map((section) => {return (
                        <ColumnSection
                            key={section.key}
                            section={section}
                            columns={columns}
                            open={defaultOpen !== toggledSections.has(section.key)}
                            onToggle={() => { toggleSection(section.key); }}
                            onChange={onChange}
                        />
                    )})}
                </div>
            ) : null}
        </>
    );
}

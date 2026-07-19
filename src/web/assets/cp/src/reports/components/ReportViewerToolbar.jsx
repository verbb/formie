import { useEffect, useRef, useState } from 'react';

import { Button, DropdownItem, DropdownMenu, Icon, Input } from '@verbb/plugin-kit-react/components';

import { ReportViewerViewPopover } from '@reports/components/ReportViewerViewPopover';
import { ReportViewerDateRange } from '@reports/components/ReportViewerDateRange';

const EXPORT_FORMATS = [
    { value: 'xlsx', label: 'Excel' },
    { value: 'csv', label: 'CSV' },
    { value: 'json', label: 'JSON' },
    { value: 'xml', label: 'XML' },
    { value: 'text', label: 'Text' },
];

export function ReportViewerToolbar({
    search,
    onSearchChange,
    dateRange,
    onDateRangeChange,
    sort,
    sortDir,
    sortOptions,
    columns,
    onSortChange,
    onSortDirChange,
    onColumnsChange,
    canExport,
    exportLoading = false,
    exportQueuedNoticeOpen = false,
    onExportQueuedNoticeOpenChange,
    onExport,
    hasViewerChanges,
    onResetViewer,
}) {
    const [searchValue, setSearchValue] = useState(search || '');
    const exportAnchorRef = useRef(null);
    const exportNoticeRef = useRef(null);

    useEffect(() => {
        if (!exportQueuedNoticeOpen) {
            return undefined;
        }

        const handlePointerDown = (event) => {
            const target = event.target;

            if (!(target instanceof Node)) {
                return;
            }

            if (exportAnchorRef.current?.contains(target) || exportNoticeRef.current?.contains(target)) {
                return;
            }

            onExportQueuedNoticeOpenChange?.(false);
        };

        document.addEventListener('pointerdown', handlePointerDown);

        return () => {
            document.removeEventListener('pointerdown', handlePointerDown);
        };
    }, [exportQueuedNoticeOpen, onExportQueuedNoticeOpenChange]);

    useEffect(() => {
        setSearchValue(search || '');
    }, [search]);

    useEffect(() => {
        const timeout = window.setTimeout(() => {
            if (searchValue !== search) {
                onSearchChange(searchValue);
            }
        }, 300);

        return () => {
            window.clearTimeout(timeout);
        };
    }, [onSearchChange, search, searchValue]);

    return (
        <div className="flex flex-wrap items-center gap-2 rounded-lg border border-gray-200 bg-white p-2">
            <Input
                className="min-w-[220px] flex-1"
                value={searchValue}
                placeholder={Craft.t('app', 'Search')}
                onChange={(event) => { setSearchValue(event.target.value); }}
            >
                {/* Adornment inside the control shell (part=base) — not absolutely positioned outside. */}
                <Icon slot="start" icon="search" className="size-3 text-gray-400" />
            </Input>

            <ReportViewerDateRange
                startDate={dateRange?.startDate}
                endDate={dateRange?.endDate}
                onChange={onDateRangeChange}
            />

            <ReportViewerViewPopover
                sort={sort}
                sortDir={sortDir}
                sortOptions={sortOptions}
                columns={columns}
                onSortChange={onSortChange}
                onSortDirChange={onSortDirChange}
                onColumnsChange={onColumnsChange}
            />

            {hasViewerChanges ? (
                <Button type="button" onClick={onResetViewer}>
                    {Craft.t('app', 'Reset')}
                </Button>
            ) : null}

            {canExport ? (
                <div ref={exportAnchorRef} className="relative inline-flex">
                    <DropdownMenu placement="bottom-end">
                        <Button slot="trigger" type="button" disabled={exportLoading}>
                            {exportLoading
                                ? Craft.t('formie', 'Exporting…')
                                : Craft.t('formie', 'Export')}
                            <Icon slot="end" icon="chevron-down" className="size-3" />
                        </Button>
                        {EXPORT_FORMATS.map((format) => (
                            <DropdownItem
                                key={format.value}
                                value={format.value}
                                onPkSelect={() => { onExport(format.value); }}
                            >
                                {format.label}
                            </DropdownItem>
                        ))}
                    </DropdownMenu>

                    {exportQueuedNoticeOpen ? (
                        <div
                            ref={exportNoticeRef}
                            role="status"
                            className="absolute right-0 top-[calc(100%+0.25rem)] z-50 w-[min(92vw,280px)] rounded-md border border-gray-200 bg-white p-3 shadow-lg"
                        >
                            <p className="m-0 text-sm leading-snug text-gray-600">
                                {Craft.t('formie', 'This export is running in the queue. Your download will start automatically when it’s ready.')}
                            </p>
                        </div>
                    ) : null}
                </div>
            ) : null}
        </div>
    );
}

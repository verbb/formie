import {
    useCallback,
    useEffect,
    useMemo,
    useRef,
    useState,
} from 'react';

import {
    Button,
    ButtonGroup,
} from '@verbb/plugin-kit-react/components';

import { ReportDataLoadingOverlay } from '@reports/components/ReportDataLoadingOverlay';
import { ReportSubmissionsChart } from '@reports/components/ReportSubmissionsChart';
import { ReportSubmissionsTable } from '@reports/components/ReportSubmissionsTable';
import { ReportViewerToolbar } from '@reports/components/ReportViewerToolbar';
import {
    appendViewerDateParams,
    normalizeDateRange,
} from '@reports/utils/reportViewerDates';
import {
    clearStoredViewerPreferences,
    cloneViewerColumns,
    getViewerColumnFetchKey,
    hasViewerPreferenceChanges,
    loadStoredViewerColumns,
    loadStoredViewerDateRange,
    loadStoredViewerSort,
    orderTableDataByViewerColumns,
    persistViewerColumns as storeViewerColumns,
    persistViewerDateRange as storeViewerDateRange,
    persistViewerSort as storeViewerSort,
} from '@reports/utils/reportViewerStorage';

import styles from '@reports/css/style.css?inline';

function SummaryCard({ label, value }) {
    return (
        <div className="rounded-lg border border-gray-200 bg-white p-4">
            <div className="text-sm text-gray-500">{label}</div>
            <div className="mt-1 text-2xl font-semibold tabular-nums text-gray-900">{value}</div>
        </div>
    );
}

export const ReportViewApp = ({ settings, embedded = false }) => {
    const [tableData, setTableData] = useState(null);
    const [tableLoading, setTableLoading] = useState(true);
    const [tableError, setTableError] = useState(null);
    const [overviewLoading, setOverviewLoading] = useState(false);
    const [page, setPage] = useState(1);
    const [search, setSearch] = useState('');
    const [summary, setSummary] = useState(settings.summary || {});
    const [chart, setChart] = useState(settings.chart || {});

    const reportId = settings.report?.id;
    const defaultSort = settings.defaultSort || { handle: 'dateCreated', dir: 'desc' };
    const defaultDateRange = useMemo(() => {
        return normalizeDateRange(settings.defaultDateRange || {});
    }, [settings.defaultDateRange]);

    const defaultViewerColumns = useMemo(() => {
        return cloneViewerColumns(settings.viewerColumns || settings.exportColumns || settings.columns || []);
    }, [settings.viewerColumns, settings.exportColumns, settings.columns]);

    const sortOptions = useMemo(() => {
        return (settings.sortableColumns || []).map((column) => ({
            value: column.handle,
            label: column.label || column.handle,
        }));
    }, [settings.sortableColumns]);

    const [viewerColumns, setViewerColumns] = useState(() => {
        return loadStoredViewerColumns(reportId, defaultViewerColumns);
    });
    const [sort, setSort] = useState(() => {
        return loadStoredViewerSort(reportId, defaultSort);
    });
    const [dateRange, setDateRange] = useState(() => {
        return loadStoredViewerDateRange(reportId, defaultDateRange);
    });

    useEffect(() => {
        setViewerColumns(loadStoredViewerColumns(reportId, defaultViewerColumns));
        setSort(loadStoredViewerSort(reportId, defaultSort));
        setDateRange(loadStoredViewerDateRange(reportId, defaultDateRange));
        setSummary(settings.summary || {});
        setChart(settings.chart || {});
        setSearch('');
        setPage(1);
    }, [defaultDateRange, defaultSort, defaultViewerColumns, reportId, settings.chart, settings.summary]);

    const persistViewerColumns = useCallback((nextColumns) => {
        if (getViewerColumnFetchKey(nextColumns) !== getViewerColumnFetchKey(viewerColumns)) {
            setTableLoading(true);
        }

        setViewerColumns(nextColumns);
        storeViewerColumns(reportId, nextColumns);
    }, [reportId, viewerColumns]);

    const persistViewerSort = useCallback((nextSort) => {
        setTableLoading(true);
        setSort(nextSort);
        storeViewerSort(reportId, nextSort);
    }, [reportId]);

    const persistViewerDateRange = useCallback((nextDateRange) => {
        const normalized = normalizeDateRange(nextDateRange);
        setDateRange(normalized);
        storeViewerDateRange(reportId, normalized);
    }, [reportId]);

    const hasViewerChanges = useMemo(() => {
        return hasViewerPreferenceChanges({
            columns: viewerColumns,
            defaultColumns: defaultViewerColumns,
            sort,
            defaultSort,
            dateRange,
            defaultDateRange,
            search,
        });
    }, [
        dateRange,
        defaultDateRange,
        defaultSort,
        defaultViewerColumns,
        search,
        sort,
        viewerColumns,
    ]);

    const resetViewerPreferences = useCallback(() => {
        clearStoredViewerPreferences(reportId);
        setTableLoading(true);
        setViewerColumns(cloneViewerColumns(defaultViewerColumns));
        setSort({ ...defaultSort });
        setDateRange(normalizeDateRange(defaultDateRange));
        setSearch('');
    }, [defaultDateRange, defaultSort, defaultViewerColumns, reportId]);

    const viewerColumnFetchKey = useMemo(() => {
        return getViewerColumnFetchKey(viewerColumns);
    }, [viewerColumns]);

    const tableRequestIdRef = useRef(0);
    const overviewRequestIdRef = useRef(0);
    const tableParamsRef = useRef({
        dateRange,
        search,
        sort,
        viewerColumns,
    });

    tableParamsRef.current = {
        dateRange,
        search,
        sort,
        viewerColumns,
    };

    const orderedTableData = useMemo(() => {
        return orderTableDataByViewerColumns(tableData, viewerColumns);
    }, [tableData, viewerColumns]);

    const loadOverview = useCallback(async (nextDateRange = dateRange) => {
        if (!settings.viewerDataUrl) {
            return;
        }

        const requestId = ++overviewRequestIdRef.current;
        setOverviewLoading(true);

        try {
            const body = new FormData();
            body.append(settings.csrfTokenName, settings.csrfTokenValue);
            appendViewerDateParams(body, nextDateRange);

            const response = await fetch(settings.viewerDataUrl, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                },
                body,
            });

            if (!response.ok) {
                throw new Error(Craft.t('formie', 'Unable to load report data.'));
            }

            const payload = await response.json();

            if (requestId !== overviewRequestIdRef.current) {
                return;
            }

            setSummary(payload.summary || {});
            setChart(payload.chart || {});
        } catch {
            // Keep the previous overview visible if the refresh fails.
        } finally {
            if (requestId === overviewRequestIdRef.current) {
                setOverviewLoading(false);
            }
        }
    }, [
        dateRange,
        settings.csrfTokenName,
        settings.csrfTokenValue,
        settings.viewerDataUrl,
    ]);

    const loadTable = useCallback(async (nextPage = 1) => {
        const requestId = ++tableRequestIdRef.current;
        const {
            dateRange: activeDateRange,
            search: activeSearch,
            sort: activeSort,
            viewerColumns: activeViewerColumns,
        } = tableParamsRef.current;

        setTableLoading(true);
        setTableError(null);

        try {
            const body = new FormData();
            body.append(settings.csrfTokenName, settings.csrfTokenValue);
            body.append('page', String(nextPage));
            body.append('limit', String(settings.tablePageSize || 100));
            body.append('search', activeSearch);
            body.append('sort', activeSort.handle);
            body.append('sortDir', activeSort.dir);
            appendViewerDateParams(body, activeDateRange);
            body.append('columns', JSON.stringify(activeViewerColumns));

            const response = await fetch(settings.tableDataUrl, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                },
                body,
            });

            if (!response.ok) {
                throw new Error(Craft.t('formie', 'Unable to load submissions.'));
            }

            const payload = await response.json();

            if (requestId !== tableRequestIdRef.current) {
                return;
            }

            setTableData(payload);
            setPage(nextPage);

            if (payload.sort?.handle) {
                storeViewerSort(reportId, {
                    handle: payload.sort.handle,
                    dir: payload.sort.dir === 'asc' ? 'asc' : 'desc',
                });
            }
        } catch (error) {
            if (requestId !== tableRequestIdRef.current) {
                return;
            }

            setTableError(error instanceof Error ? error.message : Craft.t('formie', 'Unable to load submissions.'));
        } finally {
            if (requestId === tableRequestIdRef.current) {
                setTableLoading(false);
            }
        }
    }, [
        reportId,
        settings.csrfTokenName,
        settings.csrfTokenValue,
        settings.tableDataUrl,
    ]);

    useEffect(() => {
        loadOverview();
    }, [loadOverview]);

    useEffect(() => {
        loadTable(1);
    }, [
        dateRange.endDate,
        dateRange.startDate,
        loadTable,
        search,
        sort.dir,
        sort.handle,
        viewerColumnFetchKey,
    ]);

    const pagination = tableData?.pagination;
    const canExport = Boolean(settings.canExport && settings.exportUrl);
    const isDataLoading = tableLoading || overviewLoading;

    const handleExport = (format) => {
        const form = document.createElement('form');
        form.method = 'post';
        form.action = `${settings.exportUrl}?format=${encodeURIComponent(format)}`;

        const appendField = (name, value) => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = name;
            input.value = value;
            form.appendChild(input);
        };

        appendField(settings.csrfTokenName, settings.csrfTokenValue);
        appendField('columns', JSON.stringify(viewerColumns.filter((column) => column.enabled !== false)));
        appendField('search', search);
        appendField('sort', sort.handle);
        appendField('sortDir', sort.dir);
        appendField('startDate', dateRange.startDate || '');
        appendField('endDate', dateRange.endDate || '');
        appendField('format', format);

        document.body.appendChild(form);
        form.submit();
        form.remove();
    };

    const handleDateRangeChange = (nextDateRange) => {
        persistViewerDateRange(nextDateRange);
    };

    return (
        <div className="w-full space-y-4">
            {!embedded ? (
                <div className="flex flex-wrap items-center justify-between gap-4 border-b border-gray-200 pb-4">
                    <p className="m-0 text-sm text-gray-500">
                        {Craft.t('formie', 'Saved analytical view for filtered submissions.')}
                    </p>

                    {(settings.canEdit && settings.editUrl) || canExport ? (
                        <ButtonGroup>
                            {settings.canEdit && settings.editUrl ? (
                                <Button href={settings.editUrl}>
                                    {Craft.t('formie', 'Report settings')}
                                </Button>
                            ) : null}
                        </ButtonGroup>
                    ) : null}
                </div>
            ) : null}

            <ReportViewerToolbar
                search={search}
                onSearchChange={(value) => {
                    setTableLoading(true);
                    setSearch(value);
                }}
                dateRange={dateRange}
                onDateRangeChange={handleDateRangeChange}
                sort={sort.handle}
                sortDir={sort.dir}
                sortOptions={sortOptions}
                columns={viewerColumns}
                onSortChange={(handle) => { persistViewerSort({ handle, dir: sort.dir }); }}
                onSortDirChange={(dir) => { persistViewerSort({ handle: sort.handle, dir }); }}
                onColumnsChange={persistViewerColumns}
                canExport={canExport}
                onExport={handleExport}
                hasViewerChanges={hasViewerChanges}
                onResetViewer={resetViewerPreferences}
            />

            <div className={`grid gap-3 sm:grid-cols-2 xl:grid-cols-4 ${overviewLoading ? 'opacity-60' : ''}`}>
                <SummaryCard label={Craft.t('formie', 'Total submissions')} value={summary.total ?? 0} />
                <SummaryCard label={Craft.t('formie', 'Complete')} value={summary.complete ?? 0} />
                <SummaryCard label={Craft.t('formie', 'Incomplete')} value={summary.incomplete ?? 0} />
                <SummaryCard label={Craft.t('formie', 'Spam')} value={summary.spam ?? 0} />
            </div>

            <section className="relative overflow-hidden rounded-lg border border-gray-200 bg-white">
                {isDataLoading ? <ReportDataLoadingOverlay /> : null}

                {chart.enabled !== false ? (
                    <div className="px-4 pt-3 pb-5">
                        <ReportSubmissionsChart data={chart.data || []} />
                    </div>
                ) : null}

                {tableError ? (
                    <p className="px-4 py-3 text-sm text-rose-600">{tableError}</p>
                ) : null}

                <ReportSubmissionsTable
                    columns={orderedTableData.columns}
                    rows={orderedTableData.rows}
                    sort={tableData?.sort || sort}
                    loading={tableLoading}
                    pagination={pagination}
                    onSortChange={(handle, dir) => { persistViewerSort({ handle, dir }); }}
                    onPageChange={(nextPage) => { loadTable(nextPage); }}
                />
            </section>
        </div>
    );
};

export { styles as reportViewStyles };

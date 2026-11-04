import { memo, useCallback, useEffect, useMemo, useRef, useState } from 'react';

import { getErrorMessage } from '@verbb/plugin-kit-core';
import { Button, Icon, SelectInput, StatePanel } from '@verbb/plugin-kit-react/components';
import { ErrorState } from '@verbb/plugin-kit-react/utils';
import { CreateReportModal } from '@reports/components/CreateReportModal';
import { ReportViewApp } from '@reports/components/ReportViewApp';
import { ReportLoadingState } from '@reports/components/ReportLoadingState';

import styles from '@reports/css/style.css?inline';

// Opening the dialog must not also rebuild a large, unchanged report table.
const MemoizedReportView = memo(ReportViewApp);

const getReportUrl = (handle) => {
    if (!handle) {
        return Craft.getUrl('formie/reports');
    }

    return Craft.getUrl(`formie/reports/${handle}`);
};

const getCreateUrl = () => { return Craft.getUrl('formie/reports/create'); };

const replaceBrowserUrl = (url) => {
    window.history.replaceState({}, '', url);
};

const readLoadReportError = async (response) => {
    const fallback = Craft.t('formie', 'Unable to load report.');

    try {
        const payload = await response.json();
        const parsed = getErrorMessage(payload);
        const detail = parsed?.text || payload?.message || payload?.error || fallback;

        return response.status ? `${detail} (${response.status})` : detail;
    } catch {
        return response.status ? `${fallback} (${response.status})` : fallback;
    }
};

export const ReportsDashboardApp = ({ settings }) => {
    const [reports, setReports] = useState(settings.reports || []);
    const [selectedReportId, setSelectedReportId] = useState(settings.selectedReportId ? String(settings.selectedReportId) : '');
    const [viewConfig, setViewConfig] = useState(settings.viewConfig || null);
    const [isLoadingView, setIsLoadingView] = useState(false);
    const [viewError, setViewError] = useState(null);
    const [createOpen, setCreateOpen] = useState(Boolean(settings.openCreate));
    const [readyToLoadView, setReadyToLoadView] = useState(!settings.openCreate);
    const viewRequestRef = useRef(null);

    const allReportHandles = useMemo(() => {
        return [...new Set([
            ...(settings.reportHandles || []),
            ...reports.map((report) => { return report.handle; }),
        ].filter(Boolean))];
    }, [reports, settings.reportHandles]);

    const loadViewConfig = useCallback(async (reportId) => {
        viewRequestRef.current?.abort();

        if (!reportId) {
            setViewConfig(null);
            setIsLoadingView(false);
            return;
        }

        const controller = new AbortController();
        viewRequestRef.current = controller;
        setIsLoadingView(true);
        setViewError(null);

        try {
            const url = new URL(settings.viewConfigUrl, window.location.origin);
            url.searchParams.set('id', String(reportId));

            const response = await fetch(url, {
                signal: controller.signal,
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            if (!response.ok) {
                throw new Error(await readLoadReportError(response));
            }

            const payload = await response.json();
            if (!controller.signal.aborted) {
                setViewConfig(payload);
            }
        } catch (error) {
            if (!controller.signal.aborted) {
                setViewError(error instanceof Error ? error.message : Craft.t('formie', 'Unable to load report.'));
                setViewConfig(null);
            }
        } finally {
            if (!controller.signal.aborted) {
                setIsLoadingView(false);
            }
        }
    }, [settings.viewConfigUrl]);

    useEffect(() => {
        if (readyToLoadView && !settings.viewConfig) {
            loadViewConfig(settings.selectedReportId);
        }

        return () => { viewRequestRef.current?.abort(); };
    }, [loadViewConfig, readyToLoadView, settings.selectedReportId, settings.viewConfig]);

    const handleCreateShown = useCallback(() => {
        setReadyToLoadView(true);
    }, []);

    const navigateToReport = useCallback((report) => {
        if (!report?.handle) {
            replaceBrowserUrl(settings.dashboardUrl || getReportUrl());
            return;
        }

        replaceBrowserUrl(report.viewUrl || getReportUrl(report.handle));
    }, [settings.dashboardUrl]);

    const handleReportChange = (reportId) => {
        const nextReportId = String(reportId ?? '');
        const report = reports.find((entry) => { return entry.value === nextReportId; }) || null;

        setSelectedReportId(nextReportId);
        navigateToReport(report);
        loadViewConfig(nextReportId);
    };

    const handleCreated = (payload) => {
        viewRequestRef.current?.abort();
        setIsLoadingView(false);
        setViewError(null);
        const nextReport = {
            value: String(payload.report.id),
            label: payload.report.name,
            handle: payload.report.handle,
            editUrl: payload.viewConfig?.editUrl || null,
            viewUrl: payload.redirect || getReportUrl(payload.report.handle),
        };

        setReports((currentReports) => { return [...currentReports, nextReport]; });
        setSelectedReportId(nextReport.value);
        setViewConfig(payload.viewConfig);
        replaceBrowserUrl(nextReport.viewUrl);
        setCreateOpen(false);
    };

    const openCreateModal = () => {
        setCreateOpen(true);
        replaceBrowserUrl(settings.createPageUrl || getCreateUrl());
    };

    const handleCreateOpenChange = (open) => {
        if (open === createOpen) {
            return;
        }

        setCreateOpen(open);

        if (open) {
            replaceBrowserUrl(settings.createPageUrl || getCreateUrl());
            return;
        }

        setReadyToLoadView(true);
        const selectedReport = reports.find((report) => { return report.value === selectedReportId; }) || null;
        navigateToReport(selectedReport);
    };

    useEffect(() => {
        if (settings.openCreate && settings.canManageReports) {
            setCreateOpen(true);
        }
    }, [settings.canManageReports, settings.openCreate]);

    const selectedReport = reports.find((report) => { return report.value === selectedReportId; }) || null;
    const editUrl = selectedReport?.editUrl || null;

    if (!reports.length) {
        return (
            <div className="w-full">
                <StatePanel
                    variant="empty"
                    size="lg"
                    icon="empty-set"
                    heading={Craft.t('formie', 'No reports created')}
                    className="py-20"
                >
                    {Craft.t('formie', 'Create a report to filter, chart, and export submission data across your forms. Reports are saved views you can open any time, download as CSV, or schedule by email.')}

                    {settings.canManageReports ? (
                        <Button slot="actions" type="button" variant="primary" onClick={openCreateModal}>
                            <Icon slot="start" icon="plus" className="size-3" />
                            {Craft.t('formie', 'New report')}
                        </Button>
                    ) : null}
                </StatePanel>

                {settings.canManageReports ? (
                    <CreateReportModal
                        open={createOpen}
                        onOpenChange={handleCreateOpenChange}
                        createActionUrl={settings.createActionUrl}
                        csrfTokenName={settings.csrfTokenName}
                        csrfTokenValue={settings.csrfTokenValue}
                        reservedHandles={settings.reservedHandles}
                        reportHandles={allReportHandles}
                        formOptions={settings.formOptions}
                        formOptionsUrl={settings.formOptionsUrl}
                        onShown={handleCreateShown}
                        onCreated={handleCreated}
                    />
                ) : null}
            </div>
        );
    }

    return (
        <>
            <div className="flex w-full flex-col gap-4" aria-busy={isLoadingView}>
                <header className="flex flex-wrap items-center justify-between gap-4">
                    <div className="flex min-w-0 items-center gap-3">
                        <h1 className="truncate text-lg font-bold text-gray-900">
                            {Craft.t('formie', 'Reports')}
                        </h1>

                        {reports.length > 1 ? (
                            <SelectInput
                                value={selectedReportId}
                                options={reports}
                                onChange={(value) => { handleReportChange(value); }}
                                triggerClassName="min-w-[220px]"
                            />
                        ) : null}
                    </div>

                    <div className="flex items-center gap-2">
                        {settings.canManageReports && editUrl ? (
                            <Button href={editUrl}>
                                {Craft.t('app', 'Edit')}
                            </Button>
                        ) : null}
                        {settings.canManageReports ? (
                            <Button type="button" variant="primary" onClick={openCreateModal}>
                                <Icon slot="start" icon="plus" className="size-3" />
                                {Craft.t('formie', 'New report')}
                            </Button>
                        ) : null}
                    </div>
                </header>

                {viewError ? (
                    <ErrorState
                        heading={Craft.t('formie', 'Unable to load report')}
                        message={viewError}
                        actionLabel={Craft.t('formie', 'Try Again')}
                        onAction={() => { loadViewConfig(selectedReportId); }}
                        showDetails={false}
                        size="sm"
                    />
                ) : null}

                {!viewConfig && !viewError ? (
                    <ReportLoadingState chartEnabled={selectedReport?.chartEnabled} />
                ) : null}

                {viewConfig ? (
                    <MemoizedReportView
                        key={viewConfig.report?.id}
                        settings={viewConfig}
                        embedded
                    />
                ) : null}
            </div>

            {settings.canManageReports ? (
                <CreateReportModal
                    open={createOpen}
                    onOpenChange={handleCreateOpenChange}
                    createActionUrl={settings.createActionUrl}
                    csrfTokenName={settings.csrfTokenName}
                    csrfTokenValue={settings.csrfTokenValue}
                    reservedHandles={settings.reservedHandles}
                    reportHandles={allReportHandles}
                    formOptions={settings.formOptions}
                    formOptionsUrl={settings.formOptionsUrl}
                    onShown={handleCreateShown}
                    onCreated={handleCreated}
                />
            ) : null}
        </>
    );
};

export { styles as reportsDashboardStyles };

import { useCallback, useEffect, useMemo, useState } from 'react';

import { getErrorMessage } from '@verbb/plugin-kit-core';
import { Button, Icon, SelectInput } from '@verbb/plugin-kit-react/components';

import { StatePanel } from '@utils';
import { CreateReportModal } from '@reports/components/CreateReportModal';
import { ReportViewApp } from '@reports/components/ReportViewApp';

import styles from '@reports/css/style.css?inline';

const getReportUrl = (handle) => {
    if (!handle) {
        return Craft.getUrl('formie/reports');
    }

    return Craft.getUrl(`formie/reports/${handle}`);
};

const getCreateUrl = () => Craft.getUrl('formie/reports/create');

const replaceBrowserUrl = (url) => {
    window.history.replaceState({}, '', url);
};

/**
 * Append a path segment before any query string.
 * Craft `cpUrl()` often includes `?site=…`; naively doing `${base}/${id}` turns into
 * `view-config?site=default/3` and 404s.
 */
const appendUrlPathSegment = (baseUrl, segment) => {
    const url = new URL(baseUrl, window.location.origin);
    url.pathname = `${url.pathname.replace(/\/$/, '')}/${encodeURIComponent(String(segment))}`;
    return url.toString();
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

    const allReportHandles = useMemo(() => {
        return [...new Set([
            ...(settings.reportHandles || []),
            ...reports.map((report) => report.handle),
        ].filter(Boolean))];
    }, [reports, settings.reportHandles]);

    const loadViewConfig = useCallback(async (reportId) => {
        if (!reportId) {
            setViewConfig(null);
            return;
        }

        setIsLoadingView(true);
        setViewError(null);

        try {
            const response = await fetch(appendUrlPathSegment(settings.viewConfigUrl, reportId), {
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            if (!response.ok) {
                throw new Error(await readLoadReportError(response));
            }

            const payload = await response.json();
            setViewConfig(payload);
        } catch (error) {
            setViewError(error instanceof Error ? error.message : Craft.t('formie', 'Unable to load report.'));
            setViewConfig(null);
        } finally {
            setIsLoadingView(false);
        }
    }, [settings.viewConfigUrl]);

    const navigateToReport = useCallback((report) => {
        if (!report?.handle) {
            replaceBrowserUrl(settings.dashboardUrl || getReportUrl());
            return;
        }

        replaceBrowserUrl(report.viewUrl || getReportUrl(report.handle));
    }, [settings.dashboardUrl]);

    const handleReportChange = (reportId) => {
        const nextReportId = String(reportId ?? '');
        const report = reports.find((entry) => entry.value === nextReportId) || null;

        setSelectedReportId(nextReportId);
        navigateToReport(report);
        loadViewConfig(nextReportId);
    };

    const handleCreated = (payload) => {
        const nextReport = {
            value: String(payload.report.id),
            label: payload.report.name,
            handle: payload.report.handle,
            editUrl: payload.viewConfig?.editUrl || null,
            viewUrl: payload.redirect || getReportUrl(payload.report.handle),
        };

        setReports((currentReports) => [...currentReports, nextReport]);
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
        setCreateOpen(open);

        if (open) {
            replaceBrowserUrl(settings.createPageUrl || getCreateUrl());
            return;
        }

        const selectedReport = reports.find((report) => report.value === selectedReportId) || null;
        navigateToReport(selectedReport);
    };

    useEffect(() => {
        if (settings.openCreate && settings.canManageReports) {
            setCreateOpen(true);
        }
    }, [settings.canManageReports, settings.openCreate]);

    const selectedReport = reports.find((report) => report.value === selectedReportId) || null;
    const editUrl = selectedReport?.editUrl || null;

    if (!reports.length) {
        return (
            <div className="w-full">
                <StatePanel
                    variant="empty"
                    icon="empty-set"
                    title={Craft.t('formie', 'No reports created')}
                    message={Craft.t('formie', 'Create a report to filter, chart, and export submission data across your forms. Reports are saved views you can open any time, download as CSV, or schedule by email.')}
                    containerClassName="py-20"
                    contentClassName="mx-auto flex w-[90%] max-w-[560px] flex-col items-center text-center"
                    messageClassName="mb-5 text-sm text-gray-500"
                >
                    {settings.canManageReports ? (
                        <Button type="button" variant="primary" onClick={openCreateModal}>
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
                        onCreated={handleCreated}
                    />
                ) : null}
            </div>
        );
    }

    return (
        <div className="w-full space-y-4">
            <header className="mb-4 flex flex-wrap items-center justify-between gap-4">
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
                <p className="text-sm text-rose-600">{viewError}</p>
            ) : null}

            {isLoadingView && !viewConfig ? (
                <p className="text-sm text-gray-500">{Craft.t('app', 'Loading')}</p>
            ) : null}

            {viewConfig ? (
                <ReportViewApp
                    key={viewConfig.report?.id}
                    settings={viewConfig}
                    embedded
                />
            ) : null}

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
                    onCreated={handleCreated}
                />
            ) : null}
        </div>
    );
};

export { styles as reportsDashboardStyles };

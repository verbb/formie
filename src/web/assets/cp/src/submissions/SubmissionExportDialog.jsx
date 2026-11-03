import { useEffect, useMemo, useRef, useState } from 'react';

import { ALL_VALUE, Alert, Button, CheckboxSelect, Dialog, SelectInput, Spinner } from '@verbb/plugin-kit-react/components';
import { FieldLayout } from '@verbb/plugin-kit-react/forms';
import { hostRequest } from '@verbb/plugin-kit-react/utils';

import { ReportFormsSelect } from '@reports/components/ReportFormsSelect';
import { ReportViewerDateRange } from '@reports/components/ReportViewerDateRange';
import { runReportExport } from '@reports/utils/reportExport';
import { EXPORT_FORMATS } from '@reports/utils/reportExportFormats';

import {
    getFormSelectionKey,
    getSubmissionExportParams,
    getSubmissionTypes,
    loadStoredExportColumns,
    loadStoredExportFormat,
    mergeExportColumns,
    persistExportColumns,
    persistExportFormat,
    resolveStatusIds,
} from './utils/submissionExportState.js';
import { SubmissionExportColumns } from './SubmissionExportColumns.jsx';

const COUNT_DEBOUNCE_MS = 300;

const SUBMISSION_TYPE_OPTIONS = [
    { value: 'complete', label: 'Complete' },
    { value: 'incomplete', label: 'Incomplete' },
    { value: 'spam', label: 'Spam' },
];

const requestErrorMessage = (error, fallback) => {
    return error?.response?.data?.message || error?.message || fallback;
};

export function SubmissionExportDialog({ defaults, onClose }) {
    const [open, setOpen] = useState(true);
    const [options, setOptions] = useState(null);
    const [formIds, setFormIds] = useState(defaults.formIds);
    const [submissionTypes, setSubmissionTypes] = useState(() => {return getSubmissionTypes(defaults)});
    const [statusIds, setStatusIds] = useState([]);
    const [siteId, setSiteId] = useState(defaults.siteId);
    const [dateRange, setDateRange] = useState(defaults.dateRange);
    const [format, setFormat] = useState(() => {return loadStoredExportFormat(EXPORT_FORMATS)});
    const [columns, setColumns] = useState([]);
    const [defaultColumns, setDefaultColumns] = useState([]);
    const [columnGroups, setColumnGroups] = useState([]);
    const [columnsLoading, setColumnsLoading] = useState(true);
    const [count, setCount] = useState(null);
    const [error, setError] = useState('');
    const [submitting, setSubmitting] = useState(false);
    const [queued, setQueued] = useState(false);
    const columnsRef = useRef(columns);
    const submitInFlight = useRef(false);

    columnsRef.current = columns;

    const filterParams = useMemo(() => {return getSubmissionExportParams({
        formIds,
        submissionTypes,
        statusIds,
        siteId,
    })}, [formIds, siteId, statusIds, submissionTypes]);
    const formKey = getFormSelectionKey(formIds);
    const hasForms = formIds === '*' || formIds.length > 0;
    const enabledColumnCount = columns.filter((column) => {return column.enabled}).length;

    useEffect(() => {
        let active = true;

        hostRequest('POST', 'formie/reports/submission-export-options')
            .then(({ data }) => {
                if (active) {
                    setOptions(data);
                    setStatusIds(resolveStatusIds(defaults.status, data.statuses));
                }
            })
            .catch((requestError) => {
                if (active) {
                    setError(requestErrorMessage(requestError, Craft.t('formie', 'Unable to load the export options.')));
                }
            });

        return () => {
            active = false;
        };
    }, [defaults.status]);

    // Columns depend only on the selected forms, so filter changes don't refetch them.
    useEffect(() => {
        let active = true;

        if (!hasForms) {
            setColumnsLoading(false);

            return undefined;
        }

        setColumnsLoading(true);

        hostRequest('POST', 'formie/reports/submission-export-columns', { data: { formIds } })
            .then(({ data }) => {
                if (!active) {
                    return;
                }

                const stored = loadStoredExportColumns(formIds);
                const current = stored.length ? stored : columnsRef.current;

                setDefaultColumns(data.columns);
                setColumnGroups(data.groups);
                setColumns(mergeExportColumns(current, data.columns));
            })
            .catch((requestError) => {
                if (active) {
                    setError(requestErrorMessage(requestError, Craft.t('formie', 'Unable to load the export columns.')));
                }
            })
            .finally(() => {
                if (active) {
                    setColumnsLoading(false);
                }
            });

        return () => {
            active = false;
        };
        // eslint-disable-next-line react-hooks/exhaustive-deps -- formKey identifies the selected forms.
    }, [formKey]);

    useEffect(() => {
        let active = true;

        if (!hasForms) {
            setCount(0);

            return undefined;
        }

        setCount(null);

        const timeout = window.setTimeout(() => {
            hostRequest('POST', 'formie/reports/submission-export-count', {
                data: {
                    ...filterParams,
                    search: defaults.search,
                    startDate: dateRange.startDate,
                    endDate: dateRange.endDate,
                },
            })
                .then(({ data }) => {
                    if (active) {
                        setCount(data.count);
                    }
                })
                .catch((requestError) => {
                    if (active) {
                        setError(requestErrorMessage(requestError, Craft.t('formie', 'Unable to count the submissions.')));
                    }
                });
        }, COUNT_DEBOUNCE_MS);

        return () => {
            active = false;
            window.clearTimeout(timeout);
        };
    }, [dateRange.endDate, dateRange.startDate, defaults.search, filterParams, hasForms]);

    const close = () => {
        setOpen(false);
        onClose?.();
    };

    const updateColumns = (nextColumns) => {
        setColumns(nextColumns);
        persistExportColumns(formIds, nextColumns);
    };

    const resetColumns = () => {
        setColumns(defaultColumns);
        persistExportColumns(formIds, null);
    };

    const submit = async(event) => {
        event.preventDefault();

        if (submitInFlight.current) {
            return;
        }

        if (!hasForms) {
            setError(Craft.t('formie', 'Choose at least one form.'));
            return;
        }

        if (!enabledColumnCount) {
            setError(Craft.t('formie', 'Choose at least one column.'));
            return;
        }

        if (count === 0) {
            setError(Craft.t('formie', 'No submissions match these settings.'));
            return;
        }

        submitInFlight.current = true;
        setSubmitting(true);
        setError('');
        persistExportFormat(format);

        try {
            const result = await runReportExport({
                exportUrl: Craft.getActionUrl('formie/reports/submission-export'),
                csrfTokenName: Craft.csrfTokenName,
                csrfTokenValue: Craft.csrfTokenValue,
                format,
                viewerColumns: columns,
                search: defaults.search,
                sort: defaults.sort,
                dateRange,
                params: filterParams,
                onQueued: () => {
                    setQueued(true);
                },
            });

            if (!result.queued) {
                close();
            }
        } catch (exportError) {
            setQueued(false);
            setError(exportError?.message || Craft.t('formie', 'Export failed.'));
        } finally {
            submitInFlight.current = false;
            setSubmitting(false);
        }
    };

    const formId = 'formie-submission-export';
    const willQueue = options && count !== null && count > options.asyncExportRowThreshold;
    const submitLabel = count === null
        ? Craft.t('formie', 'Export')
        : Craft.t('formie', 'Export {count, number} {count, plural, =1{submission} other{submissions}}', { count });
    return (
        <Dialog
            open={open}
            label={Craft.t('formie', 'Export Submissions')}
            description={Craft.t('formie', 'These settings start from your current view. Change anything you need for this export.')}
            style={{
                '--pk-dialog-width': 'min(100vw - 2rem, 44rem)',
                '--pk-dialog-max-width': 'min(100vw - 2rem, 44rem)',
            }}
            onPkOpenChange={(event) => {
                if (!(event.detail?.open ?? event.target?.open ?? false)) {
                    close();
                }
            }}
        >
            <form id={formId} className="flex flex-col gap-4" onSubmit={submit}>
                {!options && !error ? (
                    <div className="flex min-h-40 items-center justify-center">
                        <Spinner />
                    </div>
                ) : null}

                {options ? (
                    <>
                        {defaults.hasUncarriedFilters ? (
                            <Alert variant="warning" size="sm">
                                {Craft.t('formie', 'Filters from the index’s filter menu or a custom source aren’t included. Use these settings to narrow the export.')}
                            </Alert>
                        ) : null}

                        <FieldLayout name="formIds" label={Craft.t('formie', 'Forms')}>
                            <ReportFormsSelect
                                formIds={formIds === '*' ? ['*'] : formIds}
                                options={options.forms}
                                includeAllOption
                                onChange={(nextFormIds) => {
                                    setFormIds(nextFormIds);
                                    setError('');
                                }}
                            />
                        </FieldLayout>

                        {defaults.search ? (
                            <p className="m-0 text-sm text-gray-600">
                                {Craft.t('formie', 'Only submissions matching your search for “{search}” are included.', { search: defaults.search })}
                            </p>
                        ) : null}

                        {formIds === '*' || formIds.length > 1 ? (
                            <p className="m-0 text-sm text-gray-600">
                                {Craft.t('formie', 'This export has a column for each form’s fields. Forms with fields that share a handle share a column.')}
                            </p>
                        ) : null}

                        {options.sites.length > 1 ? (
                            <FieldLayout name="siteId" label={Craft.t('formie', 'Site')}>
                                <SelectInput
                                    name="siteId"
                                    aria-label={Craft.t('formie', 'Site')}
                                    options={[
                                        { value: '', label: Craft.t('formie', 'All Sites') },
                                        ...options.sites,
                                    ]}
                                    value={siteId}
                                    onChange={(value) => { setSiteId(String(value || '')); }}
                                />
                            </FieldLayout>
                        ) : null}

                        <FieldLayout name="submissionTypes" label={Craft.t('formie', 'Submission Types')}>
                            <CheckboxSelect
                                value={submissionTypes}
                                options={SUBMISSION_TYPE_OPTIONS.map((option) => {return {
                                    value: option.value,
                                    label: Craft.t('formie', option.label),
                                }})}
                                onChange={(selected) => {
                                    setSubmissionTypes(Array.isArray(selected) ? selected : []);
                                }}
                            />
                        </FieldLayout>

                        <FieldLayout name="statusIds" label={Craft.t('formie', 'Statuses')}>
                            <CheckboxSelect
                                value={statusIds.length ? statusIds : ALL_VALUE}
                                options={options.statuses}
                                showAllOption
                                allLabel={Craft.t('formie', 'All Statuses')}
                                onChange={(selected) => {
                                    setStatusIds(selected === ALL_VALUE || selected.includes?.(ALL_VALUE) ? [] : selected);
                                }}
                            />
                        </FieldLayout>

                        <FieldLayout name="dateRange" label={Craft.t('formie', 'Date Range')}>
                            <ReportViewerDateRange
                                startDate={dateRange.startDate}
                                endDate={dateRange.endDate}
                                onChange={setDateRange}
                            />
                        </FieldLayout>

                        <FieldLayout name="columns" label={Craft.t('formie', 'Columns')}>
                            <SubmissionExportColumns
                                columns={columns}
                                groups={columnGroups}
                                loading={columnsLoading}
                                onChange={updateColumns}
                                onReset={resetColumns}
                            />
                        </FieldLayout>

                        <FieldLayout name="format" label={Craft.t('formie', 'Format')}>
                            <SelectInput
                                name="format"
                                aria-label={Craft.t('formie', 'Format')}
                                options={EXPORT_FORMATS}
                                value={format}
                                onChange={(value) => { setFormat(String(value)); }}
                            />
                        </FieldLayout>
                    </>
                ) : null}

                {queued ? (
                    <Alert variant="info" announce="polite" size="sm">
                        {Craft.t('formie', 'This export is running in the background. You can close this window and your download will start when it’s ready. If you leave this page, a download link will be emailed to you.')}
                    </Alert>
                ) : null}

                {willQueue && !queued ? (
                    <p className="m-0 text-sm text-gray-600">
                        {Craft.t('formie', 'This export will run in the background, and your download will start when it’s ready.')}
                    </p>
                ) : null}

                {error ? (
                    <Alert variant="error" announce="assertive" size="sm">
                        {error}
                    </Alert>
                ) : null}
            </form>

            <Button slot="footer" type="button" onClick={close}>
                {queued ? Craft.t('formie', 'Close') : Craft.t('formie', 'Cancel')}
            </Button>
            {!queued ? (
                <Button
                    slot="footer"
                    type="submit"
                    form={formId}
                    variant="primary"
                    loading={submitting}
                    disabled={!options}
                    onClick={submit}
                >
                    {submitLabel}
                </Button>
            ) : null}
        </Dialog>
    );
}

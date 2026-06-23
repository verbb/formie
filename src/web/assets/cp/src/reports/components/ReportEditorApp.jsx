import { startTransition, useEffect, useMemo, useState } from 'react';

import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faPlus } from '@fortawesome/pro-solid-svg-icons';

import {
    ALL_VALUE,
    Button,
    CheckboxSelect,
    Input,
    Lightswitch,
    PaneTabs,
    PaneTabsContent,
    PaneTabsList,
    PaneTabsTrigger,
    SelectInput,
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@verbb/plugin-kit-react/components';
import { FieldLayout } from '@verbb/plugin-kit-react/forms';
import { FormieErrorsPane } from '@utils/FormieErrorsPane';
import { useCpFormPayloadSync } from '@utils';
import {
    ReportColumnsEditor,
    compactColumnsForStorage,
    mergeReportColumns,
} from '@reports/components/ReportColumnsEditor';
import { ReportDateBoundEditor } from '@reports/components/ReportDateBoundEditor';
import { ReportExportFilenameField } from '@reports/components/ReportExportFilenameField';
import { DEFAULT_END_DATE_BOUND, DEFAULT_START_DATE_BOUND } from '@reports/utils/reportDateBound';
import { ReportFormsSelect } from '@reports/components/ReportFormsSelect';
import { useReportFieldColumns } from '@reports/utils/useReportFieldColumns';
import {
    FIELD_COLUMNS_MODE_ALL,
    FIELD_COLUMNS_MODE_SELECTED,
    isAllFieldColumnsMode,
} from '@reports/utils/reportColumnModes';

import styles from '@reports/css/style.css?inline';

const REPORT_TAB_CONTENT_CLASS = 'flex flex-col gap-5 p-6 max-[640px]:p-4';

const REPORT_TABS = ['general', 'filters', 'columns', 'display', 'scheduled'];

const getAvailableReportTabs = (canManageScheduled) => {
    return canManageScheduled
        ? REPORT_TABS
        : REPORT_TABS.filter((tab) => tab !== 'scheduled');
};

const getReportTabHashFromLocation = () => {
    return window.location.hash.replace(/^#/, '');
};

const getInitialReportTabHash = () => {
    if (window.LOCATION_HASH) {
        return String(window.LOCATION_HASH);
    }

    return getReportTabHashFromLocation();
};

const getReportTabFromHash = (canManageScheduled, hash = getInitialReportTabHash()) => {
    const availableTabs = getAvailableReportTabs(canManageScheduled);

    if (hash && availableTabs.includes(hash)) {
        return hash;
    }

    return 'general';
};

const setReportTabHash = (tab) => {
    const url = new URL(window.location.href);
    url.hash = tab === 'general' ? '' : tab;
    window.history.replaceState({}, '', url.toString());
};

const SUBMISSION_TYPE_OPTIONS = [
    { value: 'complete', label: 'Complete' },
    { value: 'incomplete', label: 'Incomplete' },
    { value: 'spam', label: 'Spam' },
];

const getSubmissionTypesValue = (filters = {}) => {
    const selected = [];

    if (filters.includeComplete !== false) {
        selected.push('complete');
    }

    if (filters.includeIncomplete !== false) {
        selected.push('incomplete');
    }

    if (filters.includeSpam) {
        selected.push('spam');
    }

    return selected;
};

const syncHiddenInput = (inputId, value) => {
    const input = document.getElementById(inputId);

    if (!input) {
        return;
    }

    input.value = value;
    input.dispatchEvent(new Event('change', { bubbles: true }));
};

const getFieldErrors = (errors, field) => {
    const value = errors?.[field];

    if (Array.isArray(value)) {
        return value.filter(Boolean);
    }

    return value ? [String(value)] : [];
};

const flattenModelErrors = (errors) => {
    if (!errors || typeof errors !== 'object') {
        return [];
    }

    return Object.values(errors).flatMap((value) => {
        if (Array.isArray(value)) {
            return value.filter(Boolean).map(String);
        }

        return value ? [String(value)] : [];
    });
};

const normalizeStatusIdsForEditor = (statusIds) => {
    if (!statusIds?.length) {
        return [ALL_VALUE];
    }

    return statusIds.map(String);
};

const normalizeStatusIdsForPayload = (selectedValues) => {
    if (!selectedValues?.length || selectedValues.includes(ALL_VALUE)) {
        return [];
    }

    return selectedValues.map((value) => parseInt(value, 10)).filter(Boolean);
};

export const ReportEditorApp = ({ settings }) => {
    const [values, setValues] = useState(settings.values || {});
    const modelErrors = settings.errors || {};
    const filters = values.filters || {};
    const display = values.display || {};
    const [activeTab, setActiveTab] = useState(() => getReportTabFromHash(settings.canManageScheduled));
    const [shouldLoadFieldColumns, setShouldLoadFieldColumns] = useState(() => (
        getReportTabFromHash(settings.canManageScheduled) === 'columns'
    ));
    const fieldColumnsMode = display.fieldColumnsMode || FIELD_COLUMNS_MODE_ALL;
    const usesAllFieldColumns = isAllFieldColumnsMode(fieldColumnsMode);
    const shouldFetchFieldCatalog = shouldLoadFieldColumns && !usesAllFieldColumns;
    const { fieldColumnGroups, isLoading: fieldColumnsLoading } = useReportFieldColumns({
        formIds: filters.formIds,
        fieldColumnsUrl: settings.fieldColumnsUrl,
        csrfTokenName: Craft.csrfTokenName,
        csrfTokenValue: Craft.csrfTokenValue,
        enabled: shouldFetchFieldCatalog,
    });
    const editorColumns = useMemo(() => {
        return mergeReportColumns(
            values.columns,
            settings.attributeColumns,
            [],
        );
    }, [values.columns, settings.attributeColumns]);
    const payload = useMemo(() => values, [values]);

    useCpFormPayloadSync({
        inputId: settings.payloadInputId,
        payload,
        onBeforeSubmit: () => {
            syncHiddenInput('report-name', values.name || '');
            syncHiddenInput('report-handle', values.handle || '');
        },
    });

    useEffect(() => {
        syncHiddenInput('report-name', values.name || '');
        syncHiddenInput('report-handle', values.handle || '');
    }, [values.name, values.handle]);

    const errorMessages = useMemo(() => flattenModelErrors(modelErrors), [modelErrors]);

    const updateValue = (path, value) => {
        setValues((currentValues) => {
            const nextValues = { ...currentValues };
            const keys = path.split('.');
            let cursor = nextValues;

            keys.slice(0, -1).forEach((key) => {
                cursor[key] = { ...(cursor[key] || {}) };
                cursor = cursor[key];
            });

            cursor[keys[keys.length - 1]] = value;

            return nextValues;
        });
    };

    const exportSettings = values.export || {};
    const chart = values.chart || {};
    const fieldColumnModeOptions = useMemo(() => ([
        {
            value: FIELD_COLUMNS_MODE_ALL,
            label: Craft.t('formie', 'All Form Fields'),
        },
        {
            value: FIELD_COLUMNS_MODE_SELECTED,
            label: Craft.t('formie', 'Choose fields manually'),
        },
    ]), []);
    const handleFieldColumnsModeChange = (nextMode) => {
        const normalizedMode = nextMode === FIELD_COLUMNS_MODE_SELECTED
            ? FIELD_COLUMNS_MODE_SELECTED
            : FIELD_COLUMNS_MODE_ALL;

        setValues((currentValues) => {
            const nextColumns = normalizedMode === FIELD_COLUMNS_MODE_ALL
                ? compactColumnsForStorage(
                    mergeReportColumns(
                        currentValues.columns,
                        settings.attributeColumns,
                        [],
                    ),
                    FIELD_COLUMNS_MODE_ALL,
                )
                : currentValues.columns;

            return {
                ...currentValues,
                display: {
                    ...(currentValues.display || {}),
                    fieldColumnsMode: normalizedMode,
                },
                columns: nextColumns,
            };
        });
    };
    const statusIdsValue = !filters.statusIds?.length
        ? ALL_VALUE
        : normalizeStatusIdsForEditor(filters.statusIds);
    const submissionTypesValue = getSubmissionTypesValue(filters);
    const submissionTypeOptions = SUBMISSION_TYPE_OPTIONS.map((option) => ({
        value: option.value,
        label: Craft.t('formie', option.label),
    }));

    useEffect(() => {
        if (activeTab === 'columns') {
            setShouldLoadFieldColumns(true);
        }
    }, [activeTab]);

    useEffect(() => {
        const handleHashChange = () => {
            setActiveTab(getReportTabFromHash(
                settings.canManageScheduled,
                getReportTabHashFromLocation(),
            ));
        };

        window.addEventListener('hashchange', handleHashChange);

        return () => {
            window.removeEventListener('hashchange', handleHashChange);
        };
    }, [settings.canManageScheduled]);

    const handleTabChange = (nextTab) => {
        setActiveTab(nextTab);
        setReportTabHash(nextTab);
    };

    return (
        <div className="flex w-full flex-col gap-4">
            {errorMessages.length ? (
                <FormieErrorsPane errors={errorMessages} />
            ) : null}

            <PaneTabs value={activeTab} onValueChange={handleTabChange} className="w-full">
                <PaneTabsList aria-label={Craft.t('formie', 'Report sections')}>
                    <PaneTabsTrigger value="general">{Craft.t('formie', 'General')}</PaneTabsTrigger>
                    <PaneTabsTrigger value="filters">{Craft.t('formie', 'Filters')}</PaneTabsTrigger>
                    <PaneTabsTrigger value="columns">{Craft.t('formie', 'Columns')}</PaneTabsTrigger>
                    <PaneTabsTrigger value="display">{Craft.t('formie', 'Display')}</PaneTabsTrigger>
                    {settings.canManageScheduled ? (
                        <PaneTabsTrigger value="scheduled">{Craft.t('formie', 'Scheduled')}</PaneTabsTrigger>
                    ) : null}
                </PaneTabsList>

                <PaneTabsContent value="general" className={REPORT_TAB_CONTENT_CLASS}>
                    <FieldLayout
                        name="name"
                        label={Craft.t('formie', 'Name')}
                        instructions={Craft.t('formie', 'What this report will be called in the control panel.')}
                        required
                        errors={getFieldErrors(modelErrors, 'name')}
                    >
                        <Input
                            value={values.name || ''}
                            disabled={!settings.canEdit}
                            onChange={(event) => { updateValue('name', event.target.value); }}
                        />
                    </FieldLayout>

                    <FieldLayout
                        name="handle"
                        label={Craft.t('formie', 'Handle')}
                        instructions={Craft.t('formie', 'How you’ll refer to this report in templates and URLs.')}
                        required
                        errors={getFieldErrors(modelErrors, 'handle')}
                    >
                        <Input
                            className="code"
                            value={values.handle || ''}
                            disabled={!settings.canEdit}
                            onChange={(event) => { updateValue('handle', event.target.value); }}
                        />
                    </FieldLayout>

                    <FieldLayout
                        name="chart.enabled"
                        label={Craft.t('formie', 'Show Chart')}
                        instructions={Craft.t('formie', 'Display a submissions chart when viewing this report.')}
                    >
                        <Lightswitch
                            checked={Boolean(chart.enabled)}
                            disabled={!settings.canEdit}
                            onCheckedChange={(enabled) => { updateValue('chart.enabled', enabled); }}
                        />
                    </FieldLayout>
                </PaneTabsContent>

                <PaneTabsContent value="filters" className={REPORT_TAB_CONTENT_CLASS}>
                    <FieldLayout
                        name="filters.formIds"
                        label={Craft.t('formie', 'Forms')}
                        instructions={Craft.t('formie', 'Search and choose one or more forms for this report, or select “All forms”.')}
                    >
                        <ReportFormsSelect
                            formIds={filters.formIds}
                            options={settings.formOptions || []}
                            includeAllOption
                            disabled={!settings.canEdit}
                            onChange={(nextFormIds) => {
                                updateValue('filters.formIds', nextFormIds);
                            }}
                        />
                    </FieldLayout>

                    <FieldLayout
                        name="filters.submissionTypes"
                        label={Craft.t('formie', 'Submission Types')}
                        instructions={Craft.t('formie', 'Choose which submission types to include in this report.')}
                    >
                        <CheckboxSelect
                            value={submissionTypesValue}
                            options={submissionTypeOptions}
                            disabled={!settings.canEdit}
                            onChange={(selectedValues) => {
                                const selected = Array.isArray(selectedValues) ? selectedValues : [];

                                setValues((currentValues) => ({
                                    ...currentValues,
                                    filters: {
                                        ...(currentValues.filters || {}),
                                        includeComplete: selected.includes('complete'),
                                        includeIncomplete: selected.includes('incomplete'),
                                        includeSpam: selected.includes('spam'),
                                    },
                                }));
                            }}
                        />
                    </FieldLayout>

                    <FieldLayout
                        name="filters.statusIds"
                        label={Craft.t('formie', 'Statuses')}
                        instructions={Craft.t('formie', 'Filter by submission status. Choose “All” to include every status.')}
                    >
                        <CheckboxSelect
                            value={statusIdsValue}
                            options={settings.statusOptions || []}
                            showAllOption
                            allLabel={Craft.t('formie', 'All Statuses')}
                            disabled={!settings.canEdit}
                            onChange={(selectedValues) => {
                                const normalizedSelection = selectedValues === ALL_VALUE
                                    ? [ALL_VALUE]
                                    : selectedValues;
                                updateValue('filters.statusIds', normalizeStatusIdsForPayload(normalizedSelection));
                            }}
                        />
                    </FieldLayout>

                    <FieldLayout
                        name="filters.startBound"
                        label={Craft.t('formie', 'Start Date')}
                        instructions={Craft.t('formie', 'Only include submissions created on or after this date. Leave as “None” for no lower limit.')}
                    >
                        <ReportDateBoundEditor
                            value={filters.startBound || DEFAULT_START_DATE_BOUND}
                            boundary="start"
                            disabled={!settings.canEdit}
                            onChange={(nextValue) => {
                                updateValue('filters.startBound', nextValue);
                            }}
                        />
                    </FieldLayout>

                    <FieldLayout
                        name="filters.endBound"
                        label={Craft.t('formie', 'End Date')}
                        instructions={Craft.t('formie', 'Only include submissions created on or before this date. Leave as “None” for no upper limit.')}
                    >
                        <ReportDateBoundEditor
                            value={filters.endBound || DEFAULT_END_DATE_BOUND}
                            boundary="end"
                            disabled={!settings.canEdit}
                            onChange={(nextValue) => {
                                updateValue('filters.endBound', nextValue);
                            }}
                        />
                    </FieldLayout>
                </PaneTabsContent>

                <PaneTabsContent value="columns" className={REPORT_TAB_CONTENT_CLASS}>
                    <p className="m-0 text-sm text-gray-500">
                        {Craft.t('formie', 'Choose which submission attributes and fields to include in this report. Drag to reorder columns for the table and export.')}
                    </p>

                    <FieldLayout
                        name="display.fieldColumnsMode"
                        label={Craft.t('formie', 'Form Fields')}
                        instructions={Craft.t('formie', 'Include every field from the filtered forms automatically, or choose specific fields manually.')}
                    >
                        <SelectInput
                            value={fieldColumnsMode}
                            options={fieldColumnModeOptions}
                            disabled={!settings.canEdit}
                            onChange={handleFieldColumnsModeChange}
                        />
                    </FieldLayout>

                    {activeTab === 'columns' ? (
                        usesAllFieldColumns || !fieldColumnsLoading ? (
                            <ReportColumnsEditor
                                columns={editorColumns}
                                disabled={!settings.canEdit}
                                scrollable
                                fieldColumnsMode={fieldColumnsMode}
                                fieldColumnGroups={fieldColumnGroups}
                                useFieldHandles={Boolean(display.useFieldHandles)}
                                onChange={(nextColumns) => {
                                    startTransition(() => {
                                        updateValue(
                                            'columns',
                                            compactColumnsForStorage(nextColumns, fieldColumnsMode),
                                        );
                                    });
                                }}
                            />
                        ) : (
                            <p className="m-0 text-sm text-gray-500">{Craft.t('formie', 'Loading field columns…')}</p>
                        )
                    ) : null}
                </PaneTabsContent>

                <PaneTabsContent value="display" className={REPORT_TAB_CONTENT_CLASS}>
                    <FieldLayout
                        name="display.useFieldHandles"
                        label={Craft.t('formie', 'Field Handles in Export Headers')}
                        instructions={Craft.t('formie', 'Export column headers using field handles instead of labels.')}
                    >
                        <Lightswitch
                            checked={Boolean(display.useFieldHandles)}
                            disabled={!settings.canEdit}
                            onCheckedChange={(enabled) => { updateValue('display.useFieldHandles', enabled); }}
                        />
                    </FieldLayout>

                    <FieldLayout
                        name="display.useOptionLabels"
                        label={Craft.t('formie', 'Option Labels in Exports')}
                        instructions={Craft.t('formie', 'For option-based fields, export the selected label instead of the stored value where possible.')}
                    >
                        <Lightswitch
                            checked={display.useOptionLabels !== false}
                            disabled={!settings.canEdit}
                            onCheckedChange={(enabled) => { updateValue('display.useOptionLabels', enabled); }}
                        />
                    </FieldLayout>

                    <FieldLayout
                        name="export.filename"
                        label={Craft.t('formie', 'Export Filename')}
                        instructions={Craft.t('formie', 'Base filename for exports. The file extension is added automatically. Leave blank to use the default.')}
                    >
                        <ReportExportFilenameField
                            value={exportSettings.filename || ''}
                            disabled={!settings.canEdit}
                            variableCategories={settings.variableCategories}
                            variableCategoryLabels={settings.variableCategoryLabels}
                            variableCategoryOrder={settings.variableCategoryOrder}
                            variableTransformerRegistry={settings.variableTransformerRegistry}
                            onChange={(nextValue) => { updateValue('export.filename', nextValue); }}
                        />
                    </FieldLayout>
                </PaneTabsContent>

                {settings.canManageScheduled ? (
                    <PaneTabsContent value="scheduled" className={REPORT_TAB_CONTENT_CLASS}>
                        <p className="m-0 text-sm text-gray-500">
                            {Craft.t('formie', 'Email this report on a schedule. Delivery uses the filters, columns, and display settings saved on this report.')}
                        </p>

                        {settings.scheduledReportsNewUrl ? (
                            <div className="mb-4">
                                <Button href={settings.scheduledReportsNewUrl} variant="primary">
                                    <FontAwesomeIcon icon={faPlus} className="size-3" /> {Craft.t('formie', 'New Scheduled Report')}
                                </Button>
                            </div>
                        ) : null}

                        {(settings.scheduledReports || []).length ? (
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead className="px-3">{Craft.t('formie', 'Name')}</TableHead>
                                        <TableHead className="px-3">{Craft.t('formie', 'Frequency')}</TableHead>
                                        <TableHead className="px-3">{Craft.t('formie', 'Last Sent')}</TableHead>
                                        <TableHead className="px-3">{Craft.t('app', 'Enabled')}</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {settings.scheduledReports.map((scheduledReport) => (
                                        <TableRow key={scheduledReport.id}>
                                            <TableCell className="px-3">
                                                <Button
                                                    href={scheduledReport.editUrl}
                                                    variant="link"
                                                    size="none"
                                                    className="h-auto p-0 font-normal text-[var(--color-link)]"
                                                >
                                                    {scheduledReport.name}
                                                </Button>
                                            </TableCell>
                                            <TableCell className="px-3">
                                                {scheduledReport.frequency === 'daily'
                                                    ? Craft.t('formie', 'Daily')
                                                    : Craft.t('formie', 'Weekly')}
                                            </TableCell>
                                            <TableCell className="px-3">
                                                {scheduledReport.lastSentAt
                                                    ? new Date(scheduledReport.lastSentAt).toLocaleString()
                                                    : '—'}
                                            </TableCell>
                                            <TableCell className="px-3">
                                                {scheduledReport.enabled ? Craft.t('app', 'Yes') : Craft.t('app', 'No')}
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        ) : (
                            <p className="text-sm text-gray-500">{Craft.t('formie', 'No scheduled deliveries for this report yet.')}</p>
                        )}
                    </PaneTabsContent>
                ) : null}
            </PaneTabs>
        </div>
    );
};

export { styles as reportEditorStyles };

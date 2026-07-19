import { takeAtLeast, createItem, generateHandle, findUniqueHandle } from '@verbb/plugin-kit-core';
import React, {
    useState, useEffect, useMemo, useRef,
} from 'react';

import {
    Button, ButtonGroup, Dialog, DropdownItem, DropdownMenu, Icon, Input, Spinner,
} from '@verbb/plugin-kit-react/components';

import { cn } from '@verbb/plugin-kit-react/utils';
import { useFormValues } from '@form-builder/hooks/useFormTools';
import { useBuilderActions } from '@form-builder/builder/useBuilderActions';
import { useFormBuilderApp } from '@form-builder/contexts/FormBuilderAppContext';
import { collectFieldHandlesFromRows } from '@form-builder/utils/duplicateField';
import { getDevToolsConfig } from '@form-builder/dev/config';
import { createMockExistingFieldsData } from '@form-builder/dev/scenarios/existingFieldsStressScenario';
import { LargeErrorState, StatePanel } from '@utils';

const getExistingFieldSettings = (field = {}) => {
    if (field?.settings && typeof field.settings === 'object') {
        return field.settings;
    }

    return field || {};
};

const getExistingFieldLabel = (field = {}) => {
    const settings = getExistingFieldSettings(field);
    return settings.label || field.label || '';
};

const getExistingFieldHandle = (field = {}) => {
    const settings = getExistingFieldSettings(field);
    return settings.handle || field.handle || '';
};

const normalizeExistingField = (field = {}) => {
    const settings = getExistingFieldSettings(field);

    return {
        ...field,
        settings,
        type: field.type || settings.type,
    };
};

const getFieldSelectionKey = (field = {}) => {
    if (field?._selectionKey) {
        return field._selectionKey;
    }

    return `${field.reference || field.id || field.handle || 'field'}:${field.type || 'unknown'}`;
};

const EXISTING_FIELDS_SEARCH_DEBOUNCE_MS = 600;

const createHandleCollisionError = ({ label = '', handle = '', scope = 'form' } = {}) => {
    const fieldLabel = label || Craft.t('formie', 'This field');
    const collisionMessage = scope === 'group'
        ? Craft.t('formie', '{name} uses the handle "{handle}", which already exists in this group. Synced fields must keep their original handle, so detach it first or rename the conflicting field.', {
            name: fieldLabel,
            handle,
        })
        : Craft.t('formie', '{name} uses the handle "{handle}", which already exists in this form. Synced fields must keep their original handle, so detach it first or rename the conflicting field.', {
            name: fieldLabel,
            handle,
        });

    return {
        heading: Craft.t('formie', 'Unable to add synced field'),
        text: collisionMessage,
    };
};

const createDisallowedFieldTypeError = ({ label = '', type = '' } = {}) => {
    return {
        heading: Craft.t('formie', 'Unable to add field'),
        text: Craft.t('formie', '{name} cannot be added inside this group.', {
            name: label || type || Craft.t('formie', 'This field'),
        }),
    };
};

const isNestedGroupPlacement = (nestedPlacement) => {
    if (!nestedPlacement || typeof nestedPlacement !== 'object') {
        return false;
    }

    return [nestedPlacement.pageIndex, nestedPlacement.rowIndex, nestedPlacement.fieldIndex]
        .every((value) => { return Number.isFinite(value); });
};

const getParentFieldFromPages = (pagesSnapshot, nestedPlacement) => {
    return pagesSnapshot?.[nestedPlacement.pageIndex]
        ?.rows?.[nestedPlacement.rowIndex]
        ?.fields?.[nestedPlacement.fieldIndex];
};

const META_KEYS_TO_STRIP = new Set([
    'id',
    'fieldId',
    'layoutId',
    'pageId',
    'rowId',
    'syncId',
    'nestedLayoutId',
    'contentTable',
    'settings',
    'isSynced',
    'reference',
]);

const stripImportedFieldMeta = (value, { keepTopLevelSync = false, depth = 0 } = {}) => {
    if (Array.isArray(value)) {
        return value.map((item) => {
            return stripImportedFieldMeta(item, { keepTopLevelSync: false, depth: depth + 1 });
        });
    }

    if (!value || typeof value !== 'object') {
        return value;
    }

    const stripped = {};

    Object.entries(value).forEach(([key, entryValue]) => {
        if (META_KEYS_TO_STRIP.has(key)) {
            if (keepTopLevelSync && depth === 0 && (key === 'fieldId' || key === 'syncId')) {
                stripped[key] = entryValue;
            }

            return;
        }

        stripped[key] = stripImportedFieldMeta(entryValue, { keepTopLevelSync: false, depth: depth + 1 });
    });

    return stripped;
};

const collectFieldHandles = (fields = [], handles = []) => {
    (fields || []).forEach((field) => {
        if (!field || typeof field !== 'object') {
            return;
        }

        const handle = field.handle || field?.settings?.handle;
        if (handle) {
            handles.push(handle);
        }

        const nestedRows = Array.isArray(field.rows) ? field.rows : [];
        nestedRows.forEach((row) => {
            collectFieldHandles(row?.fields || [], handles);
        });
    });

    return handles;
};

const filterFormBySearch = (form, searchTerm) => {
    const resolvedForm = form || {};
    const term = (searchTerm || '').toLowerCase();

    if (!term) {
        return resolvedForm;
    }

    const syncedFields = [];
    const filteredPages = (resolvedForm.pages || []).map((page) => {
        const filteredFields = (page?.fields || []).filter((field) => {
            const label = getExistingFieldLabel(field).toLowerCase();
            const handle = getExistingFieldHandle(field).toLowerCase();
            const inLabel = label.includes(term);
            const inHandle = handle.includes(term);

            // When selecting all forms, ensure we filter out duplicate synced fields
            if (resolvedForm.key === '*') {
                const definitionId = field?.fieldId || field?.syncId;

                if (field?.isSynced && definitionId && syncedFields.includes(definitionId)) {
                    return false;
                }

                if (field?.isSynced && definitionId) {
                    syncedFields.push(definitionId);
                }
            }

            return inLabel || inHandle;
        });

        return {
            ...page,
            fields: filteredFields || [],
        };
    }).filter((page) => { return page.fields.length > 0; });

    return {
        ...resolvedForm,
        pages: filteredPages || [],
    };
};

const ExistingFields = ({ onClose, nestedPlacement = null }) => {
    const formValues = useFormValues();
    const pages = formValues.pages || [];
    const { activePageHandle } = useFormBuilderApp();
    const resolvedPageIndex = pages.findIndex((page) => { return page._handle === activePageHandle; });
    const activePageIndex = resolvedPageIndex >= 0 ? resolvedPageIndex : 0;
    const { getPages, setPages } = useBuilderActions();
    const nestedGroupPlacement = isNestedGroupPlacement(nestedPlacement);
    const handleCollisionScope = nestedGroupPlacement ? 'group' : 'form';
    const allowedNestedFieldTypes = useMemo(() => {
        if (!nestedGroupPlacement || !Array.isArray(nestedPlacement?.allowedFieldTypes)) {
            return null;
        }

        return new Set(nestedPlacement.allowedFieldTypes.filter(Boolean));
    }, [nestedGroupPlacement, nestedPlacement?.allowedFieldTypes]);

    const [existingFields, setExistingFields] = useState([]);
    const [loading, setLoading] = useState(true);
    const [loadError, setLoadError] = useState(null);
    const [submitError, setSubmitError] = useState(null);
    const [search, setSearch] = useState('');
    const [selectedForm, setSelectedForm] = useState(null);
    const [selectedFields, setSelectedFields] = useState([]);
    const [mounted, setMounted] = useState(false);
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [isLoadingResults, setIsLoadingResults] = useState(false);
    const resultsRequestIdRef = useRef(0);
    const previousSelectedFormKeyRef = useRef(null);
    const searchInputRef = useRef(null);
    const builderDevSettings = useMemo(() => {
        if (!import.meta.env.DEV) {
            return null;
        }

        return getDevToolsConfig();
    }, []);
    const shouldMockExistingFields = Boolean(
        builderDevSettings?.enabled
        && builderDevSettings?.mode === 'existingFieldsStress',
    );
    const existingFieldsStressPattern = builderDevSettings?.existingFieldsPattern || '100x5x24';
    const trimmedSearch = search.trim();
    const hasSearch = Boolean(trimmedSearch);
    const meetsSearchMinimum = trimmedSearch.length >= 3;

    const normalizeForms = (forms = []) => {
        return (forms || []).map((form, formIndex) => {
            return {
                ...form,
                pages: (form.pages || []).map((page, pageIndex) => {
                    return {
                        ...page,
                        fields: (page.fields || []).map((field, fieldIndex) => {
                            const normalizedField = normalizeExistingField(field);

                            return {
                                ...normalizedField,
                                _selectionKey: `${form.key || formIndex}:${pageIndex}:${fieldIndex}:${normalizedField.reference || normalizedField.id || normalizedField.handle || 'field'}`,
                            };
                        }),
                    };
                }),
            };
        });
    };

    // Filtered fields for the currently selected form based on search.
    // For non-dev stress mode, filtering is handled server-side.
    const filteredSelectedForm = useMemo(() => {
        if (!selectedForm) {
            return selectedForm;
        }

        if (!hasSearch) {
            return selectedForm;
        }

        if (shouldMockExistingFields || !meetsSearchMinimum) {
            return filterFormBySearch(selectedForm, trimmedSearch);
        }

        return selectedForm;
    }, [selectedForm, shouldMockExistingFields, hasSearch, meetsSearchMinimum, trimmedSearch]);
    const hasFilteredSelectedFields = useMemo(() => {
        return Boolean((filteredSelectedForm?.pages || []).some((page) => {
            return Array.isArray(page?.fields) && page.fields.length > 0;
        }));
    }, [filteredSelectedForm]);

    const totalSelected = selectedFields.length;

    const submitText = (() => {
        if (totalSelected > 1) {
            return Craft.t('formie', 'Add {num} as new fields', { num: totalSelected });
        }

        if (totalSelected > 0) {
            return Craft.t('formie', 'Add {num} as new field', { num: totalSelected });
        }

        return Craft.t('formie', 'Add as new field');
    })();

    const syncedText = (() => {
        if (totalSelected > 1) {
            return Craft.t('formie', 'Add {num} as synced fields', { num: totalSelected });
        }

        if (totalSelected > 0) {
            return Craft.t('formie', 'Add {num} as synced field', { num: totalSelected });
        }

        return Craft.t('formie', 'Add as synced field');
    })();

    // Helper functions
    const buildImportedFieldData = (field, { synced = false, syncSourceId = null } = {}) => {
        const settings = getExistingFieldSettings(field);
        const source = JSON.parse(JSON.stringify({
            ...settings,
            ...field,
        }));

        const type = source.type || field.type || settings.type;
        const data = stripImportedFieldMeta(source);

        return {
            ...data,
            type,
            isSynced: synced,
            fieldId: synced ? syncSourceId : null,
            syncId: synced ? syncSourceId : null,
        };
    };

    const createNewField = (type, config) => {
        // Create a new field using the createItem utility.
        // Existing imported fields should not auto-open the edit modal.
        const newField = createItem({
            type,
            ...config,
            _isNew: false,
        });

        return newField;
    };

    // Trigger data fetching when modal opens
    useEffect(() => {
        handleOpen();
    }, []);

    // Content mounts after the dialog's open focus pass (spinner first). Focus search once ready.
    useEffect(() => {
        if (loading || loadError || !mounted || !existingFields.length) {
            return;
        }

        const frameId = window.requestAnimationFrame(() => {
            searchInputRef.current?.focus?.({ preventScroll: true });
        });

        return () => {
            window.cancelAnimationFrame(frameId);
        };
    }, [loading, loadError, mounted, existingFields.length]);

    const fetchExistingFieldsForSelectedForm = async(formKey, searchTerm = '') => {
        if (!formKey) {
            return;
        }

        if (formKey === '*' && !searchTerm.trim()) {
            setIsLoadingResults(false);

            setExistingFields((prev) => {
                return prev.map((form) => {
                    if (form.key !== formKey) {
                        return form;
                    }

                    return {
                        ...form,
                        pages: [],
                    };
                });
            });

            setSelectedForm((prev) => {
                if (!prev || prev.key !== formKey) {
                    return prev;
                }

                return {
                    ...prev,
                    pages: [],
                };
            });

            return;
        }

        const requestId = ++resultsRequestIdRef.current;
        setIsLoadingResults(true);

        try {
            const response = await Craft.sendActionRequest('POST', 'formie/forms/get-existing-fields', {
                data: {
                    formId: formValues.id,
                    compact: true,
                    includeFields: true,
                    formKey,
                    search: searchTerm,
                },
            });

            if (requestId !== resultsRequestIdRef.current) {
                return;
            }

            if (response.data?.error) {
                throw new Error(response.data.error);
            }

            const normalizedResults = normalizeForms(response.data || []);
            const resolvedResult = normalizedResults.find((form) => {
                return form.key === formKey;
            }) || {
                key: formKey,
                pages: [],
            };

            setExistingFields((prev) => {
                return prev.map((form) => {
                    if (form.key !== formKey) {
                        return form;
                    }

                    return {
                        ...form,
                        pages: resolvedResult.pages || [],
                    };
                });
            });

            setSelectedForm((prev) => {
                if (!prev || prev.key !== formKey) {
                    return prev;
                }

                return {
                    ...prev,
                    pages: resolvedResult.pages || [],
                };
            });
        } catch (error) {
            if (requestId === resultsRequestIdRef.current) {
                setLoadError(error);
            }
        } finally {
            if (requestId === resultsRequestIdRef.current) {
                setIsLoadingResults(false);
            }
        }
    };

    const fetchExistingFields = async() => {
        setLoadError(null);
        setSubmitError(null);
        setLoading(true);

        try {
            const responseForms = shouldMockExistingFields
                ? createMockExistingFieldsData([], existingFieldsStressPattern)
                : (
                    await takeAtLeast(500)(
                        Craft.sendActionRequest('POST', 'formie/forms/get-existing-fields', {
                            data: {
                                formId: formValues.id,
                                compact: true,
                                includeFields: false,
                            },
                        }),
                    )
                ).data || [];

            const normalized = normalizeForms(responseForms);
            const initialSelection = normalized.find((form) => { return form.key === '*'; })
                || normalized[0]
                || null;

            setExistingFields(normalized);
            setSelectedForm(initialSelection);

            setMounted(true);
        } catch (error) {
            setLoadError(error);
        }

        setLoading(false);
    };

    const fetchSelectedFieldConfigs = async(selected = []) => {
        const fieldIds = selected
            .map((field) => { return Number(field?.id); })
            .filter((id) => { return Number.isFinite(id) && id > 0; });

        if (!fieldIds.length) {
            return [];
        }

        const response = await Craft.sendActionRequest('POST', 'formie/forms/get-existing-field-configs', {
            data: {
                formId: formValues.id,
                fieldIds,
            },
        });

        if (response.data?.error) {
            throw new Error(response.data.error);
        }

        return response.data || [];
    };

    const handleOpen = () => {
        setLoading(true);
        setIsLoadingResults(false);
        setIsSubmitting(false);
        setSelectedFields([]);
        setSearch('');
        setLoadError(null);
        setSubmitError(null);

        // Fetch existing fields via Ajax for performance
        if (!existingFields.length) {
            fetchExistingFields();
        } else {
            // For a large amount of fields, the modal will stutter when loading, so add a little delay
            // to ensure the modal opens, then loads the fields, to help with a nice UX.
            setTimeout(() => {
                setMounted(true);
                setLoading(false);
            }, 100);
        }
    };

    const handleClose = () => {
        resultsRequestIdRef.current += 1;
        setIsSubmitting(false);
        setIsLoadingResults(false);
        setSelectedFields([]);
        setSearch('');
        setLoadError(null);
        setSubmitError(null);
        setMounted(false);
        onClose();
    };

    const selectTab = (nextSelectedForm) => {
        const nextFormKey = nextSelectedForm?.key;
        const willFetchResults = !shouldMockExistingFields && Boolean(nextFormKey) && (
            nextFormKey === '*'
                ? meetsSearchMinimum
                : (!hasSearch || meetsSearchMinimum)
        );

        // Set loading before switching tabs to prevent any intermediate
        // empty-state render between the old and new selected form.
        setIsLoadingResults(willFetchResults);
        setSelectedForm(nextSelectedForm);
    };

    useEffect(() => {
        const selectedFormKey = selectedForm?.key;

        if (!mounted || shouldMockExistingFields || !selectedFormKey) {
            return;
        }

        const keyChanged = previousSelectedFormKeyRef.current !== selectedFormKey;
        previousSelectedFormKeyRef.current = selectedFormKey;
        const willFetchResults = selectedFormKey === '*'
            ? meetsSearchMinimum
            : (!hasSearch || meetsSearchMinimum);

        // Loading for the whole debounce + request window (tab switch and search).
        setIsLoadingResults(willFetchResults);

        const timeoutId = window.setTimeout(() => {
            if (selectedFormKey === '*') {
                if (!meetsSearchMinimum) {
                    fetchExistingFieldsForSelectedForm(selectedFormKey, '');
                    return;
                }

                fetchExistingFieldsForSelectedForm(selectedFormKey, trimmedSearch);
                return;
            }

            if (hasSearch && !meetsSearchMinimum) {
                return;
            }

            fetchExistingFieldsForSelectedForm(selectedFormKey, trimmedSearch);
        }, keyChanged ? 0 : EXISTING_FIELDS_SEARCH_DEBOUNCE_MS);

        return () => {
            window.clearTimeout(timeoutId);
        };
    }, [mounted, shouldMockExistingFields, selectedForm?.key, trimmedSearch, hasSearch, meetsSearchMinimum]);

    const isFieldSelected = (field) => {
        const selectionKey = getFieldSelectionKey(field);
        return selectedFields.some((selectedField) => {
            return getFieldSelectionKey(selectedField) === selectionKey;
        });
    };

    const fieldSelected = (field, selected) => {
        const selectionKey = getFieldSelectionKey(field);

        if (selected) {
            setSelectedFields((prev) => {
                const exists = prev.some((selectedField) => {
                    return getFieldSelectionKey(selectedField) === selectionKey;
                });

                if (exists) {
                    return prev;
                }

                return [...prev, field];
            });
        } else {
            setSelectedFields((prev) => {
                return prev.filter((selectedField) => {
                    return getFieldSelectionKey(selectedField) !== selectionKey;
                });
            });
        }
    };

    const collectExistingHandles = (pagesSnapshot) => {
        if (nestedGroupPlacement) {
            const parentField = getParentFieldFromPages(pagesSnapshot, nestedPlacement);
            const handles = [];
            collectFieldHandlesFromRows(parentField?.rows || [], handles);

            return handles;
        }

        const handles = [];
        (pagesSnapshot || []).forEach((page) => {
            (page?.rows || []).forEach((row) => {
                collectFieldHandles(row?.fields || [], handles);
            });
        });

        return handles;
    };

    const addSelectedToLayout = async(selected, { synced = false } = {}) => {
        const pagesSnapshot = JSON.parse(JSON.stringify(getPages() || []));
        const selectedFieldConfigs = await fetchSelectedFieldConfigs(selected);
        const selectedFieldConfigById = new Map(selectedFieldConfigs.map((item) => {
            return [Number(item?.id), item?.field || null];
        }));
        const existingHandles = collectExistingHandles(pagesSnapshot);
        const fieldsToInsert = [];

        for (const field of selected) {
            const fieldId = Number(field?.id);
            const hasNumericFieldId = Number.isFinite(fieldId) && fieldId > 0;
            const resolvedFieldConfig = hasNumericFieldId
                ? selectedFieldConfigById.get(fieldId)
                : field;
            const syncDefinitionId = Number(
                resolvedFieldConfig?.fieldId
                || field?.fieldId
                || null,
            );

            if (!resolvedFieldConfig) {
                continue;
            }

            const resolvedFieldType = resolvedFieldConfig.type || field.type;

            if (allowedNestedFieldTypes && resolvedFieldType && !allowedNestedFieldTypes.has(resolvedFieldType)) {
                throw createDisallowedFieldTypeError({
                    label: getExistingFieldLabel(resolvedFieldConfig) || getExistingFieldLabel(field),
                    type: resolvedFieldType,
                });
            }

            const importedField = buildImportedFieldData(resolvedFieldConfig, {
                synced,
                syncSourceId: synced && Number.isFinite(syncDefinitionId) && syncDefinitionId > 0
                    ? syncDefinitionId
                    : null,
            });
            const baseHandle = importedField.handle || generateHandle(importedField.label || field?.label || '');
            const nextHandle = findUniqueHandle(baseHandle, existingHandles);

            if (synced && nextHandle !== baseHandle) {
                throw createHandleCollisionError({
                    label: importedField.label || field?.label || '',
                    handle: baseHandle,
                    scope: handleCollisionScope,
                });
            }

            importedField.handle = nextHandle;
            existingHandles.push(nextHandle);

            fieldsToInsert.push(createNewField(resolvedFieldType || field.type, importedField));
        }

        if (!fieldsToInsert.length) {
            return;
        }

        if (nestedGroupPlacement) {
            const parentField = getParentFieldFromPages(pagesSnapshot, nestedPlacement);

            if (!parentField) {
                return;
            }

            if (!Array.isArray(parentField.rows)) {
                parentField.rows = [];
            }

            fieldsToInsert.forEach((newField) => {
                parentField.rows.push({
                    ...createItem({}),
                    fields: [createItem(newField)],
                });
            });

            setPages(pagesSnapshot);

            return;
        }

        const targetPage = pagesSnapshot[activePageIndex];

        if (!targetPage) {
            return;
        }

        const nextRows = Array.isArray(targetPage.rows) ? [...targetPage.rows] : [];

        fieldsToInsert.forEach((newField) => {
            nextRows.push({
                ...createItem({}),
                fields: [createItem(newField)],
            });
        });

        pagesSnapshot[activePageIndex] = {
            ...targetPage,
            rows: nextRows,
        };

        setPages(pagesSnapshot);
    };

    const addFields = async() => {
        if (isSubmitting) {
            return;
        }

        setSubmitError(null);
        setIsSubmitting(true);

        try {
            await addSelectedToLayout(selectedFields, { synced: false });
            handleClose();
        } catch (error) {
            setSubmitError(error);
        } finally {
            setIsSubmitting(false);
        }
    };

    const addSynced = async() => {
        if (isSubmitting) {
            return;
        }

        setSubmitError(null);
        setIsSubmitting(true);

        try {
            await addSelectedToLayout(selectedFields, { synced: true });
            handleClose();
        } catch (error) {
            setSubmitError(error);
        } finally {
            setIsSubmitting(false);
        }
    };

    return (
        <Dialog
            open
            label={Craft.t('formie', 'Add Existing Field')}
            withoutBodyPadding
            // Panel size via tokens (host Tailwind never reaches shadow .dialog).
            className="formie-existing-fields-dialog"
            onPkOpenChange={(event) => {
                if (!(event.detail?.open ?? event.target?.open ?? false)) {
                    handleClose();
                }
            }}
        >
            {/*
              Fixed dialog height (66%) means loading/error/content all fill the
              same shell — no jump when the form list arrives.
            */}
            <div className="flex h-full min-h-0 flex-col overflow-hidden">
                {loadError && (
                    <LargeErrorState
                        error={loadError}
                        message={Craft.t('formie', 'Unable to load existing fields.')}
                        detailsLabel={Craft.t('formie', 'Show error details')}
                        actionLabel={Craft.t('formie', 'Try Again')}
                        onAction={handleOpen}
                        containerClassName="absolute inset-0 z-10 flex items-center justify-center bg-white"
                    />
                )}

                {loading && (
                    <div className="flex h-full min-h-0 items-center justify-center">
                        <Spinner size="lg" />
                    </div>
                )}

                {!loading && !loadError && mounted && existingFields.length && (
                    <div className="flex h-full min-h-0 flex-col md:flex-row">
                        <div className={cn(
                            'relative',
                            'bg-[#f3f7fc]',
                            'border-b md:border-b-0 md:border-r',
                            'border-[rgba(51,64,77,.1)]',
                            'overflow-auto',
                            'w-full max-h-[180px] md:max-h-none md:w-[240px] md:shrink-0',
                            'px-2 pt-3',
                        )}>
                            <div className="space-y-1">
                                {existingFields.map((form, index) => {
                                    if (form.heading) {
                                        return (
                                            <div key={index} className="mt-4 ml-[10px]">
                                                <h3 className="text-[11px] font-bold text-gray-500 uppercase">
                                                    {form.heading}
                                                </h3>
                                            </div>
                                        );
                                    }

                                    return (
                                        <Button
                                            key={index}
                                            type="button"
                                            size="none"
                                            variant="transparent"
                                            onClick={() => { return selectTab(form); }}
                                            className={cn(
                                                'formie-existing-picker-nav-item',
                                                selectedForm?.key === form.key && 'is-selected',
                                            )}
                                        >
                                            {form.label}
                                        </Button>
                                    );
                                })}
                            </div>
                        </div>

                        <div className="flex min-h-0 flex-1 flex-col">
                            <div className="border-b border-gray-100 p-3 md:p-4">
                                <Input
                                    ref={searchInputRef}
                                    autofocus
                                    value={search}
                                    onChange={(e) => { return setSearch(e.target.value); }}
                                    placeholder={Craft.t('formie', 'Search')}
                                >
                                    <Icon slot="start" icon="search" className="size-4" />
                                </Input>
                            </div>

                            <div className="flex-1 overflow-y-auto p-3 md:p-4">
                                {isLoadingResults && !shouldMockExistingFields ? (
                                    <div className="h-full flex items-center justify-center">
                                        <Spinner size="lg" />
                                    </div>
                                ) : (!shouldMockExistingFields && selectedForm?.key === '*' && !hasSearch) ? (
                                    <StatePanel
                                        variant="info"
                                        showIcon={false}
                                        message={Craft.t('formie', 'Search to browse fields across all forms.')}
                                        containerClassName="py-4"
                                        contentClassName="flex flex-col items-center text-center"
                                        messageClassName="mb-0 text-sm text-gray-500"
                                    />
                                ) : (!shouldMockExistingFields && selectedForm?.key === '*' && hasSearch && !meetsSearchMinimum) ? (
                                    <StatePanel
                                        variant="info"
                                        showIcon={false}
                                        message={Craft.t('formie', 'Type at least 3 characters to search all forms.')}
                                        containerClassName="py-4"
                                        contentClassName="flex flex-col items-center text-center"
                                        messageClassName="mb-0 text-sm text-gray-500"
                                    />
                                ) : (hasFilteredSelectedFields) ? (
                                    <div className="space-y-4">
                                        {filteredSelectedForm.pages.map((page, pIndex) => {
                                            return (
                                                <div key={pIndex}>
                                                    <div className={cn(
                                                        'relative mb-3',

                                                        'after:absolute after:top-[50%] after:left-0 after:w-full after:h-[1px] after:bg-[#e2e8f0] after:-translate-y-[50%]',
                                                    )}>
                                                        <div className={cn(
                                                            'inline-block relative bg-white pr-[10px] z-[1]',
                                                            'uppercase font-semibold text-[11px] text-slate-600',
                                                        )}>
                                                            {page.label}
                                                        </div>
                                                    </div>

                                                    <div className="grid grid-cols-1 gap-2 md:grid-cols-2">
                                                        {page.fields.map((field, fieldIndex) => {
                                                            return (
                                                                <ExistingFieldItem
                                                                    key={getFieldSelectionKey(field) || fieldIndex}
                                                                    field={field}
                                                                    selected={isFieldSelected(field)}
                                                                    onSelected={(selected) => { return fieldSelected(field, selected); }}
                                                                />
                                                            );
                                                        })}
                                                    </div>
                                                </div>
                                            );
                                        })}
                                    </div>
                                ) : (
                                    <StatePanel
                                        variant="empty"
                                        showIcon={false}
                                        message={Craft.t('formie', 'No fields found.')}
                                        containerClassName="py-4"
                                        contentClassName="flex flex-col items-center text-center"
                                        messageClassName="mb-0 text-sm text-gray-500"
                                    />
                                )}
                            </div>
                        </div>
                    </div>
                )}

                {!loading && !loadError && !mounted && (
                    <StatePanel
                        variant="empty"
                        showIcon={false}
                        message={Craft.t('formie', 'No existing fields to select.')}
                        containerClassName="h-full flex items-center justify-center"
                        contentClassName="flex flex-col items-center text-center"
                        messageClassName="mb-0 text-sm text-gray-500"
                    />
                )}
            </div>

            {submitError && (
                <div className="border-t border-rose-100 bg-rose-50/40 px-6 py-3 text-sm text-rose-600">
                    <div className="font-medium">
                        {submitError.heading || Craft.t('formie', 'Unable to add fields')}
                    </div>

                    <div className="mt-1">
                        {submitError.text || submitError.message || Craft.t('formie', 'An error has occurred.')}
                    </div>
                </div>
            )}

            {/* justify-between: Cancel left, split submit right */}
            <div slot="footer" className="flex w-full justify-between gap-2">
                <Button type="button" onClick={handleClose}>
                    {Craft.t('app', 'Cancel')}
                </Button>

                <ButtonGroup>
                    <Button
                        type="button"
                        variant="primary"
                        disabled={totalSelected === 0 || isSubmitting}
                        onClick={addFields}
                    >
                        {submitText}
                    </Button>
                    <DropdownMenu placement="bottom-end">
                        <Button
                            slot="trigger"
                            type="button"
                            variant="primary"
                            disabled={totalSelected === 0 || isSubmitting}
                            groupTrigger
                            aria-label={Craft.t('app', 'Open menu')}
                        />
                        <DropdownItem value="synced" onPkSelect={addSynced}>
                            {syncedText}
                        </DropdownItem>
                    </DropdownMenu>
                </ButtonGroup>
            </div>
        </Dialog>
    );
};

const ExistingFieldItem = ({ field, selected, onSelected }) => {
    const label = getExistingFieldLabel(field);
    const handle = getExistingFieldHandle(field);

    return (
        <div
            className={cn(
                'p-3 border border-[2px] rounded-lg cursor-pointer transition-colors',
                selected
                    ? 'border-blue-500 bg-blue-50'
                    : 'border-gray-200 hover:border-gray-300 hover:bg-gray-50',
            )}
            onClick={() => { return onSelected(!selected); }}
        >
            <div className="flex items-center gap-2">
                <div className="flex-1">
                    <div className="font-medium text-sm">
                        {label}
                    </div>

                    <div className="text-xs text-gray-500">
                        {handle}
                    </div>
                </div>
            </div>
        </div>
    );
};

export { ExistingFields };

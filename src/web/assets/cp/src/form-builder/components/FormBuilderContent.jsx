import {
    useMemo, useEffect, useRef, useState, useCallback,
} from 'react';

import { flatten } from 'flat';

import { SchemaFormEngine, useSchemaFormEngine } from '@verbb/plugin-kit-react/forms';
import {
    normalizeFormData, serializeFormData, saveForm, saveAsStencil,
} from '@form-builder/hooks/useFormTools';
import { useHandleSyncOnChange } from '@form-builder/hooks/useHandleSyncOnChange';
import { stableSerialize, useUnloadWarning } from '@form-builder/hooks/useUnloadWarning';
import { dirtyFormSnapshot } from '@form-builder/utils/formBuilderSnapshot';
import { getFieldOverrideForSite, getSiteOverrideForSite, mergeSiteOverridesIntoFormData } from '@form-builder/utils/siteOverrides';
import { FormBuilderErrorsPane } from '@form-builder/components/FormBuilderErrorsPane';
import { FormBuilderFormProvider } from '@form-builder/contexts/FormBuilderFormContext';
import { VariableCategoriesProvider } from '@form-builder/components/VariableCategoriesProvider';

import { useFormBuilderApp } from '@form-builder/contexts/FormBuilderAppContext';
import useAppStore from '@form-builder/hooks/useAppStore';
import { announceFormBuilderStatus } from '@form-builder/utils/accessibility';
import { formHasQuestionnaireFields, formHasQuizFields } from '@form-builder/utils/questionnaireFields';

function FormBuilderContent({
    formRef,
    initialData,
    schema = [],
    schemaIndex = null,
}) {
    const {
        setSaving, setSaveFeedbackState, setTitle, saveAction, setSaveAction, saveActionUrl, saveRequestData,
        saveDuplicateRequestData, saveSuccessMessage,
    } = useFormBuilderApp();
    const allowedSubmitMethods = useAppStore((state) => state.allowedSubmitMethods);
    const setSelectedTemplateId = useAppStore((state) => {
        return state.setSelectedTemplateId;
    });
    const setFormMeta = useAppStore((state) => {
        return state.setFormMeta;
    });
    const [errors, setErrors] = useState({});
    const isAjaxSubmissionForced = useAppStore((state) => {
        return state.isAjaxSubmissionForced;
    });

    const normalizedInitialData = useMemo(() => {
        const normalized = normalizeFormData(initialData || {});
        const requiresForcedAjax = isAjaxSubmissionForced(normalized)
            || allowedSubmitMethods === 'ajax';
        const requiresPageReload = allowedSubmitMethods === 'page-reload' && !isAjaxSubmissionForced(normalized);
        const currentSubmitMethod = normalized?.settings?.submitMethod;
        let submitMethod = currentSubmitMethod;

        if (requiresForcedAjax) {
            submitMethod = 'ajax';
        } else if (requiresPageReload) {
            submitMethod = 'page-reload';
        }

        return {
            ...normalized,
            settings: {
                ...(normalized?.settings || {}),
                submitMethod,
            },
        };
    }, [initialData, isAjaxSubmissionForced, allowedSubmitMethods]);

    const normalizedSchema = useMemo(() => {
        return schemaIndex?.schema ?? schema;
    }, [schema, schemaIndex]);
    const handleSyncOnChange = useHandleSyncOnChange(normalizedSchema);
    const lastTitleRef = useRef(null);
    const latestFormValuesRef = useRef(normalizedInitialData);

    const form = useSchemaFormEngine({
        schema: normalizedSchema,
        schemaIndex,
        defaultValues: normalizedInitialData,
        errors,
        getConditionContext: (values) => {
            return {
                formBuilder: {
                    ajaxSubmissionForced: isAjaxSubmissionForced(values),
                    allowedSubmitMethods: allowedSubmitMethods || 'both',
                    hasSubmissions: Boolean(useAppStore.getState().hasSubmissions),
                    hasQuestionnaireFields: formHasQuestionnaireFields(
                        values,
                        useAppStore.getState().getFieldTypeByType,
                    ),
                    hasQuizFields: formHasQuizFields(
                        values,
                        useAppStore.getState().getFieldTypeByType,
                    ),
                },
            };
        },
        onChange: (data, formApi) => {
            latestFormValuesRef.current = data;
            handleSyncOnChange(data, formApi);

            const nextRequiresForcedAjax = isAjaxSubmissionForced(data)
                || allowedSubmitMethods === 'ajax';
            const nextRequiresPageReload = allowedSubmitMethods === 'page-reload' && !isAjaxSubmissionForced(data);

            if (nextRequiresForcedAjax && data?.settings?.submitMethod !== 'ajax') {
                formApi.setFieldValue('settings.submitMethod', 'ajax');
            } else if (nextRequiresPageReload && data?.settings?.submitMethod !== 'page-reload') {
                formApi.setFieldValue('settings.submitMethod', 'page-reload');
            }

            const nextTitle = data?.title;
            if (nextTitle !== undefined && nextTitle !== lastTitleRef.current) {
                lastTitleRef.current = nextTitle;
                setTitle(nextTitle);
            }

            const nextTemplateId = data?.templateId ?? null;
            setSelectedTemplateId(nextTemplateId);
        },
    });
    const initialTitle = normalizedInitialData?.title;
    const computeDirtySnapshot = useCallback((values = null) => {
        const sourceValues = values ?? latestFormValuesRef.current ?? form?.store?.state?.values ?? normalizedInitialData;
        return stableSerialize(dirtyFormSnapshot(sourceValues));
    }, [form, normalizedInitialData]);
    const subscribeToFormChanges = useCallback((listener) => {
        if (!form?.store?.subscribe) {
            return undefined;
        }

        return form.store.subscribe(listener);
    }, [form]);
    const {
        captureBaseline: captureUnloadWarningBaseline,
        recaptureBaseline: recaptureUnloadWarningBaseline,
        suppressWarning: suppressUnloadWarning,
    } = useUnloadWarning({
        baselineSettleQuietMs: 400,
        baselineSettleMaxMs: 3000,
        computeSnapshot: computeDirtySnapshot,
        subscribe: subscribeToFormChanges,
    });

    useEffect(() => {
        if (!form) {
            return undefined;
        }

        form.recaptureUnloadBaseline = () => {
            const values = form.store?.state?.values ?? {};
            latestFormValuesRef.current = values;
            recaptureUnloadWarningBaseline(stableSerialize(dirtyFormSnapshot(values)));
        };

        return () => {
            delete form.recaptureUnloadBaseline;
        };
    }, [form, recaptureUnloadWarningBaseline]);

    useEffect(() => {
        latestFormValuesRef.current = normalizedInitialData;
        setSelectedTemplateId(normalizedInitialData?.templateId ?? null);
    }, [normalizedInitialData?.templateId, setSelectedTemplateId]);

    useEffect(() => {
        if (initialTitle === undefined) {
            return;
        }

        if (lastTitleRef.current === initialTitle) {
            return;
        }

        lastTitleRef.current = initialTitle;
        setTitle(initialTitle);
    }, [initialTitle, setTitle]);

    // Set event handlers after form creation
    form.onSubmit(async(data) => {
        setErrors({});
        setSaving(true);
        setSaveFeedbackState('idle');

        // Add a delay before letting form validation run for a nicer UX
        await new Promise((resolve) => { return setTimeout(resolve, 300); });
    });

    form.onError((errors) => {
        setSaving(false);
        setSaveFeedbackState('error');
        announceFormBuilderStatus(Craft.t('formie', 'Unable to save. Please review the highlighted errors.'));
    });

    form.onSuccess(async(data) => {
        setErrors({});
        setSaving(true);
        const currentSaveAction = useAppStore.getState().saveAction || saveAction;
        const shouldSaveAsNew = currentSaveAction === 'saveAsNew';
        const shouldSaveAsStencil = currentSaveAction === 'saveAsStencil';
        const isDuplicateSave = currentSaveAction !== 'save' && !shouldSaveAsStencil;

        const {
            multiSite,
            activeSiteId,
            canonicalData,
        } = useAppStore.getState();
        const isSourceSite = multiSite?.enabled
            && Number(activeSiteId) !== Number(multiSite.sourceSiteId)
            && currentSaveAction === 'save';
        const baseRequestData = isDuplicateSave ? {
            ...saveRequestData,
            ...saveDuplicateRequestData,
        } : saveRequestData;

        let result;

        if (shouldSaveAsStencil) {
            result = await saveAsStencil(data);
        } else {
            result = await saveForm(data, {
                saveAsNew: shouldSaveAsNew,
                action: saveActionUrl,
                canonicalData: isSourceSite ? canonicalData : null,
                sourceSiteId: multiSite?.sourceSiteId ?? null,
                requestData: {
                    ...baseRequestData,
                    siteId: activeSiteId || baseRequestData?.siteId,
                },
            });
        }

        if (!result.ok && result.errors) {
            const normalizedErrors = normalizeServerErrors(result.errors);
            setErrors(normalizedErrors);
            setSaving(false);
            setSaveFeedbackState('error');
            announceFormBuilderStatus(Craft.t('formie', 'Unable to save. Please review the highlighted errors.'));
            if (shouldSaveAsStencil) {
                Craft.cp.displayError(Craft.t('formie', 'Couldn\'t save stencil.'));
            }
            setSaveAction('save');
            return;
        }

            if (result.ok) {
            const redirectUrl = result?.data?.redirect;

            // New-form saves return a redirect URL; follow it so the builder reloads
            // with fully-hydrated server state (ids, layout/page/row/field references).
            if (redirectUrl) {
                suppressUnloadWarning();
                window.location.href = redirectUrl;
                return;
            }

            const serverFormData = result?.data?.data;
            const serverCanonicalData = result?.data?.canonicalData;
            const serverMultiSite = result?.data?.multiSite;
            const activeSiteId = serverMultiSite?.activeSiteId ?? useAppStore.getState().activeSiteId;
            const canonicalDataForMerge = serverCanonicalData || useAppStore.getState().canonicalData;
            const shouldMergeSiteOverrides = Boolean(
                serverMultiSite?.enabled
                && canonicalDataForMerge
                && Number(activeSiteId) !== Number(serverMultiSite?.sourceSiteId),
            );
            const displayFormData = shouldMergeSiteOverrides
                ? mergeSiteOverridesIntoFormData(
                    canonicalDataForMerge,
                    getSiteOverrideForSite(serverMultiSite?.overrides, activeSiteId),
                    getFieldOverrideForSite(serverMultiSite?.fieldOverrides, activeSiteId),
                )
                : serverFormData;

            if (serverCanonicalData || serverMultiSite) {
                useAppStore.setState((state) => {
                    return {
                        canonicalData: serverCanonicalData || state.canonicalData,
                        multiSite: serverMultiSite || state.multiSite,
                        activeSiteId: serverMultiSite?.activeSiteId ?? state.activeSiteId,
                        layoutReadOnly: serverMultiSite?.layoutReadOnly ?? state.layoutReadOnly,
                    };
                });
            }

            // Reconcile local state with the server display payload after save.
            if (displayFormData && typeof displayFormData === 'object') {
                const reconciledServerFormData = preserveNotificationClientIds(displayFormData, latestFormValuesRef.current);
                const normalizedServerFormData = normalizeFormData(reconciledServerFormData);
                latestFormValuesRef.current = normalizedServerFormData;
                form.store.reset(normalizedServerFormData);
                setTitle(normalizedServerFormData.title || '');
                captureUnloadWarningBaseline(stableSerialize(dirtyFormSnapshot(normalizedServerFormData)));
            } else if (!shouldSaveAsStencil) {
                latestFormValuesRef.current = data;
                captureUnloadWarningBaseline(stableSerialize(dirtyFormSnapshot(data)));
            }

            if (result?.data?.formMeta) {
                setFormMeta(result.data.formMeta);
            }

            const resolvedSaveSuccessMessage = shouldSaveAsStencil
                ? Craft.t('formie', 'Stencil saved.')
                : (saveSuccessMessage || Craft.t('formie', 'Form saved.'));
            Craft.cp.displayNotice(resolvedSaveSuccessMessage);
            setSaveFeedbackState('success');
            announceFormBuilderStatus(resolvedSaveSuccessMessage);
        }

        setSaving(false);
        setSaveAction('save');
    });

    return (
        <FormBuilderFormProvider form={form}>
            <VariableCategoriesProvider>
                <FormBuilderErrorsPane />

                <SchemaFormEngine
                    ref={formRef}
                    form={form}
                    className="min-h-0 flex-1"
                />
            </VariableCategoriesProvider>
        </FormBuilderFormProvider>
    );
}

function preserveNotificationClientIds(serverFormData = {}, currentFormData = {}) {
    const serverNotifications = Array.isArray(serverFormData?.notifications) ? serverFormData.notifications : [];
    const currentNotifications = Array.isArray(currentFormData?.notifications) ? currentFormData.notifications : [];

    if (!serverNotifications.length || !currentNotifications.length) {
        return serverFormData;
    }

    const currentById = new Map();
    const currentByUid = new Map();
    const currentByHandle = new Map();

    currentNotifications.forEach((notification) => {
        if (!notification || typeof notification !== 'object') {
            return;
        }

        if (notification.id != null) {
            currentById.set(String(notification.id), notification);
        }

        if (notification.uid) {
            currentByUid.set(String(notification.uid), notification);
        }

        if (notification.handle) {
            currentByHandle.set(String(notification.handle).toLowerCase(), notification);
        }
    });

    return {
        ...serverFormData,
        notifications: serverNotifications.map((notification) => {
            if (!notification || typeof notification !== 'object') {
                return notification;
            }

            const matchingNotification = (
                (notification.id != null ? currentById.get(String(notification.id)) : null)
                || (notification.uid ? currentByUid.get(String(notification.uid)) : null)
                || (notification.handle ? currentByHandle.get(String(notification.handle).toLowerCase()) : null)
            );

            if (!matchingNotification?._id) {
                return notification;
            }

            return {
                ...notification,
                _id: matchingNotification._id,
            };
        }),
    };
}

function normalizeServerErrors(errors) {
    if (!errors || typeof errors !== 'object') {
        return {};
    }

    const entries = Object.entries(errors);
    if (entries.length && entries.every(([, value]) => {
        return Array.isArray(value) && value.every((item) => { return typeof item === 'string'; });
    })) {
        return errors;
    }

    const flatErrors = flatten(errors);
    const normalized = {};

    Object.entries(flatErrors).forEach(([key, value]) => {
        if (Array.isArray(value) && value.every((item) => { return typeof item === 'string'; })) {
            normalized[key] = value;
            return;
        }

        if (typeof value !== 'string') {
            return;
        }

        const parts = key.split('.');
        const lastPart = parts[parts.length - 1];
        const baseKey = /^\d+$/.test(lastPart) ? parts.slice(0, -1).join('.') : key;

        if (!normalized[baseKey]) {
            normalized[baseKey] = [];
        }

        normalized[baseKey].push(value);
    });

    return normalized;
}

export { FormBuilderContent };

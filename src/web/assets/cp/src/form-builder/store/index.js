import { create } from 'zustand';
import { subscribeWithSelector } from 'zustand/middleware';
import { cloneDeep } from 'lodash-es';

import { zustandHmrFix } from '@utils/zustandHmrFix';
import { isAjaxSubmissionForcedByPayments } from '@form-builder/utils/paymentSubmission';

import { createFieldTypesSlice } from './slices/fieldTypesSlice';

const createAppStore = (set, get) => {
    return {
        activeTab: null,
        activePageHandle: null,
        activeIntegrationHandle: null,
        tabLabels: {},
        allowAdminChanges: true,
        canEdit: true,
        readOnlyMessage: null,
        stencilScope: null,
        stencilScopeLabel: null,
        baseUrl: null,
        viewSubmissionsUrl: null,
        hasSubmissions: false,
        showFieldHandles: false,
        entityType: 'form',
        entityId: null,
        newItemTitle: null,
        saveActionUrl: 'formie/forms/save',
        saveRequestData: {},
        saveDuplicateAction: 'saveAsNew',
        saveDuplicateLabel: null,
        saveDuplicateRequestData: {},
        saveSuccessMessage: null,
        deleteAction: 'formie/forms/delete-form',
        deleteRequestData: null,
        deleteRedirectUrl: null,
        deleteConfirmMessage: null,
        deleteErrorMessage: null,
        formId: null,
        formMeta: null,
        selectedTemplateId: null,
        isSaving: false,
        saveFeedbackState: 'idle',
        saveAction: 'save',
        title: null,
        pageSettingsSchema: null,
        pageButtonSettingsSchema: null,
        canonicalData: null,
        multiSite: null,
        activeSiteId: null,
        siteDisplayRevision: 0,
        layoutReadOnly: false,
        paymentIntegrations: [],
        allowedSubmitMethods: 'both',
        enableMultiPageForms: true,
        templateFieldLayoutInfo: {},
        setTitle: (title) => {
            set({ title });
        },
        setSelectedTemplateId: (selectedTemplateId) => {
            set({ selectedTemplateId });
        },
        variables: {},
        fieldTypeGroups: [],

        // Include field types slice
        ...createFieldTypesSlice(set, get),

        loadForm: (formData) => {
            const {
                data, schema, ...rest
            } = formData;

            // Set non-form state (app-level data only)
            set({
                ...rest,
                formId: data?.id ?? null,
                formMeta: formData.formMeta ?? null,
                title: data?.title ?? rest?.title ?? null,
                selectedTemplateId: data?.templateId ?? null,
        canonicalData: formData.canonicalData ? cloneDeep(formData.canonicalData) : null,
                multiSite: formData.multiSite ?? null,
                activeSiteId: formData.multiSite?.activeSiteId ?? null,
                layoutReadOnly: Boolean(formData.multiSite?.layoutReadOnly),
            });

            // Initialize each slice with its data
            const {
                initFieldTypes,
            } = get();

            // Initialize field types if they exist
            if (formData.fieldTypeGroups) {
                initFieldTypes(formData.fieldTypeGroups);
            }

            // Initialize integrations if they exist
        },

        setInitializedRouter: (initializedRouter) => {
            set({ initializedRouter });
        },

        setSaving: (isSaving) => {
            set({ isSaving });
        },

        setSaveFeedbackState: (saveFeedbackState) => {
            set({ saveFeedbackState });
        },

        setSaveAction: (saveAction) => {
            set({ saveAction });
        },

        setFormMeta: (formMeta) => {
            set({ formMeta });
        },

        setActiveTab: (tab) => {
            set({ activeTab: tab });
        },

        setActivePageHandle: (pageHandle) => {
            set({ activePageHandle: pageHandle });
        },

        setActiveIntegrationHandle: (integrationHandle) => {
            set({ activeIntegrationHandle: integrationHandle });
        },

        setActiveSiteId: (activeSiteId) => {
            set({ activeSiteId });
        },

        bumpSiteDisplayRevision: () => {
            set((state) => {
                return {
                    siteDisplayRevision: state.siteDisplayRevision + 1,
                };
            });
        },

        setMultiSite: (multiSite) => {
            set({
                multiSite,
                layoutReadOnly: Boolean(multiSite?.layoutReadOnly),
            });
        },

        isAjaxSubmissionForced: (values) => {
            const { paymentIntegrations } = get();
            return isAjaxSubmissionForcedByPayments(values, paymentIntegrations);
        },

    };
};

// Create the store with middleware
const appStore = create(subscribeWithSelector(createAppStore));

// Apply HMR fix
zustandHmrFix('appStore', appStore);

export { appStore };

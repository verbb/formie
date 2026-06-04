import {
    createContext, useContext, useMemo, useState,
} from 'react';
import useAppStore from '@form-builder/hooks/useAppStore';

const FormBuilderAppContext = createContext(null);

export const FormBuilderAppProvider = ({ children }) => {
    const [isFieldTypeSidebarOpen, setIsFieldTypeSidebarOpen] = useState(false);
    const activeTab = useAppStore((state) => { return state.activeTab; });
    const activePageHandle = useAppStore((state) => { return state.activePageHandle; });
    const activeIntegrationHandle = useAppStore((state) => { return state.activeIntegrationHandle; });
    const allowAdminChanges = useAppStore((state) => { return state.allowAdminChanges; });
    const baseUrl = useAppStore((state) => { return state.baseUrl; });
    const viewSubmissionsUrl = useAppStore((state) => { return state.viewSubmissionsUrl; });
    const entityType = useAppStore((state) => { return state.entityType; });
    const entityId = useAppStore((state) => { return state.entityId; });
    const newItemTitle = useAppStore((state) => { return state.newItemTitle; });
    const saveActionUrl = useAppStore((state) => { return state.saveActionUrl; });
    const saveRequestData = useAppStore((state) => { return state.saveRequestData; });
    const saveDuplicateAction = useAppStore((state) => { return state.saveDuplicateAction; });
    const saveDuplicateLabel = useAppStore((state) => { return state.saveDuplicateLabel; });
    const saveDuplicateRequestData = useAppStore((state) => { return state.saveDuplicateRequestData; });
    const saveSuccessMessage = useAppStore((state) => { return state.saveSuccessMessage; });
    const deleteAction = useAppStore((state) => { return state.deleteAction; });
    const deleteRequestData = useAppStore((state) => { return state.deleteRequestData; });
    const deleteRedirectUrl = useAppStore((state) => { return state.deleteRedirectUrl; });
    const deleteConfirmMessage = useAppStore((state) => { return state.deleteConfirmMessage; });
    const deleteErrorMessage = useAppStore((state) => { return state.deleteErrorMessage; });
    const formId = useAppStore((state) => { return state.formId; });
    const formMeta = useAppStore((state) => { return state.formMeta; });
    const isSaving = useAppStore((state) => { return state.isSaving; });
    const saveFeedbackState = useAppStore((state) => { return state.saveFeedbackState; });
    const saveAction = useAppStore((state) => { return state.saveAction; });
    const title = useAppStore((state) => { return state.title; });
    const fieldTypeGroups = useAppStore((state) => { return state.fieldTypeGroups; });
    const getFieldTypeByType = useAppStore((state) => { return state.getFieldTypeByType; });
    const pageSettingsSchema = useAppStore((state) => { return state.pageSettingsSchema; });
    const pageButtonSettingsSchema = useAppStore((state) => { return state.pageButtonSettingsSchema; });

    const setTitle = useAppStore((state) => { return state.setTitle; });
    const setSaving = useAppStore((state) => { return state.setSaving; });
    const setSaveFeedbackState = useAppStore((state) => { return state.setSaveFeedbackState; });
    const setSaveAction = useAppStore((state) => { return state.setSaveAction; });
    const setActiveTab = useAppStore((state) => { return state.setActiveTab; });
    const setActivePageHandle = useAppStore((state) => { return state.setActivePageHandle; });
    const setActiveIntegrationHandle = useAppStore((state) => { return state.setActiveIntegrationHandle; });

    const contextValue = useMemo(() => {
        return {
            activeTab,
            activePageHandle,
            activeIntegrationHandle,
            allowAdminChanges,
            baseUrl,
            viewSubmissionsUrl,
            entityType,
            entityId,
            newItemTitle,
            saveActionUrl,
            saveRequestData,
            saveDuplicateAction,
            saveDuplicateLabel,
            saveDuplicateRequestData,
            saveSuccessMessage,
            deleteAction,
            deleteRequestData,
            deleteRedirectUrl,
            deleteConfirmMessage,
            deleteErrorMessage,
            formId,
            formMeta,
            isSaving,
            saveFeedbackState,
            saveAction,
            title,
            fieldTypeGroups,
            getFieldTypeByType,
            pageSettingsSchema,
            pageButtonSettingsSchema,
            isFieldTypeSidebarOpen,
            setTitle,
            setSaving,
            setSaveFeedbackState,
            setSaveAction,
            setActiveTab,
            setActivePageHandle,
            setActiveIntegrationHandle,
            setIsFieldTypeSidebarOpen,
        };
    }, [
        activeTab,
        activePageHandle,
        activeIntegrationHandle,
        allowAdminChanges,
        baseUrl,
        viewSubmissionsUrl,
        entityType,
        entityId,
        newItemTitle,
        saveActionUrl,
        saveRequestData,
        saveDuplicateAction,
        saveDuplicateLabel,
        saveDuplicateRequestData,
        saveSuccessMessage,
        deleteAction,
        deleteRequestData,
        deleteRedirectUrl,
        deleteConfirmMessage,
        deleteErrorMessage,
        formId,
        formMeta,
        isSaving,
        saveFeedbackState,
        saveAction,
        title,
        fieldTypeGroups,
        getFieldTypeByType,
        pageSettingsSchema,
        pageButtonSettingsSchema,
        isFieldTypeSidebarOpen,
        setTitle,
        setSaving,
        setSaveFeedbackState,
        setSaveAction,
        setActiveTab,
        setActivePageHandle,
        setActiveIntegrationHandle,
        setIsFieldTypeSidebarOpen,
    ]);

    return (
        <FormBuilderAppContext.Provider value={contextValue}>
            {children}
        </FormBuilderAppContext.Provider>
    );
};

export const useFormBuilderApp = () => {
    const context = useContext(FormBuilderAppContext);
    if (!context) {
        throw new Error('useFormBuilderApp must be used within FormBuilderAppProvider');
    }
    return context;
};

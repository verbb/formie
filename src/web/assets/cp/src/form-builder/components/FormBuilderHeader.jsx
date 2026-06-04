import { useEffect, useState } from 'react';
import { Button, MenuButton } from '@verbb/plugin-kit-react/components';

import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faCheck, faExternalLink, faEye, faPlus, faTableList } from '@fortawesome/pro-solid-svg-icons';

import { useFormBuilderApp } from '@form-builder/contexts/FormBuilderAppContext';
import useAppStore from '@form-builder/hooks/useAppStore';
import { deleteForm, prepareFormPreview } from '@form-builder/hooks/useFormTools';
import { announceFormBuilderStatus } from '@form-builder/utils/accessibility';
import { cn } from '@verbb/plugin-kit-react/utils';

function FormBuilderHeader({ formRef }) {
    const {
        isSaving, saveFeedbackState, viewSubmissionsUrl, title, newItemTitle, setSaveAction, setSaving, formId, entityId, entityType,
        allowAdminChanges, canEdit, readOnlyMessage, stencilScopeLabel, saveDuplicateAction, saveDuplicateLabel, saveDuplicateRequestData, saveRequestData,
        deleteAction, deleteRequestData, deleteRedirectUrl, deleteConfirmMessage, deleteErrorMessage, activeTab, setIsFieldTypeSidebarOpen,
    } = useFormBuilderApp();
    const templateFieldLayoutInfo = useAppStore((state) => {
        return state.templateFieldLayoutInfo || {};
    });
    const selectedTemplateId = useAppStore((state) => {
        return state.selectedTemplateId;
    });
    const [showSavedState, setShowSavedState] = useState(false);
    const [isPreparingPreview, setIsPreparingPreview] = useState(false);
    const resolvedTitle = title || newItemTitle || Craft.t('formie', 'New Form');
    const selectedTemplateInfo = selectedTemplateId ? templateFieldLayoutInfo[String(selectedTemplateId)] : null;
    const canEditTemplateFields = Boolean(selectedTemplateId && selectedTemplateInfo?.hasFields);
    const resolvedStencilScopeLabel = stencilScopeLabel ? ` (${stencilScopeLabel})` : '';

    useEffect(() => {
        if (isSaving || saveFeedbackState !== 'success') {
            setShowSavedState(false);
            return undefined;
        }

        setShowSavedState(true);

        const timeout = setTimeout(() => {
            setShowSavedState(false);
        }, 2200);

        return () => {
            clearTimeout(timeout);
        };
    }, [isSaving, saveFeedbackState]);

    const handleDelete = async () => {
        const deleteId = entityId ?? formId;

        if (!deleteId && !deleteRequestData) {
            return;
        }

        const confirmationMessage = deleteConfirmMessage || Craft.t('formie', 'Are you sure you want to delete this form?');
        const isConfirmed = window.confirm(confirmationMessage);
        if (!isConfirmed) {
            return;
        }

        setSaving(true);

        const result = await deleteForm(deleteId, {
            action: deleteAction,
            requestData: deleteRequestData,
        });

        if (!result.ok) {
            setSaving(false);
            const resolvedDeleteErrorMessage = deleteErrorMessage || Craft.t('formie', 'Couldn’t delete form.');
            Craft.cp.displayError(resolvedDeleteErrorMessage);
            announceFormBuilderStatus(resolvedDeleteErrorMessage);
            return;
        }

        const redirectUrl = result?.data?.redirect || deleteRedirectUrl || Craft.getCpUrl('formie/forms');
        window.location.href = redirectUrl;
    };

    const handleDuplicateSave = () => {
        setSaveAction(saveDuplicateAction || 'saveAsNew');
        formRef.current?.handleSubmit?.();
    };

    const saveMenuItems = canEdit
        ? [
            {
                label: saveDuplicateLabel || Craft.t('formie', 'Save as a new form'),
                onClick: handleDuplicateSave,
            },
            ...(entityType !== 'stencil' ? [
                {
                    label: Craft.t('formie', 'Save as a new stencil'),
                    onClick: () => {
                        setSaveAction('saveAsStencil');
                        formRef.current?.handleSubmit?.();
                    },
                },
            ] : []),
            {
                type: 'separator',
            },
            {
                label: Craft.t('formie', 'Delete'),
                variant: 'destructive',
                onClick: handleDelete,
            },
        ]
        : [];

    const handlePreview = async (event) => {
        event?.currentTarget?.blur?.();

        const formValues = formRef.current?.store?.state?.values;

        if (!formValues || typeof formValues !== 'object') {
            Craft.cp.displayError(Craft.t('formie', 'Missing form preview data.'));
            return;
        }

        setIsPreparingPreview(true);

        const result = await prepareFormPreview(formValues, {
            entityType,
            saveRequestData,
        });

        setIsPreparingPreview(false);

        if (!result.ok) {
            Craft.cp.displayError(result.error || Craft.t('formie', 'Could not prepare form preview.'));
            announceFormBuilderStatus(result.error || Craft.t('formie', 'Could not prepare form preview.'));
            return;
        }

        const slideout = new Craft.CpScreenSlideout('formie/forms/preview-slideout', {
            containerElement: 'div',
            showHeader: true,
            params: {
                previewKey: result.data.token,
            },
        });

        slideout.open();
        announceFormBuilderStatus(Craft.t('formie', 'Form Preview'));
    };

    const handleEditTemplateFields = (event) => {
        event?.currentTarget?.blur?.();

        const templateId = selectedTemplateId;

        const slideout = new Craft.CpScreenSlideout('formie/forms/template-fields-slideout', {
            params: {
                formId,
                templateId,
            },
        });

        slideout.open();
    };

    return (
        <>
            {readOnlyMessage && (
                <div className="mb-4 rounded border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                    {readOnlyMessage}
                </div>
            )}

            <header className={cn('form-builder-header flex justify-between items-center mt-1 mb-5')}>
                <div className={cn('form-builder-header-title-wrap flex items-center gap-3')}>
                    <h1 className={cn('form-builder-header-title text-lg font-bold')} title={resolvedTitle}>
                        {resolvedTitle}
                        {resolvedStencilScopeLabel}
                    </h1>
                </div>

                <div className={cn('form-builder-header-actions flex justify-between items-center gap-2')}>
                    {viewSubmissionsUrl && (
                        <Button
                            className="form-builder-header-secondary-action"
                            target="_blank"
                            rel="noopener"
                            href={viewSubmissionsUrl}
                            aria-label={Craft.t('formie', 'View Submissions')}
                        >
                            <span className="form-builder-header-action-label-full">{Craft.t('formie', 'View Submissions')}</span>
                            <span className="form-builder-header-action-label-short">{Craft.t('formie', 'Submissions')}</span>
                            <FontAwesomeIcon icon={faExternalLink} className="form-builder-submissions-icon-link size-3" />
                            <FontAwesomeIcon icon={faTableList} className="form-builder-submissions-icon-compact size-3" />
                        </Button>
                    )}

                    {canEdit && canEditTemplateFields && (
                        <Button onClick={handleEditTemplateFields}>
                            {Craft.t('formie', 'Template Fields')}
                        </Button>
                    )}

                    {canEdit && activeTab === 'fields' && (
                        <Button
                            type="button"
                            className="form-builder-add-fields-action form-builder-header-secondary-action"
                            aria-label={Craft.t('formie', 'Add fields')}
                            onClick={() => {
                                setIsFieldTypeSidebarOpen(true);
                            }}
                        >
                            <FontAwesomeIcon icon={faPlus} className="size-3" />
                            <span className="form-builder-header-action-label">{Craft.t('formie', 'Add fields')}</span>
                        </Button>
                    )}

                    <Button
                        type="button"
                        className="form-builder-header-secondary-action form-builder-preview-action"
                        loading={isPreparingPreview}
                        aria-label={Craft.t('formie', 'Form Preview')}
                        onClick={handlePreview}
                    >
                        <FontAwesomeIcon icon={faEye} className="size-3" />
                        <span className="form-builder-header-action-label form-builder-header-action-label-full">{Craft.t('formie', 'Preview')}</span>
                    </Button>

                    {canEdit ? (
                        <MenuButton
                            variant="primary"
                            loading={isSaving}
                            mainAction={{
                                label: Craft.t('formie', 'Save'),
                                labelClassName: showSavedState ? 'text-transparent' : '',
                                icon: showSavedState ? (
                                    <FontAwesomeIcon
                                        icon={faCheck}
                                        className={cn(
                                            'size-3 transition-opacity duration-300',
                                            'opacity-100',
                                        )}
                                    />
                                ) : null,
                                iconPosition: 'overlay',
                                onClick: () => {
                                    setSaveAction('save');
                                    formRef.current?.handleSubmit?.();
                                },
                            }}
                            menuItems={saveMenuItems}
                        />
                    ) : (
                        <Button
                            variant="primary"
                            loading={isSaving}
                            onClick={handleDuplicateSave}
                        >
                            {saveDuplicateLabel || Craft.t('formie', 'Save a copy')}
                        </Button>
                    )}
                </div>
            </header>
        </>
    );
}

export { FormBuilderHeader };

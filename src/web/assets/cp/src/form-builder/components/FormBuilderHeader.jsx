import { useEffect, useState } from 'react';
import { Button, MenuButton } from '@verbb/plugin-kit-react/components';

import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faCheck, faExternalLink, faPlus, faTableList } from '@fortawesome/pro-solid-svg-icons';

import { useFormBuilderApp } from '@form-builder/contexts/FormBuilderAppContext';
import useAppStore from '@form-builder/hooks/useAppStore';
import { deleteForm } from '@form-builder/hooks/useFormTools';
import { announceFormBuilderStatus } from '@form-builder/utils/accessibility';
import { cn } from '@verbb/plugin-kit-react/utils';

function FormBuilderHeader({ formRef }) {
    const {
        isSaving, saveFeedbackState, viewSubmissionsUrl, title, newItemTitle, setSaveAction, setSaving, formId, entityId, entityType,
        allowAdminChanges, saveDuplicateAction, saveDuplicateLabel, deleteAction, deleteRequestData, deleteRedirectUrl,
        deleteConfirmMessage, deleteErrorMessage, activeTab, setIsFieldTypeSidebarOpen,
    } = useFormBuilderApp();
    const templateFieldLayoutInfo = useAppStore((state) => {
        return state.templateFieldLayoutInfo || {};
    });
    const selectedTemplateId = useAppStore((state) => {
        return state.selectedTemplateId;
    });
    const [showSavedState, setShowSavedState] = useState(false);
    const resolvedTitle = title || newItemTitle || Craft.t('formie', 'New Form');
    const selectedTemplateInfo = selectedTemplateId ? templateFieldLayoutInfo[String(selectedTemplateId)] : null;
    const canEditTemplateFields = Boolean(selectedTemplateId && selectedTemplateInfo?.hasFields);

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

    const handleDelete = async() => {
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

    const saveMenuItems = [
        {
            label: saveDuplicateLabel || Craft.t('formie', 'Save as a new form'),
            onClick: () => {
                setSaveAction(saveDuplicateAction || 'saveAsNew');
                formRef.current?.handleSubmit?.();
            },
        },
        ...(allowAdminChanges && entityType !== 'stencil' ? [
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
    ];

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
        <header className={cn('form-builder-header flex justify-between items-center mt-1 mb-5')}>
            <div className={cn('form-builder-header-title-wrap flex items-center gap-3')}>
                <h1 className={cn('form-builder-header-title text-lg font-bold')} title={resolvedTitle}>
                    {resolvedTitle}
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

                {canEditTemplateFields && (
                    <Button onClick={handleEditTemplateFields}>
                        {Craft.t('formie', 'Template Fields')}
                    </Button>
                )}

                {activeTab === 'fields' && (
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
            </div>
        </header>
    );
}

export { FormBuilderHeader };

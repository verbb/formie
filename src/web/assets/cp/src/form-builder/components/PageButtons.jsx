import { useEffect, useMemo, useRef, useState } from 'react';

import { cn } from '@verbb/plugin-kit-react/utils';
import { SchemaFormEngine, useSchemaFormEngine } from '@verbb/plugin-kit-react/forms';
import { Button, Dialog } from '@verbb/plugin-kit-react/components';

import { useFormValues } from '@form-builder/hooks/useFormTools';
import { useBuilderActions } from '@form-builder/builder/useBuilderActions';
import { useHandleSyncOnChange } from '@form-builder/hooks/useHandleSyncOnChange';
import { useFormBuilderApp } from '@form-builder/contexts/FormBuilderAppContext';
import { useResetDialogBodyScrollOnTabChange } from '@form-builder/hooks/useResetDialogBodyScrollOnTabChange';

const getDefaultPageSettings = (settings = {}, pageIndex = 0) => {
    const isFirstPage = pageIndex === 0;
    const hasShowBackButtonValue = typeof settings?.showBackButton === 'boolean';

    return {
        submitButtonLabel: settings?.submitButtonLabel || Craft.t('formie', 'Submit'),
        backButtonLabel: settings?.backButtonLabel || Craft.t('formie', 'Back'),
        showBackButton: isFirstPage ? false : (hasShowBackButtonValue ? settings.showBackButton : true),
        saveButtonLabel: settings?.saveButtonLabel || Craft.t('formie', 'Save'),
        showSaveButton: Boolean(settings?.showSaveButton),
        saveButtonStyle: settings?.saveButtonStyle || 'link',
        buttonsPosition: settings?.buttonsPosition || 'left',
        submitButtonPlacement: settings?.submitButtonPlacement || 'page-footer',
    };
};

const getButtonAlignmentStyles = (buttonsPosition) => {
    if (buttonsPosition === 'right') {
        return { justifyContent: 'flex-end' };
    }

    if (buttonsPosition === 'center') {
        return { justifyContent: 'center' };
    }

    if (buttonsPosition === 'left-right') {
        return { justifyContent: 'space-between' };
    }

    if (buttonsPosition === 'right-save-left') {
        return { justifyContent: 'flex-end' };
    }

    if (buttonsPosition === 'center-save-left') {
        return { justifyContent: 'center' };
    }

    if (buttonsPosition === 'center-save-right') {
        return { justifyContent: 'center' };
    }

    if (buttonsPosition === 'save-right') {
        return { justifyContent: 'flex-start' };
    }

    if (buttonsPosition === 'save-left') {
        return { justifyContent: 'flex-start' };
    }

    return { justifyContent: 'normal' };
};

const getButtonItemStyles = (buttonsPosition, action, saveButtonStyle) => {
    if (action === 'back') {
        if (buttonsPosition === 'left-right') {
            return { marginInlineEnd: 'auto' };
        }

        return { order: 0 };
    }

    if (action === 'submit') {
        return { order: 10 };
    }

    const styles = {
        order: 20,
    };

    if (buttonsPosition === 'save-right') {
        styles.marginInlineStart = 'auto';
    }

    if (buttonsPosition === 'save-left') {
        styles.order = -10;
        styles.marginInlineEnd = 'auto';
    }

    if (buttonsPosition === 'right-save-left' || buttonsPosition === 'center-save-left') {
        styles.order = -10;
    }

    return styles;
};

const renderPreviewButton = (action, label, { saveButtonStyle, buttonsPosition }) => {
    const style = getButtonItemStyles(buttonsPosition, action, saveButtonStyle);

    if (action === 'save' && saveButtonStyle === 'link') {
        return (
            <span
                key={action}
                className="block max-w-[220px] truncate text-sm font-medium text-[#2563eb] underline underline-offset-2"
                style={style}
            >
                {label}
            </span>
        );
    }

    const isPrimary = action === 'submit';

    return (
        <Button
            key={action}
            type="button"
            variant={isPrimary ? 'primary' : undefined}
            className={cn(
                action === 'submit' ? 'max-w-[260px]' : 'max-w-[220px]',
                'shrink-0',
            )}
            style={style}
        >
            <span className="block truncate">
                {label}
            </span>
        </Button>
    );
};

function PageButtons({ page, pageIndex, isAnyDragActive = false }) {
    const { pageButtonSettingsSchema } = useFormBuilderApp();
    const formValues = useFormValues();
    const { updatePages } = useBuilderActions();
    const [isModalOpen, setIsModalOpen] = useState(false);
    const [activePage, setActivePage] = useState(page?._handle || null);
    const [pages, setPages] = useState(formValues.pages || []);
    const dialogBodyRef = useRef(null);
    // Defer updatePages until pk-after-hide so Apply doesn't remount preview mid-exit.
    const pendingCloseRef = useRef(null);
    // Panel-owned scroll (v1 ModalTabs) — hook retained as no-op for shared imports.
    useResetDialogBodyScrollOnTabChange(dialogBodyRef, isModalOpen);

    const formSchema = useMemo(() => {
        return pageButtonSettingsSchema?.schema ?? [];
    }, [pageButtonSettingsSchema]);
    const handleSyncOnChange = useHandleSyncOnChange(formSchema);

    const form = useSchemaFormEngine({
        schema: formSchema,
        schemaIndex: pageButtonSettingsSchema,
        defaultValues: { activePage, pages },
        onChange: (data, formApi) => {
            handleSyncOnChange(data, formApi);

            if (data.activePage !== undefined) {
                setActivePage(data.activePage);
            }

            if (data.pages !== undefined) {
                setPages([...data.pages]);
            }
        },
    });

    useEffect(() => {
        if (!isModalOpen) {
            return;
        }

        const latestPages = formValues.pages || [];
        const currentActivePage = page?._handle || latestPages[0]?._handle || null;

        setPages(latestPages);
        setActivePage(currentActivePage);
        form.setFieldValue('pages', latestPages);
        form.setFieldValue('activePage', currentActivePage);
    }, [isModalOpen, formValues.pages, page?._handle]);

    form.onSuccess((data) => {
        const normalizedPages = (data?.pages || []).map((pageItem, index) => {
            const pageSettings = pageItem?.settings || {};
            const hasShowBackButtonValue = typeof pageSettings.showBackButton === 'boolean';

            return {
                ...pageItem,
                settings: {
                    ...pageSettings,
                    showBackButton: index === 0 ? false : (hasShowBackButtonValue ? pageSettings.showBackButton : true),
                },
            };
        });

        // Same as field edit: close first (exit animation), commit on pk-after-hide.
        // Immediate updatePages re-renders the button preview and cuts the fade short.
        pendingCloseRef.current = { type: 'save', pages: normalizedPages };
        setIsModalOpen(false);
    });

    const handleAfterHide = () => {
        const pending = pendingCloseRef.current;
        pendingCloseRef.current = null;

        if (pending?.type === 'save') {
            updatePages(pending.pages);
        }
    };

    const displaySettings = useMemo(() => {
        return getDefaultPageSettings(page?.settings || {}, pageIndex);
    }, [page?.settings, pageIndex]);

    const isFirstPage = pageIndex === 0;
    const showBackButton = !isFirstPage && displaySettings.showBackButton;
    const { showSaveButton } = displaySettings;
    const alignmentStyles = getButtonAlignmentStyles(displaySettings.buttonsPosition);

    const handleSave = (e) => {
        e.preventDefault();
        form.handleSubmit();
    };

    return (
        <>
            <div
                className={cn(
                    'relative',
                    'rounded-lg',
                    'px-3 py-4',
                    !isAnyDragActive && 'hover:bg-[#f1f5f8]',
                )}
            >
                <div
                    className="absolute inset-0 z-10 cursor-pointer rounded-lg focus-visible:outline-none focus-visible:shadow-[inset_0_0_0_2px_var(--color-sky-600),inset_0_0_0_4px_#ffffff]"
                    onClick={() => {
                        setIsModalOpen(true);
                    }}
                    role="button"
                    tabIndex={0}
                    aria-label={Craft.t('formie', 'Edit page buttons')}
                    onKeyDown={(event) => {
                        if (event.key === 'Enter' || event.key === ' ') {
                            event.preventDefault();
                            setIsModalOpen(true);
                        }
                    }}
                />

                <div
                    className={cn(
                        'pointer-events-none relative z-0',
                        'flex w-full items-center gap-2',
                    )}
                    style={alignmentStyles}
                >
                    {showBackButton && renderPreviewButton('back', displaySettings.backButtonLabel, displaySettings)}

                    {renderPreviewButton('submit', displaySettings.submitButtonLabel, displaySettings)}

                    {showSaveButton && renderPreviewButton('save', displaySettings.saveButtonLabel, displaySettings)}
                </div>
            </div>

            <Dialog
                open={isModalOpen}
                label={Craft.t('formie', 'Edit Buttons')}
                withoutBodyPadding
                className="formie-page-buttons-dialog"
                onPkOpenChange={(event) => {
                    setIsModalOpen(Boolean(event.detail?.open ?? event.target?.open));
                }}
                onPkAfterHide={handleAfterHide}
            >
                {/* Flush body (withoutBodyPadding) — tab list spans edge-to-edge;
                 * panels own scroll + 1rem inset (v1 ModalTabs).
                 * Intermediate flex-1 min-h-0 shells match field edit / v1 — without them
                 * the form grows with tab content and clips under the footer with no scroll. */}
                <div ref={dialogBodyRef} className="formie-page-buttons-dialog-body">
                    <div className="relative flex min-h-0 flex-1 flex-col overflow-hidden">
                        <div className="flex h-full min-h-0 flex-col overflow-hidden">
                            <SchemaFormEngine
                                form={form}
                                className="flex h-full min-h-0 flex-col"
                            />
                        </div>
                    </div>
                </div>

                <Button
                    slot="footer"
                    type="button"
                    data-dialog-close
                >
                    {Craft.t('formie', 'Cancel')}
                </Button>

                <Button
                    slot="footer"
                    type="button"
                    variant="primary"
                    onClick={handleSave}
                >
                    {Craft.t('formie', 'Apply')}
                </Button>
            </Dialog>
        </>
    );
}

export { PageButtons };

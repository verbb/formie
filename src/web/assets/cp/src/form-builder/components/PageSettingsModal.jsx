import {
    useState, useMemo, useEffect, useRef, useSyncExternalStore,
} from 'react';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faPlus } from '@fortawesome/pro-solid-svg-icons';

import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogFooter,
    DialogDescription,
    Button,
} from '@verbb/plugin-kit-react/components';

import { cn, generateHandle, findUniqueHandle } from '@verbb/plugin-kit-react/utils';
import { useFormValues } from '@form-builder/hooks/useFormTools';
import { useBuilderActions } from '@form-builder/builder/useBuilderActions';
import { useFormBuilderApp } from '@form-builder/contexts/FormBuilderAppContext';

import {
    DragDropProvider,
    PointerSensor,
    KeyboardSensor,
} from '@dnd-kit/react';
import { RestrictToVerticalAxis } from '@dnd-kit/abstract/modifiers';
import { RestrictToElement } from '@dnd-kit/dom/modifiers';

import { isSortable, useSortable } from '@dnd-kit/react/sortable';

import { useSchemaFormEngine, SchemaFormEngine } from '@verbb/plugin-kit-react/forms';
import { useHandleSyncOnChange } from '@form-builder/hooks/useHandleSyncOnChange';
import { createNewPageData } from '@form-builder/utils/pages';
import { announceFormBuilderStatus } from '@form-builder/utils/accessibility';

function SortablePageItem({
    page, isActive, hasErrors, onClick, setNodeRef, handleRef, style,
}) {
    return (
        <div
            ref={setNodeRef}
            style={style}
            className={cn(
                'flex items-center pl-4 pr-2 py-3 cursor-pointer outline-none',
                'border-b border-gray-200',
                'focus-visible:shadow-[inset_0_0_0_2px_var(--color-sky-600),inset_0_0_0_4px_#ffffff]',
                isActive ? 'bg-[#cdd8e4]' : 'bg-[#e4edf6]',
                hasErrors && 'text-red-600 bg-red-100 border-l-4 border-l-red-500',
            )}
            onMouseDown={(e) => {
                // Prevent first click from being consumed by focus move when coming from an input in the right pane.
                e.preventDefault();
            }}
            onClick={onClick}
            role="button"
            tabIndex={0}
            aria-label={Craft.t('formie', 'Edit page {label}', { label: page.label })}
            aria-current={isActive ? 'page' : undefined}
            onKeyDown={(event) => {
                if (event.key !== 'Enter' && event.key !== ' ') {
                    return;
                }

                event.preventDefault();
                onClick(event);
            }}
        >
            <span className={cn(
                'flex-1 truncate',
            )}>
                {page.label}
            </span>

            <div ref={handleRef} className="">
                <Button
                    variant="none"
                    aria-label={Craft.t('formie', 'Reorder page {label}', { label: page.label })}
                    className={cn(
                        'size-6 rounded-none p-1 text-gray-500',
                        'bg-transparent',
                        'hover:cursor-move',
                    )}>
                    <svg className="size-full inline-block" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512" fill="currentColor">
                        <path d="M71.3 295.6c-21.9-21.9-21.9-57.3 0-79.2s57.3-21.9 79.2 0 21.9 57.3 0 79.2s-57.4 21.9-79.2 0zM184.4 182.5c-21.9-21.9-21.9-57.3 0-79.2s57.3-21.9 79.2 0 21.9 57.3 0 79.2-57.3 21.8-79.2 0zm0 147c21.9-21.9 57.3-21.9 79.2 0s21.9 57.3 0 79.2s-57.3 21.9-79.2 0c-21.9-21.8-21.9-57.3 0-79.2zM297.5 216.4c21.9-21.9 57.3-21.9 79.2 0s21.9 57.3 0 79.2s-57.3 21.9-79.2 0c-21.8-21.9-21.8-57.3 0-79.2z" />
                    </svg>
                </Button>
            </div>

        </div>
    );
}

function PageSettingsModal({
    isOpen, onClose, schemaIndex, initialActivePageHandle = null, createPageOnOpen = false,
}) {
    const DRAG_REORDER_DELAY_MS = 300;
    const formValues = useFormValues();
    const { activePageHandle, setActivePageHandle } = useFormBuilderApp();

    // Get initial data from store
    const initialPages = formValues.pages || [];
    const initialActivePage = initialPages.some((page) => { return page?._handle === activePageHandle; })
        ? activePageHandle
        : (initialPages[0]?._handle || null);
    const initialData = {
        activePage: initialActivePage,
        pages: initialPages,
    };

    // Split state for better performance - only update what changed
    const [activePage, setActivePage] = useState(initialData.activePage);
    const [pages, setPages] = useState(initialData.pages);
    const { updatePages } = useBuilderActions();
    const contentRef = useRef(null);
    const pageListRef = useRef(null);
    const dragReorderTimeoutRef = useRef(null);
    const createOnOpenHandledRef = useRef(false);
    const pendingFocusPageHandleRef = useRef(null);

    const formSchema = useMemo(() => {
        return schemaIndex?.schema ?? [];
    }, [schemaIndex]);

    const handleSyncOnChange = useHandleSyncOnChange(formSchema);

    const syncPageHandlesFromLabels = (inputPages = [], activePageHandle = null) => {
        if (!Array.isArray(inputPages)) {
            return { pages: [], activePageHandle, changed: false };
        }

        const reservedHandles = [];
        let changed = false;
        const activePageIndex = inputPages.findIndex((page) => {
            return (page?._handle ?? page?.handle) === activePageHandle;
        });

        const syncedPages = inputPages.map((page, index) => {
            const currentHandle = page?._handle ?? page?.handle ?? '';
            const fallbackHandle = currentHandle || `page${index + 1}`;
            const sourceLabel = page?.label ?? '';
            const baseHandle = generateHandle(String(sourceLabel)) || generateHandle(String(fallbackHandle)) || `page${index + 1}`;
            const nextHandle = findUniqueHandle(baseHandle, reservedHandles);

            reservedHandles.push(nextHandle);

            if (nextHandle !== currentHandle) {
                changed = true;
            }

            return {
                ...page,
                _handle: nextHandle,
            };
        });

        const nextActivePageHandle = activePageIndex >= 0
            ? (syncedPages[activePageIndex]?._handle ?? null)
            : activePageHandle;

        if (nextActivePageHandle !== activePageHandle) {
            changed = true;
        }

        return {
            pages: syncedPages,
            activePageHandle: nextActivePageHandle,
            changed,
        };
    };

    const focusPageLabelInput = () => {
        let attempts = 0;
        const maxAttempts = 8;

        const isVisibleElement = (element) => {
            if (!(element instanceof HTMLElement)) {
                return false;
            }

            if (element.hidden) {
                return false;
            }

            if (element.closest('[hidden], [aria-hidden="true"]')) {
                return false;
            }

            if (element.getClientRects().length === 0) {
                return false;
            }

            return true;
        };

        const tryFocus = () => {
            const root = contentRef.current;

            if (!root) {
                return;
            }

            const labelCandidates = Array.from(root.querySelectorAll('input[name$=".label"]:not([type="hidden"]):not([disabled])'));
            const visibleLabelInput = labelCandidates.find((input) => {
                return isVisibleElement(input);
            });
            const fallbackCandidates = Array.from(root.querySelectorAll('input:not([type="hidden"]):not([disabled])'));
            const fallbackVisibleInput = fallbackCandidates.find((input) => {
                return isVisibleElement(input);
            });
            const labelInput = visibleLabelInput || fallbackVisibleInput;

            if (!labelInput) {
                attempts += 1;
                if (attempts < maxAttempts) {
                    window.setTimeout(tryFocus, 40);
                }
                return;
            }

            labelInput.focus?.();

            if (labelInput instanceof HTMLInputElement || labelInput instanceof HTMLTextAreaElement) {
                const cursorPosition = labelInput.value.length;
                labelInput.setSelectionRange?.(cursorPosition, cursorPosition);
            }
        };

        window.requestAnimationFrame(tryFocus);
    };

    const form = useSchemaFormEngine({
        schema: formSchema,
        schemaIndex,
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

    // Subscribe to form errors so the sidebar re-renders when validation fails (e.g. on Apply).
    const formErrorMap = useSyncExternalStore(
        form.store.subscribe.bind(form.store),
        () => { return form.getErrorMapFields(); },
        () => { return form.getErrorMapFields(); },
    );
    const hasPageErrors = (page, pageIndex) => {
        const errors = formErrorMap || {};
        return Object.keys(errors).some((key) => { return key.startsWith(`pages.${pageIndex}.`); });
    };

    // Keep modal state in sync with latest builder values each time it opens.
    // This prevents stale page snapshots from overwriting unsaved field changes.
    useEffect(() => {
        if (!isOpen) {
            return;
        }

        const latestPages = formValues.pages || [];
        const preferredHandle = initialActivePageHandle || activePageHandle;
        const resolvedActivePage = latestPages.some((page) => { return page?._handle === preferredHandle; })
            ? preferredHandle
            : (latestPages[0]?._handle || null);
        const synced = syncPageHandlesFromLabels(latestPages, resolvedActivePage);

        setPages(synced.pages);
        setActivePage(synced.activePageHandle);
        form.setFieldValue('pages', synced.pages);
        form.setFieldValue('activePage', synced.activePageHandle);
    }, [isOpen, formValues.pages, activePageHandle, initialActivePageHandle]);

    form.onSuccess((data) => {
        // Update the store with the form values
        const synced = syncPageHandlesFromLabels(data.pages, data.activePage);
        updatePages(synced.pages);
        setActivePageHandle(synced.activePageHandle);
        announceFormBuilderStatus(Craft.t('formie', 'Page settings applied.'));

        onClose();
    });

    form.onError((errorMap) => {
        if (!errorMap || typeof errorMap !== 'object') {
            return;
        }

        const hasPageLabelErrors = Object.keys(errorMap).some((key) => {
            return /^pages\.\d+\.label$/.test(key);
        });

        // Prevent duplicate messaging in the modal where both:
        // - pages.N.label (inline field error), and
        // - pages (grouped summary error)
        // are shown at once.
        if (hasPageLabelErrors && errorMap.pages) {
            const nextErrorMap = { ...errorMap };
            delete nextErrorMap.pages;
            form.store.setErrors(nextErrorMap);
        }
    });

    const handleCancel = () => {
        onClose();
    };

    const handleSave = (e) => {
        e.preventDefault();

        form.handleSubmit();
    };

    const handleDragEnd = (event) => {
        if (event.canceled) {
            return;
        }

        const { source } = event.operation;

        if (!isSortable(source)) {
            return;
        }

        const { initialIndex, index } = source;

        if (initialIndex === index || initialIndex < 0 || index < 0 || initialIndex >= pages.length || index >= pages.length) {
            return;
        }

        if (dragReorderTimeoutRef.current) {
            clearTimeout(dragReorderTimeoutRef.current);
        }

        dragReorderTimeoutRef.current = window.setTimeout(() => {
            setPages((items) => {
                if (initialIndex < 0 || index < 0 || initialIndex >= items.length || index >= items.length) {
                    return items;
                }

                const nextPages = [...items];
                const [movedPage] = nextPages.splice(initialIndex, 1);
                nextPages.splice(index, 0, movedPage);

                form.setFieldValue('pages', nextPages);

                return nextPages;
            });
        }, DRAG_REORDER_DELAY_MS);
    };

    useEffect(() => {
        return () => {
            if (dragReorderTimeoutRef.current) {
                clearTimeout(dragReorderTimeoutRef.current);
            }
        };
    }, []);

    const handleAddPage = ({ prefillLabel = false, sourcePages = null } = {}) => {
        const basePages = Array.isArray(sourcePages) ? sourcePages : pages;
        const newPage = createNewPageData(basePages, { prefillLabel });

        const newPages = [...basePages, newPage];
        const newPageHandle = newPage?._handle || null;

        setPages(newPages);
        setActivePage(newPageHandle);

        // Update form with new pages
        form.setFieldValue('pages', newPages);
        form.setFieldValue('activePage', newPageHandle);
        announceFormBuilderStatus(Craft.t('formie', '{label} page added.', {
            label: newPage.label || Craft.t('formie', 'New Page'),
        }));

        pendingFocusPageHandleRef.current = newPageHandle;
    };

    useEffect(() => {
        if (!isOpen) {
            createOnOpenHandledRef.current = false;
            return;
        }

        if (!createPageOnOpen || createOnOpenHandledRef.current) {
            return;
        }

        createOnOpenHandledRef.current = true;
        handleAddPage({
            prefillLabel: false,
            sourcePages: formValues.pages || [],
        });
    }, [isOpen, createPageOnOpen, formValues.pages]);

    useEffect(() => {
        if (!isOpen) {
            pendingFocusPageHandleRef.current = null;
            return;
        }

        const pendingHandle = pendingFocusPageHandleRef.current;
        if (!pendingHandle) {
            return;
        }

        if (pendingHandle !== activePage) {
            return;
        }

        pendingFocusPageHandleRef.current = null;
        focusPageLabelInput();
    }, [isOpen, activePage, pages]);

    const handleDeletePage = () => {
        const activePageObj = pages.find((page) => { return page._handle === activePage; });

        const isConfirmed = window.confirm(
            Craft.t('formie', 'Are you sure you want to delete "{name}"? This will also delete all fields for this page, and cannot be undone.', { name: activePageObj?.label || 'Unknown Page' }),
        );

        if (isConfirmed) {
            // Remove the active page
            const newPages = pages.filter((page) => { return page._handle !== activePage; });
            const newActivePage = newPages.length > 0 ? newPages[0]._handle : null;

            // Update state
            setPages(newPages);
            setActivePage(newActivePage);

            // Update form with new pages
            form.setFieldValue('pages', newPages);
            form.setFieldValue('activePage', newActivePage);
            announceFormBuilderStatus(Craft.t('formie', '{label} page deleted.', {
                label: activePageObj?.label || Craft.t('formie', 'Page'),
            }));
        }
    };

    const handlePageClick = (page) => {
        setActivePage(page._handle);

        form.setFieldValue('activePage', page._handle);
        announceFormBuilderStatus(Craft.t('formie', '{label} page selected.', {
            label: page.label || Craft.t('formie', 'Page'),
        }));
    };

    function DraggableSidebarItem({
        page, pageIndex, isActive, onClick, hasErrors,
    }) {
        const {
            ref, handleRef, isDragging,
        } = useSortable({
            id: page._handle,
            index: pageIndex,
            transition: null,
            sensors: [
                PointerSensor.configure({ activationConstraint: { delay: 0, tolerance: 5 } }),
                KeyboardSensor,
            ],
            modifiers: [
                RestrictToVerticalAxis,
                RestrictToElement.configure({
                    element: () => {
                        return pageListRef.current;
                    },
                }),
            ],
        });

        const style = {
            position: 'relative',
            zIndex: isDragging ? 10 : undefined,
        };

        return (
            <SortablePageItem
                page={page}
                isActive={isActive}
                hasErrors={hasErrors}
                onClick={onClick}
                setNodeRef={ref}
                handleRef={handleRef}
                style={style}
            />
        );
    }

    if (!pages || pages.length === 0) {
        return null;
    }

    return (
        <Dialog open={isOpen} onOpenChange={onClose}>
            <DialogContent className={cn(
                'w-[calc(100vw-24px)] h-[calc(100dvh-24px)]',
                'min-w-0 min-h-0 max-w-none',
                'md:w-[60%] md:h-[60%]',
                'md:min-w-[600px] md:min-h-[400px]',
            )}>
                <DialogHeader>
                    <DialogTitle>
                        {Craft.t('formie', 'Edit Pages')}
                    </DialogTitle>

                    <DialogDescription className="hidden">
                        {Craft.t('formie', 'Edit the pages for this form.')}
                    </DialogDescription>
                </DialogHeader>

                <div ref={contentRef} className="h-full overflow-y-auto">
                    <div className="flex min-h-full flex-1 flex-col md:flex-row">
                        <div className={cn(
                            'relative',
                            'bg-[#f3f7fc]',
                            'border-b border-gray-200 md:border-b-0 md:border-r',
                            'w-full min-h-0 md:w-[200px] md:min-h-full',
                            'shrink-0 basis-auto md:basis-[200px]',
                            'flex flex-col',
                        )}>
                            <div ref={pageListRef} className="max-h-[180px] overflow-y-auto md:max-h-none">
                                <DragDropProvider onDragEnd={handleDragEnd}>
                                    {pages.map((page, pageIndex) => {
                                        const isActive = activePage && activePage === page._handle;
                                        const hasErrors = hasPageErrors(page, pageIndex);

                                        return (
                                            <DraggableSidebarItem
                                                key={page._handle}
                                                page={page}
                                                pageIndex={pageIndex}
                                                isActive={isActive}
                                                onClick={() => { return handlePageClick(page); }}
                                                hasErrors={hasErrors}
                                            />
                                        );
                                    })}
                                </DragDropProvider>
                            </div>

                            <div className="p-3">
                                <Button
                                    variant="dashed"
                                    onClick={handleAddPage}
                                    className="tw-w-full tw-flex tw-items-center tw-justify-center tw-gap-2"
                                >
                                    <FontAwesomeIcon icon={faPlus} className="size-3" />
                                    {Craft.t('formie', 'New Page')}
                                </Button>
                            </div>
                        </div>

                        <div className="flex-1 min-w-0 w-full overflow-x-hidden p-3 md:p-4">
                            <SchemaFormEngine
                                form={form}
                                className="mb-8 space-y-4"
                            />

                            {pages.length > 1 && (
                                <>
                                    <hr />

                                    <Button
                                        variant="link"
                                        size="none"
                                        onClick={handleDeletePage}
                                        className="text-error mt-4"
                                    >
                                        {Craft.t('formie', 'Delete')}
                                    </Button>
                                </>
                            )}
                        </div>
                    </div>
                </div>

                <DialogFooter className="flex flex-row gap-2">
                    <Button
                        type="button"
                        onClick={handleCancel}
                    >
                        {Craft.t('formie', 'Close')}
                    </Button>

                    <Button
                        type="button"
                        variant="primary"
                        onClick={handleSave}
                        className="flex items-center gap-2"
                    >
                        {Craft.t('formie', 'Apply')}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog >
    );
}

export { PageSettingsModal };

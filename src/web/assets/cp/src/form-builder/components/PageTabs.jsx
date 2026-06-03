import {
    useState, useEffect, useMemo, useRef,
} from 'react';
import { useDroppable } from '@dnd-kit/react';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faCog, faPlus, faTriangleExclamation } from '@fortawesome/pro-solid-svg-icons';

import { Button } from '@verbb/plugin-kit-react/components';
import { cn } from '@verbb/plugin-kit-react/utils';
import { useFormValue } from '@form-builder/hooks/useFormTools';
import useUrlRouter from '@form-builder/hooks/useUrlRouter';
import { getDevToolsConfig } from '@form-builder/dev/config';
import { PageSettingsModal } from './PageSettingsModal';
import { useFormBuilderForm } from '@form-builder/contexts/FormBuilderFormContext';
import { useFormBuilderApp } from '@form-builder/contexts/FormBuilderAppContext';
import { ScrollArea } from '@verbb/plugin-kit-react/components';
import { announceFormBuilderStatus } from '@form-builder/utils/accessibility';

function DroppablePageTab({
    page, pageIndex, isActive, hasErrors, onTabClick, onTabDoubleClick, isAnyDragActive = false,
}) {
    const { ref, isDropTarget } = useDroppable({
        id: `page-tab-${pageIndex}`,
        data: { pageIndex },
    });

    return (
        <div ref={ref} className="form-builder-page-tab flex-shrink-0 h-full max-w-[200px] min-w-0">
            <Button
                type="button"
                variant="none"
                onMouseDown={(e) => {
                    // Prevent first click from being consumed by focus move when coming from an input in the right pane.
                    e.preventDefault();
                }}
                onClick={() => { return onTabClick(page); }}
                onDoubleClick={() => { return onTabDoubleClick(page); }}
                className={cn(
                    'outline-none shadow-none',

                    'relative',
                    'h-full',
                    'w-full min-w-0',
                    'form-builder-page-tab-button',
                    'px-[15px]',
                    'text-[#64788d]',
                    'text-[12px]',
                    'font-medium',
                    'uppercase',
                    'rounded-none',

                    !isAnyDragActive && 'hover:text-sky-600',
                    !isAnyDragActive && 'hover:bg-transparent',

                    'focus-visible:shadow-[inset_0_0_0_2px_var(--color-sky-600)]',

                    hasErrors ? 'text-error' : '',

                    // Active state
                    isActive ? [
                        'after:content-[""]',
                        'after:absolute',
                        'after:bottom-0',
                        'after:left-[15px]',
                        'after:right-0',
                        'after:w-[calc(100%-30px)]',
                        'after:h-[2px]',
                        'after:bg-sky-600',
                    ] : [],

                    // Drop target state
                    isDropTarget ? 'bg-[#e5f5f8]' : '',
                )}
            >
                <span className="block truncate">{page.label}</span>
            </Button>
        </div>
    );
}

function PageTabs({ isAnyDragActive = false }) {
    const pages = useFormValue('pages', []);
    const { hasErrorsForPrefix, hasErrorsForFieldNames } = useFormBuilderForm();
    const { activePageHandle, activeTab, pageSettingsSchema } = useFormBuilderApp();
    const router = useUrlRouter();

    const [isSettingsModalOpen, setIsSettingsModalOpen] = useState(false);
    const [settingsModalInitialPageHandle, setSettingsModalInitialPageHandle] = useState(null);
    const [createPageOnModalOpen, setCreatePageOnModalOpen] = useState(false);
    const hasInitialAutoOpenPageSettingsRunRef = useRef(false);
    const builderDevSettings = useMemo(() => {
        if (!import.meta.env.DEV) {
            return null;
        }

        return getDevToolsConfig();
    }, []);
    const hasPages = Array.isArray(pages) && pages.length > 0;
    const hasPageListErrors = hasErrorsForFieldNames(['pages']);

    const getPageHandle = (page) => {
        return page?._handle ?? page?.handle ?? null;
    };

    const handleTabClick = (page) => {
        const pageHandle = getPageHandle(page);
        if (!pageHandle) {
            return;
        }

        router.navigateToPage(pageHandle);
        announceFormBuilderStatus(Craft.t('formie', '{label} page selected.', {
            label: page.label || Craft.t('formie', 'Page'),
        }));
    };

    const handleSettingsClick = () => {
        setSettingsModalInitialPageHandle(null);
        setIsSettingsModalOpen(true);
    };

    const handleQuickAddPage = () => {
        setSettingsModalInitialPageHandle(null);
        setCreatePageOnModalOpen(true);
        setIsSettingsModalOpen(true);
    };

    const handleTabDoubleClick = (page) => {
        if (!page?._handle) {
            return;
        }

        // Ensure route and modal are both focused on the same page.
        router.navigateToPage(page._handle);
        setSettingsModalInitialPageHandle(page._handle);
        setIsSettingsModalOpen(true);
    };

    useEffect(() => {
        if (activeTab !== 'fields' || !hasPages) {
            return;
        }

        const validPageHandles = pages
            .map((page) => {
                return getPageHandle(page);
            })
            .filter(Boolean);
        const firstPageHandle = validPageHandles[0];

        if (!firstPageHandle) {
            return;
        }

        if (!activePageHandle || !validPageHandles.includes(activePageHandle)) {
            router.navigateToPage(firstPageHandle, { replace: true });
        }
    }, [activeTab, activePageHandle, hasPages, pages, router]);

    useEffect(() => {
        if (hasInitialAutoOpenPageSettingsRunRef.current) {
            return;
        }

        const shouldAutoOpenPageSettings = Boolean(
            builderDevSettings?.enabled && builderDevSettings?.autoOpenPageSettings,
        );

        if (!shouldAutoOpenPageSettings || activeTab !== 'fields' || !hasPages) {
            return;
        }

        setSettingsModalInitialPageHandle(null);
        setCreatePageOnModalOpen(false);
        setIsSettingsModalOpen(true);
        hasInitialAutoOpenPageSettingsRunRef.current = true;
    }, [activeTab, builderDevSettings, hasPages, pages]);

    return (
        <div className={cn(
            'form-builder-page-tabs',
            'flex items-center gap-2 mx-2',
            'h-full',
        )}>
            <ScrollArea size="xs" orientation="horizontal" className="h-full flex-1 min-w-0" contentClassName="h-full">
                <div className={cn(
                    'flex items-center gap-1 h-full',
                    'flex-1',
                )}>
                    {hasPages ? (
                        pages.map((page, pageIndex) => {
                            const pageHandle = getPageHandle(page);
                            const isActive = activePageHandle && pageHandle && activePageHandle === pageHandle;
                            const pagePrefix = `pages.${pageIndex}.`;
                            const hasErrors = hasErrorsForPrefix(pagePrefix);

                            return (
                                <DroppablePageTab
                                    key={page._handle || page.handle || page.id || `page-tab-${pageIndex}`}
                                    page={page}
                                    pageIndex={pageIndex}
                                    isActive={isActive}
                                    hasErrors={hasErrors}
                                    onTabClick={handleTabClick}
                                    onTabDoubleClick={handleTabDoubleClick}
                                    isAnyDragActive={isAnyDragActive}
                                />
                            );
                        })
                    ) : (
                        <div className={cn(
                            'flex items-center gap-1.5 px-[15px] text-[12px] font-medium uppercase',
                            hasPageListErrors ? 'text-error' : 'text-[#64788d]',
                        )}>
                            {hasPageListErrors && <FontAwesomeIcon icon={faTriangleExclamation} className="size-3" />}
                            <span>{Craft.t('formie', 'No pages')}</span>
                        </div>
                    )}
                </div>
            </ScrollArea>

            <Button
                type="button"
                variant="outline"
                size="sm"
                onClick={handleQuickAddPage}
                className={cn(
                    'p-2',
                    hasPageListErrors ? 'text-error border-error' : 'text-gray-600',
                    !isAnyDragActive && (hasPageListErrors ? 'hover:text-error' : 'hover:text-gray-800'),
                )}
                aria-label={Craft.t('formie', 'New Page')}
                aria-invalid={hasPageListErrors || undefined}
            >
                <FontAwesomeIcon icon={faPlus} className="size-4" />
            </Button>

            <Button
                type="button"
                variant="outline"
                size="sm"
                onClick={handleSettingsClick}
                className={cn(
                    'p-2 mr-1',
                    hasPageListErrors ? 'text-error border-error' : 'text-gray-600',
                    !isAnyDragActive && (hasPageListErrors ? 'hover:text-error' : 'hover:text-gray-800'),
                )}
                aria-label={Craft.t('formie', 'Page Settings')}
                aria-invalid={hasPageListErrors || undefined}
            >
                <FontAwesomeIcon icon={faCog} className="size-4" />
            </Button>

            <PageSettingsModal
                isOpen={isSettingsModalOpen}
                onClose={() => {
                    setIsSettingsModalOpen(false);
                    setSettingsModalInitialPageHandle(null);
                    setCreatePageOnModalOpen(false);
                }}
                schemaIndex={pageSettingsSchema}
                initialActivePageHandle={settingsModalInitialPageHandle}
                createPageOnOpen={createPageOnModalOpen}
            />
        </div>
    );
}

export { PageTabs };

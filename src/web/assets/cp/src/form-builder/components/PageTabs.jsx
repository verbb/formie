import {
    useState, useEffect, useMemo, useRef,
} from 'react';
import { useDroppable } from '@dnd-kit/react';

import { Button, Icon } from '@verbb/plugin-kit-react/components';
import { cn } from '@verbb/plugin-kit-react/utils';
import { useFormValue } from '@form-builder/hooks/useFormTools';
import useUrlRouter from '@form-builder/hooks/useUrlRouter';
import { getDevToolsConfig } from '@form-builder/dev/config';
import { PageSettingsModal } from './PageSettingsModal';
import { useFormBuilderForm } from '@form-builder/contexts/FormBuilderFormContext';
import { useFormBuilderApp } from '@form-builder/contexts/FormBuilderAppContext';
import useAppStore from '@form-builder/hooks/useAppStore';
import { ScrollArea } from '@verbb/plugin-kit-react/components';
import { announceFormBuilderStatus } from '@form-builder/utils/accessibility';

function DroppablePageTab({
    page, pageIndex, isActive, hasErrors, onTabClick, onTabDoubleClick,
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
                size="none"
                onMouseDown={(e) => {
                    // Prevent first click from being consumed by focus move when coming from an input in the right pane.
                    e.preventDefault();
                }}
                onClick={() => { return onTabClick(page); }}
                onDoubleClick={() => { return onTabDoubleClick(page); }}
                className={cn(
                    'form-builder-page-tab-button',
                    isActive && 'is-active',
                    isDropTarget && 'is-drop-target',
                    hasErrors && 'has-errors',
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
    const enableMultiPageForms = useAppStore((state) => state.enableMultiPageForms ?? true);
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
        if (!enableMultiPageForms) {
            return;
        }

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
            'flex items-center gap-2',
            'h-full',
        )} data-drag-active={isAnyDragActive ? 'true' : undefined}>
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
                                />
                            );
                        })
                    ) : (
                        <div className={cn(
                            'flex items-center gap-1.5 px-[15px] text-[12px] font-medium uppercase',
                            hasPageListErrors ? 'text-error' : 'text-[#64788d]',
                        )}>
                            {hasPageListErrors && <Icon icon="triangle-exclamation" className="size-3" />}
                            <span>{Craft.t('formie', 'No pages')}</span>
                        </div>
                    )}
                </div>
            </ScrollArea>

            {/* ml-auto: ScrollArea may not flex-fill (WC host), so pin actions to the far right.
                mr-1 + page-tabs margin-inline 8px ≈ v1 mx-2 + gear mr-1 gap before the sidebar. */}
            <div className="ml-auto flex shrink-0 items-center gap-2 mr-1">
                {enableMultiPageForms && (
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        onClick={handleQuickAddPage}
                        className={cn(
                            'form-builder-page-tab-action',
                            hasPageListErrors && 'has-errors',
                        )}
                        aria-label={Craft.t('formie', 'New Page')}
                        aria-invalid={hasPageListErrors || undefined}
                    >
                        <Icon slot="start" icon="plus" />
                    </Button>
                )}

                <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    onClick={handleSettingsClick}
                    className={cn(
                        'form-builder-page-tab-action',
                        hasPageListErrors && 'has-errors',
                    )}
                    aria-label={Craft.t('formie', 'Page Settings')}
                    aria-invalid={hasPageListErrors || undefined}
                >
                    <Icon slot="start" icon="gear" />
                </Button>
            </div>

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

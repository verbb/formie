import { useCallback, useEffect, useRef, useState } from 'react';
import { Button, Dialog, Spinner } from '@verbb/plugin-kit-react/components';
import { useTranslation } from '@verbb/plugin-kit-react/hooks';

/**
 * Spike: mount Craft’s element-selector *index* inside a kit Dialog instead of
 * opening Craft.createElementSelectorModal (Garnish). Same body action +
 * Craft.createElementIndex as BaseElementSelectorModal — kit owns chrome only.
 *
 * Tradeoff to evaluate: nested showModal avoids yield/pin, but Craft menus/HUDs
 * that append to document.body may still paint under the top layer.
 */
export function CraftElementIndexDialog({
    open,
    onOpenChange,
    elementType,
    storageKey,
    sources = ['*'],
    criteria = {},
    multiSelect = true,
    showSiteMenu = null,
    defaultSiteId = null,
    title,
    selectLabel,
    onSelect,
}) {
    const t = useTranslation();
    const bodyRef = useRef(null);
    const secondaryButtonsRef = useRef(null);
    const elementIndexRef = useRef(null);
    const optionsRef = useRef({});
    const onSelectRef = useRef(onSelect);
    const onOpenChangeRef = useRef(onOpenChange);
    const [isLoading, setIsLoading] = useState(false);
    const [loadError, setLoadError] = useState(null);
    const [hasSelection, setHasSelection] = useState(false);

    // Snapshot options each render so the open→mount effect does not re-fire on new object identities.
    optionsRef.current = {
        elementType,
        storageKey,
        sources,
        criteria,
        multiSelect,
        showSiteMenu,
        defaultSiteId,
    };
    onSelectRef.current = onSelect;
    onOpenChangeRef.current = onOpenChange;

    const destroyIndex = useCallback(() => {
        const index = elementIndexRef.current;

        if (index) {
            // Craft ElementIndex tear-down; ignore if a Craft version omits destroy.
            try {
                index.destroy?.();
            } catch {
                // Best-effort cleanup for the spike.
            }

            elementIndexRef.current = null;
        }

        if (bodyRef.current) {
            window.jQuery?.(bodyRef.current).off('.formieEmbeddedSelector');
            bodyRef.current.innerHTML = '';
        }

        setHasSelection(false);
    }, []);

    const updateSelectionState = useCallback(() => {
        const selected = elementIndexRef.current?.getSelectedElements?.();
        setHasSelection(Boolean(selected?.length));
    }, []);

    const confirmSelection = useCallback(() => {
        const Craft = window.Craft;
        const $ = window.jQuery;
        const index = elementIndexRef.current;

        if (!Craft || !$ || !index) {
            return;
        }

        const $selected = index.getSelectedElements();

        if (!$selected?.length) {
            return;
        }

        // Same payload shape as BaseElementSelectorModal.getElementInfo → onSelect.
        const elements = [];

        for (let i = 0; i < $selected.length; i += 1) {
            elements.push(Craft.getElementInfo($($selected[i])));
        }

        onSelectRef.current?.(elements);
        onOpenChangeRef.current?.(false);
    }, []);

    useEffect(() => {
        if (!open) {
            destroyIndex();
            setIsLoading(false);
            setLoadError(null);
            return undefined;
        }

        const Craft = window.Craft;
        const $ = window.jQuery;
        let cancelled = false;

        const mountIndex = async () => {
            if (!Craft || !$) {
                setLoadError('Craft CP APIs are unavailable.');
                return;
            }

            if (!bodyRef.current) {
                return;
            }

            const {
                elementType: nextElementType,
                storageKey: nextStorageKey,
                sources: nextSources,
                criteria: nextCriteria,
                multiSelect: nextMultiSelect,
                showSiteMenu: nextShowSiteMenu,
                defaultSiteId: nextDefaultSiteId,
            } = optionsRef.current;

            destroyIndex();
            setIsLoading(true);
            setLoadError(null);

            const bodyParams = {
                context: 'modal',
                elementType: nextElementType,
                sources: nextSources,
            };

            // Mirror BaseElementSelectorModal.getElementIndexParams — only send when explicit.
            if (nextShowSiteMenu !== null && nextShowSiteMenu !== 'auto') {
                bodyParams.showSiteMenu = nextShowSiteMenu ? '1' : '0';
            }

            try {
                const response = await Craft.sendActionRequest('POST', 'element-selector-modals/body', {
                    data: bodyParams,
                });

                if (cancelled || !bodyRef.current) {
                    return;
                }

                const $body = $(bodyRef.current);
                $body.html(response.data.html);

                const $index = $body.children('.element-index');

                if (!$index.length) {
                    setLoadError('Craft did not return an element index.');
                    return;
                }

                // Same index settings BaseElementSelectorModal.getIndexSettings uses (minus Garnish modal).
                elementIndexRef.current = Craft.createElementIndex(nextElementType, $index, {
                    context: 'modal',
                    storageKey: nextStorageKey,
                    criteria: { ...(nextCriteria || {}) },
                    selectable: true,
                    multiSelect: nextMultiSelect,
                    waitForDoubleClicks: true,
                    // Asset upload / secondary actions land here (footer-left in Craft’s modal).
                    buttonContainer: $(secondaryButtonsRef.current),
                    onSelectionChange: updateSelectionState,
                    onSourcePathChange: updateSelectionState,
                    defaultSiteId: nextDefaultSiteId ?? undefined,
                    showSourcePath: true,
                });

                updateSelectionState();

                // Double-click confirms, matching Craft’s selector modal.
                $body.on('dblclick.formieEmbeddedSelector', '.elements .element, .elements tr', () => {
                    if (elementIndexRef.current?.getSelectedElements?.()?.length) {
                        confirmSelection();
                    }
                });
            } catch (error) {
                if (!cancelled) {
                    setLoadError(error?.message || 'Failed to load Craft element index.');
                }
            } finally {
                if (!cancelled) {
                    setIsLoading(false);
                }
            }
        };

        // Wait until pk-dialog has painted slotted body (after showModal).
        const frame = window.requestAnimationFrame(() => {
            if (!cancelled) {
                mountIndex();
            }
        });

        return () => {
            cancelled = true;
            window.cancelAnimationFrame(frame);
            destroyIndex();
        };
    }, [open, destroyIndex, updateSelectionState, confirmSelection]);

    return (
        <Dialog
            open={open}
            label={title || t('Select element')}
            className="formie-craft-element-index-dialog"
            withoutBodyPadding
            disablePointerDismissal
            onPkOpenChange={(event) => {
                const nextOpen = event.detail?.open ?? event.target?.open ?? false;

                if (!nextOpen) {
                    onOpenChange?.(false);
                }
            }}
        >
            <div className="formie-craft-element-index-shell">
                {isLoading ? (
                    <div className="formie-craft-element-index-status">
                        <Spinner size="sm" />
                        <span>{t('Loading…')}</span>
                    </div>
                ) : null}

                {loadError ? (
                    <div className="formie-craft-element-index-status text-rose-600" role="alert">
                        {loadError}
                    </div>
                ) : null}

                {/* Craft injects `.element-index` HTML here (light DOM → CP CSS applies). */}
                <div
                    ref={bodyRef}
                    className="formie-craft-element-index-body"
                    hidden={isLoading || Boolean(loadError)}
                />
            </div>

            {/* Craft AssetIndex mounts upload controls into buttonContainer. */}
            <div
                ref={secondaryButtonsRef}
                slot="footer"
                className="formie-craft-element-index-secondary"
                style={{ marginInlineEnd: 'auto' }}
            />

            <Button
                slot="footer"
                type="button"
                onClick={() => onOpenChange?.(false)}
            >
                {t('Cancel')}
            </Button>

            <Button
                slot="footer"
                type="button"
                variant="primary"
                disabled={!hasSelection}
                onClick={confirmSelection}
            >
                {selectLabel || t('Select')}
            </Button>
        </Dialog>
    );
}

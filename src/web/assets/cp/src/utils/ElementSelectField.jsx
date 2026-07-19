import { useEffect, useState } from 'react';
import { Button, Icon, Spinner, Status } from '@verbb/plugin-kit-react/components';
import { cn, hostOpenElementSelector, hostRequest } from '@verbb/plugin-kit-react/utils';
import { useTranslation } from '@verbb/plugin-kit-react/hooks';
import { FieldLayout } from '@verbb/plugin-kit-react/forms';
import useElementStore from './element-store.js';
import { CraftElementIndexDialog } from './CraftElementIndexDialog.jsx';

const isRecord = (value) => Boolean(value) && typeof value === 'object' && !Array.isArray(value);

const isElementReference = (value) => isRecord(value) && typeof value.id === 'number' && typeof value.siteId === 'number';

const normalizeSelectedElement = (element, elementType) => {
    const url = typeof element.$element?.data === 'function'
        ? element.$element.data('cp-url')
        : null;

    return {
        id: element.id,
        siteId: element.siteId,
        label: element.label,
        url: typeof url === 'string' ? url : null,
        status: element.status ?? null,
        elementType,
    };
};

const normalizeStoredElement = (value, elementType) => {
    if (!isRecord(value) || typeof value.id !== 'number' || typeof value.siteId !== 'number') {
        return null;
    }

    return {
        id: value.id,
        siteId: value.siteId,
        label: typeof value.label === 'string' ? value.label : `Element ${value.id}`,
        url: typeof value.url === 'string' ? value.url : null,
        status: typeof value.status === 'string' ? value.status : null,
        elementType: typeof value.elementType === 'string' ? value.elementType : elementType,
    };
};

export const ElementSelectField = ({ form, field }) => {
    const [isLoading, setIsLoading] = useState(false);
    // Spike: Attach Assets uses kit Dialog + Craft ElementIndex instead of Garnish modal.
    const [embeddedSelectorOpen, setEmbeddedSelectorOpen] = useState(false);
    const { getElementData, setElementData, hasElementData } = useElementStore();
    const t = useTranslation();
    const selectedElements = getElementData(field.name) || [];
    const errors = form?.getErrorMapFields?.()[field.name] || [];
    const elementType = field.elementType || 'craft\\elements\\Entry';
    const useEmbeddedElementIndex = Boolean(field.embeddedElementIndex);
    const storageKey = field.elementSelectStorageKeyPrefix
        ? `${field.elementSelectStorageKeyPrefix}.${field.name}.${elementType}`
        : null;

    const applySelectedElements = (elements) => {
        const updatedElements = [
            ...selectedElements,
            ...elements.map((element) => normalizeSelectedElement(element, elementType)),
        ];

        setElementData(field.name, updatedElements);
        form.setFieldValue(field.name, updatedElements.map((element) => ({
            id: element.id,
            siteId: element.siteId,
        })));
    };

    const handleButtonClick = () => {
        if (!field.elementSelectStorageKeyPrefix) {
            throw new Error(`ElementSelectField requires "elementSelectStorageKeyPrefix" for "${field.name}".`);
        }

        if (useEmbeddedElementIndex) {
            setEmbeddedSelectorOpen(true);
            return;
        }

        hostOpenElementSelector(elementType, {
            storageKey,
            sources: field.sources || ['*'],
            criteria: field.criteria || {},
            multiSelect: !field.limit,
            limit: field.limit || null,
            autoFocusSearchBox: false,
            onShow: () => {
                document.body.style.pointerEvents = '';
            },
            onSelect: applySelectedElements,
            closeOtherModals: false,
        });
    };

    const removeElement = (elementId) => {
        const updatedElements = selectedElements.filter((element) => element.id !== elementId);
        setElementData(field.name, updatedElements);
        form.setFieldValue(field.name, updatedElements.map((element) => ({
            id: element.id,
            siteId: element.siteId,
        })));
    };

    useEffect(() => {
        const loadElements = async () => {
            if (!field.name || hasElementData(field.name)) {
                return;
            }

            const currentValue = form.getFieldValue(field.name);

            if (!Array.isArray(currentValue) || !currentValue.length) {
                return;
            }

            const currentReferences = currentValue.filter(isElementReference);

            if (!currentReferences.length) {
                return;
            }

            setIsLoading(true);

            try {
                if (!field.elementSelectOptionsAction) {
                    throw new Error(`ElementSelectField requires "elementSelectOptionsAction" for "${field.name}".`);
                }

                const response = await hostRequest('POST', field.elementSelectOptionsAction, {
                    data: { elements: currentReferences },
                });

                if (Array.isArray(response.data)) {
                    const normalizedElements = response.data
                        .map((item) => normalizeStoredElement(item, field.elementType || 'craft\\elements\\Entry'))
                        .filter(Boolean);
                    setElementData(field.name, normalizedElements);
                }
            } catch {
                setElementData(field.name, currentReferences.map((item) => ({
                    id: item.id,
                    siteId: item.siteId,
                    label: `Element ${item.id}`,
                    url: null,
                    status: null,
                    elementType: field.elementType || 'craft\\elements\\Entry',
                })));
            } finally {
                setIsLoading(false);
            }
        };

        loadElements();
    }, [form, field, getElementData, hasElementData, setElementData]);

    return (
        <FieldLayout
            name={field.name}
            label={field.label}
            instructions={field.instructions}
            warning={field.warning}
            required={field.required}
            errors={errors}
        >
            <div className="space-y-3">
                {isLoading ? (
                    <div className="relative flex items-center text-sm text-gray-500 min-h-[34px]">
                        <Spinner size="xs" variant="default" className="absolute left-0" />
                        <span className="ml-6">{t('Loading elements...')}</span>
                    </div>
                ) : null}

                {selectedElements.length > 0 ? (
                    <div className="flex flex-wrap gap-2">
                        {selectedElements.map((element) => (
                            <div
                                key={`${element.id}-${element.siteId}`}
                                className={cn(
                                    'flex items-center gap-1.5 border border-gray-200 rounded-lg text-sm px-2 py-1.5 bg-[rgb(243,247,252)] shadow-sm',
                                )}
                            >
                                <div className="flex items-center gap-2">
                                    {element.status ? <Status status={element.status} /> : null}
                                    <a href={element.url || undefined}>
                                        <span>{element.label}</span>
                                    </a>
                                </div>

                                <Button
                                    type="button"
                                    variant="transparent"
                                    size="xs"
                                    onClick={() => removeElement(element.id)}
                                    className="-mr-1"
                                    aria-label={t('Remove element')}
                                >
                                    <Icon slot="start" icon="xmark" className="size-[15px]" />
                                </Button>
                            </div>
                        ))}
                    </div>
                ) : null}

                {(!field.limit || selectedElements.length < field.limit) && !isLoading ? (
                    <Button
                        type="button"
                        variant="dashed"
                        aria-label={field.selectionLabel || t('Choose')}
                        className={cn(errors.length > 0 && 'border-rose-600!')}
                        onClick={handleButtonClick}
                    >
                        <Icon slot="start" icon="plus" className="size-[16px]" />
                        {field.selectionLabel || t('Choose')}
                    </Button>
                ) : null}
            </div>

            {useEmbeddedElementIndex ? (
                <CraftElementIndexDialog
                    open={embeddedSelectorOpen}
                    onOpenChange={setEmbeddedSelectorOpen}
                    elementType={elementType}
                    storageKey={storageKey}
                    sources={field.sources || ['*']}
                    criteria={field.criteria || {}}
                    multiSelect={!field.limit}
                    title={field.label || t('Select element')}
                    selectLabel={t('Select')}
                    onSelect={applySelectedElements}
                />
            ) : null}
        </FieldLayout>
    );
};

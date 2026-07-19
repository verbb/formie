import { useEffect, useState } from 'react';

import useAppStore from '@form-builder/hooks/useAppStore';
import { Button, Icon } from '@verbb/plugin-kit-react/components';
import { cn } from '@verbb/plugin-kit-react/utils';
import { copyToClipboardWithMeta } from '@utils/copyToClipboard';

const FieldBuilderHandle = ({ handle, className, isAnyDragActive = false }) => {
    const showFieldHandles = useAppStore((state) => { return state.showFieldHandles; });
    const [hasCopied, setHasCopied] = useState(false);

    useEffect(() => {
        if (!hasCopied) {
            return undefined;
        }

        const timeoutId = window.setTimeout(() => {
            setHasCopied(false);
        }, 2000);

        return () => {
            window.clearTimeout(timeoutId);
        };
    }, [hasCopied]);

    if (!showFieldHandles || !handle) {
        return null;
    }

    return (
        <Button
            type="button"
            variant="outline"
            size="none"
            className={cn(
                'form-builder-field-handle',
                'absolute right-0 top-1/2 z-10 -translate-y-1/2',
                'inline-flex items-center gap-[5px] px-[5px] py-[2px] text-[10px] font-mono',
                'pointer-events-auto transition-opacity',
                !isAnyDragActive && 'opacity-0 group-hover:opacity-100 group-focus-within:opacity-100',
                isAnyDragActive && 'opacity-0',
                hasCopied && !isAnyDragActive && 'opacity-100',
                className,
            )}
            title={Craft.t('app', 'Copy to clipboard')}
            onMouseDown={(event) => {
                event.stopPropagation();
            }}
            onClick={(event) => {
                event.stopPropagation();
                event.preventDefault();

                copyToClipboardWithMeta(handle);
                setHasCopied(true);
            }}
        >
            <span aria-hidden="true">{handle}</span>
            <span className="sr-only">{Craft.t('app', 'Copy to clipboard')}</span>
            {hasCopied ? (
                <Icon slot="end" icon="check" className="size-[9px]" aria-hidden="true" />
            ) : (
                <Icon slot="end" icon="clipboard" className="size-[9px]" aria-hidden="true" />
            )}
        </Button>
    );
};

export { FieldBuilderHandle };

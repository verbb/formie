import { cn } from '@verbb/plugin-kit-react/utils';
import { Button, Icon } from '@verbb/plugin-kit-react/components';

export function DragHandle({
    handleRef,
    disabled = false,
    ariaLabel,
    className,
}) {
    return (
        <span ref={handleRef}>
            <Button
                type="button"
                variant="none"
                size="xs"
                disabled={disabled}
                className={cn(
                    'cursor-move',
                    'p-0 w-[24px] h-[24px]',
                    'text-gray-500',
                    'hover:bg-transparent',
                    'active:bg-transparent',
                    'hover:text-blue-500',
                    disabled && 'opacity-40 cursor-default',
                    className,
                )}
                aria-label={ariaLabel}
            >
                <Icon icon="grip-move" className="size-3" />
            </Button>
        </span>
    );
}

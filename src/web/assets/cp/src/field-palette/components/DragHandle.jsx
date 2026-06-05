import { Button } from '@verbb/plugin-kit-react/components';
import { cn } from '@verbb/plugin-kit-react/utils';

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
                <div className="size-3">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512" focusable="false" aria-hidden="true">
                        <path fill="currentColor" d="M71.3 295.6c-21.9-21.9-21.9-57.3 0-79.2s57.3-21.9 79.2 0 21.9 57.3 0 79.2s-57.4 21.9-79.2 0zM184.4 182.5c-21.9-21.9-21.9-57.3 0-79.2s57.3-21.9 79.2 0 21.9 57.3 0 79.2-57.3 21.8-79.2 0zm0 147c21.9-21.9 57.3-21.9 79.2 0s21.9 57.3 0 79.2s-57.3 21.9-79.2 0c-21.9-21.8-21.9-57.3 0-79.2zM297.5 216.4c21.9-21.9 57.3-21.9 79.2 0s21.9 57.3 0 79.2s-57.3 21.9-79.2 0c-21.8-21.9-21.8-57.3 0-79.2z" />
                    </svg>
                </div>
            </Button>
        </span>
    );
}

import { Button, DropdownMenu, Icon } from '@verbb/plugin-kit-react/components';

/**
 * Stock `<pk-dropdown-menu>` actions trigger for the variable picker.
 * Trigger uses `slot="trigger"`; items are direct menu children.
 *
 * Flex alignment/sizing must land on the menu host (the flex item) — classes on
 * the inner Button cannot `self-center` the host, and `-mr-1` ate cell padding.
 */
export function VariablePickerActionsMenu({
    label,
    placement = 'bottom-end',
    sideOffset = 8,
    children,
}) {
    return (
        <DropdownMenu
            size="sm"
            placement={placement}
            side-offset={sideOffset}
            className="inline-flex size-[22px] shrink-0 items-center justify-center self-center -mr-[0.5rem]"
        >
            <Button
                slot="trigger"
                type="button"
                size="none"
                icon
                variant="none"
                aria-label={label}
                className="size-full [&::part(base)]:size-full [&::part(base)]:justify-center"
            >
                <Icon slot="start" icon="ellipsis" className="size-3" />
            </Button>
            {children}
        </DropdownMenu>
    );
}

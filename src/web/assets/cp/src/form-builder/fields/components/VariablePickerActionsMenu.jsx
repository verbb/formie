import {
    Button,
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuGroup,
    DropdownMenuTrigger,
} from '@verbb/plugin-kit-react/components';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faEllipsis } from '@fortawesome/pro-solid-svg-icons';

export function VariablePickerActionsMenu({
    label,
    align = 'end',
    sideOffset = 8,
    children,
}) {
    return (
        <DropdownMenu size="sm">
            <DropdownMenuTrigger
                render={(
                    <Button className="rounded-none w-7 h-7 -mr-2" variant="none" aria-label={label} size="icon-xs">
                        <FontAwesomeIcon icon={faEllipsis} className="size-3" />
                    </Button>
                )}
            />
            <DropdownMenuContent align={align} sideOffset={sideOffset}>
                <DropdownMenuGroup>
                    {children}
                </DropdownMenuGroup>
            </DropdownMenuContent>
        </DropdownMenu>
    );
}

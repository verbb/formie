import {
    Button,
    ButtonGroup,
    Popover,
    PopoverContent,
    PopoverTrigger,
    SelectInput,
} from '@verbb/plugin-kit-react/components';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faArrowDownWideShort, faArrowUpWideShort, faSliders } from '@fortawesome/pro-solid-svg-icons';

import { ReportViewerColumnList } from '@reports/components/ReportViewerColumnList';

export function ReportViewerViewPopover({
    sort,
    sortDir,
    sortOptions,
    columns,
    onSortChange,
    onSortDirChange,
    onColumnsChange,
}) {
    return (
        <Popover modal={false}>
            <PopoverTrigger
                render={(
                    <Button type="button" className="gap-2">
                        <FontAwesomeIcon icon={faSliders} className="size-3" />
                        {Craft.t('app', 'View')}
                    </Button>
                )}
            />

            <PopoverContent align="end" className="w-[min(92vw,560px)] p-0">
                <div className="grid grid-cols-[120px_minmax(0,1fr)]">
                    <div className="border-r border-gray-200 bg-gray-50 px-4 py-4 text-sm text-gray-600">
                        {Craft.t('formie', 'Sort by')}
                    </div>
                    <div className="flex items-center gap-2 px-4 py-3">
                        <SelectInput
                            value={sort}
                            options={sortOptions}
                            onChange={onSortChange}
                            triggerClassName="min-w-[180px] flex-1 h-[2.125rem]"
                        />
                        <ButtonGroup className="shrink-0">
                            <Button
                                type="button"
                                variant={sortDir === 'asc' ? 'secondary' : 'default'}
                                size="none"
                                className="h-[2.125rem] w-[2.125rem] justify-center p-0"
                                aria-label={Craft.t('app', 'Ascending')}
                                aria-pressed={sortDir === 'asc'}
                                onClick={() => { onSortDirChange('asc'); }}
                            >
                                <FontAwesomeIcon icon={faArrowUpWideShort} className="size-3.5" />
                            </Button>
                            <Button
                                type="button"
                                variant={sortDir === 'desc' ? 'secondary' : 'default'}
                                size="none"
                                className="h-[2.125rem] w-[2.125rem] justify-center p-0"
                                aria-label={Craft.t('app', 'Descending')}
                                aria-pressed={sortDir === 'desc'}
                                onClick={() => { onSortDirChange('desc'); }}
                            >
                                <FontAwesomeIcon icon={faArrowDownWideShort} className="size-3.5" />
                            </Button>
                        </ButtonGroup>
                    </div>

                    <div className="border-r border-t border-gray-200 bg-gray-50 px-4 py-4 text-sm text-gray-600">
                        {Craft.t('formie', 'Table Columns')}
                    </div>
                    <div className="border-t border-gray-200 px-4 py-1">
                        <ReportViewerColumnList
                            columns={columns}
                            onChange={onColumnsChange}
                        />
                    </div>
                </div>
            </PopoverContent>
        </Popover>
    );
}

import { Button, ButtonGroup, Icon, Popover, SelectInput } from '@verbb/plugin-kit-react/components';

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
        <Popover
            placement="bottom-end"
            /* Panel defaults to 1rem pad / 18rem width — flush + tokens match v1 PopoverContent. */
            flush
            style={{
                '--pk-popover-flush-min-width': 'min(92vw, 560px)',
                '--pk-popover-flush-max-width': 'min(92vw, 560px)',
            }}
        >
            <Button slot="trigger" type="button" className="gap-2">
                <Icon slot="start" icon="sliders" className="size-3" />
                {Craft.t('app', 'View')}
            </Button>

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
                            className="shrink-0"
                            aria-label={Craft.t('app', 'Ascending')}
                            aria-pressed={sortDir === 'asc'}
                            onClick={() => { onSortDirChange('asc'); }}
                        >
                            <Icon slot="start" icon="arrow-up-wide-short" />
                        </Button>
                        <Button
                            type="button"
                            variant={sortDir === 'desc' ? 'secondary' : 'default'}
                            className="shrink-0"
                            aria-label={Craft.t('app', 'Descending')}
                            aria-pressed={sortDir === 'desc'}
                            onClick={() => { onSortDirChange('desc'); }}
                        >
                            <Icon slot="start" icon="arrow-down-wide-short" />
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
        </Popover>
    );
}

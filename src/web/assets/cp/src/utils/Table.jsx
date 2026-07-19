import { forwardRef } from 'react';

import { cn } from './cn.js';

/**
 * Plain HTML table primitives ported from the v1 plugin-kit-react `Table` component.
 * Formie CP uses these lightweight wrappers (not a kit web component) for data grids.
 */
export function Table({ className, ...props }) {
    return (
        <div
            data-slot="table-container"
            className={cn('border border-gray-200 rounded-md rounded-b-none overflow-x-auto')}
        >
            <table data-slot="table" className={cn('w-full', className)} {...props} />
        </div>
    );
}

export function TableHeader({ className, ...props }) {
    return <thead data-slot="table-header" className={cn('bg-gray-50', className)} {...props} />;
}

export function TableBody({ className, ...props }) {
    return (
        <tbody
            data-slot="table-body"
            className={cn('[&_tr:last-child]:border-0', '[&_tr]:bg-white', 'relative', className)}
            {...props}
        />
    );
}

export function TableFooter({ className, ...props }) {
    return <tfoot data-slot="table-footer" className={cn(className)} {...props} />;
}

export const TableRow = forwardRef(({ className, ...props }, ref) => {
    return <tr ref={ref} data-slot="table-row" className={cn(className)} {...props} />;
});

TableRow.displayName = 'TableRow';

export function TableHead({ className, ...props }) {
    return (
        <th
            data-slot="table-head"
            className={cn(
                'whitespace-nowrap',
                'px-2 py-1.5! text-left text-xs font-medium text-gray-700',
                className,
            )}
            {...props}
        />
    );
}

export function TableCell({ className, ...props }) {
    return (
        <td
            data-slot="table-cell"
            className={cn(
                'h-[34px]',
                'whitespace-nowrap',
                'p-0 border-t border-gray-100 border-l border-l-gray-100 first:border-l-0',
                className,
            )}
            {...props}
        />
    );
}

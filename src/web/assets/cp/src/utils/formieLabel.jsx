import { cn } from '@verbb/plugin-kit-react/utils';

/** Simple form label used outside SchemaForm FieldLayout shells. */
export function Label({ className, ...props }) {
    return (
        <label
            data-slot="label"
            className={cn('text-sm font-bold leading-none text-slate-700', className)}
            {...props}
        />
    );
}

import { FieldLayout } from '@verbb/plugin-kit-react/forms';
import { cn } from '@verbb/plugin-kit-react/utils';

/**
 * Formie-owned SchemaForm `$field: 'list'` — was a kit v1 builtin.
 * Renders `field.schema` once per array item under `field.name`, rewriting nested
 * `$field` names to `${field.name}.${index}.${child.name}` with `$item` / `$key` context.
 *
 * Unlabeled lists skip `pk-field` — that shadow shell is `display:block` inside and
 * breaks flex `min-height:0` chains (Edit Buttons / Edit Pages → modal tabs never
 * get a bounded scrollport). Schema `className` (e.g. `flex h-full min-h-0 …`) must
 * land on a real flex host wrapping the item rows.
 */
export function ListField({ form, field }) {
    const Renderer = form?.SchemaRenderer;
    const shouldShowGroupedErrors = field.showGroupedErrors !== false;
    const errors = shouldShowGroupedErrors
        ? (form?.getGroupedErrorsForPath?.(field.name)
            ?? form?.getErrorMapFields?.()[field.name]
            ?? [])
        : [];

    if (!Renderer) {
        return null;
    }

    const items = Array.isArray(form.getFieldValue(field.name)) ? form.getFieldValue(field.name) : [];
    const itemSchema = field.schema;

    const itemRows = (
        <>
            {items.map((item, index) => {
                const modifyChildren = (children) => {
                    let normalizedChildren = [];
                    if (Array.isArray(children)) {
                        normalizedChildren = children;
                    } else if (children) {
                        normalizedChildren = [children];
                    }

                    return normalizedChildren.map((child) => {
                        if (!child || typeof child !== 'object' || Array.isArray(child)) {
                            return child;
                        }

                        const shouldMapChildren = Boolean(child.children) && typeof child.children === 'object';
                        const resolvedChildren = shouldMapChildren
                            ? modifyChildren(child.children)
                            : child.children;

                        const childWithContext = {
                            ...child,
                            _data: {
                                $item: item,
                                $key: index,
                            },
                            children: resolvedChildren,
                        };

                        if (child.$field && typeof child.name === 'string') {
                            return {
                                ...childWithContext,
                                name: `${field.name}.${index}.${child.name}`,
                            };
                        }

                        return childWithContext;
                    });
                };

                const modifiedChildren = modifyChildren(itemSchema);
                const key = item?._uid || item?.id || `${field.name}-${index}`;

                return (
                    <Renderer
                        key={key}
                        schema={modifiedChildren}
                    />
                );
            })}
        </>
    );

    const hasChrome = Boolean(
        field.label
        || field.instructions
        || field.warning
        || field.tip
        || errors.length > 0,
    );

    // Fill host for modal height chains when schema supplies flex classes.
    if (!hasChrome) {
        return (
            <div
                className={cn(field.className)}
                data-name={field.name}
            >
                {itemRows}
            </div>
        );
    }

    return (
        <FieldLayout
            name={field.name}
            label={field.label}
            instructions={field.instructions}
            warning={field.warning}
            tip={field.tip}
            required={field.required}
            errors={errors}
            className={field.className}
        >
            {/* Single wrapper so item rows share one control slot under pk-field. */}
            <div className="flex min-h-0 flex-1 flex-col">
                {itemRows}
            </div>
        </FieldLayout>
    );
}

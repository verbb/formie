import React, { createElement } from 'react';
import { getFormComponentRegistry } from '@verbb/plugin-kit-react/forms/registry';
import { normalizeAttrs } from '@verbb/plugin-kit-react/utils/schema';
import { PreviewSchemaProvider } from './PreviewSchemaContext';
import {
    resolvePreviewNodeAttrs,
    resolvePreviewNodeProps,
    shouldRenderPreviewNode,
} from './previewSchema';

const PreviewSchemaNode = ({ node, context }) => {
    if (node === null || node === undefined || node === false) {
        return null;
    }

    if (typeof node === 'string' || typeof node === 'number') {
        return node;
    }

    if (Array.isArray(node)) {
        return (
            <>
                {node.map((childNode, index) => {
                    return <PreviewSchemaNode key={index} node={childNode} context={context} />;
                })}
            </>
        );
    }

    if (typeof node !== 'object' || !shouldRenderPreviewNode(node, context)) {
        return null;
    }

    if (node.$el) {
        const attrs = normalizeAttrs(resolvePreviewNodeAttrs(node, context));

        return createElement(
            node.$el,
            attrs,
            node.children ? <PreviewSchemaNode node={node.children} context={context} /> : null,
        );
    }

    if (node.$cmp) {
        const Component = getFormComponentRegistry()[node.$cmp];

        if (!Component) {
            console.warn(`Unknown preview schema component: ${node.$cmp}`);
            return null;
        }

        const props = resolvePreviewNodeProps(node, context);

        if (Component.usesSchemaNode) {
            props.schemaNode = node;
        }

        return createElement(
            Component,
            props,
            node.children ? <PreviewSchemaNode node={node.children} context={context} /> : null,
        );
    }

    return null;
};

export const renderFieldPreviewSchema = (previewSchema, field, fieldType = null) => {
    try {
        if (!previewSchema || !Array.isArray(previewSchema) || previewSchema.length === 0) {
            return null;
        }

        const context = {
            field,
            fieldType,
        };

        return (
            <PreviewSchemaProvider value={context}>
                <PreviewSchemaNode node={previewSchema} context={context} />
            </PreviewSchemaProvider>
        );
    } catch (error) {
        console.error('Failed to render preview schema:', error);
        console.error('Preview schema was:', previewSchema);

        return (
            <div className="text-error mt-2">
                <p>{Craft.t('formie', 'Unable to render field preview.')}</p>
            </div>
        );
    }
};

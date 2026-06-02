import { evaluateCondition } from '@verbb/plugin-kit-react/utils/schema';

const isBinding = (value) => {
    return Boolean(value) && typeof value === 'object' && !Array.isArray(value) && ('$bind' in value);
};

export const getPreviewPathValue = (source, path, fallback = undefined) => {
    if (!path) {
        return fallback;
    }

    const segments = String(path).split('.').filter(Boolean);
    let current = source;

    for (const segment of segments) {
        if (current == null || typeof current !== 'object' || !(segment in current)) {
            return fallback;
        }

        current = current[segment];
    }

    return current ?? fallback;
};

export const resolvePreviewValue = (value, context) => {
    if (isBinding(value)) {
        return getPreviewPathValue(context, value.$bind, value.fallback);
    }

    if (Array.isArray(value)) {
        return value.map((item) => { return resolvePreviewValue(item, context); });
    }

    if (value && typeof value === 'object') {
        return Object.fromEntries(Object.entries(value).map(([key, entry]) => {
            return [key, resolvePreviewValue(entry, context)];
        }));
    }

    return value;
};

export const resolvePreviewNodeProps = (node, context) => {
    const props = {};

    Object.entries(node).forEach(([key, value]) => {
        if (key === '$cmp' || key === '$el' || key === 'if' || key === 'attrs' || key === 'children') {
            return;
        }

        props[key] = resolvePreviewValue(value, context);
    });

    return props;
};

export const resolvePreviewNodeAttrs = (node, context) => {
    const attrs = node?.attrs;

    if (!attrs || typeof attrs !== 'object' || Array.isArray(attrs)) {
        return {};
    }

    return resolvePreviewValue(attrs, context);
};

export const shouldRenderPreviewNode = (node, context) => {
    if (!node?.if) {
        return true;
    }

    return Boolean(evaluateCondition(node.if, context));
};

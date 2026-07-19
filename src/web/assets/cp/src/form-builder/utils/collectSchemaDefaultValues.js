import { get, set } from 'lodash-es';

/**
 * Walk a compiled schema tree and collect `$field` nodes that declare `defaultValue`.
 * Used so new editors (notifications, integrations, …) seed the form store before
 * the user opens every tab — otherwise selects render blank and omit the default on save.
 */
export const collectSchemaDefaultValues = (node, prefix = '', defaults = {}) => {
    if (Array.isArray(node)) {
        node.forEach((child) => {
            collectSchemaDefaultValues(child, prefix, defaults);
        });
        return defaults;
    }

    if (!node || typeof node !== 'object') {
        return defaults;
    }

    if (node.$field && node.name && Object.prototype.hasOwnProperty.call(node, 'defaultValue')) {
        const path = `${prefix}${node.name}`;

        if (path && get(defaults, path) === undefined) {
            set(defaults, path, node.defaultValue);
        }
    }

    const childPrefix = `${prefix}${typeof node.schemaChildPrefix === 'string' ? node.schemaChildPrefix : ''}`;

    if (node.schema) {
        collectSchemaDefaultValues(node.schema, childPrefix, defaults);
    } else if (node.children) {
        collectSchemaDefaultValues(node.children, childPrefix, defaults);
    }

    return defaults;
};

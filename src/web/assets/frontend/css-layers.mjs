// Only remove Formie's layers. Vendor CSS keeps its own cascade semantics.
export function formieCssLayers() {
    const layers = new Set(['formie-base', 'formie-theme-base', 'formie-theme']);

    return {
        postcssPlugin: 'formie-unlayered-css',
        AtRule: {
            layer(rule) {
                const names = rule.params.split(',').map((name) => name.trim());

                if (!names.every((name) => layers.has(name))) {
                    return;
                }

                if (rule.nodes) {
                    rule.replaceWith(...rule.nodes);
                } else {
                    rule.remove();
                }
            },
        },
    };
}

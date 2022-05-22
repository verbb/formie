import { composable } from '@formkit/inputs';
import { extend, clone } from '@formkit/utils';

//
// A plugin to wrap select fields with a `<div class="select">` to play nice with Craft
// This is overly complicated at the moment, due to FormKit limitations.
//

const prefix = composable('prefix', () => { return { $el: null }; });
const suffix = composable('suffix', () => { return { $el: null }; });

const select = composable('input', (children) => {
    return {
        $el: 'select',
        bind: '$attrs',
        attrs: {
            id: '$id',
            'data-placeholder': {
                if: '$placeholder',
                then: {
                    if: '$value',
                    then: undefined,
                    else: 'true',
                },
            },
            disabled: '$disabled',
            class: '$classes.input',
            name: '$node.name',
            onInput: '$handlers.selectInput',
            onBlur: '$handlers.blur',
            'aria-describedby': '$describedBy',
        },
        children: {
            if: '$slots.default',
            then: '$slots.default',
            else: children,
        },
    };
});

const option = (schema = {}, children = []) => {
    return {
        if: '$slots.option',
        then: [
            {
                $el: 'text',
                if: '$options.length',
                for: ['option', '$options'],
                children: '$slots.option',
            },
        ],
        else: extend(
            {
                $el: 'option',
                if: '$options.length',
                for: ['option', '$options'],
                bind: '$option.attrs',
                attrs: {
                    class: '$classes.option',
                    value: '$option.value',
                    selected: '$fns.isSelected($option.value)',
                },
                children,
            },
            schema,
        ),
    };
};

const addSelectWrapperPlugin = function(node) {
    node.on('created', () => {
        if (!['select'].includes(node.props.type)) { return; }

        const inputDefinition = clone(node.props.definition);
        const originalSchema = inputDefinition.schema;

        const higherOrderSchema = (extensions) => {
            extensions.inner = {
                $el: 'div',
                children: [{
                    $el: 'div',
                    attrs: { class: 'select' },
                    children: [
                        prefix(extensions.prefix),
                        select(extensions.input, [option(extensions.option, '$option.label')]),
                        suffix(extensions.suffix),
                    ],
                }],
            };

            return originalSchema(extensions);
        };

        inputDefinition.schema = higherOrderSchema;
        node.props.definition = inputDefinition;
    });
};

export default addSelectWrapperPlugin;

import {
    outer,
    inner,
    wrapper,
    label,
    help,
    messages,
    message,
    prefix,
    suffix,
    icon,
    selectInput,
    option,
    optionSlot,
    optGroup,
    $if,
    $attrs,
    $extend,
    options,
    selects,
    defaultIcon,
} from '@formkit/inputs';

export default {
    schema: outer(
        wrapper(
            label('$label'),
            inner(
                // Wrap select fields with a `<div class="select">` to play nice with Craft
                $extend(inner(
                    icon('prefix'),
                    prefix(),
                    selectInput(
                        $if(
                            '$slots.default',
                            () => { return '$slots.default'; },
                            optionSlot(
                                $if(
                                    '$option.group',
                                    optGroup(optionSlot(option('$option.label'))),
                                    option('$option.label'),
                                ),
                            ),
                        ),
                    ),
                    $if('$attrs.multiple !== undefined', () => { return ''; }, icon('select')),
                    suffix(),
                    icon('suffix'),
                ), {
                    attrs: {
                        class: 'select',
                    },
                }),
            ),
        ),
        help('$help'),
        messages(message('$message.value')),
    ),
    type: 'input',
    props: ['options', 'placeholder', 'optionsLoader'],
    forceTypeProp: 'select',
    features: [options, selects, defaultIcon('select', 'select')],
    schemaMemoKey: 'w3kmuru883e',
};

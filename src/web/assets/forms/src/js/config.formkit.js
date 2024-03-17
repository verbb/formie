import { defaultConfig, createInput } from '@formkit/vue';
import { generateClasses } from '@formkit/themes';

// FormKit Plugins
import customLabelPlugin from '@formkit-components/plugins/customLabelPlugin';

// FormKit Inputs
import CheckboxSelectInput from '@formkit-components/inputs/CheckboxSelectInput.vue';
import DateInput from '@formkit-components/inputs/DateInput.vue';
import ElementSelectInput from '@formkit-components/inputs/ElementSelectInput.vue';
import FieldSelectInput from '@formkit-components/inputs/FieldSelectInput.vue';
import HandleInput from '@formkit-components/inputs/HandleInput.vue';
import LightswitchInput from '@formkit-components/inputs/LightswitchInput.vue';
import MultiSelectInput from '@formkit-components/inputs/MultiSelectInput.vue';
import NotificationRecipientsInput from '@formkit-components/inputs/NotificationRecipientsInput.vue';
import RichTextInput from '@formkit-components/inputs/RichTextInput.vue';
import StaticTableInput from '@formkit-components/inputs/StaticTableInput.vue';
import TableInput from '@formkit-components/inputs/TableInput';
import SelectInput from '@formkit-components/inputs/SelectInput';
import VariableTextInput from '@formkit-components/inputs/VariableTextInput.vue';

// FormKit Components
import Collapse from '@formkit-components/Collapse.vue';
import FieldConditions from '@formkit-components/FieldConditions.vue';
import NotificationConditions from '@formkit-components/NotificationConditions.vue';
import ToggleBlock from '@formkit-components/inputs/toggle-blocks/ToggleBlock.vue';

// FormKit Validation Rules
import emailOrVariable from '@formkit-components/rules/emailOrVariable';
import minBlock from '@formkit-components/rules/minBlock';
import required from '@formkit-components/rules/required';
import requiredIf from '@formkit-components/rules/requiredIf';
import uniqueHandle from '@formkit-components/rules/uniqueHandle';
import requiredRichText from '@formkit-components/rules/requiredRichText';
import requiredDate from '@formkit-components/rules/requiredDate';

// FormKit can't handle multiple same-name vaidators, otherwise we could do `requiredTableCell:label`
// eslint-disable-next-line
import { requiredTableCellLabel, requiredTableCellValue, uniqueTableCellLabel, uniqueTableCellValue } from '@formkit-components/rules/tableCell';

export default defaultConfig({
    plugins: [
        customLabelPlugin,
    ],

    rules: {
        emailOrVariable,
        minBlock,
        required,
        requiredDate,
        requiredIf,
        requiredRichText,
        requiredTableCellLabel,
        requiredTableCellValue,
        uniqueHandle,
        uniqueTableCellLabel,
        uniqueTableCellValue,
    },

    messages: {
        en: {
            validation: {
                minBlock({ name }) {
                    return Craft.t('formie', 'At least one field is required.');
                },

                required({ name }) {
                    return Craft.t('formie', '{name} is required.', { name });
                },

                requiredDate({ name }) {
                    return Craft.t('formie', '{name} is required.', { name });
                },

                requiredIf({ name }) {
                    return Craft.t('formie', '{name} is required.', { name });
                },

                requiredRichText({ name }) {
                    return Craft.t('formie', '{name} is required.', { name });
                },

                requiredTableCellLabel(options) {
                    const column = options.node.context.columns.find((item) => {
                        return item.type === 'label';
                    });

                    return Craft.t('formie', '{name} is required.', { name: column.label });
                },

                requiredTableCellValue(options) {
                    const column = options.node.context.columns.find((item) => {
                        return item.type === 'value';
                    });

                    return Craft.t('formie', '{name} is required.', { name: column.label });
                },

                uniqueHandle({ name }) {
                    return Craft.t('formie', 'Handle must be unique.');
                },

                uniqueTableCellLabel(options) {
                    const column = options.node.context.columns.find((item) => {
                        return item.type === 'label';
                    });

                    return Craft.t('formie', 'All {name} must be unique.', { name: column.label });
                },

                uniqueTableCellValue(options) {
                    const column = options.node.context.columns.find((item) => {
                        return item.type === 'value';
                    });

                    return Craft.t('formie', 'All {name} must be unique.', { name: column.label });
                },
            },
        },
    },

    config: {
        classes: generateClasses({
            global: {
                outer: '$reset field',
                wrapper: '$reset field field-wrapper',
                inner: '$reset input',
                label: '$reset field-label',
                help: '$reset instructions',
                messages: '$reset errors',
                message: '$reset error',
                input: '$reset',
            },

            select: {
                input: '$reset select',
            },
        }),
    },

    inputs: {
        // Inputs
        checkboxSelect: createInput(CheckboxSelectInput),
        date: createInput(DateInput),
        elementSelect: createInput(ElementSelectInput),
        fieldSelect: createInput(FieldSelectInput),
        handle: createInput(HandleInput),
        lightswitch: createInput(LightswitchInput),
        multiSelect: createInput(MultiSelectInput),
        notificationRecipients: createInput(NotificationRecipientsInput),
        richText: createInput(RichTextInput),
        staticTable: createInput(StaticTableInput),
        table: TableInput,
        select: SelectInput,
        variableText: createInput(VariableTextInput),

        // Components
        collapse: createInput(Collapse),
        fieldConditions: createInput(FieldConditions),
        fieldWrap: createInput({
            $el: 'div',
            children: '$slots.default',
        }),
        notificationConditions: createInput(NotificationConditions),
        toggleBlocks: createInput({
            $el: 'div',
            children: '$slots.default',
        }),
        toggleBlock: createInput(ToggleBlock),

        subFields: createInput({
            $el: 'div',
            children: '$slots.default',
        }),
    },
});

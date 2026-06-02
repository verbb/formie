import type { FormiePreviewSourceDefinition } from '../../../.vitepress/theme/components/previewSources';
import { renderChoiceField, renderPreviewForm } from './elementDisplayPreview';

const recipientOptions = [
    { label: 'Sales team', value: 'sales@example.com' },
    { label: 'Projects team', value: 'projects@example.com' },
    { label: 'Support team', value: 'support@example.com' },
];

const preview: FormiePreviewSourceDefinition = {
    minHeight: 860,
    markup: renderPreviewForm('recipients-demo', [
        renderChoiceField({
            fieldType: 'recipients',
            handle: 'recipientDropdown',
            label: 'Recipients (dropdown)',
            inputIdPrefix: 'recipients-dropdown',
            displayType: 'dropdown',
            options: recipientOptions,
            selectedValues: ['projects@example.com'],
        }),
        renderChoiceField({
            fieldType: 'recipients',
            handle: 'recipientMultiDropdown',
            label: 'Recipients (multi-dropdown)',
            inputIdPrefix: 'recipients-multi-dropdown',
            displayType: 'dropdown',
            multiple: true,
            options: recipientOptions,
            selectedValues: ['sales@example.com', 'projects@example.com'],
        }),
        renderChoiceField({
            fieldType: 'recipients',
            handle: 'recipientCheckboxes',
            label: 'Recipients (checkboxes)',
            inputIdPrefix: 'recipients-checkboxes',
            displayType: 'checkboxes',
            options: recipientOptions,
            selectedValues: ['sales@example.com', 'support@example.com'],
        }),
        renderChoiceField({
            fieldType: 'recipients',
            handle: 'recipientRadio',
            label: 'Recipients (radio buttons)',
            inputIdPrefix: 'recipients-radio',
            displayType: 'radio',
            options: recipientOptions,
            selectedValues: ['projects@example.com'],
        }),
    ]),
};

export default preview;

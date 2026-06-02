import type { FormiePreviewSourceDefinition } from '../../../.vitepress/theme/components/previewSources';
import { renderChoiceField, renderPreviewForm } from './elementDisplayPreview';

const entryOptions = [
    { label: 'Homepage refresh kickoff', value: 'entry-1001' },
    { label: 'Case study: Alpine redesign', value: 'entry-1002' },
    { label: 'Launch checklist', value: 'entry-1003' },
];

const preview: FormiePreviewSourceDefinition = {
    minHeight: 860,
    markup: renderPreviewForm('entries-demo', [
        renderChoiceField({
            fieldType: 'entries',
            handle: 'featuredEntry',
            label: 'Entries (dropdown)',
            inputIdPrefix: 'entries-dropdown',
            displayType: 'dropdown',
            options: entryOptions,
            selectedValues: ['entry-1002'],
        }),
        renderChoiceField({
            fieldType: 'entries',
            handle: 'relatedEntries',
            label: 'Entries (multi-dropdown)',
            inputIdPrefix: 'entries-multi-dropdown',
            displayType: 'dropdown',
            multiple: true,
            options: entryOptions,
            selectedValues: ['entry-1001', 'entry-1003'],
        }),
        renderChoiceField({
            fieldType: 'entries',
            handle: 'entryCheckboxes',
            label: 'Entries (checkboxes)',
            inputIdPrefix: 'entries-checkboxes',
            displayType: 'checkboxes',
            options: entryOptions,
            selectedValues: ['entry-1001', 'entry-1003'],
        }),
        renderChoiceField({
            fieldType: 'entries',
            handle: 'entryRadio',
            label: 'Entries (radio buttons)',
            inputIdPrefix: 'entries-radio',
            displayType: 'radio',
            options: entryOptions,
            selectedValues: ['entry-1002'],
        }),
    ]),
};

export default preview;

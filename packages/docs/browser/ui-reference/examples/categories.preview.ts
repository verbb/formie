import type { FormiePreviewSourceDefinition } from '../../../.vitepress/theme/components/previewSources';
import { renderChoiceField, renderPreviewForm } from './elementDisplayPreview';

const categoryOptions = [
    { label: 'News', value: 'category-news' },
    { label: 'Case Studies', value: 'category-case-studies' },
    { label: 'Guides', value: 'category-guides' },
];

const preview: FormiePreviewSourceDefinition = {
    minHeight: 860,
    markup: renderPreviewForm('categories-demo', [
        renderChoiceField({
            fieldType: 'categories',
            handle: 'primaryCategory',
            label: 'Categories (dropdown)',
            inputIdPrefix: 'categories-dropdown',
            displayType: 'dropdown',
            options: categoryOptions,
            selectedValues: ['category-case-studies'],
        }),
        renderChoiceField({
            fieldType: 'categories',
            handle: 'visibleCategories',
            label: 'Categories (multi-dropdown)',
            inputIdPrefix: 'categories-multi-dropdown',
            displayType: 'dropdown',
            multiple: true,
            options: categoryOptions,
            selectedValues: ['category-news', 'category-guides'],
        }),
        renderChoiceField({
            fieldType: 'categories',
            handle: 'categoryCheckboxes',
            label: 'Categories (checkboxes)',
            inputIdPrefix: 'categories-checkboxes',
            displayType: 'checkboxes',
            options: categoryOptions,
            selectedValues: ['category-case-studies', 'category-guides'],
        }),
        renderChoiceField({
            fieldType: 'categories',
            handle: 'categoryRadio',
            label: 'Categories (radio buttons)',
            inputIdPrefix: 'categories-radio',
            displayType: 'radio',
            options: categoryOptions,
            selectedValues: ['category-news'],
        }),
    ]),
};

export default preview;

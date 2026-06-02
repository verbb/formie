import type { Theme } from 'vitepress';
import { createVerbbDocsTheme } from '@verbb/vitepress-theme';
import FormiePreview from './components/FormiePreview.vue';
import './site.css';

const theme: Theme = createVerbbDocsTheme({
    enhanceApp({ app }) {
        app.component('FormiePreview', FormiePreview);
    },
});

export default theme;

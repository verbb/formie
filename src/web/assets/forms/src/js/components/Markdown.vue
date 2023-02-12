<template>
    <p v-html="html"></p>
</template>

<script>
import MarkdownIt from 'markdown-it';

const markdown = new MarkdownIt();

export default {
    name: 'Markdown',

    props: {
        source: {
            type: String,
            default: '',
        },
    },

    computed: {
        html() {
            return markdown.renderInline(this.source);
        },
    },
};

</script>

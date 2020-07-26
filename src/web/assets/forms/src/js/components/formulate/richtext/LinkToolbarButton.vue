<template>
    <div v-on-clickaway="clickAway">
        <button v-tooltip="button.text" class="btn fui-toolbar-btn" :class="{ active }" @click.prevent="showLinkToolbar(getMarkAttrs('link'))">
            <i class="far" :class="'fa-' + button.icon"></i>
        </button>

        <link-toolbar
            v-if="showingToolbar"
            :initial-link-attrs="linkAttrs"
            @updated="setLink"
            @deselected="showingToolbar = false"
        />
    </div>
</template>

<script>
import { directive as onClickaway } from 'vue-clickaway';

import LinkToolbar from './LinkToolbar.vue';

export default {
    name: 'LinkToolbarButton',

    components: {
        LinkToolbar,
    },

    directives: {
        onClickaway,
    },

    props: {
        button: {
            type: Object,
            default: () => {},
        },

        active: {
            type: Boolean,
            default: false,
        },

        field: {
            type: Object,
            default: () => {},
        },

        editor: {
            type: Object,
            default: () => {},
        },
    },

    data() {
        return {
            linkAttrs: null,
            showingToolbar: false,
            getMarkAttrs: this.editor.getMarkAttrs.bind(this.editor),
        };
    },

    methods: {
        showLinkToolbar(attrs) {
            this.showingToolbar = false;

            this.$nextTick(() => {
                this.showingToolbar = true;
                this.linkAttrs = attrs;
            });
        },

        setLink(attributes) {
            this.editor.commands.link(attributes);
            this.linkAttrs = null;
            this.showingToolbar = false;
            this.editor.focus();
        },

        clickAway() {
            this.showingToolbar = false;
        },
    },
};

</script>

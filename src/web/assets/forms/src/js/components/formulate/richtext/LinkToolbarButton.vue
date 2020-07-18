<template>
    <div>
        <button v-tooltip="button.text" class="btn fui-toolbar-btn" :class="{ active }" @click.prevent="showLinkToolbar(getMarkAttrs('link'))">
            <i class="far" :class="'fa-' + button.icon"></i>
        </button>

        <link-toolbar
            v-if="showingToolbar"
            :initial-link-attrs="linkAttrs"
            :config="config"
            @updated="setLink"
            @deselected="showingToolbar = false"
        />
    </div>
</template>

<script>
import LinkToolbar from './LinkToolbar.vue';

export default {
    components: {
        LinkToolbar,
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

        config: {
            type: Object,
            default: () => {},
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
            linkAttrs: {},
            showingToolbar: false,
            getMarkAttrs: this.editor.getMarkAttrs.bind(this.editor),
        };
    },

    methods: {
        showLinkToolbar(attrs) {
            this.showingToolbar = false;

            this.$nextTick(() => {
                this.showingToolbar = true;
                // this.linkAttrs = attrs;
                this.linkAttrs = {
                    href: '',
                    target: '',
                };
            });
        },

        setLink(attributes) {
            this.editor.commands.link(attributes);
            this.linkAttrs = {};
            this.showingToolbar = false;
            this.editor.focus();
        },
    },
};

</script>

<template>
    <div class="fui-link-toolbar">
        <div class="">
            <div class="">
                <div class="link-container">
                    <a
                        v-show="!isEditing"
                        :href="actualLinkHref"
                        class="link"
                        target="_blank"
                        v-text="actualLinkText"
                    ></a>
                </div>

                <div :class="isEditing ? '' : 'hidden'">
                    <input
                        ref="input"
                        v-model="linkInput"
                        type="text"
                        class=""
                        @keydown.enter.prevent="commit"
                    >
                </div>
            </div>

            <div class="fui-link-toolbar-buttons">
                <button v-show="!isEditing" v-tooltip="$options.filters.t('Edit Link', 'formie')" @click="edit">
                    <span class="icon icon-pencil"></span>
                </button>

                <button v-show="hasLink && isEditing" v-tooltip="$options.filters.t('Remove Link', 'formie')" @click="remove">
                    <span class="icon icon-trash"></span>
                </button>

                <button v-show="isEditing" v-tooltip="$options.filters.t('Done', 'formie')" @click="commit">
                    <span class="icon icon-check"></span>
                </button>
            </div>
        </div>

        <div v-show="isEditing" class="">
            <label class="">
                <input v-model="targetBlank" class="" type="checkbox">
                {{ $options.filters.t('Open in new window', 'formie') }}
            </label>
        </div>
    </div>
</template>

<script>

export default {
    props: {
        config: {
            type: Object,
            default: () => {},
        },

        initialLinkAttrs: {
            type: Object,
            default: () => {},
        },
    },

    data() {
        return {
            linkAttrs: this.initialLinkAttrs,
            linkInput: this.initialLinkAttrs.href,
            targetBlank: null,
            isEditing: false,
        };
    },

    computed: {
        hasLink() {
            return this.actualLinkHref != null;
        },

        isInternalLink() {
            return false;
        },

        actualLinkHref() {
            return this.isInternalLink ? this.internalLink.url : this.linkAttrs.href;
        },

        actualLinkText() {
            return this.isInternalLink ? this.internalLink.text : this.linkAttrs.href;
        },
    },

    created() {
        this.targetBlank = this.linkAttrs.href ? this.linkAttrs.target == '_blank' : '';

        if (!this.linkAttrs.href) {
            this.edit();
        }
    },

    methods: {
        edit() {
            this.isEditing = true;
            this.$nextTick(() => this.$refs.input.focus());
        },

        remove() {
            this.$emit('updated', { href: null });
        },

        commit() {
            var rel = [];

            rel = rel.length ? rel.join(' ') : null;

            this.$emit('updated', {
                href: this.sanitizeLink(this.linkInput),
                rel,
                target: this.targetBlank ? '_blank' : null,
            });
        },

        getLinkId(link) {
            const match = link.match(/^{{ link:(.*) }}$/);

            if (!match || !match[1]) {
                return null;
            }

            return match[1];
        },

        sanitizeLink(link) {
            const str = link.trim();

            return str.match(/^\w[\w\-_.]+\.(co|uk|com|org|net|gov|biz|info|us|eu|de|fr|it|es|pl|nz)/i) ? 'https://' + str : str;
        },
    },

};

</script>

<style lang="scss">

.fui-link-toolbar {
    background-color: #000;
    border-radius: 3px;
    position: absolute;
    line-height: 1;
    box-shadow: 0 0 0 1px rgba(49,49,93,.05), 0 2px 5px 0 rgba(49,49,93,.075), 0 1px 3px 0 rgba(49,49,93,.15);
    margin-top: 8px;
    z-index: 100;
    width: 300px;
    top: 100%;
}

</style>

<template>
    <div class="fui-link-toolbar">
        <div class="fui-link-toolbar-wrap">
            <div class="fui-link-toolbar-inner">
                <div class="fui-link-container">
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
                        class="input"
                        @keydown.enter.prevent="commit"
                    >
                </div>
            </div>

            <div class="fui-link-toolbar-buttons">
                <button v-show="!isEditing" v-tooltip="$options.filters.t('Edit Link', 'formie')" class="btn fui-link-btn" @click.prevent="edit">
                    <span class="fas fa-pencil"></span>
                </button>

                <button v-show="hasLink && isEditing" v-tooltip="$options.filters.t('Remove Link', 'formie')" class="btn fui-link-btn" @click.prevent="remove">
                    <span class="fas fa-trash"></span>
                </button>

                <button v-show="isEditing" v-tooltip="$options.filters.t('Done', 'formie')" class="btn fui-link-btn" @click.prevent="commit">
                    <span class="fas fa-check"></span>
                </button>
            </div>
        </div>

        <div v-show="isEditing" class="fui-link-footer">
            <label class="fui-link-footer-label">
                <input v-model="targetBlank" class="" type="checkbox">
                {{ $options.filters.t('Open in new window', 'formie') }}
            </label>
        </div>
    </div>
</template>

<script>
export default {
    name: 'LinkToolbar',

    props: {
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

        this.edit();
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
            if (!link) {
                return '';
            }
            
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
    color: #fff;

    &::before {
        content: "";
        border: 6px solid transparent;
        border-bottom-color: #000;
        position: absolute;
        bottom: 100%;
        left: 10px;
    }
}

.fui-link-btn {
    background: transparent;
    color: #fff;
    font-size: 14px;
    height: 25px;
    width: 25px;
    padding: 0;

    &:hover {
        background: transparent;
    }
}

.fui-link-toolbar-wrap {
    padding: 0 0.75rem;
    align-items: center;
    display: flex;
    height: 40px;
}

.fui-link-toolbar-inner {
    min-width: 0;
    flex: 1 1 0%;
}

.fui-link-container {
    width: 100%;
    overflow: hidden;
    color: #fff;
    line-height: 1.5;
    text-overflow: ellipsis;

    .link {
        color: #fff;
        font-size: 13px;
    }
}

.fui-link-toolbar .input {
    padding: 0;
    font-size: 13px;
    color: #fff;
    height: 40px;
    background-color: transparent;
    box-shadow: none;
    border-style: none;
    width: 100%;
    outline: none;
    flex: 1 1 0%;
    line-height: normal;
}



.fui-link-footer {
    padding: 0.75rem 0.75rem;
    border-top: 1px solid white;
}

.fui-link-footer-label {
    font-size: 12px;
    color: #fff;
    align-items: center;
    display: flex;

    input {
        margin-right: 5px;
    }
}

</style>

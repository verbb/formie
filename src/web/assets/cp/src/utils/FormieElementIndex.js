/**
 * Keep Craft's source navigation while avoiding repeated DOM sorts on sites with
 * thousands of forms. This subclass only applies to Formie's submission indexes.
 */
export const FormieElementIndex = Craft.BaseElementIndex.extend({
    initSources($sources) {
        this.pendingSourceItems = [];

        try {
            return this.base($sources);
        } finally {
            const items = this.pendingSourceItems;
            this.pendingSourceItems = null;

            if (items.length) {
                this.sourceNav.addItems($(items));
            }
        }
    },

    initSource($source) {
        // Older Craft versions use a different navigation implementation.
        if (!this.pendingSourceItems || !this.sourceNav) {
            return this.base($source);
        }

        this.pendingSourceItems.push(...$source);
        this.initSourceToggle($source);
        this.sourcesByKey[$source.data('key')] = $source;

        if ($source.data('hasNestedSources') && this.instanceState.expandedSources.includes($source.data('key'))) {
            this._expandSource($source);
        }
    },

    updateSourceVisibility() {
        // $sources is already in document order. Filtering it preserves that order
        // without sorting the growing visible collection once per source.
        this.$visibleSources = this.$sources.filter((index, source) => {
            const $source = $(source);
            const sites = $source.data('sites');
            const visible = !Garnish.hasAttr($source, 'data-disabled') && (
                typeof sites === 'undefined' || sites.toString().split(',').some((siteId) => {
                    return siteId == this.siteId;
                })
            );

            $source.parent().toggleClass('hidden', !visible);

            if (!visible && this.$source?.get(0) === source) {
                this.$source = null;
                this.$rootSource = null;
                this.sourceKey = null;
                this.rootSourceKey = null;
            }

            return visible;
        });
    },
});

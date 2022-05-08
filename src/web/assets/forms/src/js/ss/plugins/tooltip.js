import { VTooltip } from 'v-tooltip';

export default Vue => {
    Vue.directive('tooltip', VTooltip);
    VTooltip.options.defaultTemplate = '<div class="fui-tooltip" role="tooltip"><div class="tooltip-arrow"></div><div class="tooltip-inner"></div></div>';

    // WHy won't this work?!
    // Vue.use(VTooltip, {
    //     // These don't seem to work?!
    //     // defaultPlacement: 'top',
    //     // defaultClass: 'vue-tooltip-theme',
    //     // defaultTargetClass: 'has-tooltip',
    //     // defaultHtml: true,
    //     defaultTemplate: '<div class="fui-tooltip" role="tooltip"><div class="tooltip-arrow"></div><div class="tooltip-inner"></div></div>',
    // });
};

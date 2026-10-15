import{_ as e,a as t,d as n,i as r,l as i,n as a,p as o,r as s,s as c,t as l,u}from"./has-slot-DJv86HKx-D2K9y9lH.js";import{a as d,c as f,d as p,f as m,i as h,l as g,m as _,o as v,p as y,s as b}from"./lit-C7H9X-yg.js";var ee=e=>e.replace(/\n/g,`<br>`),te=e=>e?e.response?.statusText?e.response.statusText:e.message?.includes(`Network Error`)?`Network Error`:e.message?.includes(`timeout`)?`Request Timeout`:`An error has occurred`:`An error has occurred`,x=e=>e?e.response?.data?.message?e.response.data.message:e.response?.data?.error?e.response.data.error:e.message?e.message:String(e):``,S=(e,t=5)=>{let n=[];if(!e)return{traces:n,traceAsString:``};let r=e.response?.data?.file,i=e.response?.data?.line;r&&i&&n.push(`${r}:${i}`);let a=e.response?.data?.trace||[];for(let e=0;e<Math.min(t,a.length);e++){let t=a[e];t?.file&&t?.line&&n.push(`${t.file}:${t.line}`)}return e.stack&&n.length===0&&n.push(e.stack),{traces:n,traceAsString:n.map(ee).join(`<br>`)}},C=function(e,t=5){if(e==null)return{heading:`An error has occurred`,text:``,trace:``,traceAsString:``,traceAsArray:[]};let{traces:n,traceAsString:r}=S(e,t);return{heading:te(e),text:x(e),trace:r,traceAsString:r,traceAsArray:n}};function w(e=`default`){return e===`xxs`||e===`xs`?`xxs`:e===`lg`||e===`xl`?`sm`:`xs`}function T(e=`default`,t){return t||(e===`primary`||e===`secondary`||e===`dashed`||e===`outline`||e===`transparent`?e:`default`)}var E=[t,y`
        @layer pk-component {
            :host {
                display: block;
                box-sizing: border-box;
            }

            :host([centered]) {
                position: absolute;
                top: 50%;
                left: 50%;
                display: block;
                width: fit-content;
                height: fit-content;
                margin: 0;
                transform: translate(-50%, -50%);
            }

            .spinner {
                display: block;
                box-sizing: border-box;
                margin-inline: auto;
                border-style: solid;
                border-bottom-color: transparent;
                border-left-color: transparent;
                border-radius: 50%;
                animation: pk-spinner-spin 0.5s linear infinite;
            }

            /* Sizes */
            :host([size='xxs']) .spinner {
                width: 0.75rem;
                height: 0.75rem;
                border-width: 1px;
            }

            :host([size='xs']) .spinner {
                width: 1rem;
                height: 1rem;
                border-width: 2px;
            }

            :host([size='sm']) .spinner,
            :host(:not([size])) .spinner {
                width: 1.5rem;
                height: 1.5rem;
                border-width: 2px;
            }

            :host([size='md']) .spinner {
                width: 2rem;
                height: 2rem;
                border-width: 2px;
            }

            :host([size='lg']) .spinner {
                width: 3rem;
                height: 3rem;
                border-width: 2px;
            }

            :host([size='xl']) .spinner {
                width: 4rem;
                height: 4rem;
                border-width: 2px;
            }

            /* Variants — matched to button loading contrast */
            :host([variant='default']:not([tone])) .spinner {
                border-top-color: var(--pk-color-red-500);
                border-right-color: var(--pk-color-red-500);
            }

            :host([variant='primary']:not([tone])) .spinner,
            :host([variant='secondary']:not([tone])) .spinner {
                border-top-color: var(--pk-color-white);
                border-right-color: var(--pk-color-white);
            }

            :host([variant='dashed']:not([tone])) .spinner,
            :host([variant='outline']:not([tone])) .spinner,
            :host([variant='transparent']:not([tone])) .spinner {
                border-top-color: var(--pk-color-gray-700);
                border-right-color: var(--pk-color-gray-700);
            }

            /* Standalone tone overrides */
            :host([tone='sky']) .spinner {
                border-top-color: var(--pk-color-sky-600);
                border-right-color: var(--pk-color-sky-600);
            }

            :host([tone='emerald']) .spinner {
                border-top-color: var(--pk-color-emerald-600);
                border-right-color: var(--pk-color-emerald-600);
            }

            :host([tone='violet']) .spinner {
                border-top-color: var(--pk-color-violet-600);
                border-right-color: var(--pk-color-violet-600);
            }

            :host([tone='amber']) .spinner {
                border-top-color: var(--pk-color-amber-500);
                border-right-color: var(--pk-color-amber-500);
            }

            @keyframes pk-spinner-spin {
                to {
                    transform: rotate(360deg);
                }
            }
        }
    `],D=class extends a{constructor(...e){super(...e),this.variant=`default`,this.size=`sm`,this.centered=!1}static{this.styles=E}render(){return m`
            <div part="base" class="spinner" aria-hidden="true"></div>
        `}};s([g({reflect:!0})],D.prototype,`variant`,void 0),s([g({reflect:!0})],D.prototype,`size`,void 0),s([g({reflect:!0})],D.prototype,`tone`,void 0),s([g({type:Boolean,reflect:!0})],D.prototype,`centered`,void 0),D=s([r(`pk-spinner`)],D);var O=y`
    @layer pk-component {
        slot[name='start']::slotted(svg),
        slot[name='end']::slotted(svg) {
            display: block;
            width: 1em;
            height: 1em;
            flex-shrink: 0;
            pointer-events: none;
            vertical-align: middle;
            overflow: visible;
        }
    }
`;function k(e,t=`var(--pk-btn-radius, var(--pk-radius-lg))`){let n=_(e),r=_(t);return y`
        ${n} {
            border-top-left-radius: var(--pk-bg-start-start-radius, ${r});
            border-top-right-radius: var(--pk-bg-start-end-radius, ${r});
            border-bottom-left-radius: var(--pk-bg-end-start-radius, ${r});
            border-bottom-right-radius: var(--pk-bg-end-end-radius, ${r});
        }
    `}function A(){return y`
        :host([data-pk-group-orientation='horizontal']:not([data-pk-group-item-first]):not([data-pk-group-item-last])) {
            --pk-bg-start-start-radius: 0;
            --pk-bg-start-end-radius: 0;
            --pk-bg-end-start-radius: 0;
            --pk-bg-end-end-radius: 0;
        }

        :host([data-pk-group-orientation='horizontal'][data-pk-group-item-first]:not([data-pk-group-item-last])) {
            --pk-bg-start-end-radius: 0;
            --pk-bg-end-end-radius: 0;
        }

        :host([data-pk-group-orientation='horizontal'][data-pk-group-item-last]:not([data-pk-group-item-first])) {
            --pk-bg-start-start-radius: 0;
            --pk-bg-end-start-radius: 0;
        }

        :host([data-pk-group-orientation='vertical']:not([data-pk-group-item-first]):not([data-pk-group-item-last])) {
            --pk-bg-start-start-radius: 0;
            --pk-bg-start-end-radius: 0;
            --pk-bg-end-start-radius: 0;
            --pk-bg-end-end-radius: 0;
        }

        :host([data-pk-group-orientation='vertical'][data-pk-group-item-first]:not([data-pk-group-item-last])) {
            --pk-bg-end-start-radius: 0;
            --pk-bg-end-end-radius: 0;
        }

        :host([data-pk-group-orientation='vertical'][data-pk-group-item-last]:not([data-pk-group-item-first])) {
            --pk-bg-start-start-radius: 0;
            --pk-bg-start-end-radius: 0;
        }
    `}function j(){return y`
        :host([data-pk-group-orientation='horizontal'][data-pk-group-join][variant='outline']),
        :host([data-pk-group-orientation='horizontal'][data-pk-group-join][variant='dashed']) {
            margin-inline-start: var(--pk-bg-horizontal-indent-outlined, 0);
            margin-block-start: 0;
        }

        :host([data-pk-group-orientation='vertical'][data-pk-group-join][variant='outline']),
        :host([data-pk-group-orientation='vertical'][data-pk-group-join][variant='dashed']) {
            margin-block-start: var(--pk-bg-vertical-indent-outlined, 0);
            margin-inline-start: 0;
        }

        :host([data-pk-group-orientation='horizontal'][data-pk-group-join]:not([variant='outline']):not([variant='dashed']):not([variant='link']):not([variant='none'])) {
            margin-inline-start: var(--pk-bg-horizontal-indent, 0);
            margin-block-start: 0;
        }

        :host([data-pk-group-orientation='vertical'][data-pk-group-join]:not([variant='outline']):not([variant='dashed']):not([variant='link']):not([variant='none'])) {
            margin-block-start: var(--pk-bg-vertical-indent, 0);
            margin-inline-start: 0;
        }

        :host([data-pk-group-orientation='horizontal'][data-pk-group-divider][data-pk-group-join][variant='primary']),
        :host([data-pk-group-orientation='horizontal'][data-pk-group-divider][data-pk-group-join][variant='secondary']),
        :host([data-pk-group-orientation='horizontal'][data-pk-group-divider][data-pk-group-join][variant='default']) {
            margin-inline-start: 0;
            margin-block-start: 0;
        }

        :host([data-pk-group-orientation='vertical'][data-pk-group-divider][data-pk-group-join][variant='primary']),
        :host([data-pk-group-orientation='vertical'][data-pk-group-divider][data-pk-group-join][variant='secondary']),
        :host([data-pk-group-orientation='vertical'][data-pk-group-divider][data-pk-group-join][variant='default']) {
            margin-block-start: 0;
            margin-inline-start: 0;
        }

        /* Filled variants — Craft margin gap; parent background shows through.
         * !important: outer preflight/utilities beat non-important :host margin
         * (revert-layer cannot restore shadow host values — it still specifies outer 0).
         */
        :host([data-pk-group-orientation='horizontal'][data-pk-group-internal-trail][variant='primary']),
        :host([data-pk-group-orientation='horizontal'][data-pk-group-internal-trail][variant='secondary']),
        :host([data-pk-group-orientation='horizontal'][data-pk-group-internal-trail][variant='default']) {
            margin-inline-end: var(--pk-btn-group-gap, 1px) !important;
        }

        :host([data-pk-group-orientation='vertical'][data-pk-group-internal-trail][variant='primary']),
        :host([data-pk-group-orientation='vertical'][data-pk-group-internal-trail][variant='secondary']),
        :host([data-pk-group-orientation='vertical'][data-pk-group-internal-trail][variant='default']) {
            margin-block-end: var(--pk-btn-group-gap, 1px) !important;
        }
    `}function M(e){let t=_(e);return y`
        :host([data-pk-group-orientation='horizontal'][data-pk-group-join]:not([data-pk-group-divider])) ${t} {
            border-left-width: 0;
        }

        :host([data-pk-group-orientation='vertical'][data-pk-group-join]:not([data-pk-group-divider])) ${t} {
            border-top-width: 0;
        }

        :host([data-pk-group-orientation='horizontal'][data-pk-group-divider]:not([variant='outline']):not([variant='dashed'])) ${t} {
            border-left-width: 0;
        }

        :host([data-pk-group-orientation='vertical'][data-pk-group-divider]:not([variant='outline']):not([variant='dashed'])) ${t} {
            border-top-width: 0;
        }

        :host([data-pk-group-orientation='horizontal'][data-pk-group-internal-trail][variant='outline']) ${t},
        :host([data-pk-group-orientation='horizontal'][data-pk-group-internal-trail][variant='dashed']) ${t} {
            border-right-width: 0;
        }

        :host([data-pk-group-orientation='vertical'][data-pk-group-internal-trail][variant='outline']) ${t},
        :host([data-pk-group-orientation='vertical'][data-pk-group-internal-trail][variant='dashed']) ${t} {
            border-bottom-width: 0;
        }
    `}var N=[t,O,A(),k(`.button`),j(),M(`.button`),y`
        @layer pk-component {
            :host {
                font-family: var(--pk-font-family);
                cursor: pointer;
                --pk-btn-height: var(--pk-btn-height-default);
                --pk-btn-font: var(--pk-btn-font-default);
                --pk-btn-padding-inline: var(--pk-btn-padding-inline-default);
                --pk-btn-icon-size: var(--pk-btn-icon-size-default);
                --pk-btn-icon-gap: var(--pk-btn-icon-gap-default);
                --pk-btn-caret-size: var(--pk-btn-caret-size-default);
                --pk-btn-radius: var(--pk-btn-radius-default);
                /*
                 * Slotted labels inherit from the host — pin the size-token font
                 * (and button line-height) so Craft CP / Tailwind hosts match.
                 */
                font-size: var(--pk-btn-font);
                line-height: 1.2;
            }

            :host([disabled]) {
                cursor: not-allowed;
                pointer-events: none;
            }

            :host([loading]):not([disabled]) {
                pointer-events: none;
            }

            .button {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                gap: var(--pk-btn-icon-gap);
                box-sizing: border-box;
                width: auto;
                margin: 0;
                /* Every button carries a 1px border (transparent for fill/plain variants) so the box
                 * model is identical across variants and states. Prevents width shift when swapping a
                 * button between filled and outline/dashed, or toggling states. Matches Bootstrap
                 * (transparent baseline) and  (border always present, only color changes).
                 */
                border: 1px solid transparent;
                border-radius: var(--pk-btn-radius);
                font: inherit;
                font-size: var(--pk-btn-font);
                font-weight: 400;
                line-height: 1.2;
                text-decoration: none;
                white-space: nowrap;
                /* Inherit host cursor so className/style (e.g. cursor-move) pierce shadow. */
                cursor: inherit;
                user-select: none;
                vertical-align: middle;
                appearance: none;
                background: var(--pk-btn-fill, var(--pk-action-fill));
                color: var(--pk-btn-on, var(--pk-action-on));
                height: var(--pk-btn-height);
                min-height: var(--pk-btn-height);
                /* Block padding defaults to 0 (height tokens center content). Override for nav rows. */
                padding-block: var(--pk-btn-padding-block, 0);
                padding-inline: var(--pk-btn-padding-inline);
                transition: background-color 0.12s ease, box-shadow 0.12s ease, color 0.12s ease;
            }

            .button:disabled {
                opacity: 0.5;
            }

            .icon-slot {
                display: none;
                align-items: center;
                justify-content: center;
                flex-shrink: 0;
                line-height: 0;
            }

            .icon-slot--has-content {
                display: inline-flex;
            }

            /* Fixed token sizes for all icons (labeled or icon-only) — matches plugin-kit-react Button. */
            .icon-slot slot::slotted(svg),
            slot[name='start']::slotted(svg),
            slot[name='end']::slotted(svg) {
                display: block;
                width: var(--pk-btn-icon-size);
                height: var(--pk-btn-icon-size);
                flex-shrink: 0;
                pointer-events: none;
            }

            .icon-slot slot::slotted(img),
            slot[name='start']::slotted(img),
            slot[name='end']::slotted(img) {
                display: block;
                width: var(--pk-btn-icon-size);
                height: var(--pk-btn-icon-size);
                object-fit: contain;
                flex-shrink: 0;
                pointer-events: none;
            }

            /* pk-icon sizes itself from font-size (1em), so scale it to the
             * icon token. This keeps the idiomatic slotted pk-icon usage in
             * sync with raw slotted svg. Set width/height explicitly — %/size-full
             * collapses when the icon-slot has no definite box.
             */
            .icon-slot slot::slotted(pk-icon),
            slot[name='start']::slotted(pk-icon),
            slot[name='end']::slotted(pk-icon) {
                font-size: var(--pk-btn-icon-size);
                width: var(--pk-btn-icon-size);
                height: var(--pk-btn-icon-size);
                /* Kill pk-icon's text-baseline nudge (-0.125em) — flex slots center optically. */
                vertical-align: 0;
                flex-shrink: 0;
                pointer-events: none;
            }

            .label {
                display: inline-flex;
                align-items: center;
                min-width: 0;
                line-height: 1.2;
            }

            /* Trailing slot (status): grow + clip the label so end sits at the far edge
             * and long titles truncate instead of colliding with the indicator.
             */
            .button:has(.icon-slot--end.icon-slot--has-content) .label:not(.is-empty) {
                flex: 1 1 auto;
                overflow: hidden;
            }

            .label.is-empty {
                display: none;
            }

            /* Icon-only (no label): square hit box = size height. Button owns the target;
             * glyph size comes from --pk-btn-icon-size. Do not Tailwind-size the Icon.
             * Opt out with icon (compact), size=none, or group-trigger (narrow disclosure cap).
             */
            :host(:not([icon]):not([size='none']):not([group-trigger])) .button:not(.has-label) {
                width: var(--pk-btn-height);
                min-width: var(--pk-btn-height);
                padding-inline: 0;
            }

            /* Compact density (icon attr): padless box that hugs the glyph.
             * size still drives --pk-btn-icon-size; height/width tiers do not apply.
             * Use for dense x / ellipsis in cells — not for table action rows (prefer square above).
             * line-height: 0 collapses whitespace flex-struts so the glyph sits dead-center.
             */
            :host([icon]) {
                display: inline-flex;
                line-height: 0;
                vertical-align: middle;
            }

            :host([icon]) .button {
                display: flex;
                width: auto;
                min-width: 0;
                height: auto;
                min-height: 0;
                padding-inline: 0.25rem;
                padding-block: 0;
                line-height: 0;
                align-items: center;
                justify-content: center;
            }

            /* Keep label space while loading even before slotchange runs. */
            .button.loading .label.is-empty {
                display: inline-flex;
                visibility: hidden;
            }

            /* Sizes — token-driven scale (see tokens.css) */
            :host([size='xxs']) {
                --pk-btn-height: var(--pk-btn-height-xxs);
                --pk-btn-font: var(--pk-btn-font-xxs);
                --pk-btn-padding-inline: var(--pk-btn-padding-inline-xxs);
                --pk-btn-icon-size: var(--pk-btn-icon-size-xxs);
                --pk-btn-icon-gap: var(--pk-btn-icon-gap-xxs);
                --pk-btn-caret-size: var(--pk-btn-caret-size-xxs);
                --pk-btn-radius: var(--pk-btn-radius-xxs);
            }

            :host([size='xs']) {
                --pk-btn-height: var(--pk-btn-height-xs);
                --pk-btn-font: var(--pk-btn-font-xs);
                --pk-btn-padding-inline: var(--pk-btn-padding-inline-xs);
                --pk-btn-icon-size: var(--pk-btn-icon-size-xs);
                --pk-btn-icon-gap: var(--pk-btn-icon-gap-xs);
                --pk-btn-caret-size: var(--pk-btn-caret-size-xs);
                --pk-btn-radius: var(--pk-btn-radius-xs);
            }

            :host([size='sm']) {
                --pk-btn-height: var(--pk-btn-height-sm);
                --pk-btn-font: var(--pk-btn-font-sm);
                --pk-btn-padding-inline: var(--pk-btn-padding-inline-sm);
                --pk-btn-icon-size: var(--pk-btn-icon-size-sm);
                --pk-btn-icon-gap: var(--pk-btn-icon-gap-sm);
                --pk-btn-caret-size: var(--pk-btn-caret-size-sm);
                --pk-btn-radius: var(--pk-btn-radius-sm);
            }

            :host([size='default']) {
                --pk-btn-height: var(--pk-btn-height-default);
                --pk-btn-font: var(--pk-btn-font-default);
                --pk-btn-padding-inline: var(--pk-btn-padding-inline-default);
                --pk-btn-icon-size: var(--pk-btn-icon-size-default);
                --pk-btn-icon-gap: var(--pk-btn-icon-gap-default);
                --pk-btn-caret-size: var(--pk-btn-caret-size-default);
                --pk-btn-radius: var(--pk-btn-radius-default);
            }

            :host([size='lg']) {
                --pk-btn-height: var(--pk-btn-height-lg);
                --pk-btn-font: var(--pk-btn-font-lg);
                --pk-btn-padding-inline: var(--pk-btn-padding-inline-lg);
                --pk-btn-icon-size: var(--pk-btn-icon-size-lg);
                --pk-btn-icon-gap: var(--pk-btn-icon-gap-lg);
                --pk-btn-caret-size: var(--pk-btn-caret-size-lg);
                --pk-btn-radius: var(--pk-btn-radius-lg);
            }

            :host([size='xl']) {
                --pk-btn-height: var(--pk-btn-height-xl);
                --pk-btn-font: var(--pk-btn-font-xl);
                --pk-btn-padding-inline: var(--pk-btn-padding-inline-xl);
                --pk-btn-icon-size: var(--pk-btn-icon-size-xl);
                --pk-btn-icon-gap: var(--pk-btn-icon-gap-xl);
                --pk-btn-caret-size: var(--pk-btn-caret-size-xl);
                --pk-btn-radius: var(--pk-btn-radius-xl);
            }

            /* No preset scale — size to content or set --pk-btn-* on the host for one-off dimensions
             * (height, padding, font, icon, radius) without fighting a named size tier.
             * Pair with icon for a padless glyph host, or set --pk-btn-padding-inline / --pk-btn-height yourself.
             */
            :host([size='none']) {
                --pk-btn-height: auto;
                --pk-btn-font: inherit;
                --pk-btn-padding-inline: 0px;
                --pk-btn-padding-block: 0px;
                --pk-btn-icon-size: 1em;
                --pk-btn-icon-gap: 0px;
                --pk-btn-caret-size: 1em;
                --pk-btn-radius: 0px;
            }

            :host([size='none']) .button {
                height: auto;
                min-height: auto;
                width: 100%;
            }

            /* Variants */
            :host([variant='default']) {
                --pk-btn-fill: var(--pk-action-fill);
                --pk-btn-fill-hover: var(--pk-action-fill-hover);
                --pk-btn-fill-active: var(--pk-action-fill-active);
                --pk-btn-on: var(--pk-action-on);
            }

            :host([variant='primary']) {
                --pk-btn-fill: var(--pk-action-primary-fill);
                --pk-btn-fill-hover: var(--pk-action-primary-fill-hover);
                --pk-btn-fill-active: var(--pk-action-primary-fill-active);
                --pk-btn-on: var(--pk-action-primary-on);
            }

            :host([variant='primary']) .button,
            :host([variant='secondary']) .button {
                -moz-osx-font-smoothing: grayscale;
                -webkit-font-smoothing: antialiased;
            }

            :host([variant='secondary']) {
                --pk-btn-fill: var(--pk-color-gray-500);
                --pk-btn-fill-hover: var(--pk-color-gray-550);
                --pk-btn-fill-active: var(--pk-color-gray-600);
                --pk-btn-on: var(--pk-color-white);
            }

            :host([variant='outline']) .button {
                background: transparent;
                border-color: var(--pk-color-slate-400);
                color: var(--pk-color-gray-700);
            }

            :host([variant='transparent']) .button {
                background: transparent;
                color: var(--pk-color-gray-700);
            }

            /* link/none opt out of the shared transparent 1px border: they never render a border, so
             * carrying one only pads the box by 2px inline (and 2px block at size='none', where height
             * is auto). These are the "inline text" / "no chrome" variants — content-sized is the point,
             * and neither participates in button-group border joins. Other variants keep the stable box.
             */
            /*
             * Craft CP sets --link-color on :root (inherits into shadow). Prefer that,
             * then kit --pk-color-link — not sky-700 (reads as a different “CP blue”).
             * Color on :host so consumer utilities (e.g. text-[var(--link-color)]) can override.
             * Height must be content-sized — default --pk-btn-height (34px) bloated table rows.
             */
            :host([variant='link']) {
                color: var(--link-color, var(--pk-color-link));
                --pk-btn-height: auto;
                --pk-btn-padding-inline: 0;
                --pk-btn-padding-block: 0;
            }

            :host([variant='link']) .button {
                background: transparent;
                border-width: 0;
                border-radius: 0;
                color: inherit;
                width: auto;
                height: auto;
                min-height: 0;
                padding: 0;
                text-underline-offset: 2px;
            }

            :host([variant='dashed']) .button {
                background: transparent;
                border-style: dashed;
                border-color: var(--pk-color-slate-500);
                color: var(--pk-color-gray-700);
            }

            :host([variant='none']) .button {
                border-width: 0;
                border-radius: 0;
                background: transparent;
                color: inherit;
            }

            /* Interaction — pseudo-classes only; playground matrices use dev/pk-button-demo-states.css */
            .button:hover:not(:disabled) {
                background: var(--pk-btn-fill-hover, var(--pk-btn-fill));
            }

            :host([variant='outline']) .button:hover:not(:disabled),
            :host([variant='transparent']) .button:hover:not(:disabled),
            :host([variant='dashed']) .button:hover:not(:disabled) {
                background: var(--pk-color-slate-150);
            }

            :host([variant='link']) .button:hover:not(:disabled) {
                background: transparent;
                text-decoration: underline;
            }

            :host([variant='none']) .button:hover:not(:disabled) {
                background: transparent;
            }

            .button:active:not(:disabled) {
                background: var(--pk-btn-fill-active, var(--pk-btn-fill-hover, var(--pk-btn-fill)));
            }

            :host([variant='outline']) .button:active:not(:disabled),
            :host([variant='transparent']) .button:active:not(:disabled),
            :host([variant='dashed']) .button:active:not(:disabled) {
                background: var(--pk-color-slate-200);
            }

            :host([variant='link']) .button:active:not(:disabled),
            :host([variant='none']) .button:active:not(:disabled) {
                background: transparent;
            }

            .button:focus {
                outline: none;
            }

            .button:focus-visible {
                box-shadow: var(--pk-shadow-focus);
            }

            /* Bordered variants: fold the button's own border into the focus ring by recoloring it to
             * the accent (and solidifying dashed) so focus reads as one cohesive ring instead of a
             * doubled border. The ring is thinned to 1px here because the recolored 1px border already
             * supplies the other half — total 2px, matching the filled variants' ring weight.
             */
            :host([variant='outline']) .button:focus-visible,
            :host([variant='dashed']) .button:focus-visible {
                border-color: var(--pk-color-sky-600);
                box-shadow: 0 0 0 1px var(--pk-color-sky-600), 0 0 5px 1px hsl(from var(--pk-color-sky-600) h s l / 0.7);
            }

            :host([variant='dashed']) .button:focus-visible {
                border-style: solid;
            }

            :host(.pk-dialog__close) .button:focus-visible {
                box-shadow: 0 0 0 2px var(--pk-color-gray-600);
            }

            :host-context(pk-button-group) {
                position: relative;
            }

            :host-context(pk-button-group[orientation='vertical']) {
                display: block;
                width: 100%;
                max-width: 100%;
                box-sizing: border-box;
            }

            :host-context(pk-button-group[orientation='vertical']) .button {
                width: 100%;
                box-sizing: border-box;
            }

            :host-context(pk-button-group:focus-visible) {
                z-index: 2;
            }

            /* Bordered variants — matching border divider (filled uses margin gap via buttonGroupIndentStyles) */

            :host([variant='primary']) .button:focus-visible,
            :host([variant='secondary']) .button:focus-visible {
                box-shadow: var(--pk-shadow-focus-inset);
            }

            :host-context(pk-button-group[exclusive]):host([aria-pressed='true']) .button {
                background: var(--pk-color-gray-500);
                color: var(--pk-color-white);
            }

            :host-context(pk-button-group[exclusive]):host([aria-pressed='true']) .button:hover:not(:disabled) {
                background: var(--pk-color-gray-550);
            }

            :host-context(pk-button-group[exclusive]):host([aria-pressed='true']) .button:active:not(:disabled) {
                background: var(--pk-color-gray-600);
            }

            :host-context(pk-button-group[exclusive]):host([aria-pressed='true']) .button:focus-visible {
                box-shadow: var(--pk-shadow-focus);
            }

            :host([variant='link']) .button:focus-visible {
                box-shadow: none;
                text-decoration: underline;
            }

            .button.loading {
                position: relative;
                cursor: default;
                pointer-events: none;
            }

            .label.loading {
                visibility: hidden;
            }

            .button.loading .icon-slot,
            .button.loading slot[name='start']::slotted(*),
            .button.loading slot[name='end']::slotted(*) {
                visibility: hidden;
            }

            .button.caret .icon-slot--end.icon-slot--has-content {
                display: none;
            }

            /* Scope to the caret span — the button host also gets class caret when
               with-caret is set; an unscoped .caret rule was adding 2px margin
               to the whole button and shifting dropdown anchors left. */
            .button > .caret {
                display: inline-flex;
                align-self: center;
                align-items: center;
                justify-content: center;
                flex-shrink: 0;
                line-height: 0;
                /* Sits slightly further from the label than the flex gap alone. */
                margin-inline-start: 2px;
            }

            /* Caret has its own per-size token (--pk-btn-caret-size), kept deliberately smaller than
             * --pk-btn-icon-size so it reads as a subordinate dropdown affordance next to real icons.
             */
            .button > .caret svg {
                display: block;
                width: var(--pk-btn-caret-size);
                height: var(--pk-btn-caret-size);
            }

            :host([group-trigger]) .button {
                padding-inline: 6px;
            }

            /* Compact disclosure cap — hide content, keep only the shared SVG caret (centered). */
            :host([group-trigger]) .label,
            :host([group-trigger]) .icon-slot {
                display: none;
            }

            :host([group-trigger]) .button > .caret {
                margin-inline-start: 0;
            }

            :host([size='sm'][group-trigger]) .button,
            :host([size='xs'][group-trigger]) .button,
            :host([size='xxs'][group-trigger]) .button {
                padding-inline: 6px;
            }

            :host([size='lg'][group-trigger]) .button {
                padding-inline: 10px;
            }

            :host([size='xl'][group-trigger]) .button {
                padding-inline: 12px;
            }

            :host-context(pk-button-group[orientation='horizontal']):host([data-pk-group-join]:not([data-pk-group-divider])[variant='outline']) .button,
            :host-context(pk-button-group[orientation='horizontal']):host([data-pk-group-join]:not([data-pk-group-divider])[variant='dashed']) .button,
            :host-context(pk-button-group[orientation='horizontal']):host([data-pk-group-join]:not([data-pk-group-divider])[variant='transparent']) .button {
                border-left-width: 0;
            }

            :host-context(pk-button-group[orientation='vertical']):host([data-pk-group-join]:not([data-pk-group-divider])[variant='outline']) .button,
            :host-context(pk-button-group[orientation='vertical']):host([data-pk-group-join]:not([data-pk-group-divider])[variant='dashed']) .button,
            :host-context(pk-button-group[orientation='vertical']):host([data-pk-group-join]:not([data-pk-group-divider])[variant='transparent']) .button {
                border-top-width: 0;
            }

            :host([data-pk-group-orientation='horizontal'][data-pk-group-join]:not([data-pk-group-divider])[variant='outline']) .button,
            :host([data-pk-group-orientation='horizontal'][data-pk-group-join]:not([data-pk-group-divider])[variant='dashed']) .button,
            :host([data-pk-group-orientation='horizontal'][data-pk-group-join]:not([data-pk-group-divider])[variant='transparent']) .button {
                border-left-width: 0;
            }

            :host([data-pk-group-orientation='vertical'][data-pk-group-join]:not([data-pk-group-divider])[variant='outline']) .button,
            :host([data-pk-group-orientation='vertical'][data-pk-group-join]:not([data-pk-group-divider])[variant='dashed']) .button,
            :host([data-pk-group-orientation='vertical'][data-pk-group-join]:not([data-pk-group-divider])[variant='transparent']) .button {
                border-top-width: 0;
            }

            :host([data-pk-group-orientation='horizontal'][data-pk-group-divider][variant='outline']) .button {
                box-shadow: none;
                border-left-width: 1px;
                border-left-style: solid;
                border-left-color: var(--pk-btn-group-divider-color-outline, var(--pk-color-slate-400));
            }

            :host([data-pk-group-orientation='vertical'][data-pk-group-divider][variant='outline']) .button {
                box-shadow: none;
                border-top-width: 1px;
                border-top-style: solid;
                border-top-color: var(--pk-btn-group-divider-color-outline, var(--pk-color-slate-400));
            }

            :host([data-pk-group-orientation='horizontal'][data-pk-group-divider][variant='dashed']) .button {
                box-shadow: none;
                border-left-width: 1px;
                border-left-style: dashed;
                border-left-color: var(--pk-btn-group-divider-color-dashed, var(--pk-color-slate-500));
            }

            :host([data-pk-group-orientation='vertical'][data-pk-group-divider][variant='dashed']) .button {
                box-shadow: none;
                border-top-width: 1px;
                border-top-style: dashed;
                border-top-color: var(--pk-btn-group-divider-color-dashed, var(--pk-color-slate-500));
            }

            :host([data-pk-group-orientation='horizontal'][data-pk-group-divider][variant='outline']) .button:focus-visible,
            :host([data-pk-group-orientation='horizontal'][data-pk-group-divider][variant='dashed']) .button:focus-visible {
                box-shadow: var(--pk-shadow-focus);
            }

            :host([data-pk-group-orientation='vertical'][data-pk-group-divider][variant='outline']) .button:focus-visible,
            :host([data-pk-group-orientation='vertical'][data-pk-group-divider][variant='dashed']) .button:focus-visible {
                box-shadow: var(--pk-shadow-focus);
            }

            :host([data-pk-group-orientation='horizontal'][data-pk-group-internal-trail][variant='outline']) .button,
            :host([data-pk-group-orientation='horizontal'][data-pk-group-internal-trail][variant='dashed']) .button {
                border-right-width: 0;
            }

            :host([data-pk-group-orientation='vertical'][data-pk-group-internal-trail][variant='outline']) .button,
            :host([data-pk-group-orientation='vertical'][data-pk-group-internal-trail][variant='dashed']) .button {
                border-bottom-width: 0;
            }
        }
    `],ne=u(e),P=class extends a{constructor(...e){super(...e),this.variant=`default`,this.size=`default`,this.disabled=!1,this.loading=!1,this.withCaret=!1,this.groupTrigger=!1,this.icon=!1,this.title=``,this.ariaLabel=null,this.type=`button`,this.hasDefaultSlotContent=!1,this.hasStartSlotContent=!1,this.hasEndSlotContent=!1,this.startSlotChanged=e=>{this.iconSlotChanged(e,`start`)},this.endSlotChanged=e=>{this.iconSlotChanged(e,`end`)},this.handleHostClick=e=>{if(this.disabled||this.loading||this.href||this.type!==`submit`&&this.type!==`reset`)return;let t=this.resolveAssociatedForm();if(t){if(e.preventDefault(),e.stopPropagation(),this.type===`reset`){t.reset();return}if(typeof t.requestSubmit==`function`){t.requestSubmit();return}t.dispatchEvent(new Event(`submit`,{bubbles:!0,cancelable:!0}))}}}static{this.shadowRootOptions={mode:`open`,delegatesFocus:!0}}static{this.styles=N}defaultSlotChanged(e){let t=e.target;this.hasDefaultSlotContent=t.assignedNodes({flatten:!0}).some(e=>e.nodeType===Node.TEXT_NODE?e.textContent?.trim():e.nodeType===Node.ELEMENT_NODE)}iconSlotChanged(e,t){let n=e.target.assignedNodes({flatten:!0}).some(e=>e.nodeType===Node.TEXT_NODE?e.textContent?.trim():e.nodeType===Node.ELEMENT_NODE);t===`start`?this.hasStartSlotContent=n:this.hasEndSlotContent=n}buttonClasses(){return h({button:!0,"has-label":this.hasDefaultSlotContent,loading:this.loading,caret:this.withCaret,"group-trigger":this.groupTrigger})}connectedCallback(){super.connectedCallback(),this.setAttribute(`data-slot`,`button`),this.addEventListener(`click`,this.handleHostClick)}disconnectedCallback(){this.removeEventListener(`click`,this.handleHostClick),super.disconnectedCallback()}resolveAssociatedForm(){let e=(this.form||this.getAttribute(`form`)||``).trim();if(e){let t=this.ownerDocument?.getElementById(e);if(t instanceof HTMLFormElement&&t.id!==`main`)return t}let t=this.closest(`form`);return t&&t.id!==`main`?t:null}render(){let e=this.spinnerSize||w(this.size),t=T(this.variant,this.spinnerVariant),n=!!this.href,r=this.ariaLabel||this.title||``;return m`
            ${n?m`
                    <a
                        part="base"
                        class=${this.buttonClasses()}
                        href=${this.href}
                        target=${this.target??p}
                        rel=${this.rel??p}
                        aria-label=${r||p}
                    >
                        ${this.renderInner(e,t)}
                    </a>
                `:m`
                    <button
                        part="base"
                        class=${this.buttonClasses()}
                        type=${this.type}
                        ?disabled=${this.disabled}
                        aria-disabled=${this.disabled?`true`:p}
                        aria-busy=${this.loading?`true`:p}
                        aria-label=${r||p}
                        name=${this.name??p}
                        value=${this.value??p}
                    >
                        ${this.renderInner(e,t)}
                    </button>
                `}
        `}renderInner(e,t){return m`
            <span
                class=${h({"icon-slot":!0,"icon-slot--start":!0,"icon-slot--has-content":this.hasStartSlotContent})}
            >
                <slot name="start" @slotchange=${this.startSlotChanged}></slot>
            </span>
            ${this.loading?m`
                    <pk-spinner
                        variant=${t}
                        size=${e}
                        tone=${this.spinnerTone??p}
                        centered
                    ></pk-spinner>
                `:p}
            <span
                class=${h({label:!0,"is-empty":!this.hasDefaultSlotContent,loading:this.loading})}
            >
                <slot @slotchange=${this.defaultSlotChanged}></slot>
            </span>
            <span
                class=${h({"icon-slot":!0,"icon-slot--end":!0,"icon-slot--has-content":this.hasEndSlotContent})}
            >
                <slot name="end" @slotchange=${this.endSlotChanged}></slot>
            </span>
            ${this.withCaret||this.groupTrigger?m`<span part="caret" class="caret">${d(ne)}</span>`:p}
        `}};s([g({reflect:!0})],P.prototype,`variant`,void 0),s([g({reflect:!0})],P.prototype,`size`,void 0),s([g({type:Boolean,reflect:!0})],P.prototype,`disabled`,void 0),s([g({type:Boolean,reflect:!0})],P.prototype,`loading`,void 0),s([g({reflect:!0,attribute:`spinner-size`})],P.prototype,`spinnerSize`,void 0),s([g({reflect:!0,attribute:`spinner-variant`})],P.prototype,`spinnerVariant`,void 0),s([g({reflect:!0,attribute:`spinner-tone`})],P.prototype,`spinnerTone`,void 0),s([g({type:Boolean,reflect:!0,attribute:`with-caret`})],P.prototype,`withCaret`,void 0),s([g({type:Boolean,reflect:!0,attribute:`group-trigger`})],P.prototype,`groupTrigger`,void 0),s([g({type:Boolean,reflect:!0})],P.prototype,`icon`,void 0),s([g()],P.prototype,`href`,void 0),s([g()],P.prototype,`target`,void 0),s([g()],P.prototype,`rel`,void 0),s([g()],P.prototype,`name`,void 0),s([g()],P.prototype,`value`,void 0),s([g()],P.prototype,`title`,void 0),s([g({attribute:`aria-label`})],P.prototype,`ariaLabel`,void 0),s([g()],P.prototype,`type`,void 0),s([g({reflect:!0})],P.prototype,`form`,void 0),s([f()],P.prototype,`hasDefaultSlotContent`,void 0),s([f()],P.prototype,`hasStartSlotContent`,void 0),s([f()],P.prototype,`hasEndSlotContent`,void 0),P=s([r(`pk-button`)],P);var F=class extends Event{constructor(e){super(`pk-copy`,{bubbles:!0,cancelable:!1,composed:!0}),this.detail={value:e}}},I=class extends Event{constructor(){super(`pk-copy-error`,{bubbles:!0,cancelable:!1,composed:!0})}};async function L(e){await navigator.clipboard.writeText(e)}function R(e,t,n){if(!t)return n||null;let r=t.includes(`.`),i=t.includes(`[`)&&t.includes(`]`),a=t,o=``;r?[a,o]=t.trim().split(`.`):i&&([a,o]=t.trim().replace(/\]$/,``).split(`[`));let s=`getElementById`in e?e.getElementById(a):null;if(!s)return null;if(i)return s.getAttribute(o)??``;if(r){let e=s[o];return e==null?``:String(e)}return s.textContent??``}var z=y`
    @layer pk-component {
        :host {
            display: inline-block;
        }

        /* Match React CopyButton size="icon" — square, no horizontal padding. */
        pk-button::part(base) {
            padding-inline: 0;
            width: var(--pk-btn-height-default);
            min-width: var(--pk-btn-height-default);
            border-color: var(--pk-copy-button-border-color);
            border-radius: var(--pk-copy-button-radius);
            background: var(--pk-copy-button-background);
            color: var(--pk-copy-button-color);
        }

        pk-button::part(base):hover:not(:disabled) {
            border-color: var(
                --pk-copy-button-hover-border-color,
                var(--pk-copy-button-border-color)
            );
            background: var(--pk-copy-button-hover-background);
            color: var(--pk-copy-button-hover-color, var(--pk-copy-button-color));
        }

        /*
         * In-control trailing action (slot=end on pk-input, etc.): same family as
         * combobox expand/clear and image-browser clear — flex-reserved hit box
         * flush to the field edge, glyph sized via --pk-input-decoration-size.
         */
        :host([slot='end']) {
            display: inline-flex;
            align-self: stretch;
            height: auto;
            /* size=none buttons resolve --pk-btn-icon-size: 1em against this. */
            font-size: var(--pk-input-decoration-size, 0.75rem);
        }

        :host([slot='end']) pk-button {
            display: flex;
            height: 100%;
        }

        :host([slot='end']) pk-button::part(base) {
            box-sizing: border-box;
            width: calc(
                var(--pk-input-decoration-size, 0.75rem) + var(--pk-input-padding-inline, 8px)
            );
            min-width: calc(
                var(--pk-input-decoration-size, 0.75rem) + var(--pk-input-padding-inline, 8px)
            );
            height: 100%;
            min-height: 100%;
            padding: 0;
            border-width: 0;
            border-radius: 0;
            background: transparent;
            color: var(--pk-color-gray-600);
        }

        :host([slot='end']) pk-button::part(base):hover:not(:disabled) {
            color: var(--pk-color-gray-800);
        }
    }
`,B=i(c.check).replace(`<svg`,`<svg slot="start" part="success-icon"`),V=i(c.copy).replace(`<svg`,`<svg slot="start" part="copy-icon"`),H=2e3,U=class extends a{constructor(...e){super(...e),this.hasSlotController=new l(this,`icon`),this.value=``,this.from=``,this.disabled=!1,this.variant=`transparent`,this.ariaLabel=`Copy`,this.copiedLabel=`Copied`,this.copied=!1,this.resetCopied=()=>{this.copied=!1}}static{this.styles=z}disconnectedCallback(){window.clearTimeout(this.resetTimer),super.disconnectedCallback()}scheduleReset(){window.clearTimeout(this.resetTimer),this.resetTimer=window.setTimeout(this.resetCopied,H)}async copy(){if(this.disabled)return;let e=R(this.getRootNode(),this.from,this.value);if(e==null||e===``){this.dispatchEvent(new I);return}try{await L(e),this.copied=!0,this.scheduleReset(),this.dispatchEvent(new F(e))}catch{this.dispatchEvent(new I)}}render(){let e=this.getAttribute(`slot`)===`end`;return m`
            <pk-button
                part="button"
                variant=${e?`none`:this.variant}
                size=${e?`none`:`default`}
                ?icon=${e}
                aria-label=${this.copied?this.copiedLabel:this.ariaLabel}
                ?disabled=${this.disabled}
                @click=${this.copy}
            >
                ${this.copied?v(B):this.hasSlotController.test(`icon`)?m`<slot name="icon" slot="start"></slot>`:v(V)}
            </pk-button>
        `}};s([g()],U.prototype,`value`,void 0),s([g()],U.prototype,`from`,void 0),s([g({type:Boolean,reflect:!0})],U.prototype,`disabled`,void 0),s([g({reflect:!0})],U.prototype,`variant`,void 0),s([g({attribute:`aria-label`})],U.prototype,`ariaLabel`,void 0),s([g({attribute:`copied-label`})],U.prototype,`copiedLabel`,void 0),s([f()],U.prototype,`copied`,void 0),U=s([r(`pk-copy-button`)],U);var W=class extends Event{constructor(){super(`pk-dismiss`,{bubbles:!0,cancelable:!0,composed:!0})}},G=y`
    @layer pk-component {
        :host {
            display: block;
            width: 100%;
            color: var(--pk-alert-color);
            font-family: var(--pk-font-family);
            font-size: var(--_pk-alert-font-size);
            line-height: var(--pk-line-height);
            --pk-alert-accent: var(--pk-color-sky-600);
            --pk-alert-background: var(--pk-color-sky-50);
            --pk-alert-border: var(--pk-color-sky-300);
            --pk-alert-color: var(--pk-color-sky-800);
            --_pk-alert-font-size: var(--pk-font-size-sm);
            --_pk-alert-padding: 0.625rem 0.75rem;
            --_pk-alert-radius: var(--pk-radius-md);
            --_pk-alert-gap: 0.5rem;
            --_pk-alert-icon-size: 1.125rem;
            --_pk-alert-title-size: var(--pk-font-size-base);
            --_pk-alert-dismiss-size: 1.5rem;
            --_pk-alert-dismiss-padding: 0.375rem;
            --_pk-alert-dismiss-margin: -0.125rem -0.1875rem -0.125rem 0;
            --_pk-alert-section-margin-top: 0.625rem;
            --_pk-alert-details-padding-top: 0.5rem;
            --_pk-alert-details-content-margin-top: 0.625rem;
            --_pk-alert-details-pre-padding: 0.625rem;
            --_pk-alert-details-pre-radius: var(--pk-radius-md);
            --_pk-alert-details-pre-font-size: 0.6875rem;
            --_pk-alert-copy-button-size: var(--pk-btn-height-xs);
            --_pk-alert-copy-icon-size: var(--pk-btn-icon-size-xs);
            --_pk-alert-copy-inset: 0.375rem;
            --_pk-alert-copy-radius: var(--pk-radius-md);
            --_pk-alert-copy-status-size: 0.6875rem;
            --_pk-alert-action-height: var(--pk-btn-height-sm);
            --_pk-alert-action-font: var(--pk-btn-font-sm);
            --_pk-alert-action-padding-inline: var(--pk-btn-padding-inline-sm);
            --_pk-alert-action-icon-size: var(--pk-btn-icon-size-sm);
            --_pk-alert-action-icon-gap: var(--pk-btn-icon-gap-sm);
            --_pk-alert-action-caret-size: var(--pk-btn-caret-size-sm);
            --_pk-alert-action-radius: var(--pk-btn-radius-sm);
        }

        :host([hidden]) {
            display: none;
        }

        :host([size='sm']) {
            --_pk-alert-font-size: var(--pk-btn-font-xs);
            --_pk-alert-padding: 0.5rem 0.625rem;
            --_pk-alert-radius: var(--pk-radius-sm);
            --_pk-alert-gap: 0.4375rem;
            --_pk-alert-icon-size: 1rem;
            --_pk-alert-title-size: var(--pk-font-size-sm);
            --_pk-alert-dismiss-size: 1.375rem;
            --_pk-alert-dismiss-padding: 0.3125rem;
            --_pk-alert-dismiss-margin: -0.125rem -0.125rem -0.125rem 0;
            --_pk-alert-section-margin-top: 0.5rem;
            --_pk-alert-details-padding-top: 0.375rem;
            --_pk-alert-details-content-margin-top: 0.5rem;
            --_pk-alert-details-pre-padding: 0.5rem;
            --_pk-alert-details-pre-radius: var(--pk-radius-sm);
            --_pk-alert-details-pre-font-size: 0.6875rem;
            --_pk-alert-copy-button-size: var(--pk-btn-height-xxs);
            --_pk-alert-copy-icon-size: var(--pk-btn-icon-size-xxs);
            --_pk-alert-copy-inset: 0.3125rem;
            --_pk-alert-copy-radius: var(--pk-radius-sm);
            --_pk-alert-copy-status-size: 0.6875rem;
            --_pk-alert-action-height: var(--pk-btn-height-xs);
            --_pk-alert-action-font: var(--pk-btn-font-xs);
            --_pk-alert-action-padding-inline: var(--pk-btn-padding-inline-xs);
            --_pk-alert-action-icon-size: var(--pk-btn-icon-size-xs);
            --_pk-alert-action-icon-gap: var(--pk-btn-icon-gap-xs);
            --_pk-alert-action-caret-size: var(--pk-btn-caret-size-xs);
            --_pk-alert-action-radius: var(--pk-btn-radius-xs);
        }

        :host([size='lg']) {
            --_pk-alert-font-size: var(--pk-font-size-base);
            --_pk-alert-padding: 0.875rem 1rem;
            --_pk-alert-radius: var(--pk-radius-lg);
            --_pk-alert-gap: 0.75rem;
            --_pk-alert-icon-size: 1.375rem;
            --_pk-alert-title-size: 0.9375rem;
            --_pk-alert-dismiss-size: 1.75rem;
            --_pk-alert-dismiss-padding: 0.4375rem;
            --_pk-alert-dismiss-margin: -0.1875rem -0.25rem -0.1875rem 0;
            --_pk-alert-section-margin-top: 0.75rem;
            --_pk-alert-details-padding-top: 0.625rem;
            --_pk-alert-details-content-margin-top: 0.75rem;
            --_pk-alert-details-pre-padding: 0.75rem;
            --_pk-alert-details-pre-radius: var(--pk-radius-lg);
            --_pk-alert-details-pre-font-size: 0.75rem;
            --_pk-alert-copy-button-size: var(--pk-btn-height-sm);
            --_pk-alert-copy-icon-size: var(--pk-btn-icon-size-sm);
            --_pk-alert-copy-inset: 0.5rem;
            --_pk-alert-copy-radius: var(--pk-radius-lg);
            --_pk-alert-copy-status-size: 0.75rem;
            --_pk-alert-action-height: 2.125rem;
            --_pk-alert-action-font: var(--pk-font-size-base);
            --_pk-alert-action-padding-inline: 9px;
            --_pk-alert-action-icon-size: 14px;
            --_pk-alert-action-icon-gap: 6px;
            --_pk-alert-action-caret-size: 12px;
            --_pk-alert-action-radius: var(--pk-radius-lg);
        }

        .alert {
            box-sizing: border-box;
            width: 100%;
            padding: var(--pk-alert-padding, var(--_pk-alert-padding));
            border: 1px solid var(--pk-alert-border);
            border-inline-start: 4px solid var(--pk-alert-accent);
            border-radius: var(--pk-alert-radius, var(--_pk-alert-radius));
            background: var(--pk-alert-background);
        }

        :host([variant='success']) {
            --pk-alert-accent: var(--pk-color-teal-600);
            --pk-alert-background: var(--pk-color-teal-50);
            --pk-alert-border: var(--pk-color-teal-400);
            --pk-alert-color: var(--pk-color-teal-800);
        }

        :host([variant='neutral']) {
            --pk-alert-accent: var(--pk-color-gray-600);
            --pk-alert-background: var(--pk-color-gray-50);
            --pk-alert-border: var(--pk-color-gray-300);
            --pk-alert-color: var(--pk-color-gray-800);
        }

        :host([variant='warning']) {
            --pk-alert-accent: var(--pk-color-amber-600);
            --pk-alert-background: var(--pk-color-amber-50);
            --pk-alert-border: var(--pk-color-amber-300);
            --pk-alert-color: var(--pk-color-amber-800);
        }

        :host([variant='error']) {
            --pk-alert-accent: var(--pk-color-red-600);
            --pk-alert-background: var(--pk-color-red-50);
            --pk-alert-border: var(--pk-color-red-300);
            --pk-alert-color: var(--pk-color-red-800);
        }

        :host([appearance='filled-outlined']) .alert {
            border-inline-start-width: 1px;
            border-inline-start-color: var(--pk-alert-border);
        }

        :host([appearance='filled']) .alert {
            border-color: transparent;
            border-inline-start-width: 1px;
        }

        :host([appearance='outlined']) .alert {
            border-color: var(--pk-alert-accent);
            border-inline-start-width: 1px;
            background: transparent;
        }

        :host([appearance='plain']) .alert {
            border-color: transparent;
            border-inline-start-width: 1px;
            background: transparent;
        }

        .notice {
            display: flex;
            align-items: flex-start;
            gap: var(--_pk-alert-gap);
            min-width: 0;
        }

        .icon {
            display: inline-flex;
            flex: 0 0 auto;
            align-items: center;
            justify-content: center;
            width: var(--_pk-alert-icon-size);
            height: var(--_pk-alert-icon-size);
            margin-top: 0.0625rem;
            color: var(--pk-alert-accent);
        }

        .icon svg,
        .dismiss svg {
            display: block;
            width: 100%;
            height: 100%;
            fill: currentColor;
        }

        .content {
            min-width: 0;
            flex: 1 1 auto;
        }

        .title {
            display: block;
            margin: 0 0 0.125rem;
            color: var(--pk-alert-title-color, var(--pk-alert-accent));
            font-size: var(--pk-alert-title-size, var(--_pk-alert-title-size));
            font-weight: 700;
            line-height: 1.4;
        }

        .body {
            color: var(
                --pk-alert-body-color,
                var(--pk-alert-title-color, var(--pk-alert-accent))
            );
        }

        .body ::slotted(*) {
            /* Host document styles outrank normal shadow rules for slotted elements. */
            margin-block: 0 !important;
        }

        .dismiss {
            display: inline-flex;
            flex: 0 0 auto;
            align-items: center;
            justify-content: center;
            width: var(--_pk-alert-dismiss-size);
            height: var(--_pk-alert-dismiss-size);
            margin: var(--_pk-alert-dismiss-margin);
            padding: var(--_pk-alert-dismiss-padding);
            border: 0;
            border-radius: var(--pk-radius-md);
            background: transparent;
            color: var(--pk-alert-accent);
            cursor: pointer;
        }

        .dismiss:hover {
            background: color-mix(
                in srgb,
                var(--pk-alert-accent) 10%,
                transparent
            );
        }

        .dismiss:focus-visible,
        .copy:focus-visible,
        summary:focus-visible {
            outline: none;
            box-shadow: var(--pk-shadow-focus);
        }

        .actions,
        .details {
            margin-inline-start: calc(var(--_pk-alert-icon-size) + var(--_pk-alert-gap));
        }

        :host([hide-icon]) .actions,
        :host([hide-icon]) .details {
            margin-inline-start: 0;
        }

        .actions {
            margin-top: var(--_pk-alert-section-margin-top);
        }

        .actions slot::slotted(pk-button) {
            --pk-btn-height-default: var(--_pk-alert-action-height);
            --pk-btn-font-default: var(--_pk-alert-action-font);
            --pk-btn-padding-inline-default: var(--_pk-alert-action-padding-inline);
            --pk-btn-icon-size-default: var(--_pk-alert-action-icon-size);
            --pk-btn-icon-gap-default: var(--_pk-alert-action-icon-gap);
            --pk-btn-caret-size-default: var(--_pk-alert-action-caret-size);
            --pk-btn-radius-default: var(--_pk-alert-action-radius);
        }

        .details {
            margin-top: var(--_pk-alert-section-margin-top);
            padding-top: var(--_pk-alert-details-padding-top);
            border-top: 1px solid var(--pk-alert-border);
            color: var(--pk-color-gray-800);
        }

        summary {
            width: fit-content;
            border-radius: var(--pk-radius-sm);
            font-weight: 600;
            cursor: pointer;
        }

        .details-content {
            position: relative;
            margin-top: var(--_pk-alert-details-content-margin-top);
        }

        .details-scroll {
            min-width: 0;
        }

        .details-content ::slotted(pre) {
            box-sizing: border-box;
            max-height: var(--pk-alert-details-max-height, 16rem);
            margin: 0;
            padding: var(--_pk-alert-details-pre-padding);
            overflow: auto;
            border: 1px solid var(--pk-color-gray-200);
            border-radius: var(--_pk-alert-details-pre-radius);
            background: var(--pk-color-white);
            color: var(--pk-color-gray-800);
            /* Keep host pre resets from changing diagnostic typography. */
            font:
                var(--_pk-alert-details-pre-font-size)/1.5 ui-monospace,
                SFMono-Regular,
                Consolas,
                'Liberation Mono',
                monospace !important;
            overflow-wrap: anywhere;
            user-select: text;
            white-space: pre-wrap;
        }

        :host([copyable]) .details-content ::slotted(pre) {
            max-height: none;
            padding-inline-end: calc(
                var(--_pk-alert-details-pre-padding) + var(--_pk-alert-copy-button-size) +
                    var(--_pk-alert-copy-inset)
            );
            overflow: visible;
            border: 0;
            border-radius: 0;
            background: transparent;
        }

        :host([copyable]) .details-content {
            overflow: hidden;
            border: 1px solid var(--pk-color-gray-200);
            border-radius: var(--_pk-alert-details-pre-radius);
            background: var(--pk-color-white);
        }

        :host([copyable]) .details-scroll {
            position: relative;
            max-height: var(--pk-alert-details-max-height, 16rem);
            overflow: auto;
        }

        .copy-overlay {
            position: sticky;
            z-index: 1;
            top: 0;
            display: flex;
            justify-content: flex-end;
            height: 0;
            pointer-events: none;
        }

        .copy {
            flex: none;
            margin-block-start: var(--_pk-alert-copy-inset);
            margin-inline-end: var(--_pk-alert-copy-inset);
            pointer-events: auto;
            --pk-btn-height-default: var(--_pk-alert-copy-button-size);
            --pk-btn-icon-size-default: var(--_pk-alert-copy-icon-size);
            --pk-btn-radius-default: var(--_pk-alert-copy-radius);
            --pk-copy-button-background: var(--pk-color-white);
            --pk-copy-button-border-color: transparent;
            --pk-copy-button-hover-border-color: transparent;
            --pk-copy-button-color: var(--pk-color-gray-300);
            --pk-copy-button-hover-color: var(--pk-color-gray-500);
            --pk-copy-button-hover-background: color-mix(
                in srgb,
                var(--pk-color-gray-300) 6%,
                var(--pk-color-white)
            );
        }

        .copy-status {
            display: block;
            margin-top: 0.375rem;
            color: var(--pk-color-red-700);
            font-size: var(--_pk-alert-copy-status-size);
        }

        .copy-status:not([data-error]) {
            position: absolute;
            width: 1px;
            height: 1px;
            margin: -1px;
            padding: 0;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            white-space: nowrap;
            border: 0;
        }

        @media (max-width: 480px) {
            .actions,
            .details {
                margin-inline-start: 0;
            }
        }
    }
`,K=3e3,q={neutral:c.circleInfo,info:c.circleInfo,success:c.circleCheck,warning:c.triangleExclamation,error:c.circleExclamation},J=class extends a{constructor(...e){super(...e),this.hasSlotController=new l(this,`title`,`icon`,`actions`,`details`),this.variant=`info`,this.size=`default`,this.appearance=`accent`,this.heading=``,this.hideIcon=!1,this.dismissible=!1,this.announce=`off`,this.detailsLabel=`Details`,this.detailsOpen=!1,this.copyable=!1,this.copyLabel=`Copy details`,this.copiedLabel=`Details copied.`,this.copyErrorLabel=`Copy failed. Select the details and copy them manually.`,this.dismissLabel=`Dismiss`,this.copyStatus=``,this.copyFailed=!1}static{this.styles=G}disconnectedCallback(){window.clearTimeout(this.copyStatusResetTimer),super.disconnectedCallback()}hasTitle(){return!!this.heading||this.hasSlotController.test(`title`)}hasDetails(){return this.hasSlotController.test(`details`)}get detailsValue(){return(this.detailsSlot?.assignedNodes({flatten:!0})??[]).map(e=>e.textContent??``).join(``).trim()}resetCopyStatusLater(){window.clearTimeout(this.copyStatusResetTimer),this.copyStatusResetTimer=window.setTimeout(()=>{this.copyStatus=``,this.copyFailed=!1},K)}showCopySuccess(){this.copyFailed=!1,this.copyStatus=this.copiedLabel,this.resetCopyStatusLater()}showCopyError(){this.copyFailed=!0,this.copyStatus=this.copyErrorLabel,this.resetCopyStatusLater()}handleCopySuccess(){this.showCopySuccess()}handleCopyError(){this.showCopyError(),this.selectDetails()}selectDetails(){let e=this.detailsSlot?.assignedNodes({flatten:!0})??[],t=e[0],n=e[e.length-1];if(!t||!n)return;this.detailsOpen=!0;let r=document.createRange();r.setStartBefore(t),r.setEndAfter(n);let i=window.getSelection();i?.removeAllRanges(),i?.addRange(r)}async copyDetails(){let e=this.detailsValue;if(!e){this.showCopyError(),this.dispatchEvent(new I);return}if(this.copyButton){this.copyButton.value=e,await this.copyButton.copy();return}try{await L(e),this.showCopySuccess();let t=new F(e);this.dispatchEvent(t)}catch{this.showCopyError(),this.selectDetails(),this.dispatchEvent(new I)}}dismiss(){this.dispatchEvent(new W)&&(this.hidden=!0)}show(){this.hidden=!1}handleDetailsToggle(e){this.detailsOpen=e.currentTarget.open}renderIcon(){if(this.hideIcon)return p;let e=this.hasSlotController.test(`icon`)?m`<slot name="icon"></slot>`:d(i(q[this.variant]??q.info));return m`<span part="icon" class="icon" aria-hidden="true">${e}</span>`}render(){let e=this.announce===`assertive`?`alert`:this.announce===`polite`?`status`:p,t=this.announce===`off`?p:this.announce,n=this.hasSlotController.test(`actions`),r=this.hasDetails();return m`
            <div
                part="base"
                class="alert"
                role=${e}
                aria-live=${t}
                aria-atomic=${this.announce===`off`?p:`true`}
            >
                <div part="notice" class="notice">
                    ${this.renderIcon()}

                    <div part="content" class="content">
                        ${this.hasTitle()?m`
                                <strong part="title" class="title">
                                    ${this.hasSlotController.test(`title`)?m`<slot name="title"></slot>`:this.heading}
                                </strong>
                            `:p}

                        <div part="body" class="body"><slot></slot></div>
                    </div>

                    ${this.dismissible?m`
                            <button
                                part="dismiss-button"
                                class="dismiss"
                                type="button"
                                aria-label=${this.dismissLabel}
                                @click=${this.dismiss}
                            >
                                ${d(i(c.xmark))}
                            </button>
                        `:p}
                </div>

                ${n?m`<div part="actions" class="actions"><slot name="actions"></slot></div>`:p}

                ${r?m`
                        <details
                            part="details"
                            class="details"
                            ?open=${this.detailsOpen}
                            @toggle=${this.handleDetailsToggle}
                        >
                            <summary part="details-summary">${this.detailsLabel}</summary>
                            <div part="details-content" class="details-content">
                                <div class="details-scroll">
                                    ${this.copyable?m`
                                            <div class="copy-overlay">
                                                <pk-copy-button
                                                    part="copy-button"
                                                    class="copy"
                                                    variant="transparent"
                                                    aria-label=${this.copyLabel}
                                                    .copiedLabel=${this.copiedLabel}
                                                    .value=${this.detailsValue}
                                                    @pk-copy=${this.handleCopySuccess}
                                                    @pk-copy-error=${this.handleCopyError}
                                                ></pk-copy-button>
                                            </div>
                                        `:p}
                                    <slot name="details"></slot>
                                </div>
                            </div>

                            ${this.copyable?m`
                                    <span
                                        part="copy-status"
                                        class="copy-status"
                                        data-error=${this.copyFailed?``:p}
                                        role="status"
                                        aria-live="polite"
                                    >${this.copyStatus}</span>
                                `:p}
                        </details>
                    `:p}
            </div>
        `}};s([g({reflect:!0})],J.prototype,`variant`,void 0),s([g({reflect:!0})],J.prototype,`size`,void 0),s([g({reflect:!0})],J.prototype,`appearance`,void 0),s([g()],J.prototype,`heading`,void 0),s([g({type:Boolean,attribute:`hide-icon`,reflect:!0})],J.prototype,`hideIcon`,void 0),s([g({type:Boolean,reflect:!0})],J.prototype,`dismissible`,void 0),s([g({reflect:!0})],J.prototype,`announce`,void 0),s([g({attribute:`details-label`})],J.prototype,`detailsLabel`,void 0),s([g({type:Boolean,attribute:`details-open`,reflect:!0})],J.prototype,`detailsOpen`,void 0),s([g({type:Boolean,reflect:!0})],J.prototype,`copyable`,void 0),s([g({attribute:`copy-label`})],J.prototype,`copyLabel`,void 0),s([g({attribute:`copied-label`})],J.prototype,`copiedLabel`,void 0),s([g({attribute:`copy-error-label`})],J.prototype,`copyErrorLabel`,void 0),s([g({attribute:`dismiss-label`})],J.prototype,`dismissLabel`,void 0),s([b(`slot[name="details"]`)],J.prototype,`detailsSlot`,void 0),s([b(`pk-copy-button.copy`)],J.prototype,`copyButton`,void 0),s([f()],J.prototype,`copyStatus`,void 0),s([f()],J.prototype,`copyFailed`,void 0),J=s([r(`pk-alert`)],J);var Y=class extends a{constructor(...e){super(...e),this.icon=``,this.name=``,this.unsubscribeRegistry=null}static{this.styles=y`
        :host {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex: none;
            /* Square em box + slight baseline nudge for inline text. Flex
             * parents (e.g. button slots) should zero vertical-align. */
            width: 1em;
            height: 1em;
            line-height: 1;
            vertical-align: -0.125em;
        }

        svg {
            display: block;
            width: 100%;
            height: 100%;
            fill: currentColor;
            /* Allow intentional path overhang past the icon canvas. */
            overflow: visible;
        }
    `}connectedCallback(){super.connectedCallback(),this.unsubscribeRegistry=o(()=>{this.requestUpdate()})}disconnectedCallback(){this.unsubscribeRegistry?.(),this.unsubscribeRegistry=null,super.disconnectedCallback()}render(){let e=n(this.icon||this.name);return e?m`${d(u(e,{title:this.label}))}`:p}};s([g()],Y.prototype,`icon`,void 0),s([g()],Y.prototype,`name`,void 0),s([g()],Y.prototype,`label`,void 0),Y=s([r(`pk-icon`)],Y);var X=[];function re(e){X.push(e)}function ie(e){for(let t=X.length-1;t>=0;--t)if(X[t]===e){X.splice(t,1);break}}function ae(e){return X.length>0&&X[X.length-1]===e}var oe=class extends Event{constructor(){super(`pk-show`,{bubbles:!0,cancelable:!1,composed:!0})}},Z=class extends Event{constructor(){super(`pk-after-show`,{bubbles:!0,cancelable:!1,composed:!0})}},se=class extends Event{constructor(e=`unknown`){super(`pk-hide`,{bubbles:!0,cancelable:!0,composed:!0}),this.detail={source:e}}},ce=class extends Event{constructor(){super(`pk-after-hide`,{bubbles:!0,cancelable:!1,composed:!0})}},le=y`
    @layer pk-component {
        :host {
            display: flex;
            flex: 1 1 auto;
            box-sizing: border-box;
            align-items: center;
            justify-content: center;
            width: 100%;
            min-height: var(--pk-state-panel-min-height, var(--_pk-state-panel-min-height));
            padding: var(--pk-state-panel-padding, var(--_pk-state-panel-padding));
            color: var(--pk-state-panel-body-color, var(--pk-color-gray-500));
            font-family: var(--pk-font-family);
            font-size: var(--_pk-state-panel-font-size);
            line-height: var(--pk-line-height);
            text-align: center;
            --pk-state-panel-accent: var(--pk-color-slate-500);
            --pk-state-panel-icon-background: color-mix(
                in srgb,
                var(--pk-color-slate-200) 55%,
                transparent
            );
            --_pk-state-panel-font-size: var(--pk-font-size-sm);
            --_pk-state-panel-title-size: var(--pk-font-size-base);
            --_pk-state-panel-content-width: 32rem;
            --_pk-state-panel-min-height: 11rem;
            --_pk-state-panel-padding: 2rem 0.875rem;
            --_pk-state-panel-icon-shell-size: 2.125rem;
            --_pk-state-panel-icon-size: 1.0625rem;
            --_pk-state-panel-icon-radius: var(--pk-radius-lg);
            --_pk-state-panel-icon-margin-bottom: 0.625rem;
            --_pk-state-panel-body-margin-top: 0.375rem;
            --_pk-state-panel-details-margin-top: 0.75rem;
            --_pk-state-panel-details-font-size: 0.75rem;
            --_pk-state-panel-details-content-margin-top: 0.625rem;
            --_pk-state-panel-details-max-height: 14rem;
            --_pk-state-panel-details-pre-padding: 0.625rem;
            --_pk-state-panel-details-pre-radius: var(--pk-radius-md);
            --_pk-state-panel-details-pre-font-size: 0.6875rem;
            --_pk-state-panel-copy-button-size: var(--pk-btn-height-xs);
            --_pk-state-panel-copy-icon-size: var(--pk-btn-icon-size-xs);
            --_pk-state-panel-copy-inset: 0.375rem;
            --_pk-state-panel-copy-radius: var(--pk-radius-md);
            --_pk-state-panel-copy-status-size: 0.6875rem;
            --_pk-state-panel-actions-gap: 0.4375rem;
            --_pk-state-panel-actions-margin-top: 1rem;
            --_pk-state-panel-action-height: var(--pk-btn-height-sm);
            --_pk-state-panel-action-font: var(--pk-btn-font-sm);
            --_pk-state-panel-action-padding-inline: var(--pk-btn-padding-inline-sm);
            --_pk-state-panel-action-icon-size: var(--pk-btn-icon-size-sm);
            --_pk-state-panel-action-icon-gap: var(--pk-btn-icon-gap-sm);
            --_pk-state-panel-action-caret-size: var(--pk-btn-caret-size-sm);
            --_pk-state-panel-action-radius: var(--pk-btn-radius-sm);
        }

        :host([size='sm']) {
            --_pk-state-panel-font-size: var(--pk-btn-font-xs);
            --_pk-state-panel-title-size: var(--pk-font-size-sm);
            --_pk-state-panel-content-width: 28rem;
            --_pk-state-panel-min-height: 8rem;
            --_pk-state-panel-padding: 1.25rem 0.75rem;
            --_pk-state-panel-icon-shell-size: 1.75rem;
            --_pk-state-panel-icon-size: 0.875rem;
            --_pk-state-panel-icon-radius: var(--pk-radius-md);
            --_pk-state-panel-icon-margin-bottom: 0.5rem;
            --_pk-state-panel-body-margin-top: 0.25rem;
            --_pk-state-panel-details-margin-top: 0.625rem;
            --_pk-state-panel-details-font-size: 0.6875rem;
            --_pk-state-panel-details-content-margin-top: 0.5rem;
            --_pk-state-panel-details-max-height: 10rem;
            --_pk-state-panel-details-pre-padding: 0.5rem;
            --_pk-state-panel-details-pre-radius: var(--pk-radius-sm);
            --_pk-state-panel-details-pre-font-size: 0.6875rem;
            --_pk-state-panel-copy-button-size: var(--pk-btn-height-xxs);
            --_pk-state-panel-copy-icon-size: var(--pk-btn-icon-size-xxs);
            --_pk-state-panel-copy-inset: 0.3125rem;
            --_pk-state-panel-copy-radius: var(--pk-radius-sm);
            --_pk-state-panel-copy-status-size: 0.6875rem;
            --_pk-state-panel-actions-gap: 0.375rem;
            --_pk-state-panel-actions-margin-top: 0.75rem;
            --_pk-state-panel-action-height: var(--pk-btn-height-xs);
            --_pk-state-panel-action-font: var(--pk-btn-font-xs);
            --_pk-state-panel-action-padding-inline: var(--pk-btn-padding-inline-xs);
            --_pk-state-panel-action-icon-size: var(--pk-btn-icon-size-xs);
            --_pk-state-panel-action-icon-gap: var(--pk-btn-icon-gap-xs);
            --_pk-state-panel-action-caret-size: var(--pk-btn-caret-size-xs);
            --_pk-state-panel-action-radius: var(--pk-btn-radius-xs);
        }

        :host([size='lg']) {
            --_pk-state-panel-font-size: var(--pk-font-size-base);
            --_pk-state-panel-title-size: 1rem;
            --_pk-state-panel-content-width: 35rem;
            --_pk-state-panel-min-height: 14rem;
            --_pk-state-panel-padding: 2.5rem 1rem;
            --_pk-state-panel-icon-shell-size: 2.5rem;
            --_pk-state-panel-icon-size: 1.25rem;
            --_pk-state-panel-icon-radius: 0.625rem;
            --_pk-state-panel-icon-margin-bottom: 0.75rem;
            --_pk-state-panel-body-margin-top: 0.5rem;
            --_pk-state-panel-details-margin-top: 1rem;
            --_pk-state-panel-details-font-size: 0.8125rem;
            --_pk-state-panel-details-content-margin-top: 0.75rem;
            --_pk-state-panel-details-max-height: 18rem;
            --_pk-state-panel-details-pre-padding: 0.75rem;
            --_pk-state-panel-details-pre-radius: var(--pk-radius-lg);
            --_pk-state-panel-details-pre-font-size: 0.75rem;
            --_pk-state-panel-copy-button-size: var(--pk-btn-height-sm);
            --_pk-state-panel-copy-icon-size: var(--pk-btn-icon-size-sm);
            --_pk-state-panel-copy-inset: 0.5rem;
            --_pk-state-panel-copy-radius: var(--pk-radius-lg);
            --_pk-state-panel-copy-status-size: 0.75rem;
            --_pk-state-panel-actions-gap: 0.5rem;
            --_pk-state-panel-actions-margin-top: 1.25rem;
            --_pk-state-panel-action-height: 2.125rem;
            --_pk-state-panel-action-font: var(--pk-font-size-base);
            --_pk-state-panel-action-padding-inline: 9px;
            --_pk-state-panel-action-icon-size: 14px;
            --_pk-state-panel-action-icon-gap: 6px;
            --_pk-state-panel-action-caret-size: 12px;
            --_pk-state-panel-action-radius: var(--pk-radius-lg);
        }

        :host([variant='info']) {
            --pk-state-panel-accent: var(--pk-color-sky-600);
            --pk-state-panel-icon-background: var(--pk-color-sky-50);
        }

        :host([variant='success']) {
            --pk-state-panel-accent: var(--pk-color-teal-600);
            --pk-state-panel-icon-background: var(--pk-color-teal-50);
        }

        :host([variant='warning']) {
            --pk-state-panel-accent: var(--pk-color-amber-600);
            --pk-state-panel-icon-background: var(--pk-color-amber-50);
        }

        :host([variant='error']) {
            --pk-state-panel-accent: var(--pk-color-rose-600);
            --pk-state-panel-icon-background: color-mix(
                in srgb,
                var(--pk-color-rose-500) 12%,
                transparent
            );
        }

        .panel {
            display: flex;
            flex-direction: column;
            align-items: center;
            width: min(
                100%,
                var(--pk-state-panel-content-width, var(--_pk-state-panel-content-width))
            );
            min-width: 0;
        }

        .icon-shell {
            display: inline-flex;
            flex: 0 0 auto;
            align-items: center;
            justify-content: center;
            width: var(--pk-state-panel-icon-shell-size, var(--_pk-state-panel-icon-shell-size));
            height: var(--pk-state-panel-icon-shell-size, var(--_pk-state-panel-icon-shell-size));
            margin-bottom: var(--_pk-state-panel-icon-margin-bottom);
            border-radius: var(--pk-state-panel-icon-radius, var(--_pk-state-panel-icon-radius));
            background: var(--pk-state-panel-icon-background);
            color: var(--pk-state-panel-accent);
        }

        .icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: var(--pk-state-panel-icon-size, var(--_pk-state-panel-icon-size));
            height: var(--pk-state-panel-icon-size, var(--_pk-state-panel-icon-size));
            font-size: var(--pk-state-panel-icon-size, var(--_pk-state-panel-icon-size));
        }

        .icon svg,
        .icon pk-icon,
        .icon ::slotted(*) {
            display: block;
            width: 100%;
            height: 100%;
            fill: currentColor;
        }

        .title {
            margin: 0;
            color: var(--pk-state-panel-title-color, var(--pk-color-gray-900));
            font-size: var(--pk-state-panel-title-size, var(--_pk-state-panel-title-size));
            font-weight: 600;
            line-height: 1.4;
        }

        .title ::slotted(*) {
            margin: 0;
            color: inherit;
            font: inherit;
        }

        .body {
            max-width: 100%;
            margin-top: var(--_pk-state-panel-body-margin-top);
        }

        .body ::slotted(*) {
            margin-block: 0;
        }

        .details {
            width: 100%;
            margin-top: var(--_pk-state-panel-details-margin-top);
            color: var(--pk-state-panel-accent);
            font-size: var(--_pk-state-panel-details-font-size);
        }

        summary {
            width: fit-content;
            margin-inline: auto;
            border-radius: var(--pk-radius-sm);
            cursor: pointer;
        }

        summary:focus-visible,
        .copy:focus-visible {
            outline: none;
            box-shadow: var(--pk-shadow-focus);
        }

        .details-content {
            position: relative;
            margin-top: var(--_pk-state-panel-details-content-margin-top);
            color: var(--pk-color-gray-800);
            text-align: start;
        }

        .details-scroll {
            min-width: 0;
        }

        .details-content ::slotted(pre) {
            box-sizing: border-box;
            max-height: var(
                --pk-state-panel-details-max-height,
                var(--_pk-state-panel-details-max-height)
            );
            margin: 0;
            padding: var(--_pk-state-panel-details-pre-padding);
            overflow: auto;
            border: 1px solid var(--pk-color-gray-200);
            border-radius: var(--_pk-state-panel-details-pre-radius);
            background: var(--pk-color-white);
            color: var(--pk-color-gray-800);
            font:
                var(--_pk-state-panel-details-pre-font-size)/1.5 ui-monospace,
                SFMono-Regular,
                Consolas,
                'Liberation Mono',
                monospace;
            overflow-wrap: anywhere;
            user-select: text;
            white-space: pre-wrap;
        }

        :host([copyable]) .details-content ::slotted(pre) {
            max-height: none;
            padding-inline-end: calc(
                var(--_pk-state-panel-details-pre-padding) +
                    var(--_pk-state-panel-copy-button-size) +
                    var(--_pk-state-panel-copy-inset)
            );
            overflow: visible;
            border: 0;
            border-radius: 0;
            background: transparent;
        }

        :host([copyable]) .details-content {
            overflow: hidden;
            border: 1px solid var(--pk-color-gray-200);
            border-radius: var(--_pk-state-panel-details-pre-radius);
            background: var(--pk-color-white);
        }

        :host([copyable]) .details-scroll {
            position: relative;
            max-height: var(
                --pk-state-panel-details-max-height,
                var(--_pk-state-panel-details-max-height)
            );
            overflow: auto;
        }

        .copy-overlay {
            position: sticky;
            z-index: 1;
            top: 0;
            display: flex;
            justify-content: flex-end;
            height: 0;
            pointer-events: none;
        }

        .copy {
            flex: none;
            margin-block-start: var(--_pk-state-panel-copy-inset);
            margin-inline-end: var(--_pk-state-panel-copy-inset);
            pointer-events: auto;
            --pk-btn-height-default: var(--_pk-state-panel-copy-button-size);
            --pk-btn-icon-size-default: var(--_pk-state-panel-copy-icon-size);
            --pk-btn-radius-default: var(--_pk-state-panel-copy-radius);
            --pk-copy-button-background: var(--pk-color-white);
            --pk-copy-button-border-color: transparent;
            --pk-copy-button-hover-border-color: transparent;
            --pk-copy-button-color: var(--pk-color-gray-300);
            --pk-copy-button-hover-color: var(--pk-color-gray-500);
            --pk-copy-button-hover-background: color-mix(
                in srgb,
                var(--pk-color-gray-300) 6%,
                var(--pk-color-white)
            );
        }

        .copy-status {
            display: block;
            margin-top: 0.375rem;
            color: var(--pk-color-red-700);
            font-size: var(--_pk-state-panel-copy-status-size);
            text-align: start;
        }

        .copy-status:not([data-error]) {
            position: absolute;
            width: 1px;
            height: 1px;
            margin: -1px;
            padding: 0;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            white-space: nowrap;
            border: 0;
        }

        .actions {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: center;
            gap: var(--_pk-state-panel-actions-gap);
            margin-top: var(--_pk-state-panel-actions-margin-top);
        }

        .actions slot::slotted(pk-button) {
            --pk-btn-height-default: var(--_pk-state-panel-action-height);
            --pk-btn-font-default: var(--_pk-state-panel-action-font);
            --pk-btn-padding-inline-default: var(--_pk-state-panel-action-padding-inline);
            --pk-btn-icon-size-default: var(--_pk-state-panel-action-icon-size);
            --pk-btn-icon-gap-default: var(--_pk-state-panel-action-icon-gap);
            --pk-btn-caret-size-default: var(--_pk-state-panel-action-caret-size);
            --pk-btn-radius-default: var(--_pk-state-panel-action-radius);
        }
    }
`,ue=3e3,Q={empty:c.emptySet,info:c.circleInfo,success:c.circleCheck,warning:c.triangleExclamation,error:c.triangleExclamation},$=class extends a{constructor(...e){super(...e),this.hasSlotController=new l(this,`title`,`icon`,`details`,`actions`),this.variant=`empty`,this.size=`default`,this.heading=``,this.headingLevel=2,this.icon=``,this.hideIcon=!1,this.announce=`off`,this.detailsLabel=`Details`,this.detailsOpen=!1,this.copyable=!1,this.copyLabel=`Copy details`,this.copiedLabel=`Details copied.`,this.copyErrorLabel=`Copy failed. Select the details and copy them manually.`,this.copyStatus=``,this.copyFailed=!1}static{this.styles=le}disconnectedCallback(){window.clearTimeout(this.copyStatusResetTimer),super.disconnectedCallback()}hasTitle(){return!!this.heading||this.hasSlotController.test(`title`)}get detailsValue(){return(this.detailsSlot?.assignedNodes({flatten:!0})??[]).map(e=>e.textContent??``).join(``).trim()}resetCopyStatusLater(){window.clearTimeout(this.copyStatusResetTimer),this.copyStatusResetTimer=window.setTimeout(()=>{this.copyStatus=``,this.copyFailed=!1},ue)}showCopySuccess(){this.copyFailed=!1,this.copyStatus=this.copiedLabel,this.resetCopyStatusLater()}showCopyError(){this.copyFailed=!0,this.copyStatus=this.copyErrorLabel,this.resetCopyStatusLater()}handleCopySuccess(){this.showCopySuccess()}handleCopyError(){this.showCopyError(),this.selectDetails()}selectDetails(){let e=this.detailsSlot?.assignedNodes({flatten:!0})??[],t=e[0],n=e[e.length-1];if(!t||!n)return;this.detailsOpen=!0;let r=document.createRange();r.setStartBefore(t),r.setEndAfter(n);let i=window.getSelection();i?.removeAllRanges(),i?.addRange(r)}async copyDetails(){let e=this.detailsValue;if(!e){this.showCopyError(),this.dispatchEvent(new I);return}if(this.copyButton){this.copyButton.value=e,await this.copyButton.copy();return}try{await L(e),this.showCopySuccess();let t=new F(e);this.dispatchEvent(t)}catch{this.showCopyError(),this.selectDetails(),this.dispatchEvent(new I)}}handleDetailsToggle(e){this.detailsOpen=e.currentTarget.open}renderIcon(){if(this.hideIcon)return p;let e;return e=this.hasSlotController.test(`icon`)?m`<slot name="icon"></slot>`:this.icon?m`<pk-icon .icon=${this.icon}></pk-icon>`:d(i(Q[this.variant]??Q.empty)),m`
            <span part="icon-shell" class="icon-shell" aria-hidden="true">
                <span part="icon" class="icon">${e}</span>
            </span>
        `}render(){let e=this.announce===`assertive`?`alert`:this.announce===`polite`?`status`:p,t=this.announce===`off`?p:this.announce,n=this.hasSlotController.test(`details`),r=this.hasSlotController.test(`actions`);return m`
            <div
                part="base"
                class="panel"
                role=${e}
                aria-live=${t}
                aria-atomic=${this.announce===`off`?p:`true`}
            >
                ${this.renderIcon()}

                ${this.hasTitle()?m`
                        <div
                            part="title"
                            class="title"
                            role="heading"
                            aria-level=${this.headingLevel}
                        >
                            ${this.hasSlotController.test(`title`)?m`<slot name="title"></slot>`:this.heading}
                        </div>
                    `:p}

                <div part="body" class="body"><slot></slot></div>

                ${n?m`
                        <details
                            part="details"
                            class="details"
                            ?open=${this.detailsOpen}
                            @toggle=${this.handleDetailsToggle}
                        >
                            <summary part="details-summary">${this.detailsLabel}</summary>
                            <div part="details-content" class="details-content">
                                <div class="details-scroll">
                                    ${this.copyable?m`
                                            <div class="copy-overlay">
                                                <pk-copy-button
                                                    part="copy-button"
                                                    class="copy"
                                                    variant="transparent"
                                                    aria-label=${this.copyLabel}
                                                    .copiedLabel=${this.copiedLabel}
                                                    .value=${this.detailsValue}
                                                    @pk-copy=${this.handleCopySuccess}
                                                    @pk-copy-error=${this.handleCopyError}
                                                ></pk-copy-button>
                                            </div>
                                        `:p}
                                    <slot name="details"></slot>
                                </div>
                            </div>

                            ${this.copyable?m`
                                    <span
                                        part="copy-status"
                                        class="copy-status"
                                        data-error=${this.copyFailed?``:p}
                                        role="status"
                                        aria-live="polite"
                                    >${this.copyStatus}</span>
                                `:p}
                        </details>
                    `:p}

                ${r?m`<div part="actions" class="actions"><slot name="actions"></slot></div>`:p}
            </div>
        `}};s([g({reflect:!0})],$.prototype,`variant`,void 0),s([g({reflect:!0})],$.prototype,`size`,void 0),s([g()],$.prototype,`heading`,void 0),s([g({type:Number,attribute:`heading-level`})],$.prototype,`headingLevel`,void 0),s([g()],$.prototype,`icon`,void 0),s([g({type:Boolean,attribute:`hide-icon`,reflect:!0})],$.prototype,`hideIcon`,void 0),s([g({reflect:!0})],$.prototype,`announce`,void 0),s([g({attribute:`details-label`})],$.prototype,`detailsLabel`,void 0),s([g({type:Boolean,attribute:`details-open`,reflect:!0})],$.prototype,`detailsOpen`,void 0),s([g({type:Boolean,reflect:!0})],$.prototype,`copyable`,void 0),s([g({attribute:`copy-label`})],$.prototype,`copyLabel`,void 0),s([g({attribute:`copied-label`})],$.prototype,`copiedLabel`,void 0),s([g({attribute:`copy-error-label`})],$.prototype,`copyErrorLabel`,void 0),s([b(`slot[name="details"]`)],$.prototype,`detailsSlot`,void 0),s([b(`pk-copy-button.copy`)],$.prototype,`copyButton`,void 0),s([f()],$.prototype,`copyStatus`,void 0),s([f()],$.prototype,`copyFailed`,void 0),$=s([r(`pk-state-panel`)],$);export{O as _,oe as a,ie as c,U as d,P as f,j as g,A as h,se as i,Y as l,k as m,ce as n,ae as o,M as p,Z as r,re as s,$ as t,J as u,D as v,C as y};
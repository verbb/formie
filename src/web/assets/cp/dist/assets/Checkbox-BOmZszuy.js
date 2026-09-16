import{r as e}from"./rolldown-runtime-hePW80VL.js";import{m as t}from"./Spinner-yuRF9Eet.js";import{T as n,w as r}from"./dndkit-Tbq_EQgB.js";import{F as i}from"./utils-DF6t9GV_.js";import{s as a}from"./overlay-lifecycle-D0pkTQyI-BDCiftP5.js";import{d as o,f as s,l as c,p as l,s as u}from"./lit-C7H9X-yg.js";import{It as d,Lt as f,Rt as p}from"./render-Dvc3MHQR-Byeexk_P.js";import{t as m}from"./pk-change-BMLA71i0.js";var h=e(n(),1),g=l`
    @layer pk-component {
        :host {
            display: inline-flex;
            width: fit-content;
            vertical-align: middle;
        }

        .group {
            display: flex;
            position: relative;
            isolation: isolate;
            flex-wrap: nowrap;
            gap: 0;
            width: fit-content;
            max-width: 100%;
            align-items: stretch;
        }

        :host([orientation='horizontal']) .group {
            flex-direction: row;
        }

        :host([orientation='vertical']) .group {
            flex-direction: column;
            align-items: stretch;
        }

        :host([orientation='vertical']) ::slotted(pk-button),
        :host([orientation='vertical']) ::slotted(pk-toggle),
        :host([orientation='vertical']) ::slotted(pk-input),
        :host([orientation='vertical']) ::slotted(pk-input-group),
        :host([orientation='vertical']) ::slotted(.button-group-text),
        :host([orientation='vertical']) ::slotted(select.button-group-select) {
            align-self: stretch;
            width: 100%;
            max-width: 100%;
            box-sizing: border-box;
        }

        @media (hover: hover) {
            .group > :hover,
            ::slotted(:hover) {
                z-index: 1;
            }
        }

        .group > :focus-visible,
        ::slotted(:focus-visible),
        ::slotted(:focus-within),
        ::slotted([aria-checked='true']),
        ::slotted([checked]) {
            position: relative;
            z-index: 2;
        }

        /* Flush join: filled controls sit edge-to-edge; outlined controls overlap 1px to collapse borders */
        :host([orientation='horizontal']) {
            --pk-bg-horizontal-indent: 0;
            --pk-bg-horizontal-indent-outlined: -1px;
            --pk-btn-group-gap: 1px;
            --pk-btn-group-divider-color-outline: var(--pk-color-slate-400);
            --pk-btn-group-divider-color-dashed: var(--pk-color-slate-500);
        }

        :host([orientation='vertical']) {
            --pk-bg-vertical-indent: 0;
            --pk-bg-vertical-indent-outlined: -1px;
            --pk-btn-group-gap: 1px;
            --pk-btn-group-divider-color-outline: var(--pk-color-slate-400);
            --pk-btn-group-divider-color-dashed: var(--pk-color-slate-500);
        }

        :host([separators][orientation='horizontal']) {
            --pk-bg-horizontal-indent: 0;
        }

        :host([separators][orientation='vertical']) {
            --pk-bg-vertical-indent: 0;
        }

        ::slotted([data-pk-group-orientation='horizontal']:not([data-pk-group-item-first]):not([data-pk-group-item-last])) {
            --pk-bg-start-start-radius: 0;
            --pk-bg-start-end-radius: 0;
            --pk-bg-end-start-radius: 0;
            --pk-bg-end-end-radius: 0;
        }

        ::slotted([data-pk-group-orientation='horizontal'][data-pk-group-item-first]:not([data-pk-group-item-last])) {
            --pk-bg-start-end-radius: 0;
            --pk-bg-end-end-radius: 0;
        }

        ::slotted([data-pk-group-orientation='horizontal'][data-pk-group-item-last]:not([data-pk-group-item-first])) {
            --pk-bg-start-start-radius: 0;
            --pk-bg-end-start-radius: 0;
        }

        ::slotted([data-pk-group-orientation='vertical']:not([data-pk-group-item-first]):not([data-pk-group-item-last])) {
            --pk-bg-start-start-radius: 0;
            --pk-bg-start-end-radius: 0;
            --pk-bg-end-start-radius: 0;
            --pk-bg-end-end-radius: 0;
        }

        ::slotted([data-pk-group-orientation='vertical'][data-pk-group-item-first]:not([data-pk-group-item-last])) {
            --pk-bg-end-start-radius: 0;
            --pk-bg-end-end-radius: 0;
        }

        ::slotted([data-pk-group-orientation='vertical'][data-pk-group-item-last]:not([data-pk-group-item-first])) {
            --pk-bg-start-start-radius: 0;
            --pk-bg-start-end-radius: 0;
        }

        :host([exclusive]) ::slotted(pk-button[aria-pressed='true']) {
            --pk-btn-fill: var(--pk-color-gray-500);
            --pk-btn-fill-hover: var(--pk-color-gray-550);
            --pk-btn-fill-active: var(--pk-color-gray-600);
            --pk-btn-on: var(--pk-color-white);
        }

        :host:has(::slotted(pk-button-group-separator)) {
            --pk-btn-group-separator-color: transparent;
        }

        ::slotted(pk-input),
        ::slotted(pk-input-group) {
            width: auto;
            flex: 0 1 auto;
            align-self: stretch;
        }

        ::slotted(pk-popover),
        ::slotted(pk-dropdown-menu) {
            display: inline-flex;
            align-self: auto;
            flex: 0 0 auto;
        }

        ::slotted(.button-group-text) {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            min-height: var(--pk-btn-height-default);
            padding: 0 0.625rem;
            border-width: 1px;
            border-style: solid;
            border-color: var(--pk-color-slate-400);
            background: var(--pk-color-gray-100);
            color: var(--pk-color-gray-700);
            font-family: var(--pk-font-family);
            font-size: var(--pk-font-size-sm);
            font-weight: 500;
            line-height: var(--pk-line-height);
            box-sizing: border-box;
            white-space: nowrap;
            border-top-left-radius: var(--pk-bg-start-start-radius, var(--pk-radius-lg));
            border-top-right-radius: var(--pk-bg-start-end-radius, var(--pk-radius-lg));
            border-bottom-left-radius: var(--pk-bg-end-start-radius, var(--pk-radius-lg));
            border-bottom-right-radius: var(--pk-bg-end-end-radius, var(--pk-radius-lg));
        }

        :host([orientation='horizontal']) ::slotted([data-pk-group-orientation='horizontal'][data-pk-group-join]:not([data-pk-group-divider]).button-group-text),
        :host([orientation='horizontal']) ::slotted([data-pk-group-orientation='horizontal'][data-pk-group-join]:not([data-pk-group-divider])select.button-group-select),
        :host([orientation='horizontal']) ::slotted([data-pk-group-orientation='horizontal'][data-pk-group-join]:not([data-pk-group-divider])pk-input-group) {
            margin-inline-start: var(--pk-bg-horizontal-indent-outlined, 0);
            border-left-width: 0;
        }

        :host([orientation='vertical']) ::slotted([data-pk-group-orientation='vertical'][data-pk-group-join]:not([data-pk-group-divider]).button-group-text),
        :host([orientation='vertical']) ::slotted([data-pk-group-orientation='vertical'][data-pk-group-join]:not([data-pk-group-divider])select.button-group-select),
        :host([orientation='vertical']) ::slotted([data-pk-group-orientation='vertical'][data-pk-group-join]:not([data-pk-group-divider])pk-input-group) {
            margin-block-start: var(--pk-bg-vertical-indent-outlined, 0);
            border-top-width: 0;
        }

        ::slotted(select.button-group-select) {
            display: block;
            margin: 0;
            padding: 0 10px;
            border-width: 1px;
            border-style: solid;
            border-color: var(--pk-color-slate-400);
            background: var(--pk-color-white);
            color: var(--pk-color-gray-900);
            font-family: var(--pk-font-family);
            font-size: var(--pk-font-size-base);
            line-height: 1.4;
            appearance: none;
            box-sizing: border-box;
            align-self: stretch;
            min-height: var(--pk-btn-height-default);
            height: var(--pk-btn-height-default);
            border-top-left-radius: var(--pk-bg-start-start-radius, var(--pk-radius-lg));
            border-top-right-radius: var(--pk-bg-start-end-radius, var(--pk-radius-lg));
            border-bottom-left-radius: var(--pk-bg-end-start-radius, var(--pk-radius-lg));
            border-bottom-right-radius: var(--pk-bg-end-end-radius, var(--pk-radius-lg));
        }

        ::slotted(pk-separator) {
            align-self: stretch;
        }

        ::slotted(pk-separator[orientation='vertical']),
        ::slotted(pk-button-group-separator) {
            height: auto;
            flex-shrink: 0;
        }

        :host([orientation='horizontal']) ::slotted([data-pk-group-orientation='horizontal'][data-pk-group-divider][data-pk-group-join].button-group-text),
        :host([orientation='horizontal']) ::slotted([data-pk-group-orientation='horizontal'][data-pk-group-divider][data-pk-group-join]select.button-group-select),
        :host([orientation='horizontal']) ::slotted([data-pk-group-orientation='horizontal'][data-pk-group-divider][data-pk-group-join]pk-input-group) {
            margin-inline-start: var(--pk-bg-horizontal-indent-outlined, 0);
        }

        :host([orientation='vertical']) ::slotted([data-pk-group-orientation='vertical'][data-pk-group-divider][data-pk-group-join].button-group-text),
        :host([orientation='vertical']) ::slotted([data-pk-group-orientation='vertical'][data-pk-group-divider][data-pk-group-join]select.button-group-select),
        :host([orientation='vertical']) ::slotted([data-pk-group-orientation='vertical'][data-pk-group-divider][data-pk-group-join]pk-input-group) {
            margin-block-start: var(--pk-bg-vertical-indent-outlined, 0);
        }

        :host([orientation='horizontal']) ::slotted(.button-group-text[data-pk-group-divider]),
        :host([orientation='horizontal']) ::slotted(select.button-group-select[data-pk-group-divider]),
        :host([orientation='horizontal']) ::slotted(pk-input-group[data-pk-group-divider]) {
            border-left-width: 1px;
            border-left-style: solid;
            border-left-color: var(--pk-btn-group-divider-color-outline, var(--pk-color-slate-400));
            box-shadow: none;
        }

        :host([orientation='vertical']) ::slotted(.button-group-text[data-pk-group-divider]),
        :host([orientation='vertical']) ::slotted(select.button-group-select[data-pk-group-divider]),
        :host([orientation='vertical']) ::slotted(pk-input-group[data-pk-group-divider]) {
            border-top-width: 1px;
            border-top-style: solid;
            border-top-color: var(--pk-btn-group-divider-color-outline, var(--pk-color-slate-400));
            box-shadow: none;
        }

        :host([orientation='horizontal']) ::slotted([data-pk-group-internal-trail].button-group-text),
        :host([orientation='horizontal']) ::slotted([data-pk-group-internal-trail].button-group-select),
        :host([orientation='horizontal']) ::slotted([data-pk-group-internal-trail]select.button-group-select),
        :host([orientation='horizontal']) ::slotted([data-pk-group-internal-trail]pk-input-group) {
            border-right-width: 0;
        }

        :host([orientation='vertical']) ::slotted([data-pk-group-internal-trail].button-group-text),
        :host([orientation='vertical']) ::slotted([data-pk-group-internal-trail].button-group-select),
        :host([orientation='vertical']) ::slotted([data-pk-group-internal-trail]select.button-group-select),
        :host([orientation='vertical']) ::slotted([data-pk-group-internal-trail]pk-input-group) {
            border-bottom-width: 0;
        }
    }
`,_=`data-pk-group-join`,v=`data-pk-group-divider`,y=`data-pk-group-item-first`,b=`data-pk-group-item-last`,x=`data-pk-group-internal-trail`,S=`data-pk-group-btn-last`,C=`data-pk-group-orientation`,w=new Set([`PK-SEPARATOR`,`PK-BUTTON-GROUP-SEPARATOR`]),T=new Set([`PK-POPOVER`,`PK-DROPDOWN-MENU`]),E={fromAttribute(e){return e===null||e!==`false`},toAttribute(e){return e?``:`false`}};function D(e){return w.has(e.tagName)}function O(e){return T.has(e.tagName)?e.querySelector(`[slot="trigger"]`)??e:e}function k(e){if(T.has(e.tagName)){let t=O(e);return t===e?[e]:[e,t]}return[e]}function A(e,t){return e===t?[e]:[e,t]}function j(e){let t=O(e);return[...new Set([...k(e),...A(e,t)])]}function M(e){for(let t of j(e))t.removeAttribute(_),t.removeAttribute(v),t.removeAttribute(y),t.removeAttribute(b),t.removeAttribute(x),t.removeAttribute(S),t.removeAttribute(C)}function N(e){return O(e).hasAttribute(`group-trigger`)||e.hasAttribute(`group-trigger`)}function P(e){let t=[],n=[];for(let r of e){if(D(r)){n.length&&t.push(n),n=[];continue}n.push(r)}return n.length&&t.push(n),t}var F=class extends d{constructor(...e){super(...e),this.orientation=`horizontal`,this.separators=!0,this.exclusive=!1,this.label=``}static{this.styles=g}firstUpdated(){this.scheduleSyncGroupLayout()}updated(e){e.has(`orientation`)&&(this.setAttribute(`aria-orientation`,this.orientation),this.scheduleSyncGroupLayout()),e.has(`label`)&&(this.label?this.setAttribute(`aria-label`,this.label):this.removeAttribute(`aria-label`)),(e.has(`separators`)||e.has(`exclusive`))&&this.scheduleSyncGroupLayout()}scheduleSyncGroupLayout(){this.syncGroupLayout(),queueMicrotask(()=>this.syncGroupLayout())}handleSlotChange(){this.scheduleSyncGroupLayout()}syncGroupLayout(){let e=this.defaultSlot?.assignedElements({flatten:!0})??[],t=P(e);for(let t of e)M(t);for(let t of e)for(let e of j(t))e.setAttribute(C,this.orientation);for(let e=0;e<t.length;e++){let n=t[e],r=e===0,i=e===t.length-1;for(let e=0;e<n.length;e++){let t=n[e],a=O(t),o=A(t,a),s=n.length===1,c=e===0,l=e===n.length-1;for(let e of o)s?(r&&e.setAttribute(y,``),i&&e.setAttribute(b,``)):(c&&e.setAttribute(y,``),l&&e.setAttribute(b,``));if(c){if(this.separators&&!l)for(let e of k(t))e.setAttribute(x,``);continue}for(let e of k(t))e.setAttribute(_,``),this.separators&&e.setAttribute(v,``);if(this.separators&&!l)for(let e of k(t))e.setAttribute(x,``);N(t)&&a.setAttribute(S,``)}}}render(){return s`
            <div part="base" class="group" role="group" aria-label=${this.label||o}>
                <slot @slotchange=${this.handleSlotChange}></slot>
            </div>
        `}};f([c({reflect:!0})],F.prototype,`orientation`,void 0),f([c({type:Boolean,reflect:!0,converter:E})],F.prototype,`separators`,void 0),f([c({type:Boolean,reflect:!0})],F.prototype,`exclusive`,void 0),f([c()],F.prototype,`label`,void 0),f([u(`slot:not([name])`)],F.prototype,`defaultSlot`,void 0),F=f([p(`pk-button-group`)],F);var I=l`
    @layer pk-component {
        :host {
            display: inline-block;
            align-self: stretch;
            flex-shrink: 0;
        }

        .separator {
            display: block;
            width: 1px;
            height: 100%;
            margin-block: 1px;
            background: var(--pk-btn-group-separator-color, transparent);
        }

        :host-context(pk-button-group[orientation='vertical']) {
            display: block;
            width: 100%;
            height: auto;
        }

        :host-context(pk-button-group[orientation='vertical']) .separator {
            width: 100%;
            height: 1px;
            margin-block: 1px;
        }
    }
`,L=class extends d{constructor(...e){super(...e),this.orientation=`vertical`}static{this.styles=I}render(){return s`
            <div
                part="base"
                class="separator pk-btn-group__separator"
                role="separator"
                aria-orientation=${this.orientation}
                data-orientation=${this.orientation}
            ></div>
        `}};f([c({reflect:!0})],L.prototype,`orientation`,void 0),L=f([p(`pk-button-group-separator`)],L);var R=[a(`.text`,`var(--pk-radius-lg)`),l`
        @layer pk-component {
            :host {
                display: inline-flex;
                vertical-align: middle;
                margin-inline-start: var(--pk-bg-horizontal-indent-outlined, 0);
                margin-block-start: var(--pk-bg-vertical-indent-outlined, 0);
            }

            .text {
                display: flex;
                align-items: center;
                gap: 0.5rem;
                padding: 0 0.625rem;
                border: 1px solid var(--pk-color-slate-400);
                background: var(--pk-color-gray-100);
                color: var(--pk-color-gray-700);
                font-family: var(--pk-font-family);
                font-size: var(--pk-font-size-sm);
                font-weight: 500;
                line-height: var(--pk-line-height);
                white-space: nowrap;
            }
        }
    `],z=class extends d{static{this.styles=R}render(){return s`
            <span part="base" class="text pk-btn-group__text">
                <slot></slot>
            </span>
        `}};z=f([p(`pk-button-group-text`)],z);var B=i({tagName:`pk-button-group`,elementClass:F,react:h.default});i({tagName:`pk-button-group-separator`,elementClass:L,react:h.default}),i({tagName:`pk-button-group-text`,elementClass:z,react:h.default});var V=B,H=r(),U=i({tagName:`pk-checkbox`,elementClass:t,react:h.default,events:{onPkChange:`pk-change`,onChange:`change`}});function W({children:e,checked:t,defaultChecked:n,indeterminate:r=!1,disabled:i=!1,invalid:a=!1,required:o=!1,value:s=`on`,"data-state":c,onCheckedChange:l,onPkChange:u,...d}){let[f,p]=h.useState(!!n),g=t!==void 0,_=g?t:f,v=e=>{let t=m(e);g||p(t),u?.(e),l?.(t)};return(0,H.jsx)(U,{checked:_,defaultChecked:n,indeterminate:r,disabled:i,invalid:a,required:o,checkboxValue:s,...c?{"data-state":c}:{},onPkChange:v,...d,children:e})}export{V as n,W as t};
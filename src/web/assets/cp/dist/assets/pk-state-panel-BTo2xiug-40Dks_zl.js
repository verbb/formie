import{d as e,i as t,l as n,n as r,p as i,r as a,s as o,t as s,u as c}from"./has-slot-DJv86HKx-D2K9y9lH.js";import{a as l,c as u,d,f,l as p,p as m,s as h}from"./lit-C7H9X-yg.js";import{i as g,n as _,r as v}from"./pk-alert-BQAb4lJc-KYGPWJEN.js";var y=e=>e.replace(/\n/g,`<br>`),b=e=>e?e.response?.statusText?e.response.statusText:e.message?.includes(`Network Error`)?`Network Error`:e.message?.includes(`timeout`)?`Request Timeout`:`An error has occurred`:`An error has occurred`,x=e=>e?e.response?.data?.message?e.response.data.message:e.response?.data?.error?e.response.data.error:e.message?e.message:String(e):``,S=(e,t=5)=>{let n=[];if(!e)return{traces:n,traceAsString:``};let r=e.response?.data?.file,i=e.response?.data?.line;r&&i&&n.push(`${r}:${i}`);let a=e.response?.data?.trace||[];for(let e=0;e<Math.min(t,a.length);e++){let t=a[e];t?.file&&t?.line&&n.push(`${t.file}:${t.line}`)}return e.stack&&n.length===0&&n.push(e.stack),{traces:n,traceAsString:n.map(y).join(`<br>`)}},C=function(e,t=5){if(e==null)return{heading:`An error has occurred`,text:``,trace:``,traceAsString:``,traceAsArray:[]};let{traces:n,traceAsString:r}=S(e,t);return{heading:b(e),text:x(e),trace:r,traceAsString:r,traceAsArray:n}},w=class extends r{constructor(...e){super(...e),this.icon=``,this.name=``,this.unsubscribeRegistry=null}static{this.styles=m`
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
    `}connectedCallback(){super.connectedCallback(),this.unsubscribeRegistry=i(()=>{this.requestUpdate()})}disconnectedCallback(){this.unsubscribeRegistry?.(),this.unsubscribeRegistry=null,super.disconnectedCallback()}render(){let t=e(this.icon||this.name);return t?f`${l(c(t,{title:this.label}))}`:d}};a([p()],w.prototype,`icon`,void 0),a([p()],w.prototype,`name`,void 0),a([p()],w.prototype,`label`,void 0),w=a([t(`pk-icon`)],w);var T=[];function E(e){T.push(e)}function D(e){for(let t=T.length-1;t>=0;--t)if(T[t]===e){T.splice(t,1);break}}function O(e){return T.length>0&&T[T.length-1]===e}var k=class extends Event{constructor(){super(`pk-show`,{bubbles:!0,cancelable:!1,composed:!0})}},A=class extends Event{constructor(){super(`pk-after-show`,{bubbles:!0,cancelable:!1,composed:!0})}},j=class extends Event{constructor(e=`unknown`){super(`pk-hide`,{bubbles:!0,cancelable:!0,composed:!0}),this.detail={source:e}}},M=class extends Event{constructor(){super(`pk-after-hide`,{bubbles:!0,cancelable:!1,composed:!0})}},N=m`
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
`,P=3e3,F={empty:o.emptySet,info:o.circleInfo,success:o.circleCheck,warning:o.triangleExclamation,error:o.triangleExclamation},I=class extends r{constructor(...e){super(...e),this.hasSlotController=new s(this,`title`,`icon`,`details`,`actions`),this.variant=`empty`,this.size=`default`,this.heading=``,this.headingLevel=2,this.icon=``,this.hideIcon=!1,this.announce=`off`,this.detailsLabel=`Details`,this.detailsOpen=!1,this.copyable=!1,this.copyLabel=`Copy details`,this.copiedLabel=`Details copied.`,this.copyErrorLabel=`Copy failed. Select the details and copy them manually.`,this.copyStatus=``,this.copyFailed=!1}static{this.styles=N}disconnectedCallback(){window.clearTimeout(this.copyStatusResetTimer),super.disconnectedCallback()}hasTitle(){return!!this.heading||this.hasSlotController.test(`title`)}get detailsValue(){return(this.detailsSlot?.assignedNodes({flatten:!0})??[]).map(e=>e.textContent??``).join(``).trim()}resetCopyStatusLater(){window.clearTimeout(this.copyStatusResetTimer),this.copyStatusResetTimer=window.setTimeout(()=>{this.copyStatus=``,this.copyFailed=!1},P)}showCopySuccess(){this.copyFailed=!1,this.copyStatus=this.copiedLabel,this.resetCopyStatusLater()}showCopyError(){this.copyFailed=!0,this.copyStatus=this.copyErrorLabel,this.resetCopyStatusLater()}handleCopySuccess(){this.showCopySuccess()}handleCopyError(){this.showCopyError(),this.selectDetails()}selectDetails(){let e=this.detailsSlot?.assignedNodes({flatten:!0})??[],t=e[0],n=e[e.length-1];if(!t||!n)return;this.detailsOpen=!0;let r=document.createRange();r.setStartBefore(t),r.setEndAfter(n);let i=window.getSelection();i?.removeAllRanges(),i?.addRange(r)}async copyDetails(){let e=this.detailsValue;if(!e){this.showCopyError(),this.dispatchEvent(new _);return}if(this.copyButton){this.copyButton.value=e,await this.copyButton.copy();return}try{await g(e),this.showCopySuccess();let t=new v(e);this.dispatchEvent(t)}catch{this.showCopyError(),this.selectDetails(),this.dispatchEvent(new _)}}handleDetailsToggle(e){this.detailsOpen=e.currentTarget.open}renderIcon(){if(this.hideIcon)return d;let e;return e=this.hasSlotController.test(`icon`)?f`<slot name="icon"></slot>`:this.icon?f`<pk-icon .icon=${this.icon}></pk-icon>`:l(n(F[this.variant]??F.empty)),f`
            <span part="icon-shell" class="icon-shell" aria-hidden="true">
                <span part="icon" class="icon">${e}</span>
            </span>
        `}render(){let e=this.announce===`assertive`?`alert`:this.announce===`polite`?`status`:d,t=this.announce===`off`?d:this.announce,n=this.hasSlotController.test(`details`),r=this.hasSlotController.test(`actions`);return f`
            <div
                part="base"
                class="panel"
                role=${e}
                aria-live=${t}
                aria-atomic=${this.announce===`off`?d:`true`}
            >
                ${this.renderIcon()}

                ${this.hasTitle()?f`
                        <div
                            part="title"
                            class="title"
                            role="heading"
                            aria-level=${this.headingLevel}
                        >
                            ${this.hasSlotController.test(`title`)?f`<slot name="title"></slot>`:this.heading}
                        </div>
                    `:d}

                <div part="body" class="body"><slot></slot></div>

                ${n?f`
                        <details
                            part="details"
                            class="details"
                            ?open=${this.detailsOpen}
                            @toggle=${this.handleDetailsToggle}
                        >
                            <summary part="details-summary">${this.detailsLabel}</summary>
                            <div part="details-content" class="details-content">
                                <div class="details-scroll">
                                    ${this.copyable?f`
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
                                        `:d}
                                    <slot name="details"></slot>
                                </div>
                            </div>

                            ${this.copyable?f`
                                    <span
                                        part="copy-status"
                                        class="copy-status"
                                        data-error=${this.copyFailed?``:d}
                                        role="status"
                                        aria-live="polite"
                                    >${this.copyStatus}</span>
                                `:d}
                        </details>
                    `:d}

                ${r?f`<div part="actions" class="actions"><slot name="actions"></slot></div>`:d}
            </div>
        `}};a([p({reflect:!0})],I.prototype,`variant`,void 0),a([p({reflect:!0})],I.prototype,`size`,void 0),a([p()],I.prototype,`heading`,void 0),a([p({type:Number,attribute:`heading-level`})],I.prototype,`headingLevel`,void 0),a([p()],I.prototype,`icon`,void 0),a([p({type:Boolean,attribute:`hide-icon`,reflect:!0})],I.prototype,`hideIcon`,void 0),a([p({reflect:!0})],I.prototype,`announce`,void 0),a([p({attribute:`details-label`})],I.prototype,`detailsLabel`,void 0),a([p({type:Boolean,attribute:`details-open`,reflect:!0})],I.prototype,`detailsOpen`,void 0),a([p({type:Boolean,reflect:!0})],I.prototype,`copyable`,void 0),a([p({attribute:`copy-label`})],I.prototype,`copyLabel`,void 0),a([p({attribute:`copied-label`})],I.prototype,`copiedLabel`,void 0),a([p({attribute:`copy-error-label`})],I.prototype,`copyErrorLabel`,void 0),a([h(`slot[name="details"]`)],I.prototype,`detailsSlot`,void 0),a([h(`pk-copy-button.copy`)],I.prototype,`copyButton`,void 0),a([u()],I.prototype,`copyStatus`,void 0),a([u()],I.prototype,`copyFailed`,void 0),I=a([t(`pk-state-panel`)],I);export{k as a,D as c,j as i,w as l,M as n,O as o,A as r,E as s,I as t,C as u};
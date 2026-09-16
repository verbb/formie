import{r as e}from"./rolldown-runtime-hePW80VL.js";import{d as t,f as n,p as r,u as i}from"./Spinner-yuRF9Eet.js";import{I as a}from"./registerFormieOwnedSchemaFields-DMoAxWv1.js";import{T as o,w as s}from"./dndkit-Tbq_EQgB.js";import{E as c,F as l,N as u,O as d,S as f,T as p,b as m,j as h,k as g,v as _,x as v}from"./utils-DF6t9GV_.js";import{a as y,r as b}from"./pk-status-BehQARDv-UCn-zswF.js";import{c as x,d as S,f as C,l as ee,n as te,s as w,t as T}from"./Field-F5nY6ns6.js";import{h as E,i as D,m as O,n as k,p as A,r as j,t as M,u as N}from"./overlay-lifecycle-D0pkTQyI-BDCiftP5.js";import{a as P,c as F,d as I,f as L,i as R,l as z,p as B,s as V}from"./lit-C7H9X-yg.js";import{Lt as H,Rt as U,n as W}from"./render-Dvc3MHQR-Byeexk_P.js";var G=e(o(),1),K=s(),q=[f,B`
    ${N}
    @layer pk-component {
        :host {
            display: block;
            position: relative;
            width: 100%;
            color: var(--pk-color-gray-700);
            font-family: var(--pk-font-family);
            font-size: var(--pk-font-size-base);
            line-height: var(--pk-line-height);
            /* Match pk-input control tokens — not Combobox slate fill. */
            --pk-autocomplete-padding-block: 6px;
            --pk-autocomplete-padding-inline: 8px;
            --pk-autocomplete-control-gap: 6px;
            --pk-autocomplete-decoration-size: 0.75rem;
            --pk-autocomplete-font-size: var(--pk-font-size-base);
            --pk-select-item-min-height: 0;
            --pk-select-item-padding-block: 6px;
            --pk-select-item-padding-inline: 10px;
            --pk-select-item-padding-inline-end: 2rem;
            --pk-select-item-font-size: 14px;
            --pk-select-item-line-height: 1.4;
            --pk-select-item-indicator-size: 0.75rem;
            --pk-select-item-indicator-inset: 0.5rem;
            --pk-select-group-label-font-size: 12px;
        }

        /* width="full" is the documented stretch opt-in; host already fills by default. */
        :host([width='full']) {
            display: block;
            width: 100%;
        }

        .control {
            display: flex;
            align-items: center;
            gap: var(--pk-autocomplete-control-gap);
            width: 100%;
            max-width: 100%;
            min-width: 0;
            margin: 0;
            padding-block: var(--pk-autocomplete-padding-block);
            padding-inline: var(--pk-autocomplete-padding-inline);
            border: var(--pk-input-border);
            border-radius: var(--pk-input-border-radius, var(--pk-radius-sm));
            background: var(--pk-input-bg);
            background-clip: padding-box;
            color: var(--pk-color-gray-700);
            font: inherit;
            font-size: var(--pk-autocomplete-font-size);
            line-height: var(--pk-input-control-line-height, 1.25rem);
            white-space: nowrap;
            cursor: text;
            outline: none;
            box-sizing: border-box;
            transition: border-color 0.12s ease, box-shadow 0.12s ease;
        }

        /*
         * Craft text focus: resting border stays; ring is box-shadow only
         * (--pk-input-focus-shadow already includes the 1px edge). Same when the
         * suggestion panel is open — still a text field, not a select trigger.
         */
        :host(:not([invalid]):not(:state(user-invalid))) .control:focus-within,
        :host(:not([invalid]):not(:state(user-invalid))[data-state='focus-visible']) .control,
        :host(:not([invalid]):not(:state(user-invalid))) .control[data-popup-open] {
            box-shadow: var(--pk-input-focus-shadow);
        }

        .control.is-disabled {
            cursor: not-allowed;
            opacity: 0.5;
        }

        .control-start,
        .control-end {
            display: inline-flex;
            align-items: center;
            flex-shrink: 0;
            line-height: 0;
            color: var(--pk-color-gray-400);
        }

        slot[name='start']::slotted(svg),
        slot[name='end']::slotted(svg) {
            width: var(--pk-autocomplete-decoration-size);
            height: var(--pk-autocomplete-decoration-size);
        }

        .autocomplete-input {
            flex: 1 1 auto;
            width: 100%;
            min-width: 0;
            margin: 0;
            padding: 0;
            border: 0;
            background: transparent;
            color: inherit;
            font: inherit;
            line-height: var(--pk-input-control-line-height, 1.25rem);
            outline: none;
        }

        .autocomplete-input::placeholder {
            color: var(--pk-input-placeholder-color, var(--pk-color-gray-400));
        }

        /* Clear: flex trailing action — same contract as pk-input / Combobox clear. */
        .clear-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            align-self: stretch;
            box-sizing: border-box;
            width: calc(var(--pk-autocomplete-decoration-size) + var(--pk-autocomplete-padding-inline));
            height: auto;
            min-height: var(--pk-autocomplete-decoration-size);
            margin-block: calc(-1 * var(--pk-autocomplete-padding-block));
            /* Match pk-copy-button[slot=end]: pull into padding but leave a 4px glyph inset. */
            margin-inline-end: calc(-1 * var(--pk-autocomplete-padding-inline) + 4px);
            margin-inline-start: 0;
            padding: 0;
            border: 0;
            border-radius: 0;
            background: transparent;
            color: var(--pk-color-gray-600);
            cursor: pointer;
            outline: none;
        }

        .clear-button:disabled {
            cursor: not-allowed;
            opacity: 0.5;
        }

        .clear-button-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            line-height: 0;
            pointer-events: none;
        }

        .clear-button-icon svg {
            display: block;
            width: 0.75rem;
            height: 0.75rem;
        }

        .value-input {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            white-space: nowrap;
            border: 0;
        }

        .panel ::slotted(pk-separator) {
            margin: 4px 0;
        }

        .panel {
            width: max-content;
            min-width: var(--pk-autocomplete-anchor-width, 8rem);
            padding: 0;
            border: 0;
            border-radius: var(--pk-radius-md);
            background: var(--pk-color-white);
            box-shadow: var(--pk-shadow-popup);
            color: var(--pk-color-gray-700);
            outline: none;
        }

        .panel-body {
            max-height: 16rem;
            overflow: auto;
        }

        .panel:not([data-open]):not(.closing) {
            opacity: 0;
            pointer-events: none;
        }

        .panel[data-open]:not(.closing) {
            opacity: 1;
            pointer-events: auto;
        }

        .panel[hidden] {
            display: none !important;
        }

        .empty,
        .async-status {
            display: flex;
            align-items: center;
            margin: 0;
            min-height: var(--pk-select-item-min-height, var(--pk-input-height));
            padding-block: var(--pk-select-item-padding-block);
            padding-inline: var(--pk-select-item-padding-inline);
            border: var(--pk-select-trigger-border-width, 1px) solid transparent;
            box-sizing: border-box;
            color: var(--pk-color-gray-500);
            font-size: var(--pk-select-item-font-size);
            line-height: var(--pk-select-item-line-height);
        }

        :host([invalid]) .control,
        :host(:state(user-invalid)) .control {
            border-color: var(--pk-color-rose-600);
        }

        :host([invalid]) .control:focus-within,
        :host([invalid][data-state='focus-visible']) .control,
        :host(:state(user-invalid)) .control:focus-within,
        :host(:state(user-invalid)[data-state='focus-visible']) .control {
            box-shadow: var(--pk-input-invalid-focus-shadow);
        }

        :host([size='xs']) {
            --pk-autocomplete-padding-block: 4px;
            --pk-autocomplete-padding-inline: 6px;
            --pk-autocomplete-control-gap: 4px;
            --pk-autocomplete-decoration-size: 0.625rem;
            --pk-autocomplete-font-size: 11px;
            --pk-select-item-padding-block: 4px;
            --pk-select-item-padding-inline: 8px;
            --pk-select-item-padding-inline-end: 1.75rem;
            --pk-select-item-font-size: 11px;
            --pk-select-group-label-font-size: 11px;
        }

        :host([size='xs']) .clear-button-icon svg {
            width: 0.625rem;
            height: 0.625rem;
        }

        :host([size='sm']) {
            --pk-autocomplete-padding-block: 4px;
            --pk-autocomplete-padding-inline: 8px;
            --pk-autocomplete-control-gap: 4px;
            --pk-autocomplete-decoration-size: 0.6875rem;
            --pk-autocomplete-font-size: 12px;
            --pk-select-item-padding-block: 6px;
            --pk-select-item-padding-inline: 10px;
            --pk-select-item-padding-inline-end: 1.75rem;
            --pk-select-item-font-size: 12px;
            --pk-select-group-label-font-size: 12px;
        }

        :host([size='sm']) .clear-button-icon svg {
            width: 0.6875rem;
            height: 0.6875rem;
        }

        :host([size='lg']) {
            --pk-autocomplete-padding-block: 8px;
            --pk-autocomplete-padding-inline: 12px;
            --pk-autocomplete-control-gap: 8px;
            --pk-autocomplete-decoration-size: 0.875rem;
            --pk-autocomplete-font-size: var(--pk-font-size-base);
            --pk-select-item-padding-block: 8px;
            --pk-select-item-padding-inline: 12px;
            --pk-select-item-font-size: 14px;
            --pk-select-group-label-font-size: 14px;
        }

        :host([size='xl']) {
            --pk-autocomplete-padding-block: 10px;
            --pk-autocomplete-padding-inline: 16px;
            --pk-autocomplete-control-gap: 8px;
            --pk-autocomplete-decoration-size: 1rem;
            --pk-autocomplete-font-size: 16px;
            --pk-select-item-padding-block: 10px;
            --pk-select-item-padding-inline: 14px;
            --pk-select-item-padding-inline-end: 2.25rem;
            --pk-select-item-font-size: 14px;
            --pk-select-group-label-font-size: 16px;
        }

        :host([size='xl']) .clear-button-icon svg {
            width: 0.875rem;
            height: 0.875rem;
        }
    }
`],J=W(b.xmark),Y=class extends v{constructor(...e){super(...e),this.assumeInteractionOn=[`blur`,`input`],this.open=!1,this.placement=`bottom-start`,this.sideOffset=6,this.clearable=!1,this.withClear=!1,this.autoHighlight=!1,this.invalid=!1,this.size=`default`,this.placeholder=``,this.emptyMessage=`No options found.`,this.value=``,this.defaultValue=``,this.label=``,this.instructions=``,this.ariaLabel=null,this.loopFocus=!0,this.filter=null,this.async=!1,this.loadingMessage=`Searching…`,this.startTypingMessage=`Start typing to search…`,this.fetchOptions=null,this.hasSlotController=new h(this,`start`,`end`),this.listboxId=g(`pk-autocomplete-listbox`),this.inputId=g(`pk-autocomplete-input`),this.options=[],this.highlightedIndex=-1,this.closing=!1,this.panelAnimated=!1,this.dismissRegistered=!1,this.panelEventTarget=null,this.asyncFetcher=null,this.asyncLoading=!1,this.asyncError=null,this.onDocumentPointerDown=e=>{C(e,{anchor:this.controlElement,panel:this.panelElement})||this.closePanel(`light-dismiss`)},this.onDocumentKeyDown=e=>{if(!this.open)return;if(e.key===`Escape`){if(!A(this))return;e.preventDefault(),e.stopPropagation(),this.closePanel(`escape`);return}if(!(w.has(e.key)||ee(e)))return;let t=this.panelElement,n=e.composedPath();t&&n.includes(t)&&S(e,{anchor:this.controlElement,panel:t})&&(e.preventDefault(),e.stopPropagation(),this.onListboxKeyDown(e))},this.handleOptionSelect=e=>{let{value:t}=e.detail;this.commitValue(t,{close:!0})},this.handleOptionHighlight=e=>{if(!this.open)return;let t=this.getEnabledVisibleOptions().findIndex(t=>t.value===e.detail.value);t!==-1&&t!==this.highlightedIndex&&(this.highlightedIndex=t,this.syncHighlight())},this.handleControlMouseDown=e=>{if(this.disabled||e.composedPath().some(e=>e instanceof HTMLElement&&e.classList.contains(`clear-button`)))return;let t=e.target===this.controlInput;if(!this.open&&!this.closing){t||e.preventDefault(),this.controlInput?.focus({preventScroll:!0}),this.openPanel();return}t||(e.preventDefault(),this.controlInput?.focus({preventScroll:!0}))},this.handleListboxKeyDownEvent=e=>{this.onListboxKeyDown(e.detail.keyboardEvent)}}static{this.styles=q}static get validators(){return[...super.validators,m(),{observedAttributes:[`required`],checkValidity:e=>{let t=e,n={message:`Please fill out this field.`,isValid:!0,invalidKeys:[]};return!t.required||t.value.trim()?n:(n.isValid=!1,n.invalidKeys.push(`valueMissing`),n)}}]}get panelElement(){return this.popupElement?.getContentElement()??null}get panelBodyElement(){return this.panelElement?.querySelector(`.panel-body`)}get listScrollContainer(){return this.panelBodyElement??this.panelElement??this}connectedCallback(){this.instructions=this.getAttribute(`hint`)??this.instructions,this.refreshOptions(),super.connectedCallback(),this.syncHasValueAttribute(),this.addEventListener(`pk-listbox-keydown`,this.handleListboxKeyDownEvent),this.optionsObserver=new MutationObserver(()=>{this.handleOptionsMutation({render:!0})}),this.optionsObserver.observe(this,{childList:!0,subtree:!0})}disconnectedCallback(){this.unbindPanelEvents(),this.removeEventListener(`pk-listbox-keydown`,this.handleListboxKeyDownEvent),this.optionsObserver?.disconnect(),this.liveRegion?.destroy(),this.liveRegion=void 0,this.asyncFetcher?.cancel(),this.closePanel(`api`),super.disconnectedCallback()}firstUpdated(e){super.firstUpdated(e),!this.value&&this.defaultValue&&(this.value=this.defaultValue),this.applyOptionState()}updated(e){e.has(`value`)&&(this.syncHasValueAttribute(),this.applyOptionState()),super.updated(e)}get validationTarget(){return this.controlInput??this.controlElement}getAriaMirrorTarget(){return this.controlInput??this.controlElement??null}syncFormValue(){if(!this.name){this.setFormValue(null);return}this.setFormValue(this.value)}syncHasValueAttribute(){this.toggleAttribute(`data-has-value`,!!this.value)}refreshOptions(){this.options=[...this.querySelectorAll(`pk-option`)]}handleOptionsMutation(e={}){this.refreshOptions(),this.applyOptionState(),e.render&&this.requestUpdate()}bindPanelEvents(){let e=this.panelElement;e&&this.panelEventTarget!==e&&(this.unbindPanelEvents(),this.panelEventTarget=e,e.addEventListener(`pk-option-select`,this.handleOptionSelect),e.addEventListener(`pk-option-highlight`,this.handleOptionHighlight),e.addEventListener(`pk-listbox-keydown`,this.handleListboxKeyDownEvent))}unbindPanelEvents(){this.panelEventTarget&&=(this.panelEventTarget.removeEventListener(`pk-option-select`,this.handleOptionSelect),this.panelEventTarget.removeEventListener(`pk-option-highlight`,this.handleOptionHighlight),this.panelEventTarget.removeEventListener(`pk-listbox-keydown`,this.handleListboxKeyDownEvent),null)}isOptionInHiddenGroup(e){return!!e.closest(`pk-option-group`)?.hidden}getFilterQuery(){return this.open?this.value.trim().toLowerCase():``}get usesAsyncSearch(){return this.async&&!!this.fetchOptions}getVisibleOptions(){if(this.usesAsyncSearch)return this.options.filter(e=>!this.isOptionInHiddenGroup(e));let e=this.getFilterQuery();return this.options.filter(n=>this.isOptionInHiddenGroup(n)?!1:!e||t(n,e,this.filter))}getEnabledVisibleOptions(){return this.getVisibleOptions().filter(e=>!e.disabled)}clearAsyncOptionNodes(){this.querySelectorAll(`:scope > pk-option, :scope > pk-option-group, :scope > pk-separator`).forEach(e=>e.remove())}renderAsyncOptionNodes(e){this.clearAsyncOptionNodes();for(let t of e){let e=document.createElement(`pk-option`);e.value=t.value,e.textContent=t.label,this.append(e)}this.handleOptionsMutation({render:!0})}scheduleAsyncFetch(e){this.ensureAsyncFetcher().schedule(e)}ensureAsyncFetcher(){return this.asyncFetcher||=new i(()=>this.fetchOptions,{errorLabel:`autocomplete options`,onLoading:()=>{this.asyncLoading=!0,this.asyncError=null,this.renderAsyncOptionNodes([])},onResults:e=>{this.renderAsyncOptionNodes(e)},onError:e=>{this.asyncError=e},onSettled:()=>{this.asyncLoading=!1},onEmptyQuery:()=>{this.asyncLoading=!1,this.asyncError=null,this.renderAsyncOptionNodes([])}}),this.asyncFetcher}getAsyncStatusMessage(){if(!this.usesAsyncSearch||!this.open)return null;if(this.asyncLoading)return this.loadingMessage;if(this.asyncError)return this.asyncError;let e=this.value.trim();return e?this.getEnabledVisibleOptions().length===0?`No matches for "${e}".`:null:this.startTypingMessage}applyOptionState(){let e=this.getVisibleOptions(),t=this.open?this.getFilterQuery():``;n({host:this,options:this.options,visible:e,listboxId:this.listboxId,filterQuery:t,isSelected:()=>!1}),this.syncValueInput(),this.open&&(this.syncHighlight(),this.announceFilterResults())}syncValueInput(){this.input&&(this.input.value=this.value,this.input.required=this.required)}resetHighlightedIndexOnOpen(){this.highlightedIndex=this.autoHighlight?0:-1}syncHighlight(){let e=this.getEnabledVisibleOptions();for(let e of this.options)e.highlighted=!1,e.focusIndex=-1;if(e.length===0||this.highlightedIndex<0)return;this.highlightedIndex>=e.length&&(this.highlightedIndex=e.length-1);let t=e[this.highlightedIndex];t&&(t.highlighted=!0,t.focusIndex=-1,y(t,this.listScrollContainer,`vertical`,`auto`),this.controlInput?.focus({preventScroll:!0}))}getActiveDescendantId(){return this.getEnabledVisibleOptions()[this.highlightedIndex]?.optionId||null}announceFilterResults(){this.liveRegion||=new d(`polite`);let e=this.getEnabledVisibleOptions().length;this.getFilterQuery()&&this.liveRegion.announce(e===0?`${this.emptyMessage}`:`${e} ${e===1?`result`:`results`} available`)}openPanel(){let e=this.controlElement;if(!e)return Promise.resolve();if(this.open)return this.controlInput?.focus({preventScroll:!0}),Promise.resolve();if(this.closing)return Promise.resolve();if(this.dispatchEvent(new D),this.closing=!1,this.panelAnimated=!1,this.open=!0,this.applyOptionState(),this.resetHighlightedIndexOnOpen(),this.usesAsyncSearch){this.asyncError=null,this.renderAsyncOptionNodes([]);let e=this.value.trim();this.asyncLoading=!!e,this.scheduleAsyncFetch(e)}return this.style.setProperty(`--pk-autocomplete-anchor-width`,`${e.getBoundingClientRect().width}px`),this.popupElement.active=!0,this.panelElement&&(this.panelElement.hidden=!1,p(this.panelElement,this.placement)),this.registerDismissHandlers(),this.syncHighlight(),this.controlInput?.focus({preventScroll:!0}),this.updateComplete.then(async()=>{let e=await c(this.popupElement,this.placement,300,{requireEvent:!0});this.panelElement&&p(this.panelElement,e),this.panelAnimated=!0,this.bindPanelEvents(),this.refreshOptions(),this.controlInput?.focus({preventScroll:!0}),this.dispatchEvent(new k),this.dispatchEvent(new CustomEvent(`pk-open-change`,{detail:{open:!0},bubbles:!0,composed:!0}))})}async closePanel(e=`unknown`){if(!this.open||this.closing)return;let t=new j(e);this.dispatchEvent(t)&&(this.unbindPanelEvents(),this.closing=!0,this.panelAnimated=!1,await r(this.panelElement),this.open=!1,this.closing=!1,this.panelAnimated=!1,this.panelElement&&(this.panelElement.hidden=!0,this.panelElement.removeAttribute(`data-side`)),this.popupElement.active=!1,this.unregisterDismissHandlers(),this.applyOptionState(),this.usesAsyncSearch&&(this.asyncFetcher?.cancel(),this.asyncLoading=!1,this.asyncError=null,this.renderAsyncOptionNodes([])),e!==`light-dismiss`&&e!==`pointer-dismiss`?this.controlInput?.focus({preventScroll:!0}):this.controlInput?.blur(),this.dispatchEvent(new M),this.dispatchEvent(new CustomEvent(`pk-open-change`,{detail:{open:!1},bubbles:!0,composed:!0})))}registerDismissHandlers(){O(this),this.dismissRegistered=!0,document.addEventListener(`pointerdown`,this.onDocumentPointerDown,!0),document.addEventListener(`keydown`,this.onDocumentKeyDown,!0)}unregisterDismissHandlers(){this.dismissRegistered&&=(E(this),!1),document.removeEventListener(`pointerdown`,this.onDocumentPointerDown,!0),document.removeEventListener(`keydown`,this.onDocumentKeyDown,!0)}commitValue(e,{close:t=!1,emit:n=!0}={}){let r=this.value!==e;this.value=e,this.syncHasValueAttribute(),this.applyOptionState(),t&&this.closePanel(`api`),r&&n&&this.emitValueChange()}handleClear(e){e.preventDefault(),e.stopPropagation(),this.commitValue(``),this.dispatchEvent(new _),this.controlInput?.focus()}emitValueChange(){this.dispatchEvent(new CustomEvent(`pk-change`,{detail:{value:this.value},bubbles:!0,composed:!0})),this.dispatchEvent(new Event(`input`,{bubbles:!0,composed:!0})),this.dispatchEvent(new Event(`change`,{bubbles:!0,composed:!0}))}handleInput(e){let t=e.target.value;this.value=t,this.syncHasValueAttribute(),this.highlightedIndex=this.autoHighlight?0:-1,this.applyOptionState(),this.emitValueChange(),this.usesAsyncSearch&&(this.asyncError=null,this.scheduleAsyncFetch(t.trim())),this.open||this.openPanel()}handleInputKeyDown(e){if(e.key===`Escape`&&this.open){e.preventDefault(),this.closePanel(`escape`);return}if(e.key===`ArrowDown`&&!this.open){e.preventDefault(),this.openPanel();return}if(this.open){if(e.key===`Enter`){let t=this.getEnabledVisibleOptions();this.highlightedIndex>=0&&t[this.highlightedIndex]?(e.preventDefault(),this.commitValue(t[this.highlightedIndex].value,{close:!0})):this.closePanel(`api`);return}w.has(e.key)&&e.key!==`Enter`&&e.key!==`Escape`&&this.onListboxKeyDown(e)}}onListboxKeyDown(e){let t=this.getEnabledVisibleOptions();this.highlightedIndex=x(e,{items:t,currentIndex:this.highlightedIndex,loop:this.loopFocus,onSelect:e=>{let t=this.getEnabledVisibleOptions()[e];t&&this.commitValue(t.value,{close:!0})},onClose:()=>{this.closePanel(`escape`)},focusItem:e=>{this.highlightedIndex=e,this.syncHighlight()}})}async hide(e=`api`){await this.closePanel(e)}renderHostDecorationSlot(e){return this.hasSlotController.test(e)?L`<slot name=${e} part=${e} class=${e===`start`?`control-start`:`control-end`}></slot>`:I}render(){let e=this.getEnabledVisibleOptions(),t=this.open&&!this.usesAsyncSearch&&e.length===0,n=this.getAsyncStatusMessage(),r=(this.clearable||this.withClear)&&!!this.value&&!this.disabled,i=this.open?this.getActiveDescendantId():null;return L`
            <input
                class="value-input"
                part="value-input"
                tabindex="-1"
                aria-hidden="true"
                .value=${this.value}
                ?required=${this.required}
                @input=${()=>this.updateValidity()}
            />
            <div
                part="control"
                class=${R({control:!0,"is-disabled":this.disabled})}
                data-popup-open=${this.open||this.closing?``:I}
                @mousedown=${this.handleControlMouseDown}
            >
                ${this.renderHostDecorationSlot(`start`)}
                <input
                    part="input"
                    class="autocomplete-input control-input"
                    type="text"
                    role="combobox"
                    id=${this.inputId}
                    .value=${this.value}
                    placeholder=${this.placeholder||I}
                    ?disabled=${this.disabled}
                    aria-label=${this.ariaLabel??I}
                    aria-expanded=${this.open?`true`:`false`}
                    aria-controls=${this.listboxId}
                    aria-autocomplete="both"
                    aria-activedescendant=${i??I}
                    @input=${this.handleInput}
                    @keydown=${this.handleInputKeyDown}
                />
                ${this.renderHostDecorationSlot(`end`)}
                ${r?L`
                        <button
                            type="button"
                            class="clear-button"
                            part="clear-button"
                            aria-label="Clear"
                            ?disabled=${this.disabled}
                            @click=${this.handleClear}
                        >
                            <span class="clear-button-icon" aria-hidden="true">${P(J)}</span>
                        </button>
                    `:I}
            </div>
            <pk-popup
                .active=${this.open||this.closing}
                .anchor=${this.controlElement??``}
                .placement=${this.placement}
                .distance=${this.sideOffset}
                .sync=${`width`}
                flip
                shift
            >
                <div
                    part="panel"
                    class=${R({panel:!0,"pk-popup-content":!0,closing:this.closing})}
                    tabindex="-1"
                    ?hidden=${!this.open&&!this.closing}
                    data-open=${this.panelAnimated&&!this.closing?``:I}
                >
                    <div
                        part="panel-body"
                        class="panel-body"
                        id=${this.listboxId}
                        role="listbox"
                        aria-busy=${this.usesAsyncSearch&&this.asyncLoading?`true`:I}
                    >
                        <slot></slot>
                        ${n?L`
                                <div part="async-status" class="async-status" role="status">${n}</div>
                            `:I}
                        ${t?L`
                                <div part="empty" class="empty">${this.emptyMessage}</div>
                            `:I}
                    </div>
                </div>
            </pk-popup>
        `}};H([z({type:Boolean,reflect:!0})],Y.prototype,`open`,void 0),H([z({reflect:!0})],Y.prototype,`placement`,void 0),H([z({attribute:`side-offset`,type:Number})],Y.prototype,`sideOffset`,void 0),H([z({type:Boolean,reflect:!0})],Y.prototype,`clearable`,void 0),H([z({attribute:`with-clear`,type:Boolean})],Y.prototype,`withClear`,void 0),H([z({attribute:`auto-highlight`,type:Boolean})],Y.prototype,`autoHighlight`,void 0),H([z({type:Boolean,reflect:!0})],Y.prototype,`invalid`,void 0),H([z({reflect:!0})],Y.prototype,`size`,void 0),H([z({reflect:!0})],Y.prototype,`width`,void 0),H([z()],Y.prototype,`placeholder`,void 0),H([z({attribute:`empty-message`})],Y.prototype,`emptyMessage`,void 0),H([z()],Y.prototype,`value`,void 0),H([z({attribute:`default-value`})],Y.prototype,`defaultValue`,void 0),H([z()],Y.prototype,`label`,void 0),H([z()],Y.prototype,`instructions`,void 0),H([z({attribute:`aria-label`})],Y.prototype,`ariaLabel`,void 0),H([z({attribute:`loop-focus`,type:Boolean})],Y.prototype,`loopFocus`,void 0),H([z({attribute:!1})],Y.prototype,`filter`,void 0),H([z({type:Boolean,reflect:!0})],Y.prototype,`async`,void 0),H([z({attribute:`loading-message`})],Y.prototype,`loadingMessage`,void 0),H([z({attribute:`start-typing-message`})],Y.prototype,`startTypingMessage`,void 0),H([z({attribute:!1})],Y.prototype,`fetchOptions`,void 0),H([V(`pk-popup`)],Y.prototype,`popupElement`,void 0),H([V(`.control`)],Y.prototype,`controlElement`,void 0),H([V(`.control-input`)],Y.prototype,`controlInput`,void 0),H([V(`.value-input`)],Y.prototype,`input`,void 0),H([F()],Y.prototype,`highlightedIndex`,void 0),H([F()],Y.prototype,`closing`,void 0),H([F()],Y.prototype,`panelAnimated`,void 0),H([F()],Y.prototype,`asyncLoading`,void 0),H([F()],Y.prototype,`asyncError`,void 0),Y=H([U(`pk-autocomplete`)],Y);var X=l({tagName:`pk-autocomplete`,elementClass:Y,react:G.default,events:{onPkChange:`pk-change`,onPkClear:`pk-clear`,onInput:`input`,onNativeChange:`change`,onPkShow:`pk-show`,onPkAfterShow:`pk-after-show`,onPkHide:`pk-hide`,onPkAfterHide:`pk-after-hide`,onPkOpenChange:`pk-open-change`}}),Z=(0,G.forwardRef)(function({disabled:e,invalid:t,isInvalid:n,clearable:r,open:i,async:a,autoHighlight:o,withClear:s,onChange:c,onPkChange:l,...d},f){let p=!!(t??n),m=(0,G.useCallback)(e=>{if(l?.(e),!c)return;let t=e.detail;t&&`value`in t&&c(t.value??``)},[l,c]);return(0,K.jsx)(X,{ref:f,...d,...u([`disabled`,`invalid`,`clearable`,`open`,`async`,`autoHighlight`,`withClear`],{disabled:e,invalid:p,clearable:r,open:i,async:a,autoHighlight:o,withClear:s}),...c||l?{onPkChange:m}:{}})});Z.displayName=`Autocomplete`;var Q=e=>e==null?``:String(e),$=(0,G.forwardRef)(function({options:e,fetchOptions:t,value:n=``,onValueChange:r,disabled:i=!1,placeholder:a=``,emptyMessage:o=`No options found.`,loadingMessage:s=`Searching…`,startTypingMessage:c=`Start typing to search…`,showClear:l=!1,isInvalid:u,size:d,width:f,onOpenChange:p,name:m,id:h,"aria-label":g,"aria-describedby":_,"aria-errormessage":v,"aria-labelledby":y},b){let x=!!t&&!e?.length,S=(0,G.useMemo)(()=>e??[],[e]),C=(0,G.useMemo)(()=>t?async(e,n)=>(await t(e,n)).map(e=>({value:Q(e.value),label:e.label})):null,[t]);return(0,K.jsx)(Z,{ref:b,disabled:i,placeholder:a,emptyMessage:o,loadingMessage:s,startTypingMessage:c,clearable:l,invalid:u,size:d,width:f,async:x,fetchOptions:C,name:m,id:h,value:Q(n),onPkChange:e=>{if(!r)return;let t=e.detail;r(t?.value??``)},onPkOpenChange:p?e=>p(!!e.detail?.open):void 0,"aria-label":g,"aria-describedby":_,"aria-errormessage":v,"aria-labelledby":y,children:!x&&S.map(e=>(0,K.jsx)(te,{value:Q(e.value),disabled:e.disabled,children:e.label},Q(e.value)))})});$.displayName=`AutocompleteInput`;var ne=({form:e,field:t})=>{let{value:n,setValue:r,setTouched:i,errors:o}=a(e,t.name);return(0,K.jsx)(T,{name:t.name,label:t.label,instructions:t.instructions,warning:t.warning,required:t.required,errors:o,children:(0,K.jsx)($,{options:t.options,fetchOptions:t.fetchOptions,value:n==null?``:String(n),onValueChange:e=>{r(e),i()},disabled:t.disabled,placeholder:t.placeholder,emptyMessage:t.emptyMessage,showClear:t.showClear,isInvalid:o.length>0,width:t.width})})};export{ne as AutocompleteField};